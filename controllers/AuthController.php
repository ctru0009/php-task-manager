<?php

require_once __DIR__ . '/../models/User.php';

class AuthController {
    private $user;

    public function __construct() {
        $this->user = new User();
    }

    public function register() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            require __DIR__ . '/../views/auth/register.php';
            return;
        }

        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $errors = [];

        if (empty($username)) {
            $errors[] = 'Username is required';
        } elseif (strlen($username) < 3) {
            $errors[] = 'Username must be at least 3 characters';
        }

        if (empty($email)) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }

        if (empty($password)) {
            $errors[] = 'Password is required';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters';
        }

        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match';
        }

        if (!empty($errors)) {
            require __DIR__ . '/../views/auth/register.php';
            return;
        }

        try {
            $userId = $this->user->register($username, $email, $password);
            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $username;
            header('Location: /index.php?controller=task&action=index');
            exit;
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
            require __DIR__ . '/../views/auth/register.php';
        }
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            require __DIR__ . '/../views/auth/login.php';
            return;
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $errors = [];

        if (empty($username)) {
            $errors[] = 'Username is required';
        }

        if (empty($password)) {
            $errors[] = 'Password is required';
        }

        if (!empty($errors)) {
            require __DIR__ . '/../views/auth/login.php';
            return;
        }

        $user = $this->user->login($username, $password);

        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header('Location: /index.php?controller=task&action=index');
            exit;
        } else {
            $errors[] = 'Invalid username or password';
            require __DIR__ . '/../views/auth/login.php';
        }
    }

    public function logout() {
        session_destroy();
        header('Location: /index.php?controller=auth&action=login');
        exit;
    }
}
