<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Renders each view directly (no HTTP, no database) with hostile values in
 * every field it echoes. User data must never be able to leave its element or
 * attribute context: the escaped text appears, the raw payload never does.
 */
final class ViewEscapingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $_SESSION = [];
        $_GET = [];
    }

    public function testTaskIndexEscapesHostileTitleDescriptionStatusAndPriority(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'alice';
        $_GET['status'] = 'pending';

        $tasks = [
            [
                'id' => 7,
                'title' => '"><script>alert(index-title)</script>',
                'description' => '"><img src=x onerror=alert(index-description)>',
                'status' => '"><script>alert(index-status)</script>',
                'priority' => '"><script>alert(index-priority)</script>',
                'created_at' => '2026-01-02 03:04:05',
                'updated_at' => '2026-01-02 03:04:05',
            ],
        ];

        $html = $this->renderView('views/task/index.php', ['tasks' => $tasks]);

        $this->assertStringContainsString('&lt;script&gt;alert(index-title)&lt;/script&gt;', $html);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(index-description)&gt;', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(index-status)&lt;/script&gt;', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(index-priority)&lt;/script&gt;', $html);

        $this->assertStringNotContainsString('"><script>alert(index-title)</script>', $html);
        $this->assertStringNotContainsString('"><img src=x onerror=alert(index-description)>', $html);
        $this->assertStringNotContainsString('"><script>alert(index-status)</script>', $html);
        $this->assertStringNotContainsString('"><script>alert(index-priority)</script>', $html);

        // The payloads begin with a quotation mark, so the class attributes
        // they are echoed into must not be breakable with a raw quote either.
        $this->assertStringNotContainsString('"&gt;', $html);

        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('<img src=x onerror', $html);
    }

    public function testTaskDeleteEscapesHostileTitleDescriptionStatusAndPriority(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'alice';

        $task = [
            'id' => 9,
            'title' => '"><script>alert(delete-title)</script>',
            'description' => '"><img src=x onerror=alert(delete-description)>',
            'status' => '"><script>alert(delete-status)</script>',
            'priority' => '"><script>alert(delete-priority)</script>',
            'created_at' => '2026-01-02 03:04:05',
            'updated_at' => '2026-01-02 03:04:05',
        ];

        $html = $this->renderView('views/task/delete.php', ['task' => $task]);

        $this->assertStringContainsString('&lt;script&gt;alert(delete-title)&lt;/script&gt;', $html);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(delete-description)&gt;', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(delete-status)&lt;/script&gt;', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(delete-priority)&lt;/script&gt;', $html);

        $this->assertStringNotContainsString('"><script>alert(delete-title)</script>', $html);
        $this->assertStringNotContainsString('"><img src=x onerror=alert(delete-description)>', $html);
        $this->assertStringNotContainsString('"><script>alert(delete-status)</script>', $html);
        $this->assertStringNotContainsString('"><script>alert(delete-priority)</script>', $html);

        // The payloads begin with a quotation mark, so the class attributes
        // they are echoed into must not be breakable with a raw quote either.
        $this->assertStringNotContainsString('"&gt;', $html);

        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('<img src=x onerror', $html);
    }

    public function testTaskEditEscapesHostileTitleAndDescriptionInsideTheForm(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'alice';

        $task = [
            'id' => 11,
            'title' => '<script>alert(edit-title)</script>',
            'description' => '"><img src=x onerror=alert(edit-description)>',
            'status' => 'pending',
            'priority' => 'medium',
            'created_at' => '2026-01-02 03:04:05',
            'updated_at' => '2026-01-02 03:04:05',
        ];

        $html = $this->renderView('views/task/edit.php', ['task' => $task, 'errors' => []]);

        $this->assertStringContainsString('value="&lt;script&gt;alert(edit-title)&lt;/script&gt;"', $html);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(edit-description)&gt;', $html);

        $this->assertStringNotContainsString('<script>alert(edit-title)</script>', $html);
        $this->assertStringNotContainsString('"><img src=x onerror=alert(edit-description)>', $html);

        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('<img src=x onerror', $html);
    }

    public function testLayoutEscapesHostileSessionMessageTypeInsideClassAttribute(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'alice';
        $_SESSION['message'] = 'Task created successfully.';
        $_SESSION['message_type'] = '"><script>alert(message-type)</script>';

        $html = $this->renderView('views/layout.php', []);

        $matched = preg_match('/class="message ([^"]*)"/', $html, $matches);
        $this->assertSame(1, $matched, 'The layout should render the message block with a class attribute.');
        $this->assertStringContainsString('&lt;script&gt;alert(message-type)&lt;/script&gt;', $matches[1]);
        $this->assertStringNotContainsString('<', $matches[1], 'The message type must not inject a tag into the class attribute.');

        $this->assertStringContainsString('Task created successfully.', $html);

        $this->assertStringNotContainsString('"><script>alert(message-type)</script>', $html);
        $this->assertStringNotContainsString('<script', $html);
    }

    public function testRegistrationFormEscapesHostileValuesAndErrors(): void
    {
        $html = $this->renderView('views/auth/register.php', [
            'errors' => ['"><script>alert(register-error)</script>'],
            'username' => '"><script>alert(register-username)</script>',
            'email' => '"><script>alert(register-email)</script>',
        ]);

        $this->assertStringContainsString('&lt;script&gt;alert(register-username)&lt;/script&gt;', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(register-email)&lt;/script&gt;', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(register-error)&lt;/script&gt;', $html);

        $this->assertStringNotContainsString('"><script>alert(register-username)</script>', $html);
        $this->assertStringNotContainsString('"><script>alert(register-email)</script>', $html);
        $this->assertStringNotContainsString('"><script>alert(register-error)</script>', $html);
        $this->assertStringNotContainsString('<script', $html);
    }

    public function testLoginFormEscapesHostileUsernameAndErrors(): void
    {
        $html = $this->renderView('views/auth/login.php', [
            'errors' => ['"><script>alert(login-error)</script>'],
            'username' => '"><script>alert(login-username)</script>',
        ]);

        $this->assertStringContainsString('&lt;script&gt;alert(login-username)&lt;/script&gt;', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(login-error)&lt;/script&gt;', $html);

        $this->assertStringNotContainsString('"><script>alert(login-username)</script>', $html);
        $this->assertStringNotContainsString('"><script>alert(login-error)</script>', $html);
        $this->assertStringNotContainsString('<script', $html);
    }

    public function testTaskCreateFormEscapesHostileValuesAndErrors(): void
    {
        $html = $this->renderView('views/task/create.php', [
            'errors' => ['"><script>alert(create-error)</script>'],
            'title' => '"><script>alert(create-title)</script>',
            'description' => '"><img src=x onerror=alert(create-description)>',
            'priority' => 'low',
        ]);

        $this->assertStringContainsString('value="&quot;&gt;&lt;script&gt;alert(create-title)&lt;/script&gt;"', $html);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(create-description)&gt;', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(create-error)&lt;/script&gt;', $html);

        $this->assertStringNotContainsString('"><script>alert(create-title)</script>', $html);
        $this->assertStringNotContainsString('"><img src=x onerror=alert(create-description)>', $html);
        $this->assertStringNotContainsString('"><script>alert(create-error)</script>', $html);
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('<img src=x onerror', $html);
    }

    /**
     * Render a view the way a controller does: the view variables live in this
     * scope, and everything the view prints is captured instead of emitted.
     *
     * @param array<string, mixed> $variables
     */
    private function renderView(string $relativePath, array $variables): string
    {
        extract($variables, EXTR_SKIP);

        ob_start();

        try {
            require dirname(__DIR__, 2) . '/' . $relativePath;
        } finally {
            $html = (string) ob_get_clean();
        }

        return $html;
    }
}
