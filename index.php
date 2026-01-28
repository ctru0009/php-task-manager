<?php

session_start();

require_once __DIR__ . '/config/database.php';

$controller = $_GET['controller'] ?? 'auth';
$action = $_GET['action'] ?? 'login';

switch ($controller) {
    case 'auth':
        require_once __DIR__ . '/controllers/AuthController.php';
        $authController = new AuthController();
        
        switch ($action) {
            case 'register':
                $authController->register();
                break;
            case 'login':
                $authController->login();
                break;
            case 'logout':
                $authController->logout();
                break;
            default:
                $authController->login();
        }
        break;

    case 'task':
        require_once __DIR__ . '/controllers/TaskController.php';
        $taskController = new TaskController();
        
        switch ($action) {
            case 'index':
                $taskController->index();
                break;
            case 'create':
                $taskController->create();
                break;
            case 'edit':
                $taskController->edit();
                break;
            case 'delete':
                $taskController->delete();
                break;
            case 'updateStatus':
                $taskController->updateStatus();
                break;
            default:
                $taskController->index();
        }
        break;

    default:
        header('Location: /index.php?controller=auth&action=login');
        exit;
}
