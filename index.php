<?php
session_start();

$isLoggedIn = isset($_SESSION['username']);
$username = $isLoggedIn ? htmlspecialchars($_SESSION['username']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Week 5 - PHP CRUD System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <main class="app-container">
        <div class="simple-card">
            <!-- Landing Title -->
            <h1 class="card-title">Database Item Manager</h1>
            <p class="card-subtitle">Week 5 Secure CRUD Application</p>

            <?php if ($isLoggedIn): ?>
                <div class="alert alert-success">
                    <span>Logged in as <strong><?php echo $username; ?></strong></span>
                </div>
                <p style="text-align: center; margin-bottom: 20px;">
                    Welcome back! You have a secure database-authenticated session.
                </p>
                <div class="hero-buttons">
                    <a href="dashboard.php" class="btn">Go to Dashboard</a>
                    <a href="logout.php" class="btn btn-secondary">Logout</a>
                </div>
            <?php else: ?>
                <p style="text-align: center; margin-bottom: 20px; color: var(--text-secondary);">
                    Access your personal dashboard to perform secure CRUD operations on items. Sign in or register to get started.
                </p>
                <div class="hero-buttons">
                    <a href="login.php" class="btn">Sign In</a>
                    <a href="register.php" class="btn btn-secondary">Register</a>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
