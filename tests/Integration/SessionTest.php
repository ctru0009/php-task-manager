<?php

declare(strict_types=1);

/**
 * Session handling over real HTTP: cookie flags (HttpOnly, SameSite=Lax, no
 * Secure while SESSION_COOKIE_SECURE=0), session id rotation on login and
 * registration, POST-only logout, and rejection of fabricated session ids
 * under session.use_strict_mode.
 */
final class SessionTest extends DatabaseTestCase
{
    private const REGISTER_PATH = '/index.php?controller=auth&action=register';
    private const LOGIN_PATH = '/index.php?controller=auth&action=login';
    private const LOGOUT_PATH = '/index.php?controller=auth&action=logout';
    private const TASK_INDEX_PATH = '/index.php?controller=task&action=index';

    public function testSessionCookieIsHttpOnlyAndSameSiteLax(): void
    {
        $this->createUser('alice', 'secret123');

        $client = new HttpClient();
        $created = $client->get(self::LOGIN_PATH);

        $this->assertNotEmpty(
            $this->phpSessidSetCookies($created),
            'A fresh request must set the PHPSESSID cookie.'
        );

        $afterLogin = $this->postLogin($client, 'alice', 'secret123');
        $this->assertSame(302, $afterLogin->status, 'Login must succeed: ' . $afterLogin->body);

        $cookies = array_merge(
            $this->phpSessidSetCookies($created),
            $this->phpSessidSetCookies($afterLogin)
        );

        foreach ($cookies as $cookie) {
            $this->assertMatchesRegularExpression('/;\s*HttpOnly\b/i', $cookie, $cookie);
            $this->assertMatchesRegularExpression('/;\s*SameSite=Lax\b/i', $cookie, $cookie);
        }
    }

    public function testSessionCookieIsNotSecureWhileSecureCookiesAreDisabled(): void
    {
        $this->assertSame(
            '0',
            getenv('SESSION_COOKIE_SECURE'),
            'phpunit.xml.dist must force SESSION_COOKIE_SECURE=0 for this test.'
        );

        $this->createUser('alice', 'secret123');

        $client = new HttpClient();
        $created = $client->get(self::LOGIN_PATH);
        $afterLogin = $this->postLogin($client, 'alice', 'secret123');

        $cookies = array_merge(
            $this->phpSessidSetCookies($created),
            $this->phpSessidSetCookies($afterLogin)
        );

        $this->assertNotEmpty($cookies, 'The PHPSESSID cookie must be issued.');

        foreach ($cookies as $cookie) {
            $this->assertDoesNotMatchRegularExpression(
                '/;\s*Secure\b/i',
                $cookie,
                'Secure must be omitted while SESSION_COOKIE_SECURE=0: ' . $cookie
            );
        }
    }

    public function testSessionIdChangesAfterLogin(): void
    {
        $this->createUser('alice', 'secret123');

        $client = new HttpClient();
        $formPage = $client->get(self::LOGIN_PATH);
        $this->assertSame(200, $formPage->status);

        $before = $client->cookie('PHPSESSID');
        $this->assertNotNull($before, 'Fetching the login form must create a session.');

        $response = $this->postLogin($client, 'alice', 'secret123', $formPage->csrf());
        $this->assertSame(302, $response->status, 'Valid login should redirect: ' . $response->body);

        $after = $client->cookie('PHPSESSID');
        $this->assertNotNull($after);
        $this->assertNotSame($before, $after, 'Login must regenerate the session id.');
    }

    public function testSessionIdChangesAfterRegistration(): void
    {
        $client = new HttpClient();
        $formPage = $client->get(self::REGISTER_PATH);
        $this->assertSame(200, $formPage->status);

        $before = $client->cookie('PHPSESSID');
        $this->assertNotNull($before, 'Fetching the registration form must create a session.');

        $response = $client->post(self::REGISTER_PATH, [
            'username' => 'alice',
            'email' => 'alice@example.com',
            'password' => 'secret123',
            'confirm_password' => 'secret123',
            'csrf_token' => $formPage->csrf(),
        ]);

        $this->assertSame(302, $response->status, 'Valid registration should redirect: ' . $response->body);
        $this->assertSame(1, (int) TestDatabase::scalar('SELECT COUNT(*) FROM users'));

        $after = $client->cookie('PHPSESSID');
        $this->assertNotNull($after);
        $this->assertNotSame($before, $after, 'Registration must regenerate the session id.');
    }

    public function testPostLogoutInvalidatesThePreLogoutSessionCookie(): void
    {
        $client = new HttpClient();
        $this->registerUser($client);

        $sessionBeforeLogout = $client->cookie('PHPSESSID');
        $this->assertNotNull($sessionBeforeLogout);

        $taskPage = $client->get(self::TASK_INDEX_PATH);
        $this->assertSame(200, $taskPage->status, 'The session should be authenticated before logout.');

        $response = $client->post(self::LOGOUT_PATH, ['csrf_token' => $taskPage->csrf()]);

        $this->assertSame(302, $response->status, 'Logout should redirect: ' . $response->body);
        $this->assertSame(self::LOGIN_PATH, $response->location());

        // A client still holding the pre-logout cookie must no longer be logged in.
        $staleClient = new HttpClient();
        $followUp = $staleClient->get(self::TASK_INDEX_PATH, ['Cookie: PHPSESSID=' . $sessionBeforeLogout]);

        $this->assertSame(302, $followUp->status, 'The pre-logout session cookie must no longer be authenticated.');
        $this->assertSame(self::LOGIN_PATH, $followUp->location());
    }

    public function testGetLogoutIsMethodNotAllowedAndLeavesTheSessionUsable(): void
    {
        $client = new HttpClient();
        $this->registerUser($client);

        $response = $client->get(self::LOGOUT_PATH);

        $this->assertSame(405, $response->status, 'Logout must be POST-only.');

        $taskPage = $client->get(self::TASK_INDEX_PATH);

        $this->assertSame(200, $taskPage->status, 'A rejected GET logout must not destroy the session.');
        $this->assertStringNotContainsString('name="password"', $taskPage->body, 'The task index must not render the login form.');
    }

    public function testFabricatedSessionIdIsNotAdopted(): void
    {
        // Random rather than a literal: a fixed value could collide with a
        // session some earlier request already created, which would make the
        // server treat it as known and weaken the test.
        $fabricated = bin2hex(random_bytes(16));
        $client = new HttpClient();

        $response = $client->get(self::LOGIN_PATH, ['Cookie: PHPSESSID=' . $fabricated]);

        $this->assertSame(200, $response->status);

        $this->assertNotEmpty(
            $this->phpSessidSetCookies($response),
            'session.use_strict_mode must make the server issue a fresh session id for an unknown cookie.'
        );

        $issued = $client->cookie('PHPSESSID');

        $this->assertNotNull($issued, 'The server must issue a new session id instead of accepting the fabricated one.');
        $this->assertNotSame($fabricated, $issued);
    }

    /** @return list<string> */
    private function phpSessidSetCookies(HttpResponse $response): array
    {
        $cookies = [];

        foreach ($response->headerAll('Set-Cookie') as $cookie) {
            if (str_starts_with($cookie, 'PHPSESSID=')) {
                $cookies[] = $cookie;
            }
        }

        return $cookies;
    }

    private function postLogin(HttpClient $client, string $username, string $password, ?string $csrfToken = null): HttpResponse
    {
        if ($csrfToken === null) {
            $formPage = $client->get(self::LOGIN_PATH);
            $this->assertSame(200, $formPage->status, 'The login form should be reachable.');
            $csrfToken = $formPage->csrf();
        }

        return $client->post(self::LOGIN_PATH, [
            'username' => $username,
            'password' => $password,
            'csrf_token' => $csrfToken,
        ]);
    }

    private function registerUser(HttpClient $client, string $username = 'alice', string $password = 'secret123'): void
    {
        $formPage = $client->get(self::REGISTER_PATH);
        $this->assertSame(200, $formPage->status, 'The registration form should be reachable.');

        $response = $client->post(self::REGISTER_PATH, [
            'username' => $username,
            'email' => $username . '@example.com',
            'password' => $password,
            'confirm_password' => $password,
            'csrf_token' => $formPage->csrf(),
        ]);

        $this->assertSame(302, $response->status, 'Registration should log the user in: ' . $response->body);
    }

    private function createUser(string $username, string $password): void
    {
        TestDatabase::exec(
            'INSERT INTO users (username, email, password) VALUES (?, ?, ?)',
            [$username, $username . '@example.com', password_hash($password, PASSWORD_DEFAULT)]
        );
    }
}
