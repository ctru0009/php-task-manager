<?php

declare(strict_types=1);

/**
 * The documented setup bind-mounts the project over the Apache docroot, which
 * makes .env, .git and the tests reachable unless Apache denies them. Those
 * rules live in docker/apache-security.conf and can only be checked against
 * Apache, so this test probes the container's own web server on port 80 and
 * skips when Apache is not running (for example on a CI runner).
 */
final class DockerExposureTest extends DatabaseTestCase
{
    private const DENIED_PATHS = [
        '/.env',
        '/.git/config',
        '/schema.sql',
        '/composer.json',
        '/phpunit.xml.dist',
        '/tests/bootstrap.php',
        '/vendor/autoload.php',
    ];

    public function testApacheDeniesSecretsToolingAndTests(): void
    {
        $socket = @fsockopen('127.0.0.1', 80, $errorNumber, $errorString, 0.5);

        if (!is_resource($socket)) {
            $this->markTestSkipped('Apache is not serving on 127.0.0.1:80 here, so the deny rules cannot be probed.');
        }

        fclose($socket);

        $client = new HttpClient('http://127.0.0.1');

        foreach (self::DENIED_PATHS as $path) {
            $this->assertSame(403, $client->get($path)->status, $path . ' must not be served.');
        }

        $login = $client->get('/index.php?controller=auth&action=login');
        $this->assertSame(200, $login->status, 'The application itself must stay reachable.');
        $this->assertStringContainsString('name="password"', $login->body);

        $stylesheet = $client->get('/public/css/style.css');
        $this->assertSame(200, $stylesheet->status, 'Static assets must stay reachable.');
    }
}
