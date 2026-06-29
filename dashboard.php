<?php

session_start();
require_once 'config/database.php';

if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];
$email = isset($_SESSION['email']) ? $_SESSION['email'] : 'N/A';
$login_time = isset($_SESSION['login_time']) ? date('Y-m-d H:i:s', $_SESSION['login_time']) : 'N/A';
$session_id = session_id();


$success_message = '';
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

$error_message = '';
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

$items = [];
try {

    $stmt = $pdo->prepare("SELECT * FROM items WHERE user_id = :user_id ORDER BY created_at DESC");
    $stmt->execute(['user_id' => $_SESSION['user_id']]);
    $items = $stmt->fetchAll();
} catch (\PDOException $e) {
    $error_message = "Failed to load database items: " . htmlspecialchars($e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - PHP CRUD System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
   
    <main class="app-container wide">
        <div class="simple-card">

            <header class="dashboard-header">
                <div>
                    <h2>Hello, <?php echo htmlspecialchars($username); ?>!</h2>
                    <p style="font-size: 0.85rem; color: var(--text-secondary);">Access Level: Authenticated Student (ID: <?php echo $_SESSION['user_id']; ?>)</p>
                </div>
                <a href="logout.php" class="logout-link">Logout</a>
            </header>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <span><?php echo htmlspecialchars($success_message); ?></span>
                </div>
            <?php endif; ?>
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-error">
                    <span><?php echo htmlspecialchars($error_message); ?></span>
                </div>
            <?php endif; ?>

            <section style="margin-bottom: 30px;">
                <h3 style="margin-bottom: 12px; font-size: 1.1rem; color: var(--text-primary);">Active Session Diagnostics</h3>

                <div class="diagnostics-grid">
                    <div class="diagnostic-item">
                        <div class="diagnostic-label">Registered Email</div>
                        <div class="diagnostic-value"><?php echo htmlspecialchars($email); ?></div>
                    </div>
                    <div class="diagnostic-item">
                        <div class="diagnostic-label">Session ID Cookie</div>
                        <div class="diagnostic-value"><?php echo htmlspecialchars($session_id); ?></div>
                    </div>
                    <div class="diagnostic-item">
                        <div class="diagnostic-label">Login Timestamp</div>
                        <div class="diagnostic-value"><?php echo htmlspecialchars($login_time); ?></div>
                    </div>
                </div>
            </section>

            <section>
                <div class="dashboard-actions">
                    <h3 style="font-size: 1.1rem; color: var(--text-primary);">My Custom Items</h3>
                    <a href="crud/create.php" class="add-item-btn">+ Add Item</a>
                </div>

                <div class="table-container">
                    <?php if (empty($items)): ?>
                        <div class="empty-message">
                            No items found in your database. Click "+ Add Item" to create one.
                        </div>
                    <?php else: ?>
                        <table class="crud-table">
                            <thead>
                                <tr>
                                    <th style="width: 80px;">Item ID</th>
                                    <th>Item Name</th>
                                    <th>Description</th>
                                    <th style="width: 160px;">Created At</th>
                                    <th style="width: 140px; text-align: center;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $row): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                                        <td style="font-weight: 500;"><?php echo htmlspecialchars($row['item_name']); ?></td>
                                        <td style="color: var(--text-secondary); word-break: break-all;">
                                            <?php echo !empty($row['description']) ? htmlspecialchars($row['description']) : '<em>No description</em>'; ?>
                                        </td>
                                        <td style="color: var(--text-muted); font-size: 0.8rem;">
                                            <?php echo htmlspecialchars(date('M d, Y g:i A', strtotime($row['created_at']))); ?>
                                        </td>
                                        <td style="text-align: center; white-space: nowrap;">
                                            <a href="crud/edit.php?id=<?php echo $row['id']; ?>" class="action-link">Edit</a>
                                            <a href="crud/delete.php?id=<?php echo $row['id']; ?>" class="action-link delete-link">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>
</body>
</html>
