<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$error = '';
$success = '';

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
        $_SESSION['error_message'] = "Item not found or you do not have permission to access it.";
        header("Location: ../dashboard.php");
        exit;
    }
} catch (\PDOException $e) {
    $_SESSION['error_message'] = "Error searching for item: " . htmlspecialchars($e->getMessage());
    header("Location: ../dashboard.php");
    exit;
}

$item_name = $item['item_name'];
$description = $item['description'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $item_name = isset($_POST['item_name']) ? trim($_POST['item_name']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';

    if (empty($item_name)) {
        $error = "Item name is required.";
    } elseif (strlen($item_name) < 2) {
        $error = "Item name must be at least 2 characters.";
    }

    if (empty($error)) {
        try {
            $stmt = $pdo->prepare("UPDATE items SET item_name = :item_name, description = :description WHERE id = :id AND user_id = :user_id");
            $stmt->execute([
                'item_name'   => $item_name,
                'description' => $description,
                'id'          => $id,
                'user_id'     => $_SESSION['user_id']
            ]);

            $_SESSION['success_message'] = "Item has been updated successfully.";
            header("Location: ../dashboard.php");
            exit;
        } catch (\PDOException $e) {
            $error = "Failed to update item: " . htmlspecialchars($e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Item - PHP CRUD System</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <main class="app-container">
        <div class="simple-card">
            <h1 class="card-title">Edit Item</h1>
            <p class="card-subtitle">Modify your item details</p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <form action="edit.php?id=<?php echo $id; ?>" method="POST" autocomplete="off">
                <!-- Item Name Field -->
                <div class="form-group">
                    <label for="item_name" class="form-label">Item Name</label>
                    <input 
                        type="text" 
                        name="item_name" 
                        id="item_name" 
                        class="form-input <?php echo !empty($error) && empty($item_name) ? 'is-invalid' : ''; ?>"
                        placeholder="Enter item name" 
                        value="<?php echo htmlspecialchars($item_name); ?>" 
                        required
                    >
                </div>

                <!-- Description Field -->
                <div class="form-group">
                    <label for="description" class="form-label">Description</label>
                    <textarea 
                        name="description" 
                        id="description" 
                        class="form-input"
                        placeholder="Provide a brief description (optional)"
                    ><?php echo htmlspecialchars($description); ?></textarea>
                </div>

                <div class="action-buttons-row">
                    <button type="submit" class="btn">
                        Update Item
                    </button>
                    <a href="../dashboard.php" class="btn btn-secondary">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
