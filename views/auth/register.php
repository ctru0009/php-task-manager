<?php require __DIR__ . '/../layout.php'; ?>

<div class="auth-container">
    <h1>Register</h1>

    <?php if (!empty($errors)): ?>
        <div class="errors">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="/index.php?controller=auth&action=register">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username ?? ''); ?>" required placeholder="Choose a username">
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required placeholder="your@email.com">
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required placeholder="Choose a strong password">
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm your password">
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Register</button>
        </div>
    </form>

    <p class="auth-footer">Already have an account? <a href="/index.php?controller=auth&action=login">Login</a></p>
</div>
