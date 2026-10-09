<?php

declare(strict_types=1);

/**
 * Runs the application on PHP's built-in server so HTTP tests exercise real
 * headers, cookies, sessions and controller code. Started from tests/bootstrap.php.
 */
final class TestServer
{
    public const HOST = '127.0.0.1';
    public const PORT = 8123;

    /** @var resource|null */
    private static $process = null;

    private static string $logFile = '';

    public static function baseUrl(): string
    {
        return 'http://' . self::HOST . ':' . self::PORT;
    }

    public static function start(): void
    {
        if (self::$process !== null) {
            return;
        }

        $root = dirname(__DIR__, 2);
        self::$logFile = sys_get_temp_dir() . '/php-task-manager-test-server.log';

        if (self::portIsOpen()) {
            throw new RuntimeException(sprintf(
                'Something is already listening on %s. Stop it and run the suite again: the tests must hit the server this process started.',
                self::baseUrl()
            ));
        }

        $command = sprintf(
            'exec %s -S %s:%d -t %s',
            escapeshellarg(PHP_BINARY),
            self::HOST,
            self::PORT,
            escapeshellarg($root)
        );

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['file', self::$logFile, 'a'],
            2 => ['file', self::$logFile, 'a'],
        ];

        $process = proc_open($command, $descriptors, $pipes, $root);

        if (!is_resource($process)) {
            throw new RuntimeException('Could not start the PHP built-in server.');
        }

        self::$process = $process;

        $deadline = microtime(true) + 10.0;

        while (microtime(true) < $deadline) {
            $socket = @fsockopen(self::HOST, self::PORT, $errorNumber, $errorString, 0.2);

            if (is_resource($socket)) {
                fclose($socket);
                register_shutdown_function([self::class, 'stop']);

                return;
            }

            usleep(100_000);
        }

        self::stop();

        throw new RuntimeException(sprintf(
            'Test server did not become ready on %s within 10 seconds. Log: %s',
            self::baseUrl(),
            self::$logFile
        ));
    }

    public static function stop(): void
    {
        if (self::$process === null) {
            return;
        }

        $status = proc_get_status(self::$process);

        if (($status['running'] ?? false) === true) {
            proc_terminate(self::$process);
        }

        proc_close(self::$process);
        self::$process = null;
    }

    private static function portIsOpen(): bool
    {
        $socket = @fsockopen(self::HOST, self::PORT, $errorNumber, $errorString, 0.2);

        if (is_resource($socket)) {
            fclose($socket);

            return true;
        }

        return false;
    }
}
