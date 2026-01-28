<?php require __DIR__ . '/../layout.php'; ?>

<div class="confirm">
    <h1>Delete Task</h1>

    <p class="confirm-message">Are you sure you want to delete the following task? This action cannot be undone.</p>

    <div class="task task-<?php echo $task['status']; ?> priority-<?php echo $task['priority']; ?>">
        <div class="task-header">
            <h2><?php echo htmlspecialchars($task['title']); ?></h2>
            <span class="badge badge-<?php echo $task['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?></span>
        </div>
        <?php if ($task['description']): ?>
            <p><?php echo htmlspecialchars($task['description']); ?></p>
        <?php endif; ?>
    </div>

    <form method="POST" action="/index.php?controller=task&action=delete&id=<?php echo $task['id']; ?>">
        <div class="form-actions">
            <button type="submit" class="btn btn-danger">Yes, Delete Task</button>
            <a href="/index.php?controller=task&action=index" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
