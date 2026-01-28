<?php

require_once __DIR__ . '/../config/database.php';

class Task {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function create($userId, $title, $description = '', $priority = 'medium') {
        $stmt = $this->db->prepare('INSERT INTO tasks (user_id, title, description, priority) VALUES (?, ?, ?, ?)');
        $stmt->execute([$userId, $title, $description, $priority]);
        return $this->db->lastInsertId();
    }

    public function getByUserId($userId, $status = null) {
        if ($status) {
            $stmt = $this->db->prepare('SELECT * FROM tasks WHERE user_id = ? AND status = ? ORDER BY created_at DESC');
            $stmt->execute([$userId, $status]);
        } else {
            $stmt = $this->db->prepare('SELECT * FROM tasks WHERE user_id = ? ORDER BY created_at DESC');
            $stmt->execute([$userId]);
        }
        return $stmt->fetchAll();
    }

    public function getById($id, $userId) {
        $stmt = $this->db->prepare('SELECT * FROM tasks WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);
        return $stmt->fetch();
    }

    public function update($id, $userId, $title, $description = '', $priority = 'medium') {
        $stmt = $this->db->prepare('UPDATE tasks SET title = ?, description = ?, priority = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?');
        $stmt->execute([$title, $description, $priority, $id, $userId]);
        return $stmt->rowCount() > 0;
    }

    public function updateStatus($id, $userId, $status) {
        $stmt = $this->db->prepare('UPDATE tasks SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?');
        $stmt->execute([$status, $id, $userId]);
        return $stmt->rowCount() > 0;
    }

    public function delete($id, $userId) {
        $stmt = $this->db->prepare('DELETE FROM tasks WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);
        return $stmt->rowCount() > 0;
    }
}
