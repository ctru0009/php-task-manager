<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Registration and login over real HTTP against the behaviour contract:
 * bcrypt storage, uniqueness handling that never leaks driver errors, every
 * validation message, and the generic login failure shared by a wrong password
 * and an unknown username.
 */
final class AuthHttpTest extends DatabaseTestCase
{
    private const REGISTER_PATH = '/index.php?controller=auth&action=register';
    private const LOGIN_PATH = '/index.php?controller=auth&action=login';
    private const TASK_INDEX_PATH = '/index.php?controller=task&action=index';

    public function testValidRegistrationStoresABcryptHashAndRedirectsToTheTaskIndex(): void
    {
        $response = $this->postRegistration(new HttpClient());

        $this->assertSame(302, $response->status, 'Valid registration should redirect: ' . $response->body);
        $this->assertSame(self::TASK_INDEX_PATH, $response->location());
        $this->assertSame(1, (int) TestDatabase::scalar('SELECT COUNT(*) FROM users'));

        $user = TestDatabase::row('SELECT username, email, password FROM users WHERE username = ?', ['alice']);

        $this->assertNotNull($user, 'Registration should create the user row.');
        $this->assertSame('alice@example.com', $user['email']);
        $this->assertSame('bcrypt', password_get_info($user['password'])['algoName']);
    }

    public function testRegistrationAcceptsValuesAtTheDocumentedLimits(): void
    {
        $password = str_repeat('p', 72);

        $response = $this->postRegistration(new HttpClient(), [
            'username' => str_repeat('u', 50),
            // Exactly 100 characters: 64-character local part, 35-character domain.
            'email' => str_repeat('e', 64) . '@' . str_repeat('d', 31) . '.com',
            'password' => $password,
            'confirm_password' => $password,
        ]);

        $this->assertSame(302, $response->status, 'Values at the documented limits must be accepted: ' . $response->body);
        $this->assertSame(1, (int) TestDatabase::scalar('SELECT COUNT(*) FROM users'));
    }

    public function testDuplicateEmailIsRejectedWithoutLeakingTheDatabaseError(): void
    {
        $this->assertSame(302, $this->postRegistration(new HttpClient())->status);

        $response = $this->postRegistration(new HttpClient(), [
            'username' => 'alicia',
            'email' => 'alice@example.com',
        ]);

        $this->assertSame(200, $response->status);
        $this->assertStringContainsString('Username or email already exists', $response->body);
        $this->assertStringNotContainsString('SQLSTATE', $response->body);
        $this->assertSame(1, (int) TestDatabase::scalar('SELECT COUNT(*) FROM users'));
    }

    public function testDuplicateUsernameIsRejectedWithoutLeakingTheDatabaseError(): void
    {
        $this->assertSame(302, $this->postRegistration(new HttpClient())->status);

        $response = $this->postRegistration(new HttpClient(), [
            'username' => 'alice',
            'email' => 'other@example.com',
        ]);

        $this->assertSame(200, $response->status);
        $this->assertStringContainsString('Username or email already exists', $response->body);
        $this->assertStringNotContainsString('SQLSTATE', $response->body);
        $this->assertSame(1, (int) TestDatabase::scalar('SELECT COUNT(*) FROM users'));
    }

    /**
     * @param array<string, string> $overrides
     */
    #[DataProvider('invalidRegistrationProvider')]
    public function testRegistrationValidationMessages(array $overrides, string $expectedMessage): void
    {
        $response = $this->postRegistration(new HttpClient(), $overrides);

        $this->assertSame(200, $response->status);
        $this->assertStringContainsString($expectedMessage, $response->body);
        $this->assertStringNotContainsString('SQLSTATE', $response->body);
        $this->assertSame(0, (int) TestDatabase::scalar('SELECT COUNT(*) FROM users'), 'Invalid input must not create a user.');
    }

    /** @return array<string, array{array<string, string>, string}> */
    public static function invalidRegistrationProvider(): array
    {
        return [
            'missing username' => [['username' => ''], 'Username is required'],
            'username too short' => [['username' => 'ab'], 'Username must be at least 3 characters'],
            'username too long' => [['username' => str_repeat('u', 51)], 'Username must be at most 50 characters'],
            'missing email' => [['email' => ''], 'Email is required'],
            'malformed email' => [['email' => 'not-an-email'], 'Invalid email format'],
            'email too long' => [
                ['email' => str_repeat('e', 64) . '@' . str_repeat('d', 32) . '.com'],
                'Email must be at most 100 characters',
            ],
            'missing password' => [['password' => '', 'confirm_password' => ''], 'Password is required'],
            'password too short' => [['password' => '12345', 'confirm_password' => '12345'], 'Password must be at least 6 characters'],
            'password too long' => [
                ['password' => str_repeat('p', 73), 'confirm_password' => str_repeat('p', 73)],
                'Password must be at most 72 characters',
            ],
            'mismatched passwords' => [['confirm_password' => 'secret456'], 'Passwords do not match'],
        ];
    }

    public function testLoginWithCorrectCredentialsRedirectsToTheTaskIndex(): void
    {
        $this->createUser('alice', 'secret123');

        $client = new HttpClient();
        $response = $this->postLogin($client, 'alice', 'secret123');

        $this->assertSame(302, $response->status, 'Valid login should redirect: ' . $response->body);
        $this->assertSame(self::TASK_INDEX_PATH, $response->location());

        $tasks = $client->get(self::TASK_INDEX_PATH);
        $this->assertSame(200, $tasks->status, 'The logged-in session should reach the task index.');
    }

    public function testLoginWithWrongPasswordIsRejected(): void
    {
        $this->createUser('alice', 'secret123');

        $response = $this->postLogin(new HttpClient(), 'alice', 'wrong-password');

        $this->assertSame(200, $response->status);
        $this->assertStringContainsString('Invalid username or password', $response->body);
    }

    public function testUnknownUsernameProducesTheSameGenericMessageAsAWrongPassword(): void
    {
        $this->createUser('alice', 'secret123');

        $wrongPassword = $this->postLogin(new HttpClient(), 'alice', 'wrong-password');
        $unknownUsername = $this->postLogin(new HttpClient(), 'nobody', 'wrong-password');

        $this->assertSame(200, $wrongPassword->status);
        $this->assertSame(200, $unknownUsername->status);

        foreach (['wrong password' => $wrongPassword, 'unknown username' => $unknownUsername] as $case => $response) {
            $this->assertStringContainsString('Invalid username or password', $response->body, $case);
            $this->assertStringNotContainsString('not found', $response->body, $case);
            $this->assertStringNotContainsString('no account', $response->body, $case);
            $this->assertStringNotContainsString('does not exist', $response->body, $case);
        }
    }

    public function testTaskIndexRedirectsAnonymousVisitorsToLogin(): void
    {
        $response = (new HttpClient())->get(self::TASK_INDEX_PATH);

        $this->assertSame(302, $response->status);
        $this->assertSame(self::LOGIN_PATH, $response->location());
    }

    /** @param array<string, string> $overrides */
    private function postRegistration(HttpClient $client, array $overrides = []): HttpResponse
    {
        $formPage = $client->get(self::REGISTER_PATH);
        $this->assertSame(200, $formPage->status, 'The registration form should be reachable.');

        $fields = array_merge([
            'username' => 'alice',
            'email' => 'alice@example.com',
            'password' => 'secret123',
            'confirm_password' => 'secret123',
        ], $overrides);

        $fields['csrf_token'] = $formPage->csrf();

        return $client->post(self::REGISTER_PATH, $fields);
    }

    private function postLogin(HttpClient $client, string $username, string $password): HttpResponse
    {
        $formPage = $client->get(self::LOGIN_PATH);
        $this->assertSame(200, $formPage->status, 'The login form should be reachable.');

        return $client->post(self::LOGIN_PATH, [
            'username' => $username,
            'password' => $password,
            'csrf_token' => $formPage->csrf(),
        ]);
    }

    private function createUser(string $username, string $password): void
    {
        TestDatabase::exec(
            'INSERT INTO users (username, email, password) VALUES (?, ?, ?)',
            [$username, $username . '@example.com', password_hash($password, PASSWORD_DEFAULT)]
        );
    }
}
