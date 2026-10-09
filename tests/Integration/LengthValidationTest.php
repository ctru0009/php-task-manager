<?php

declare(strict_types=1);

/**
 * Boundary tests for the length limits: task titles at 255/256, task
 * descriptions at 65535/65536 and the registration field limits.
 */
final class LengthValidationTest extends HttpTestCase
{
    public function testTaskTitleOf256CharactersIsRejected(): void
    {
        $client = new HttpClient();
        $this->loginAs($client);

        $response = $this->postTask($client, str_repeat('a', 256), '', 'medium');

        $this->assertSame(200, $response->status);
        $this->assertStringContainsString('Title must be at most 255 characters', $response->body);
        $this->assertStringNotContainsString('SQLSTATE', $response->body);
        $this->assertSame(0, (int) TestDatabase::scalar('SELECT COUNT(*) FROM tasks'));
    }

    public function testTaskTitleOf255CharactersIsAccepted(): void
    {
        $client = new HttpClient();
        $this->loginAs($client);

        $title = str_repeat('b', 255);

        $response = $this->postTask($client, $title, '', 'medium');

        $this->assertSame(302, $response->status, 'A 255-character title should be accepted: ' . $response->body);
        $this->assertSame($title, TestDatabase::scalar('SELECT title FROM tasks'));
    }

    public function testTaskDescriptionOf65536CharactersIsRejected(): void
    {
        $client = new HttpClient();
        $this->loginAs($client);

        $response = $this->postTask($client, 'Too long description', str_repeat('c', 65536), 'medium');

        $this->assertSame(200, $response->status);
        $this->assertStringContainsString('Description must be at most 65535 characters', $response->body);
        $this->assertStringNotContainsString('SQLSTATE', $response->body);
        $this->assertSame(0, (int) TestDatabase::scalar('SELECT COUNT(*) FROM tasks'));
    }

    public function testTaskDescriptionOf65535CharactersIsAccepted(): void
    {
        $client = new HttpClient();
        $this->loginAs($client);

        $response = $this->postTask($client, 'Max description', str_repeat('d', 65535), 'medium');

        $this->assertSame(302, $response->status, 'A 65535-character description should be accepted: ' . $response->body);
        $this->assertSame(65535, (int) TestDatabase::scalar('SELECT CHAR_LENGTH(description) FROM tasks'));
    }

    public function testUsernameOf51CharactersIsRejected(): void
    {
        $client = new HttpClient();

        $response = $this->postRegistration($client, str_repeat('u', 51), 'long-username@example.com', 'secret123');

        $this->assertSame(200, $response->status);
        $this->assertStringContainsString('Username must be at most 50 characters', $response->body);
        $this->assertStringNotContainsString('SQLSTATE', $response->body);
        $this->assertSame(0, (int) TestDatabase::scalar('SELECT COUNT(*) FROM users'));
    }

    public function testEmailOf101CharactersIsRejected(): void
    {
        $client = new HttpClient();

        // 101 characters overall and still a valid email format, so the only
        // possible error is the length limit.
        $email = str_repeat('e', 62) . '@' . str_repeat('d', 34) . '.com';

        $response = $this->postRegistration($client, 'alice', $email, 'secret123');

        $this->assertSame(200, $response->status);
        $this->assertStringContainsString('Email must be at most 100 characters', $response->body);
        $this->assertStringNotContainsString('SQLSTATE', $response->body);
        $this->assertSame(0, (int) TestDatabase::scalar('SELECT COUNT(*) FROM users'));
    }

    public function testPasswordOf73CharactersIsRejected(): void
    {
        $client = new HttpClient();

        $response = $this->postRegistration($client, 'alice', 'alice@example.com', str_repeat('p', 73));

        $this->assertSame(200, $response->status);
        $this->assertStringContainsString('Password must be at most 72 characters', $response->body);
        $this->assertStringNotContainsString('SQLSTATE', $response->body);
        $this->assertSame(0, (int) TestDatabase::scalar('SELECT COUNT(*) FROM users'));
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

    private function postRegistration(HttpClient $client, string $username, string $email, string $password): HttpResponse
    {
        return $this->register($client, $username, $password, $email);
    }

    private function postTask(HttpClient $client, string $title, string $description, string $priority): HttpResponse
    {
        return $this->createTask($client, $title, $description, $priority);
    }
}
