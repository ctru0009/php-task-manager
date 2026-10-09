<?php

declare(strict_types=1);

/**
 * User B must not be able to read, edit, delete or move user A's task by
 * changing the id in the URL, over real HTTP.
 */
final class IsolationHttpTest extends HttpTestCase
{
    private const TASK_INDEX = '/index.php?controller=task&action=index';

    public function testUserBCannotReadUserAsTask(): void
    {
        [, $bob, $taskId, $title] = $this->twoUsersWithATask();

        $response = $bob->get('/index.php?controller=task&action=edit&id=' . $taskId);

        $this->assertSame(302, $response->status, 'Editing another user\'s task must redirect.');
        $this->assertStringContainsString('controller=task&action=index', $response->location());

        $list = $bob->get(self::TASK_INDEX);

        $this->assertStringNotContainsString($title, $list->body, 'Another user\'s task must not appear in the list.');
    }

    public function testUserBCannotEditUserAsTask(): void
    {
        [, $bob, $taskId, $title] = $this->twoUsersWithATask();

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
        [, $bob, $taskId, $title] = $this->twoUsersWithATask();

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
        [, $bob, $taskId, $title] = $this->twoUsersWithATask();

        $response = $bob->post('/index.php?controller=task&action=updateStatus&id=' . $taskId . '&status=completed', [
            'csrf_token' => $this->csrfFor($bob),
        ]);

        $this->assertSame(302, $response->status);
        $this->assertSame('pending', TestDatabase::scalar('SELECT status FROM tasks WHERE id = ?', [$taskId]));
        $this->assertSame($title, TestDatabase::scalar('SELECT title FROM tasks WHERE id = ?', [$taskId]));
    }

    public function testUserBCannotSeeUserAsTaskThroughTheStatusFilter(): void
    {
        [$alice, $bob, $taskId, $title] = $this->twoUsersWithATask();

        $filtered = $bob->get(self::TASK_INDEX . '&status=pending');
        $this->assertStringNotContainsString($title, $filtered->body);

        $ownList = $alice->get(self::TASK_INDEX . '&status=pending');
        $this->assertStringContainsString($title, $ownList->body, 'The owner must still see the task.');
    }

    /** @return array{0: HttpClient, 1: HttpClient, 2: int, 3: string} */
    private function twoUsersWithATask(): array
    {
        $alice = new HttpClient();
        $this->registerOrFail($alice, 'alice');

        $title = 'Alice private task';
        $this->createTaskOrFail($alice, $title);

        $bob = new HttpClient();
        $this->registerOrFail($bob, 'bob');

        return [$alice, $bob, $this->taskIdByTitle($title), $title];
    }

    private function registerOrFail(HttpClient $client, string $username): void
    {
        $response = $this->register($client, $username);

        if ($response->status !== 302) {
            $this->fail('Registration failed for ' . $username . ': ' . $response->status . ' ' . $response->body);
        }
    }

    private function createTaskOrFail(HttpClient $client, string $title): void
    {
        $response = $this->createTask($client, $title, 'owned by one user only');

        if ($response->status !== 302) {
            $this->fail('Task creation failed: ' . $response->status . ' ' . $response->body);
        }
    }

    private function csrfFor(HttpClient $client): string
    {
        return $this->csrfToken($client, self::TASK_CREATE_PATH);
    }
}
