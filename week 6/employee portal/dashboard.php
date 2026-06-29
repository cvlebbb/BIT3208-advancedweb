<?php
require_once 'config.php';
requireLogin();

$employee_name = $_SESSION['employee_name'];
$employee_email = $_SESSION['employee_email'];
$department = $_SESSION['employee_department'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <div class="card">
        <div class="card-header">
            <h1>👋 Employee Portal Dashboard</h1>
            <div>
                <a href="profile.php" class="btn btn-primary">My Profile</a>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
        <div class="welcome-message">
            <h3>Welcome back, <?php echo htmlspecialchars($employee_name); ?>!</h3>
            <p>You are logged into the secure employee portal. Session is active.</p>
        </div>
        <div class="dashboard-stats">
            <div class="stat-card">
                <h3>📧 Email</h3>
                <p><?php echo htmlspecialchars($employee_email); ?></p>
            </div>
            <div class="stat-card">
                <h3>🏢 Department</h3>
                <p><?php echo htmlspecialchars($department ?: 'Not specified'); ?></p>
            </div>
            <div class="stat-card">
                <h3>🔒 Session ID</h3>
                <p><?php echo session_id(); ?></p>
            </div>
        </div>
        <div style="padding: 1.5rem; text-align: center;">
            <p>This is a protected page. Only authenticated employees can view this content.</p>
            <p><small>Your session will expire when you close the browser or click logout.</small></p>
        </div>
    </div>
    <footer>&copy; Employee Portal - Secure Authentication System</footer>
</div>
</body>
</html>