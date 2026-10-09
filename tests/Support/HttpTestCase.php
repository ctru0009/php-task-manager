<?php

declare(strict_types=1);

/**
 * Shared HTTP flows for integration tests: fetching a CSRF token, registering
 * (which signs the user in), signing in, and creating a task through the real
 * forms. Assertions stay in the test classes.
 */
abstract class HttpTestCase extends DatabaseTestCase
{
    protected const REGISTER_PATH = '/index.php?controller=auth&action=register';
    protected const LOGIN_PATH = '/index.php?controller=auth&action=login';
    protected const LOGOUT_PATH = '/index.php?controller=auth&action=logout';
    protected const TASK_INDEX_PATH = '/index.php?controller=task&action=index';
    protected const TASK_CREATE_PATH = '/index.php?controller=task&action=create';

    /** GETs a page in this client's session and returns the CSRF token it rendered. */
    protected function csrfToken(HttpClient $client, string $path): string
    {
        $response = $client->get($path);
        $this->assertSame(200, $response->status, sprintf('GET %s should render a form.', $path));

        return $response->csrf();
    }

    /** Registers through the form, which signs the user in. Returns the response. */
    protected function register(HttpClient $client, string $username, string $password = 'secret123', ?string $email = null): HttpResponse
    {
        return $client->post(self::REGISTER_PATH, [
            'username' => $username,
            'email' => $email ?? $username . '@example.com',
            'password' => $password,
            'confirm_password' => $password,
            'csrf_token' => $this->csrfToken($client, self::REGISTER_PATH),
        ]);
    }

    /** Signs in through the form. Returns the response. */
    protected function login(HttpClient $client, string $username, string $password = 'secret123'): HttpResponse
    {
        return $client->post(self::LOGIN_PATH, [
            'username' => $username,
            'password' => $password,
            'csrf_token' => $this->csrfToken($client, self::LOGIN_PATH),
        ]);
    }

    /** Creates a task through the form. Returns the response. */
    protected function createTask(HttpClient $client, string $title, string $description = 'created through the UI', string $priority = 'medium'): HttpResponse
    {
        return $client->post(self::TASK_CREATE_PATH, [
            'title' => $title,
            'description' => $description,
            'priority' => $priority,
            'csrf_token' => $this->csrfToken($client, self::TASK_CREATE_PATH),
        ]);
    }

    protected function taskIdByTitle(string $title): int
    {
        $id = TestDatabase::scalar('SELECT id FROM tasks WHERE title = ?', [$title]);
        $this->assertNotNull($id, sprintf('Expected a task titled "%s".', $title));

        return (int) $id;
    }
}
