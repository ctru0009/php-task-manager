<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SmokeTest extends TestCase
{
    public function testLoginPageIsServedThroughTheRealApplication(): void
    {
        $client = new HttpClient();
        $response = $client->get('/index.php?controller=auth&action=login');

        $this->assertSame(200, $response->status);
        $this->assertStringContainsString('name="password"', $response->body);
    }

    public function testPagesCloseTheDocument(): void
    {
        $client = new HttpClient();
        $response = $client->get('/index.php?controller=auth&action=login');

        $this->assertStringContainsString('</main>', $response->body);
        $this->assertStringEndsWith('</html>', rtrim($response->body));
    }

    public function testResponsesCarrySecurityHeaders(): void
    {
        $client = new HttpClient();
        $response = $client->get('/index.php?controller=auth&action=login');

        $this->assertSame('DENY', $response->header('X-Frame-Options'));
        $this->assertSame("frame-ancestors 'none'", $response->header('Content-Security-Policy'));
        $this->assertSame('nosniff', $response->header('X-Content-Type-Options'));
        $this->assertSame('same-origin', $response->header('Referrer-Policy'));

        // PHP's session cache limiter sends this on every session-starting request.
        $this->assertStringContainsString('no-store', (string) $response->header('Cache-Control'));
    }
}
