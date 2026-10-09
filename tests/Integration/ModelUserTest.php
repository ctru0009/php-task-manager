<?php

declare(strict_types=1);

final class ModelUserTest extends DatabaseTestCase
{
    private const PASSWORD = 'secret123';

    public function testRegisterStoresABcryptHashAndReturnsTheNewId(): void
    {
        $user = new User();

        $id = $user->register('alice', 'alice@example.com', self::PASSWORD);

        // PDO::lastInsertId() returns the id as a numeric string; it must still
        // address the row that was just created.
        $this->assertIsNumeric($id);
        $this->assertGreaterThan(0, (int) $id);

        $row = TestDatabase::row('SELECT * FROM users WHERE id = ?', [(int) $id]);

        $this->assertNotNull($row);
        $this->assertSame('alice', $row['username']);
        $this->assertSame('alice@example.com', $row['email']);
        $this->assertTrue(password_verify(self::PASSWORD, $row['password']));
        $this->assertFalse(password_verify('not-the-password', $row['password']));
        $this->assertSame('bcrypt', password_get_info($row['password'])['algoName']);
    }

    public function testRegisterRejectsADuplicateUsername(): void
    {
        $user = new User();
        $user->register('alice', 'alice@example.com', self::PASSWORD);

        $thrown = null;

        try {
            $user->register('alice', 'other@example.com', self::PASSWORD);
        } catch (Exception $e) {
            $thrown = $e;
        }

        $this->assertNotNull($thrown, 'A duplicate username must throw an Exception.');
        $this->assertSame('Username or email already exists', $thrown->getMessage());
        $this->assertSame(1, (int) TestDatabase::scalar('SELECT COUNT(*) FROM users'));
    }

    public function testRegisterRejectsADuplicateEmail(): void
    {
        $user = new User();
        $user->register('alice', 'alice@example.com', self::PASSWORD);

        $thrown = null;

        try {
            $user->register('bob', 'alice@example.com', self::PASSWORD);
        } catch (Exception $e) {
            $thrown = $e;
        }

        $this->assertNotNull($thrown, 'A duplicate email must throw an Exception.');
        $this->assertSame('Username or email already exists', $thrown->getMessage());
        $this->assertSame(1, (int) TestDatabase::scalar('SELECT COUNT(*) FROM users'));
    }

    public function testLoginWithTheCorrectPasswordReturnsTheUserWithoutThePassword(): void
    {
        $user = new User();
        $id = (int) $user->register('alice', 'alice@example.com', self::PASSWORD);

        $row = $user->login('alice', self::PASSWORD);

        $this->assertIsArray($row);
        $this->assertEquals($id, $row['id']);
        $this->assertSame('alice', $row['username']);
        $this->assertSame('alice@example.com', $row['email']);
        $this->assertArrayNotHasKey('password', $row);
    }

    public function testLoginWithTheWrongPasswordReturnsNull(): void
    {
        $user = new User();
        $user->register('alice', 'alice@example.com', self::PASSWORD);

        $this->assertNull($user->login('alice', 'not-the-password'));
    }

    public function testLoginWithAnUnknownUsernameReturnsNull(): void
    {
        $user = new User();
        $user->register('alice', 'alice@example.com', self::PASSWORD);

        $this->assertNull($user->login('ghost', self::PASSWORD));
    }

    public function testGetUserByIdReturnsThePublicColumnsWithoutThePassword(): void
    {
        $user = new User();
        $id = (int) $user->register('alice', 'alice@example.com', self::PASSWORD);

        $row = $user->getUserById($id);

        $this->assertIsArray($row);
        $this->assertEquals($id, $row['id']);
        $this->assertSame('alice', $row['username']);
        $this->assertSame('alice@example.com', $row['email']);
        $this->assertArrayHasKey('created_at', $row);
        $this->assertNotEmpty($row['created_at']);
        $this->assertArrayNotHasKey('password', $row);
    }
}
