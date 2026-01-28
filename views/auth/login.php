<?php require __DIR__ . '/../layout.php'; ?>

<div class="auth-container">
    <h1>Login</h1>

    <?php if (!empty($errors)): ?>
        <div class="errors">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="/index.php?controller=auth&action=login">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username ?? ''); ?>" required placeholder="Enter your username">
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required placeholder="Enter your password">
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Login</button>
        </div>
    </form>

    <p class="auth-footer">Don't have an account? <a href="/index.php?controller=auth&action=register">Register</a></p>
</div>
