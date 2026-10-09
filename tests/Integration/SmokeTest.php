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
}
