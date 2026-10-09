<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/exceptions.php';

class User {
    private static ?string $dummyHash = null;

    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Hash to verify against when the account does not exist, so a missing
     * username costs the same as a wrong password. Built with the current
     * PASSWORD_DEFAULT cost (10 on PHP 8.2 and 8.3, 12 on 8.4 and later) and
     * cached for the lifetime of the process.
     */
    private static function dummyPasswordHash(): string
    {
        if (self::$dummyHash === null) {
            self::$dummyHash = password_hash('timing-oracle-dummy', PASSWORD_DEFAULT);
        }

        return self::$dummyHash;
    }

    public function register($username, $email, $password) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $this->db->prepare('INSERT INTO users (username, email, password) VALUES (?, ?, ?)');
            $stmt->execute([$username, $email, $hashedPassword]);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                throw new ValidationException('Username or email already exists');
            }
            throw $e;
        }
    }

    public function login($username, $password) {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user) {
            // Verify against a dummy hash so a missing account costs the same
            // as a wrong password. Without it the response time tells an
            // attacker whether the username exists.
            password_verify($password, self::dummyPasswordHash());

            return null;
        }

        if (password_verify($password, $user['password'])) {
            unset($user['password']);
            return $user;
        }

        return null;
    }

    public function getUserById($id) {
        $stmt = $this->db->prepare('SELECT id, username, email, created_at FROM users WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}
