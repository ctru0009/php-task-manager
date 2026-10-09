<?php

declare(strict_types=1);

/**
 * CSRF protection on every state-changing POST: register, login, logout,
 * task create, delete and updateStatus must reject a missing or wrong token
 * with 403 and leave the database untouched.
 */
final class CsrfTest extends DatabaseTestCase
{
    private const LOGIN_PATH = '/index.php?controller=auth&action=login';
    private const REGISTER_PATH = '/index.php?controller=auth&action=register';
    private const LOGOUT_PATH = '/index.php?controller=auth&action=logout';
    private const TASK_INDEX_PATH = '/index.php?controller=task&action=index';
    private const TASK_CREATE_PATH = '/index.php?controller=task&action=create';

    private const CSRF_ERROR = 'Invalid or missing security token';
    private const WRONG_CSRF_TOKEN = 'not-the-real-csrf-token';

    public function testLoginWithoutCsrfTokenIsRejectedAndDoesNotAuthenticate(): void
    {
        $registration = new HttpClient();
        $this->loginAs($registration);

        $client = new HttpClient();
        $response = $client->post(self::LOGIN_PATH, [
            'username' => 'alice',
            'password' => 'secret123',
        ]);

        $this->assertCsrfRejected($response);

        $this->assertRedirectsToLogin($client->get(self::TASK_INDEX_PATH));
    }

    public function testLoginWithWrongCsrfTokenIsRejected(): void
    {
        $registration = new HttpClient();
        $this->loginAs($registration);

        $client = new HttpClient();
        $this->csrfTokenFrom($client, self::LOGIN_PATH);

        $response = $client->post(self::LOGIN_PATH, [
            'username' => 'alice',
            'password' => 'secret123',
            'csrf_token' => self::WRONG_CSRF_TOKEN,
        ]);

        $this->assertCsrfRejected($response);
    }

    public function testLoginWithValidCsrfTokenRedirectsToTheTaskIndex(): void
    {
        $registration = new HttpClient();
        $this->loginAs($registration);

        $client = new HttpClient();
        $response = $client->post(self::LOGIN_PATH, [
            'username' => 'alice',
            'password' => 'secret123',
            'csrf_token' => $this->csrfTokenFrom($client, self::LOGIN_PATH),
        ]);

        $this->assertRedirectsToTaskIndex($response);
        $this->assertSame(200, $client->get(self::TASK_INDEX_PATH)->status);
    }

    public function testRegisterWithoutCsrfTokenIsRejectedAndCreatesNoUser(): void
    {
        $client = new HttpClient();

        $response = $client->post(self::REGISTER_PATH, [
            'username' => 'alice',
            'email' => 'alice@example.com',
            'password' => 'secret123',
            'confirm_password' => 'secret123',
        ]);

        $this->assertCsrfRejected($response);
        $this->assertSame(0, (int) TestDatabase::scalar('SELECT COUNT(*) FROM users'));
    }

    public function testRegisterWithWrongCsrfTokenIsRejectedAndCreatesNoUser(): void
    {
        $client = new HttpClient();
        $this->csrfTokenFrom($client, self::REGISTER_PATH);

        $response = $client->post(self::REGISTER_PATH, [
            'username' => 'alice',
            'email' => 'alice@example.com',
            'password' => 'secret123',
            'confirm_password' => 'secret123',
            'csrf_token' => self::WRONG_CSRF_TOKEN,
        ]);

        $this->assertCsrfRejected($response);
        $this->assertSame(0, (int) TestDatabase::scalar('SELECT COUNT(*) FROM users'));
    }

    public function testCreateTaskWithoutCsrfTokenIsRejectedAndCreatesNoRow(): void
    {
        $client = new HttpClient();
        $this->loginAs($client);

        $response = $client->post(self::TASK_CREATE_PATH, [
            'title' => 'CSRF task',
            'description' => 'must not be stored',
            'priority' => 'high',
        ]);

        $this->assertCsrfRejected($response);
        $this->assertSame(0, (int) TestDatabase::scalar('SELECT COUNT(*) FROM tasks'));
    }

    public function testCreateTaskWithWrongCsrfTokenIsRejectedAndCreatesNoRow(): void
    {
        $client = new HttpClient();
        $this->loginAs($client);
        $this->csrfTokenFrom($client, self::TASK_CREATE_PATH);

        $response = $client->post(self::TASK_CREATE_PATH, [
            'title' => 'CSRF task',
            'description' => 'must not be stored',
            'priority' => 'high',
            'csrf_token' => self::WRONG_CSRF_TOKEN,
        ]);

        $this->assertCsrfRejected($response);
        $this->assertSame(0, (int) TestDatabase::scalar('SELECT COUNT(*) FROM tasks'));
    }

    public function testCreateTaskWithValidCsrfTokenStoresTheRow(): void
    {
        $client = new HttpClient();
        $userId = $this->loginAs($client);

        $response = $client->post(self::TASK_CREATE_PATH, [
            'title' => 'CSRF task',
            'description' => 'stored through the form',
            'priority' => 'high',
            'csrf_token' => $this->csrfTokenFrom($client, self::TASK_CREATE_PATH),
        ]);

        $this->assertRedirectsToTaskIndex($response);

        $task = TestDatabase::row('SELECT * FROM tasks');

        $this->assertNotNull($task);
        $this->assertSame($userId, (int) $task['user_id']);
        $this->assertSame('CSRF task', $task['title']);
        $this->assertSame('stored through the form', $task['description']);
        $this->assertSame('high', $task['priority']);
    }

    public function testEditWithoutCsrfTokenIsRejectedAndKeepsTheRowUnchanged(): void
    {
        $client = new HttpClient();
        $this->loginAs($client);
        $taskId = $this->createTask($client, 'Edit me');

        $response = $client->post('/index.php?controller=task&action=edit&id=' . $taskId, [
            'title' => 'Renamed',
            'description' => 'changed',
            'priority' => 'high',
        ]);

        $this->assertCsrfRejected($response);
        $this->assertSame('Edit me', TestDatabase::scalar('SELECT title FROM tasks WHERE id = ?', [$taskId]));
    }

    public function testEditWithWrongCsrfTokenIsRejectedAndKeepsTheRowUnchanged(): void
    {
        $client = new HttpClient();
        $this->loginAs($client);
        $taskId = $this->createTask($client, 'Edit me');
        $this->csrfTokenFrom($client, '/index.php?controller=task&action=edit&id=' . $taskId);

        $response = $client->post('/index.php?controller=task&action=edit&id=' . $taskId, [
            'title' => 'Renamed',
            'description' => 'changed',
            'priority' => 'high',
            'csrf_token' => self::WRONG_CSRF_TOKEN,
        ]);

        $this->assertCsrfRejected($response);
        $this->assertSame('Edit me', TestDatabase::scalar('SELECT title FROM tasks WHERE id = ?', [$taskId]));
    }

    public function testDeleteWithoutCsrfTokenIsRejectedAndKeepsTheRow(): void
    {
        $client = new HttpClient();
        $this->loginAs($client);
        $taskId = $this->createTask($client, 'Keep me');

        $response = $client->post('/index.php?controller=task&action=delete&id=' . $taskId);

        $this->assertCsrfRejected($response);
        $this->assertSame(1, (int) TestDatabase::scalar('SELECT COUNT(*) FROM tasks WHERE id = ?', [$taskId]));
    }

    public function testDeleteWithWrongCsrfTokenIsRejectedAndKeepsTheRow(): void
    {
        $client = new HttpClient();
        $this->loginAs($client);
        $taskId = $this->createTask($client, 'Keep me too');
        $this->csrfTokenFrom($client, '/index.php?controller=task&action=delete&id=' . $taskId);

        $response = $client->post('/index.php?controller=task&action=delete&id=' . $taskId, [
            'csrf_token' => self::WRONG_CSRF_TOKEN,
        ]);

        $this->assertCsrfRejected($response);
        $this->assertSame(1, (int) TestDatabase::scalar('SELECT COUNT(*) FROM tasks WHERE id = ?', [$taskId]));
    }

    public function testUpdateStatusWithoutCsrfTokenIsRejectedAndLeavesTheStatusUnchanged(): void
    {
        $client = new HttpClient();
        $this->loginAs($client);
        $taskId = $this->createTask($client, 'Status target');

        $response = $client->post('/index.php?controller=task&action=updateStatus&id=' . $taskId . '&status=completed');

        $this->assertCsrfRejected($response);
        $this->assertSame('pending', TestDatabase::scalar('SELECT status FROM tasks WHERE id = ?', [$taskId]));
    }

    public function testUpdateStatusWithWrongCsrfTokenIsRejectedAndLeavesTheStatusUnchanged(): void
    {
        $client = new HttpClient();
        $this->loginAs($client);
        $taskId = $this->createTask($client, 'Status target');

        $path = '/index.php?controller=task&action=updateStatus&id=' . $taskId . '&status=completed';

        $response = $client->post($path, [
            'csrf_token' => self::WRONG_CSRF_TOKEN,
        ]);

        $this->assertCsrfRejected($response);
        $this->assertSame('pending', TestDatabase::scalar('SELECT status FROM tasks WHERE id = ?', [$taskId]));
    }

    public function testGetUpdateStatusIsMethodNotAllowedAndLeavesTheStatusUnchanged(): void
    {
        $client = new HttpClient();
        $this->loginAs($client);
        $taskId = $this->createTask($client, 'Status target');

        $response = $client->get('/index.php?controller=task&action=updateStatus&id=' . $taskId . '&status=completed');

        $this->assertSame(405, $response->status);
        $this->assertSame('pending', TestDatabase::scalar('SELECT status FROM tasks WHERE id = ?', [$taskId]));
    }

    public function testLogoutWithoutCsrfTokenIsRejectedAndKeepsTheSession(): void
    {
        $client = new HttpClient();
        $this->loginAs($client);

        $response = $client->post(self::LOGOUT_PATH);

        $this->assertCsrfRejected($response);
        $this->assertSame(200, $client->get(self::TASK_INDEX_PATH)->status);
    }

    public function testLogoutWithWrongCsrfTokenIsRejectedAndKeepsTheSession(): void
    {
        $client = new HttpClient();
        $this->loginAs($client);

        $response = $client->post(self::LOGOUT_PATH, [
            'csrf_token' => self::WRONG_CSRF_TOKEN,
        ]);

        $this->assertCsrfRejected($response);
        $this->assertSame(200, $client->get(self::TASK_INDEX_PATH)->status);
    }

    public function testCsrfTokenRotatesOnLoginAndTheOldTokenStopsWorking(): void
    {
        $registration = new HttpClient();
        $this->loginAs($registration);

        $client = new HttpClient();
        $before = $this->csrfTokenFrom($client, self::LOGIN_PATH);

        $login = $client->post(self::LOGIN_PATH, [
            'username' => 'alice',
            'password' => 'secret123',
            'csrf_token' => $before,
        ]);
        $this->assertRedirectsToTaskIndex($login);

        $after = $this->csrfTokenFrom($client, self::TASK_INDEX_PATH);
        $this->assertNotSame($before, $after, 'Login must rotate the CSRF token.');

        $stale = $client->post(self::LOGOUT_PATH, ['csrf_token' => $before]);
        $this->assertCsrfRejected($stale);
        $this->assertSame(200, $client->get(self::TASK_INDEX_PATH)->status, 'A stale token must not end the session.');
    }

    private function loginAs(HttpClient $client, string $username = 'alice', string $password = 'secret123'): int
    {
        $registerPage = $client->get(self::REGISTER_PATH);
        $this->assertSame(200, $registerPage->status, 'The registration form should be reachable.');

        $response = $client->post(self::REGISTER_PATH, [
            'username' => $username,
            'email' => $username . '@example.com',
            'password' => $password,
            'confirm_password' => $password,
            'csrf_token' => $registerPage->csrf(),
        ]);

        $this->assertSame(302, $response->status, 'Registration should log the user in: ' . $response->body);
        $this->assertSame(self::TASK_INDEX_PATH, $response->location());

        $userId = TestDatabase::scalar('SELECT id FROM users WHERE username = ?', [$username]);
        $this->assertNotNull($userId, 'Registration should create the user row.');

        return (int) $userId;
    }

    private function csrfTokenFrom(HttpClient $client, string $path): string
    {
        $response = $client->get($path);
        $this->assertSame(200, $response->status, sprintf('GET %s should render a form.', $path));

        return $response->csrf();
    }

    private function createTask(HttpClient $client, string $title): int
    {
        $response = $client->post(self::TASK_CREATE_PATH, [
            'title' => $title,
            'description' => 'created through the UI',
            'priority' => 'medium',
            'csrf_token' => $this->csrfTokenFrom($client, self::TASK_CREATE_PATH),
        ]);

        $this->assertRedirectsToTaskIndex($response);

        return (int) TestDatabase::scalar('SELECT id FROM tasks ORDER BY id DESC LIMIT 1');
    }

    private function assertCsrfRejected(HttpResponse $response): void
    {
        $this->assertSame(403, $response->status, 'CSRF rejection must use status 403: ' . $response->body);
        $this->assertStringContainsString(self::CSRF_ERROR, $response->body);
    }

    private function assertRedirectsToTaskIndex(HttpResponse $response): void
    {
        $this->assertSame(302, $response->status, 'Expected a redirect, got: ' . $response->body);
        $this->assertStringContainsString('controller=task&action=index', $response->location());
    }

    private function assertRedirectsToLogin(HttpResponse $response): void
    {
        $this->assertSame(302, $response->status, 'Expected a redirect to login, got: ' . $response->body);
        $this->assertStringContainsString('controller=auth&action=login', $response->location());
    }
}
