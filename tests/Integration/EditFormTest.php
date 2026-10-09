<?php

declare(strict_types=1);

/**
 * The edit form must preselect the option matching the task's stored
 * priority, parsed out of the <select name="priority"> block.
 */
final class EditFormTest extends DatabaseTestCase
{
    public function testLowPriorityTaskIsPreselectedInTheEditForm(): void
    {
        $client = new HttpClient();
        $this->loginAs($client);
        $taskId = $this->createTask($client, 'Low priority task', 'low');

        $response = $client->get('/index.php?controller=task&action=edit&id=' . $taskId);
        $this->assertSame(200, $response->status);

        $select = $this->prioritySelectBlock($response->body);

        $this->assertOptionSelected($select, 'low');
        $this->assertOptionNotSelected($select, 'medium');
        $this->assertOptionNotSelected($select, 'high');
    }

    public function testHighPriorityTaskIsPreselectedInTheEditForm(): void
    {
        $client = new HttpClient();
        $this->loginAs($client);
        $taskId = $this->createTask($client, 'High priority task', 'high');

        $response = $client->get('/index.php?controller=task&action=edit&id=' . $taskId);
        $this->assertSame(200, $response->status);

        $select = $this->prioritySelectBlock($response->body);

        $this->assertOptionSelected($select, 'high');
        $this->assertOptionNotSelected($select, 'low');
        $this->assertOptionNotSelected($select, 'medium');
    }

    /** @return string the inner HTML of the <select name="priority"> element */
    private function prioritySelectBlock(string $html): string
    {
        $matched = preg_match('/<select\b[^>]*name="priority"[^>]*>(.*?)<\/select>/s', $html, $matches);
        $this->assertSame(1, $matched, 'The edit form should contain a <select name="priority"> element.');

        return $matches[1];
    }

    private function assertOptionSelected(string $select, string $value): void
    {
        $tag = $this->optionTag($select, $value);
        $this->assertSame(1, preg_match('/\bselected\b/i', $tag), sprintf('The "%s" option should be selected: %s', $value, $tag));
    }

    private function assertOptionNotSelected(string $select, string $value): void
    {
        $tag = $this->optionTag($select, $value);
        $this->assertSame(0, preg_match('/\bselected\b/i', $tag), sprintf('The "%s" option should not be selected: %s', $value, $tag));
    }

    private function optionTag(string $select, string $value): string
    {
        $matched = preg_match('/<option\b[^>]*\bvalue="' . preg_quote($value, '/') . '"[^>]*>/', $select, $matches);
        $this->assertSame(1, $matched, sprintf('The priority select should contain an option with value="%s".', $value));

        return $matches[0];
    }

    private function loginAs(HttpClient $client, string $username = 'alice', string $password = 'secret123'): int
    {
        $registerPage = $client->get('/index.php?controller=auth&action=register');
        $this->assertSame(200, $registerPage->status, 'The registration form should be reachable.');

        $response = $client->post('/index.php?controller=auth&action=register', [
            'username' => $username,
            'email' => $username . '@example.com',
            'password' => $password,
            'confirm_password' => $password,
            'csrf_token' => $registerPage->csrf(),
        ]);

        $this->assertSame(302, $response->status, 'Registration should log the user in: ' . $response->body);

        $userId = TestDatabase::scalar('SELECT id FROM users WHERE username = ?', [$username]);
        $this->assertNotNull($userId, 'Registration should create the user row.');

        return (int) $userId;
    }

    private function createTask(HttpClient $client, string $title, string $priority): int
    {
        $formPage = $client->get('/index.php?controller=task&action=create');
        $this->assertSame(200, $formPage->status, 'The create form should be reachable.');

        $response = $client->post('/index.php?controller=task&action=create', [
            'title' => $title,
            'description' => '',
            'priority' => $priority,
            'csrf_token' => $formPage->csrf(),
        ]);

        $this->assertSame(302, $response->status, 'Task creation should succeed: ' . $response->body);

        $id = TestDatabase::scalar('SELECT id FROM tasks WHERE title = ?', [$title]);
        $this->assertNotNull($id, 'Task creation should insert a row.');

        return (int) $id;
    }
}
