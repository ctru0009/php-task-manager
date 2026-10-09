<?php

declare(strict_types=1);

/**
 * Task CRUD over real HTTP: create, edit, delete, the status filter and the
 * POST-only updateStatus action, plus invalid task ids.
 */
final class TaskHttpTest extends HttpTestCase
{
    public function testCreateTaskStoresAllFieldsForTheLoggedInUser(): void
    {
        $client = new HttpClient();
        $userId = $this->loginAs($client);

        $response = $this->postTask($client, [
            'title' => 'Write the quarterly report',
            'description' => 'Numbers for the finance team',
            'priority' => 'high',
        ]);

        $this->assertRedirectsToTaskIndex($response);

        $task = TestDatabase::row('SELECT * FROM tasks WHERE title = ?', ['Write the quarterly report']);

        $this->assertNotNull($task, 'A valid create should insert a task row.');
        $this->assertSame($userId, (int) $task['user_id']);
        $this->assertSame('Write the quarterly report', $task['title']);
        $this->assertSame('Numbers for the finance team', $task['description']);
        $this->assertSame('high', $task['priority']);
        $this->assertSame('pending', $task['status']);
    }

    public function testCreateWithEmptyTitleShowsErrorAndStoresNothing(): void
    {
        $client = new HttpClient();
        $this->loginAs($client);

        $response = $this->postTask($client, [
            'title' => '   ',
            'description' => 'Should never be stored',
            'priority' => 'medium',
        ]);

        $this->assertSame(200, $response->status);
        $this->assertStringContainsString('Title is required', $response->body);
        $this->assertStringNotContainsString('SQLSTATE', $response->body);
        $this->assertSame(0, (int) TestDatabase::scalar('SELECT COUNT(*) FROM tasks'));
    }

    public function testCreateWithUnknownPriorityStoresMedium(): void
    {
        $client = new HttpClient();
        $this->loginAs($client);

        $response = $this->postTask($client, [
            'title' => 'Coerced priority task',
            'description' => '',
            'priority' => 'urgent',
        ]);

        $this->assertRedirectsToTaskIndex($response);
        $this->assertSame(
            'medium',
            TestDatabase::scalar('SELECT priority FROM tasks WHERE title = ?', ['Coerced priority task'])
        );
    }

    public function testStatusFilterReturnsOnlyMatchingTasks(): void
    {
        $client = new HttpClient();
        $this->loginAs($client);

        $pendingId = $this->createTaskId($client, 'Alpha pending item');
        $completedId = $this->createTaskId($client, 'Bravo completed item');
        $progressId = $this->createTaskId($client, 'Charlie progress item');

        TestDatabase::exec('UPDATE tasks SET status = ? WHERE id = ?', ['completed', $completedId]);
        TestDatabase::exec('UPDATE tasks SET status = ? WHERE id = ?', ['in_progress', $progressId]);

        $pending = $client->get('/index.php?controller=task&action=index&status=pending');
        $this->assertSame(200, $pending->status);
        $this->assertStringContainsString('Alpha pending item', $pending->body);
        $this->assertStringNotContainsString('Bravo completed item', $pending->body);
        $this->assertStringNotContainsString('Charlie progress item', $pending->body);

        $completed = $client->get('/index.php?controller=task&action=index&status=completed');
        $this->assertSame(200, $completed->status);
        $this->assertStringContainsString('Bravo completed item', $completed->body);
        $this->assertStringNotContainsString('Alpha pending item', $completed->body);
        $this->assertStringNotContainsString('Charlie progress item', $completed->body);

        $unknown = $client->get('/index.php?controller=task&action=index&status=foo');
        $this->assertSame(200, $unknown->status);
        $this->assertStringContainsString('Alpha pending item', $unknown->body);
        $this->assertStringContainsString('Bravo completed item', $unknown->body);
        $this->assertStringContainsString('Charlie progress item', $unknown->body);
        $this->assertStringNotContainsString('SQLSTATE', $unknown->body);
    }

    public function testUpdateStatusPostChangesTheTask(): void
    {
        $client = new HttpClient();
        $this->loginAs($client);
        $taskId = $this->createTaskId($client, 'Status target');

        $token = $this->csrfTokenFrom($client, '/index.php?controller=task&action=create');

        $response = $client->post(
            '/index.php?controller=task&action=updateStatus&id=' . $taskId . '&status=completed',
            ['csrf_token' => $token]
        );

        $this->assertRedirectsToTaskIndex($response);
        $this->assertSame('completed', TestDatabase::scalar('SELECT status FROM tasks WHERE id = ?', [$taskId]));
    }

    public function testUpdateStatusWithUnknownStatusLeavesTheTaskUnchanged(): void
    {
        $client = new HttpClient();
        $this->loginAs($client);
        $taskId = $this->createTaskId($client, 'Status stays pending');

        $token = $this->csrfTokenFrom($client, '/index.php?controller=task&action=create');

        $response = $client->post(
            '/index.php?controller=task&action=updateStatus&id=' . $taskId . '&status=bogus',
            ['csrf_token' => $token]
        );

        $this->assertRedirectsToTaskIndex($response);
        $this->assertSame('pending', TestDatabase::scalar('SELECT status FROM tasks WHERE id = ?', [$taskId]));
    }

    public function testUpdateStatusRejectsGetRequests(): void
    {
        $client = new HttpClient();
        $this->loginAs($client);
        $taskId = $this->createTaskId($client, 'GET must not change me');

        $response = $client->get('/index.php?controller=task&action=updateStatus&id=' . $taskId . '&status=completed');

        $this->assertSame(405, $response->status);
        $this->assertSame('pending', TestDatabase::scalar('SELECT status FROM tasks WHERE id = ?', [$taskId]));
    }

    public function testEditPageRendersTheTaskTitle(): void
    {
        $client = new HttpClient();
        $this->loginAs($client);
        $taskId = $this->createTaskId($client, 'Editable title');

        $response = $client->get('/index.php?controller=task&action=edit&id=' . $taskId);

        $this->assertSame(200, $response->status);
        $this->assertStringContainsString('name="title"', $response->body);
        $this->assertStringContainsString('Editable title', $response->body);
    }

    public function testEditPostUpdatesTheTask(): void
    {
        $client = new HttpClient();
        $this->loginAs($client);
        $taskId = $this->createTaskId($client, 'Before edit');

        $token = $this->csrfTokenFrom($client, '/index.php?controller=task&action=edit&id=' . $taskId);

        $response = $client->post('/index.php?controller=task&action=edit&id=' . $taskId, [
            'title' => 'After edit',
            'description' => 'Updated description',
            'priority' => 'low',
            'csrf_token' => $token,
        ]);

        $this->assertRedirectsToTaskIndex($response);

        $task = TestDatabase::row('SELECT * FROM tasks WHERE id = ?', [$taskId]);

        $this->assertNotNull($task);
        $this->assertSame('After edit', $task['title']);
        $this->assertSame('Updated description', $task['description']);
        $this->assertSame('low', $task['priority']);
    }

    public function testDeletePageShowsAConfirmation(): void
    {
        $client = new HttpClient();
        $this->loginAs($client);
        $taskId = $this->createTaskId($client, 'Delete me later');

        $response = $client->get('/index.php?controller=task&action=delete&id=' . $taskId);

        $this->assertSame(200, $response->status);
        $this->assertStringContainsString('Delete Task', $response->body);
        $this->assertStringContainsString('Delete me later', $response->body);
    }

    public function testDeletePostRemovesTheTask(): void
    {
        $client = new HttpClient();
        $this->loginAs($client);
        $taskId = $this->createTaskId($client, 'Delete me now');

        $token = $this->csrfTokenFrom($client, '/index.php?controller=task&action=delete&id=' . $taskId);

        $response = $client->post('/index.php?controller=task&action=delete&id=' . $taskId, [
            'csrf_token' => $token,
        ]);

        $this->assertRedirectsToTaskIndex($response);
        $this->assertNull(TestDatabase::row('SELECT * FROM tasks WHERE id = ?', [$taskId]));
    }

    public function testEditWithNonNumericIdRedirectsToTheIndex(): void
    {
        $client = new HttpClient();
        $this->loginAs($client);

        $response = $client->get('/index.php?controller=task&action=edit&id=abc');

        $this->assertRedirectsToTaskIndex($response);
        $this->assertStringNotContainsString('SQLSTATE', $response->body);
    }

    public function testEditWithArrayIdRedirectsToTheIndex(): void
    {
        $client = new HttpClient();
        $this->loginAs($client);

        $response = $client->get('/index.php?controller=task&action=edit&id[]=1');

        $this->assertRedirectsToTaskIndex($response);
        $this->assertStringNotContainsString('SQLSTATE', $response->body);
    }

    private function loginAs(HttpClient $client, string $username = 'alice', string $password = 'secret123'): int
    {
        $response = $this->register($client, $username, $password);

        $this->assertSame(302, $response->status, 'Registration should log the user in: ' . $response->body);
        $this->assertSame('/index.php?controller=task&action=index', $response->location());

        $userId = TestDatabase::scalar('SELECT id FROM users WHERE username = ?', [$username]);
        $this->assertNotNull($userId, 'Registration should create the user row.');

        return (int) $userId;
    }

    private function csrfTokenFrom(HttpClient $client, string $path): string
    {
        return $this->csrfToken($client, $path);
    }

    /** @param array{title: string, description: string, priority: string} $fields */
    private function postTask(HttpClient $client, array $fields): HttpResponse
    {
        return $this->createTask($client, $fields['title'], $fields['description'], $fields['priority']);
    }

    private function createTaskId(HttpClient $client, string $title, string $priority = 'medium', string $description = ''): int
    {
        $response = $this->createTask($client, $title, $description, $priority);

        $this->assertRedirectsToTaskIndex($response);

        return $this->taskIdByTitle($title);
    }

    private function assertRedirectsToTaskIndex(HttpResponse $response): void
    {
        $this->assertSame(302, $response->status, 'Expected a redirect, got: ' . $response->body);
        $this->assertStringContainsString('controller=task&action=index', $response->location());
    }
}
