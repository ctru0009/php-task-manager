<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Manager</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="/public/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="/index.php?controller=task&action=index" class="nav-brand">Task Manager</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="nav-menu">
                    <span class="nav-user">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <a href="/index.php?controller=task&action=index" class="nav-link">Tasks</a>
                    <a href="/index.php?controller=auth&action=logout" class="nav-link nav-logout">Logout</a>
                </div>
            <?php else: ?>
                <div class="nav-menu">
                    <a href="/index.php?controller=auth&action=login" class="nav-link">Login</a>
                    <a href="/index.php?controller=auth&action=register" class="nav-link">Register</a>
                </div>
            <?php endif; ?>
        </div>
    </nav>

    <main class="container">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="message <?php echo $_SESSION['message_type'] ?? 'success'; ?>">
                <?php echo htmlspecialchars($_SESSION['message']); ?>
            </div>
            <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
        <?php endif; ?>
