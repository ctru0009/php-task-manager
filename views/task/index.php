<?php require __DIR__ . '/../layout.php'; ?>

<div class="header">
    <h1>My Tasks</h1>
    <a href="/index.php?controller=task&action=create" class="btn btn-primary">Create New Task</a>
</div>

<div class="filters">
    <a href="/index.php?controller=task&action=index" class="<?php echo !isset($_GET['status']) ? 'active' : ''; ?>">All</a>
    <a href="/index.php?controller=task&action=index&status=pending" class="<?php echo isset($_GET['status']) && $_GET['status'] === 'pending' ? 'active' : ''; ?>">Pending</a>
    <a href="/index.php?controller=task&action=index&status=in_progress" class="<?php echo isset($_GET['status']) && $_GET['status'] === 'in_progress' ? 'active' : ''; ?>">In Progress</a>
    <a href="/index.php?controller=task&action=index&status=completed" class="<?php echo isset($_GET['status']) && $_GET['status'] === 'completed' ? 'active' : ''; ?>">Completed</a>
</div>

<?php if (empty($tasks)): ?>
    <p class="empty">No tasks found. <a href="/index.php?controller=task&action=create">Create your first task</a>.</p>
<?php else: ?>
    <div class="tasks">
        <?php $index = 0; foreach ($tasks as $task): $index++; ?>
            <div class="task task-<?php echo $task['status']; ?> priority-<?php echo $task['priority']; ?> stagger-<?php echo min($index, 10); ?>">
                <div class="task-header">
                    <h2><?php echo htmlspecialchars($task['title']); ?></h2>
                    <span class="badge badge-<?php echo $task['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?></span>
                    <span class="badge badge-<?php echo $task['priority']; ?>"><?php echo ucfirst($task['priority']); ?></span>
                </div>
                <?php if ($task['description']): ?>
                    <p><?php echo htmlspecialchars($task['description']); ?></p>
                <?php endif; ?>
                <div class="task-meta">
                    <small>Created: <?php echo date('M d, Y', strtotime($task['created_at'])); ?></small>
                    <?php if ($task['updated_at'] !== $task['created_at']): ?>
                        <small>Updated: <?php echo date('M d, Y', strtotime($task['updated_at'])); ?></small>
                    <?php endif; ?>
                </div>
                <div class="task-actions">
                    <a href="/index.php?controller=task&action=edit&id=<?php echo $task['id']; ?>" class="btn btn-secondary">Edit</a>
                    <a href="/index.php?controller=task&action=delete&id=<?php echo $task['id']; ?>" class="btn btn-danger">Delete</a>
                    <?php if ($task['status'] !== 'pending'): ?>
                        <a href="/index.php?controller=task&action=updateStatus&id=<?php echo $task['id']; ?>&status=pending" class="btn btn-outline">Pending</a>
                    <?php endif; ?>
                    <?php if ($task['status'] !== 'in_progress'): ?>
                        <a href="/index.php?controller=task&action=updateStatus&id=<?php echo $task['id']; ?>&status=in_progress" class="btn btn-outline">In Progress</a>
                    <?php endif; ?>
                    <?php if ($task['status'] !== 'completed'): ?>
                        <a href="/index.php?controller=task&action=updateStatus&id=<?php echo $task['id']; ?>&status=completed" class="btn btn-outline">Completed</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
