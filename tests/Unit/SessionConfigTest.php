<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * config/session.php is a script loaded before session_start(), so it cannot
 * be exercised in-process (the test runner has no session to configure).
 * Each case runs it in a child PHP process with a different environment and
 * reads back the cookie parameters it set.
 */
final class SessionConfigTest extends TestCase
{
    public function testSecureFlagFollowsTheEnvironmentVariable(): void
    {
        $this->assertTrue($this->cookieParamsFor('1')['secure'], 'SESSION_COOKIE_SECURE=1 must set the Secure attribute.');
        $this->assertFalse($this->cookieParamsFor('0')['secure'], 'SESSION_COOKIE_SECURE=0 must leave Secure off for plain http.');
    }

    public function testHttpOnlyAndSameSiteAreAlwaysSet(): void
    {
        $params = $this->cookieParamsFor('0');

        $this->assertTrue($params['httponly']);
        $this->assertSame('Lax', $params['samesite']);
        $this->assertSame('/', $params['path']);
    }

    /** @return array<string, mixed> */
    private function cookieParamsFor(string $value): array
    {
        $script = sprintf(
            'putenv("SESSION_COOKIE_SECURE=%s"); require %s; echo json_encode(session_get_cookie_params());',
            $value,
            var_export(dirname(__DIR__, 2) . '/config/session.php', true)
        );

        exec(sprintf('%s -r %s', escapeshellarg(PHP_BINARY), escapeshellarg($script)), $output, $exitCode);

        $this->assertSame(0, $exitCode, 'config/session.php must load cleanly: ' . implode("\n", $output));

        $params = json_decode(implode('', $output), true);
        $this->assertIsArray($params, 'config/session.php must set cookie parameters: ' . implode('', $output));

        return $params;
    }
}
