<?php

declare(strict_types=1);

/**
 * Real MySQL access for the test suite. No mocking: every test runs against
 * the database named by DB_NAME, which must end in "_test".
 */
final class TestDatabase
{
    private static ?PDO $pdo = null;

    public static function dbName(): string
    {
        $name = getenv('DB_NAME');

        if ($name === false || $name === '') {
            throw new RuntimeException('DB_NAME is not set. The test suite uses the same DB_* variables as the application.');
        }

        if (preg_match('/^[A-Za-z0-9_]+_test$/', $name) !== 1) {
            throw new RuntimeException(sprintf(
                'Refusing to run tests against "%s": the database name must match ^[A-Za-z0-9_]+_test$.',
                $name
            ));
        }

        return $name;
    }

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            self::$pdo = self::connect(self::dbName());
        }

        return self::$pdo;
    }

    public static function createSchema(string $schemaFile): void
    {
        $dbName = self::dbName();

        $server = self::connect(null);
        $server->exec(sprintf('DROP DATABASE IF EXISTS `%s`', $dbName));
        $server->exec(sprintf('CREATE DATABASE `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci', $dbName));

        $sql = (string) file_get_contents($schemaFile);
        $sql = (string) preg_replace('/^\s*--.*$/m', '', $sql);
        // schema.sql creates and selects its own database. The test suite
        // applies the same DDL to the test database instead.
        $sql = (string) preg_replace('/^\s*(CREATE DATABASE[^;]*;|USE\s+[^;]*;)/mi', '', $sql);

        $pdo = self::pdo();

        foreach (explode(';', $sql) as $statement) {
            $statement = trim($statement);

            if ($statement !== '') {
                $pdo->exec($statement);
            }
        }
    }

    public static function truncate(): void
    {
        $pdo = self::pdo();
        $pdo->exec('DELETE FROM tasks');
        $pdo->exec('DELETE FROM users');
    }

    /** @return list<array<string, mixed>> */
    public static function rows(string $sql, array $params = []): array
    {
        $statement = self::pdo()->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public static function row(string $sql, array $params = []): ?array
    {
        $statement = self::pdo()->prepare($sql);
        $statement->execute($params);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public static function scalar(string $sql, array $params = []): mixed
    {
        $statement = self::pdo()->prepare($sql);
        $statement->execute($params);
        $value = $statement->fetchColumn();

        return $value === false ? null : $value;
    }

    public static function exec(string $sql, array $params = []): int
    {
        $statement = self::pdo()->prepare($sql);
        $statement->execute($params);

        return $statement->rowCount();
    }

    private static function connect(?string $dbName): PDO
    {
        $host = getenv('DB_HOST');
        $user = getenv('DB_USER');
        $password = getenv('DB_PASSWORD');

        if ($host === false || $host === '' || $user === false || $user === '' || $password === false || $password === '') {
            throw new RuntimeException('DB_HOST, DB_USER and DB_PASSWORD must be set for the test suite.');
        }

        $dsn = sprintf('mysql:host=%s;charset=utf8mb4', $host);

        if ($dbName !== null) {
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, $dbName);
        }

        return new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
}
