<?php

declare(strict_types=1);

/**
 * Round trip through the real application and MySQL: text the user stores must
 * come back HTML-escaped on the task index and edit pages, never as a live tag.
 */
final class XssTest extends DatabaseTestCase
{
    private const TITLE = '<script>alert(1)</script>';
    private const DESCRIPTION = '<img src=x onerror=alert(1)>';
    private const PASSWORD = 'secret123';

    public function testTaskIndexEscapesStoredTitleAndDescription(): void
    {
        $client = new HttpClient();
        $this->registerLoginAndCreateHostileTask($client);

        $response = $client->get('/index.php?controller=task&action=index');

        $this->assertSame(200, $response->status);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $response->body);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $response->body);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $response->body);
        $this->assertStringNotContainsString('<img src=x onerror', $response->body);
    }

    public function testTaskEditEscapesStoredTitleInsideTheValueAttribute(): void
    {
        $client = new HttpClient();
        $taskId = $this->registerLoginAndCreateHostileTask($client);

        $response = $client->get('/index.php?controller=task&action=edit&id=' . $taskId);

        $this->assertSame(200, $response->status);
        $this->assertStringContainsString('value="&lt;script&gt;alert(1)&lt;/script&gt;"', $response->body);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $response->body);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $response->body);
        $this->assertStringNotContainsString('<img src=x onerror', $response->body);
    }

    /**
     * Registers, logs in through the real forms (every POST carries the CSRF
     * token of a freshly fetched page) and stores the hostile task.
     *
     * @return int the id of the task that was just created
     */
    private function registerLoginAndCreateHostileTask(HttpClient $client): int
    {
        $registerForm = $client->get('/index.php?controller=auth&action=register');

        $register = $client->post('/index.php?controller=auth&action=register', [
            'username' => 'xssuser',
            'email' => 'xssuser@example.com',
            'password' => self::PASSWORD,
            'confirm_password' => self::PASSWORD,
            'csrf_token' => $registerForm->csrf(),
        ]);

        $this->assertSame(302, $register->status);
        $this->assertSame('/index.php?controller=task&action=index', $register->location());

        // Login rotates the token, so take a fresh one from a page fetched
        // after logging in.
        $loginForm = $client->get('/index.php?controller=auth&action=login');

        $login = $client->post('/index.php?controller=auth&action=login', [
            'username' => 'xssuser',
            'password' => self::PASSWORD,
            'csrf_token' => $loginForm->csrf(),
        ]);

        $this->assertSame(302, $login->status);
        $this->assertSame('/index.php?controller=task&action=index', $login->location());

        $createForm = $client->get('/index.php?controller=task&action=create');

        $create = $client->post('/index.php?controller=task&action=create', [
            'title' => self::TITLE,
            'description' => self::DESCRIPTION,
            'priority' => 'medium',
            'csrf_token' => $createForm->csrf(),
        ]);

        $this->assertSame(302, $create->status);

        $taskId = TestDatabase::scalar('SELECT id FROM tasks WHERE title = ? ORDER BY id DESC LIMIT 1', [self::TITLE]);
        $this->assertNotNull($taskId, 'The hostile task should have been stored.');

        return (int) $taskId;
    }
}
