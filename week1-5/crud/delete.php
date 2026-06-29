<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    $_SESSION['error_message'] = "Invalid item identifier.";
    header("Location: ../dashboard.php");
    exit;
}


try {
    $stmt = $pdo->prepare("SELECT * FROM items WHERE id = :id AND user_id = :user_id");
    $stmt->execute([
        'id' => $id,
        'user_id' => $_SESSION['user_id']
    ]);
    $item = $stmt->fetch();

    if (!$item) {
        $_SESSION['error_message'] = "Item not found or you do not have permission to delete it.";
        header("Location: ../dashboard.php");
        exit;
    }
} catch (\PDOException $e) {
    $_SESSION['error_message'] = "Error finding item: " . htmlspecialchars($e->getMessage());
    header("Location: ../dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("DELETE FROM items WHERE id = :id AND user_id = :user_id");
        $stmt->execute([
            'id' => $id,
            'user_id' => $_SESSION['user_id']
        ]);

        $_SESSION['success_message'] = "Item '" . htmlspecialchars($item['item_name']) . "' has been deleted successfully.";
        header("Location: ../dashboard.php");
        exit;
    } catch (\PDOException $e) {
        $_SESSION['error_message'] = "Failed to delete item: " . htmlspecialchars($e->getMessage());
        header("Location: ../dashboard.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Item - PHP CRUD System</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <main class="app-container">
        <div class="simple-card">
            <h1 class="card-title" style="color: #dc2626;">Confirm Deletion</h1>
            <p class="card-subtitle">Are you sure you want to delete this item?</p>

            <div style="background-color: #f9fafb; border: 1px solid var(--border-color); border-radius: var(--radius); padding: 16px; margin-bottom: 24px;">
                <p style="font-weight: 600; font-size: 0.95rem; margin-bottom: 6px; color: var(--text-primary);">
                    <?php echo htmlspecialchars($item['item_name']); ?>
                </p>
                <p style="color: var(--text-secondary); font-size: 0.85rem; word-break: break-all;">
                    <?php echo !empty($item['description']) ? htmlspecialchars($item['description']) : '<em>No description provided.</em>'; ?>
                </p>
            </div>

            <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 24px; text-align: center;">
                Warning: This action is permanent and cannot be undone.
            </p>

            <form action="delete.php?id=<?php echo $id; ?>" method="POST">
                <div class="action-buttons-row">
                    <button type="submit" class="btn btn-danger">
                        Yes, Delete Item
                    </button>
                    <a href="../dashboard.php" class="btn btn-secondary">
                        No, Cancel
                    </a>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
