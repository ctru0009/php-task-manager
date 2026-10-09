<?php

require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/exceptions.php';
require_once __DIR__ . '/../includes/validation.php';
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

        csrf_require();

        $username = form_string($_POST['username'] ?? '');
        $email = form_string($_POST['email'] ?? '');
        $password = form_string($_POST['password'] ?? '', false);
        $confirmPassword = form_string($_POST['confirm_password'] ?? '', false);

        $errors = registration_errors($username, $email, $password, $confirmPassword);

        if (!empty($errors)) {
            require __DIR__ . '/../views/auth/register.php';
            return;
        }

        try {
            $userId = $this->user->register($username, $email, $password);
            session_regenerate_id(true);
            csrf_rotate();
            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $username;
            header('Location: /index.php?controller=task&action=index');
            exit;
        } catch (ValidationException $e) {
            $errors[] = $e->getMessage();
            require __DIR__ . '/../views/auth/register.php';
        } catch (PDOException $e) {
            error_log('Registration failed: ' . $e->getMessage());
            $errors[] = 'Registration failed. Please try again.';
            require __DIR__ . '/../views/auth/register.php';
        }
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            require __DIR__ . '/../views/auth/login.php';
            return;
        }

        csrf_require();

        $username = form_string($_POST['username'] ?? '');
        $password = form_string($_POST['password'] ?? '', false);

        $errors = login_errors($username, $password);

        if (!empty($errors)) {
            require __DIR__ . '/../views/auth/login.php';
            return;
        }

        $user = $this->user->login($username, $password);

        if ($user) {
            session_regenerate_id(true);
            csrf_rotate();
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
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            exit('Method Not Allowed');
        }

        csrf_require();

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'],
            ]);
        }

        session_destroy();

        header('Location: /index.php?controller=auth&action=login');
        exit;
    }
}
