<?php

declare(strict_types=1);

final class ModelTaskTest extends DatabaseTestCase
{
    public function testCreateStoresTheTaskForTheUserAndReturnsItsId(): void
    {
        $userId = $this->createUser('alice');
        $task = new Task();

        $id = $task->create($userId, 'Write the report', 'Draft the quarterly report', 'high');

        // PDO::lastInsertId() returns the id as a numeric string; it must still
        // address the row that was just created.
        $this->assertIsNumeric($id);
        $this->assertGreaterThan(0, (int) $id);

        $row = TestDatabase::row('SELECT * FROM tasks WHERE id = ?', [(int) $id]);

        $this->assertNotNull($row);
        $this->assertEquals($userId, $row['user_id']);
        $this->assertSame('Write the report', $row['title']);
        $this->assertSame('Draft the quarterly report', $row['description']);
        $this->assertSame('high', $row['priority']);
        $this->assertSame('pending', $row['status']);
    }

    public function testGetByUserIdReturnsExactlyTheTasksOfThatUser(): void
    {
        $aliceId = $this->createUser('alice');
        $bobId = $this->createUser('bob');

        $aliceFirst = $this->createTask($aliceId, 'Alice first');
        $aliceSecond = $this->createTask($aliceId, 'Alice second');
        $bobTask = $this->createTask($bobId, 'Bob only');

        $task = new Task();

        $aliceTasks = $task->getByUserId($aliceId);

        $this->assertCount(2, $aliceTasks);
        $this->assertSame(['Alice first', 'Alice second'], $this->sortedTitles($aliceTasks));
        $this->assertSame([$aliceFirst, $aliceSecond], $this->sortedIds($aliceTasks));

        $bobTasks = $task->getByUserId($bobId);

        $this->assertCount(1, $bobTasks);
        $this->assertSame(['Bob only'], array_column($bobTasks, 'title'));
        $this->assertSame([$bobTask], $this->sortedIds($bobTasks));
    }

    public function testGetByUserIdFiltersByStatus(): void
    {
        $aliceId = $this->createUser('alice');
        $bobId = $this->createUser('bob');

        $this->createTask($aliceId, 'Alpha pending');
        $inProgress = $this->createTask($aliceId, 'Beta in progress');
        $completed = $this->createTask($aliceId, 'Gamma completed');
        $this->setStatus($inProgress, 'in_progress');
        $this->setStatus($completed, 'completed');

        $bobCompleted = $this->createTask($bobId, 'Bob completed');
        $this->setStatus($bobCompleted, 'completed');

        $task = new Task();

        $completedTasks = $task->getByUserId($aliceId, 'completed');

        $this->assertCount(1, $completedTasks);
        $this->assertEquals($completed, $completedTasks[0]['id']);
        $this->assertSame('Gamma completed', $completedTasks[0]['title']);

        $pendingTasks = $task->getByUserId($aliceId, 'pending');

        $this->assertCount(1, $pendingTasks);
        $this->assertSame('Alpha pending', $pendingTasks[0]['title']);

        $inProgressTasks = $task->getByUserId($aliceId, 'in_progress');

        $this->assertCount(1, $inProgressTasks);
        $this->assertSame('Beta in progress', $inProgressTasks[0]['title']);
    }

    public function testGetByIdReturnsTheRowForTheOwnerAndFalseForAnotherUser(): void
    {
        $aliceId = $this->createUser('alice');
        $bobId = $this->createUser('bob');
        $taskId = $this->createTask($aliceId, 'Alice task', 'Alice description', 'high');

        $task = new Task();

        $row = $task->getById($taskId, $aliceId);

        $this->assertIsArray($row);
        $this->assertEquals($taskId, $row['id']);
        $this->assertEquals($aliceId, $row['user_id']);
        $this->assertSame('Alice task', $row['title']);
        $this->assertSame('Alice description', $row['description']);
        $this->assertSame('high', $row['priority']);

        $this->assertFalse($task->getById($taskId, $bobId));
        $this->assertNotNull(TestDatabase::row('SELECT id FROM tasks WHERE id = ? AND user_id = ?', [$taskId, $aliceId]));
    }

    public function testUpdatePersistsTheNewValuesForTheOwner(): void
    {
        $aliceId = $this->createUser('alice');
        $taskId = $this->createTask($aliceId, 'Old title', 'Old description', 'low');

        $updated = (new Task())->update($taskId, $aliceId, 'New title', 'New description', 'high');

        $this->assertTrue($updated);

        $row = TestDatabase::row('SELECT * FROM tasks WHERE id = ?', [$taskId]);

        $this->assertNotNull($row);
        $this->assertEquals($aliceId, $row['user_id']);
        $this->assertSame('New title', $row['title']);
        $this->assertSame('New description', $row['description']);
        $this->assertSame('high', $row['priority']);
    }

    public function testUpdateDoesNothingForAnotherUser(): void
    {
        $aliceId = $this->createUser('alice');
        $bobId = $this->createUser('bob');
        $taskId = $this->createTask($aliceId, 'Alice title', 'Alice description', 'medium');

        $updated = (new Task())->update($taskId, $bobId, 'Hijacked title', 'Hijacked description', 'high');

        $this->assertFalse($updated);

        $row = TestDatabase::row('SELECT * FROM tasks WHERE id = ?', [$taskId]);

        $this->assertNotNull($row);
        $this->assertEquals($aliceId, $row['user_id']);
        $this->assertSame('Alice title', $row['title']);
        $this->assertSame('Alice description', $row['description']);
        $this->assertSame('medium', $row['priority']);
    }

    public function testUpdateStatusChangesTheStatusForTheOwner(): void
    {
        $aliceId = $this->createUser('alice');
        $taskId = $this->createTask($aliceId, 'Finish me');

        $changed = (new Task())->updateStatus($taskId, $aliceId, 'completed');

        $this->assertTrue($changed);
        $this->assertSame('completed', TestDatabase::scalar('SELECT status FROM tasks WHERE id = ?', [$taskId]));
    }

    public function testUpdateStatusDoesNothingForAnotherUser(): void
    {
        $aliceId = $this->createUser('alice');
        $bobId = $this->createUser('bob');
        $taskId = $this->createTask($aliceId, 'Alice task');

        $changed = (new Task())->updateStatus($taskId, $bobId, 'completed');

        $this->assertFalse($changed);

        $row = TestDatabase::row('SELECT * FROM tasks WHERE id = ?', [$taskId]);

        $this->assertNotNull($row);
        $this->assertEquals($aliceId, $row['user_id']);
        $this->assertSame('pending', $row['status']);
    }

    public function testDeleteRemovesTheRowForTheOwner(): void
    {
        $aliceId = $this->createUser('alice');
        $taskId = $this->createTask($aliceId, 'Doomed task');

        $deleted = (new Task())->delete($taskId, $aliceId);

        $this->assertTrue($deleted);
        $this->assertNull(TestDatabase::row('SELECT * FROM tasks WHERE id = ?', [$taskId]));
    }

    public function testDeleteDoesNothingForAnotherUser(): void
    {
        $aliceId = $this->createUser('alice');
        $bobId = $this->createUser('bob');
        $taskId = $this->createTask($aliceId, 'Survivor', 'Still here', 'high');

        $deleted = (new Task())->delete($taskId, $bobId);

        $this->assertFalse($deleted);

        $row = TestDatabase::row('SELECT * FROM tasks WHERE id = ?', [$taskId]);

        $this->assertNotNull($row);
        $this->assertEquals($aliceId, $row['user_id']);
        $this->assertSame('Survivor', $row['title']);
        $this->assertSame('Still here', $row['description']);
        $this->assertSame('high', $row['priority']);
    }

    private function createUser(string $username): int
    {
        $user = new User();

        return (int) $user->register($username, $username . '@example.com', 'secret123');
    }

    private function createTask(int $userId, string $title, string $description = '', string $priority = 'medium'): int
    {
        $task = new Task();

        return (int) $task->create($userId, $title, $description, $priority);
    }

    private function setStatus(int $taskId, string $status): void
    {
        TestDatabase::exec('UPDATE tasks SET status = ? WHERE id = ?', [$status, $taskId]);
    }

    /** @param list<array<string, mixed>> $tasks */
    private function sortedTitles(array $tasks): array
    {
        $titles = array_column($tasks, 'title');
        sort($titles);

        return $titles;
    }

    /** @param list<array<string, mixed>> $tasks */
    private function sortedIds(array $tasks): array
    {
        $ids = array_map('intval', array_column($tasks, 'id'));
        sort($ids);

        return $ids;
    }
}
