<?php

declare(strict_types=1);

/**
 * User B must not be able to read, edit, delete or move user A's task by
 * changing the id in the URL, over real HTTP.
 */
final class IsolationHttpTest extends DatabaseTestCase
{
    private const TASK_INDEX = '/index.php?controller=task&action=index';

    public function testUserBCannotReadUserAsTask(): void
    {
        [, , $taskId, $title] = $this->twoUsersWithATask();

        $bob = $this->clientFor('bob');

        $response = $bob->get('/index.php?controller=task&action=edit&id=' . $taskId);

        $this->assertSame(302, $response->status, 'Editing another user\'s task must redirect.');
        $this->assertStringContainsString('controller=task&action=index', $response->location());

        $list = $bob->get(self::TASK_INDEX);

        $this->assertStringNotContainsString($title, $list->body, 'Another user\'s task must not appear in the list.');
    }

    public function testUserBCannotEditUserAsTask(): void
    {
        [, , $taskId, $title] = $this->twoUsersWithATask();

        $bob = $this->clientFor('bob');

        $response = $bob->post('/index.php?controller=task&action=edit&id=' . $taskId, [
            'title' => 'Hijacked',
            'description' => 'changed by another user',
            'priority' => 'high',
            'csrf_token' => $this->csrfFor($bob),
        ]);

        $this->assertSame(302, $response->status);
        $this->assertSame($title, TestDatabase::scalar('SELECT title FROM tasks WHERE id = ?', [$taskId]));
        $this->assertSame('pending', TestDatabase::scalar('SELECT status FROM tasks WHERE id = ?', [$taskId]));
    }

    public function testUserBCannotDeleteUserAsTask(): void
    {
        [, , $taskId, $title] = $this->twoUsersWithATask();

        $bob = $this->clientFor('bob');

        $confirmation = $bob->get('/index.php?controller=task&action=delete&id=' . $taskId);
        $this->assertSame(302, $confirmation->status, 'The delete confirmation must not expose another user\'s task.');
        $this->assertStringNotContainsString($title, $confirmation->body);

        $response = $bob->post('/index.php?controller=task&action=delete&id=' . $taskId, [
            'csrf_token' => $this->csrfFor($bob),
        ]);

        $this->assertSame(302, $response->status);
        $this->assertSame(1, (int) TestDatabase::scalar('SELECT COUNT(*) FROM tasks WHERE id = ?', [$taskId]));
    }

    public function testUserBCannotChangeTheStatusOfUserAsTask(): void
    {
        [, , $taskId, $title] = $this->twoUsersWithATask();

        $bob = $this->clientFor('bob');

        $response = $bob->post('/index.php?controller=task&action=updateStatus&id=' . $taskId . '&status=completed', [
            'csrf_token' => $this->csrfFor($bob),
        ]);

        $this->assertSame(302, $response->status);
        $this->assertSame('pending', TestDatabase::scalar('SELECT status FROM tasks WHERE id = ?', [$taskId]));
        $this->assertSame($title, TestDatabase::scalar('SELECT title FROM tasks WHERE id = ?', [$taskId]));
    }

    public function testUserBCannotSeeUserAsTaskThroughTheStatusFilter(): void
    {
        [, $alice, $taskId, $title] = $this->twoUsersWithATask();

        $bob = $this->clientFor('bob');

        $filtered = $bob->get(self::TASK_INDEX . '&status=pending');
        $this->assertStringNotContainsString($title, $filtered->body);

        $ownList = $alice->get(self::TASK_INDEX . '&status=pending');
        $this->assertStringContainsString($title, $ownList->body, 'The owner must still see the task.');
    }

    /** @return array{0: HttpClient, 1: HttpClient, 2: int, 3: string} */
    private function twoUsersWithATask(): array
    {
        $alice = new HttpClient();
        $this->register($alice, 'alice');

        $title = 'Alice private task';
        $this->createTask($alice, $title);

        $bob = new HttpClient();
        $this->register($bob, 'bob');

        $taskId = (int) TestDatabase::scalar('SELECT id FROM tasks WHERE title = ?', [$title]);

        return [$alice, $alice, $taskId, $title];
    }

    private function clientFor(string $username): HttpClient
    {
        $client = new HttpClient();
        $this->login($client, $username);

        return $client;
    }

    private function register(HttpClient $client, string $username): void
    {
        $form = $client->get('/index.php?controller=auth&action=register');

        $response = $client->post('/index.php?controller=auth&action=register', [
            'username' => $username,
            'email' => $username . '@example.com',
            'password' => 'secret123',
            'confirm_password' => 'secret123',
            'csrf_token' => $form->csrf(),
        ]);

        if ($response->status !== 302) {
            $this->fail('Registration failed for ' . $username . ': ' . $response->status . ' ' . $response->body);
        }
    }

    private function login(HttpClient $client, string $username): void
    {
        $form = $client->get('/index.php?controller=auth&action=login');

        $response = $client->post('/index.php?controller=auth&action=login', [
            'username' => $username,
            'password' => 'secret123',
            'csrf_token' => $form->csrf(),
        ]);

        if ($response->status !== 302) {
            $this->fail('Login failed for ' . $username . ': ' . $response->status . ' ' . $response->body);
        }
    }

    private function createTask(HttpClient $client, string $title): void
    {
        $response = $client->post('/index.php?controller=task&action=create', [
            'title' => $title,
            'description' => 'owned by one user only',
            'priority' => 'medium',
            'csrf_token' => $this->csrfFor($client),
        ]);

        if ($response->status !== 302) {
            $this->fail('Task creation failed: ' . $response->status . ' ' . $response->body);
        }
    }

    private function csrfFor(HttpClient $client): string
    {
        return $client->get('/index.php?controller=task&action=create')->csrf();
    }
}
