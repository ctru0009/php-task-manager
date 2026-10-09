<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/exceptions.php';

class User {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
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

        if ($user && password_verify($password, $user['password'])) {
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
