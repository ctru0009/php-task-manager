<?php

require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../models/Task.php';

class TaskController {
    private $task;

    public function __construct() {
        $this->task = new Task();
        
        if (!isset($_SESSION['user_id'])) {
            header('Location: /index.php?controller=auth&action=login');
            exit;
        }
    }

    public function index() {
        $userId = $_SESSION['user_id'];
        $statusFilter = status_filter($_GET['status'] ?? null);
        $tasks = $this->task->getByUserId($userId, $statusFilter);
        require __DIR__ . '/../views/task/index.php';
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            require __DIR__ . '/../views/task/create.php';
            return;
        }

        $userId = $_SESSION['user_id'];
        $title = form_string($_POST['title'] ?? '');
        $description = form_string($_POST['description'] ?? '');
        $priority = normalize_priority($_POST['priority'] ?? 'medium');

        $errors = task_errors($title, $description);

        if (!empty($errors)) {
            require __DIR__ . '/../views/task/create.php';
            return;
        }

        $this->task->create($userId, $title, $description, $priority);
        header('Location: /index.php?controller=task&action=index');
        exit;
    }

    public function edit() {
        $userId = $_SESSION['user_id'];
        $id = task_id($_GET['id'] ?? null);

        if ($id === null) {
            header('Location: /index.php?controller=task&action=index');
            exit;
        }

        $task = $this->task->getById($id, $userId);

        if (!$task) {
            header('Location: /index.php?controller=task&action=index');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = form_string($_POST['title'] ?? '');
            $description = form_string($_POST['description'] ?? '');
            $priority = normalize_priority($_POST['priority'] ?? 'medium');

            $errors = task_errors($title, $description);

            if (!empty($errors)) {
                require __DIR__ . '/../views/task/edit.php';
                return;
            }

            $this->task->update($id, $userId, $title, $description, $priority);
            header('Location: /index.php?controller=task&action=index');
            exit;
        }

        require __DIR__ . '/../views/task/edit.php';
    }

    public function delete() {
        $userId = $_SESSION['user_id'];
        $id = task_id($_GET['id'] ?? null);

        if ($id === null) {
            header('Location: /index.php?controller=task&action=index');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->task->delete($id, $userId);
            header('Location: /index.php?controller=task&action=index');
            exit;
        }

        $task = $this->task->getById($id, $userId);
        if (!$task) {
            header('Location: /index.php?controller=task&action=index');
            exit;
        }

        require __DIR__ . '/../views/task/delete.php';
    }

    public function updateStatus() {
        $userId = $_SESSION['user_id'];
        $id = task_id($_GET['id'] ?? null);
        $status = $_GET['status'] ?? null;

        if ($id !== null && valid_status($status)) {
            $this->task->updateStatus($id, $userId, $status);
        }

        header('Location: /index.php?controller=task&action=index');
        exit;
    }
}
