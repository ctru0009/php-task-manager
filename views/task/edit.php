<?php require __DIR__ . '/../layout.php'; ?>

<div class="task-form-container">
    <div class="header">
        <h1>Edit Task</h1>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="errors">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="/index.php?controller=task&action=edit&id=<?php echo (int) $task['id']; ?>">
        <?php echo csrf_field(); ?>
        <div class="form-group">
            <label for="title">Title</label>
            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($title ?? $task['title']); ?>" required placeholder="Enter task title">
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="4" placeholder="Enter task description (optional)"><?php echo htmlspecialchars($description ?? $task['description']); ?></textarea>
        </div>

        <div class="form-group">
            <label for="priority">Priority</label>
            <?php $selectedPriority = $priority ?? $task['priority']; ?>
            <select id="priority" name="priority">
                <option value="low" <?php echo $selectedPriority === 'low' ? 'selected' : ''; ?>>Low</option>
                <option value="medium" <?php echo $selectedPriority === 'medium' ? 'selected' : ''; ?>>Medium</option>
                <option value="high" <?php echo $selectedPriority === 'high' ? 'selected' : ''; ?>>High</option>
            </select>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update Task</button>
            <a href="/index.php?controller=task&action=index" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
