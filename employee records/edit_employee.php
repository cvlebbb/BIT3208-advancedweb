<?php
require_once 'config.php';
requireLogin();

$id = (int)$_GET['id'];
$result = $conn->query("SELECT * FROM employees WHERE id = $id");
if ($result->num_rows === 0) {
    header("Location: index.php");
    exit();
}
$employee = $result->fetch_assoc();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $position = trim($_POST['position']);
    $salary = (float)$_POST['salary'];
    $hire_date = $_POST['hire_date'];
    
    if (empty($name)) $errors[] = "Name required.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email required.";
    if ($salary <= 0) $errors[] = "Salary must be positive.";
    if (empty($hire_date)) $errors[] = "Hire date required.";
    
    // Check email uniqueness excluding current
    if (empty($errors)) {
        $check = $conn->prepare("SELECT id FROM employees WHERE email = ? AND id != ?");
        $check->bind_param("si", $email, $id);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $errors[] = "Email already used by another employee.";
        }
        $check->close();
    }
    
    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE employees SET name=?, email=?, position=?, salary=?, hire_date=? WHERE id=?");
        $stmt->bind_param("sssdsi", $name, $email, $position, $salary, $hire_date, $id);
        if ($stmt->execute()) {
            header("Location: index.php?msg=updated");
            exit();
        } else {
            $errors[] = "Update failed.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Employee</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="form-container">
    <h2>✏️ Edit Employee</h2>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $err) echo "<div>$err</div>"; ?>
        </div>
    <?php endif; ?>
    <form method="POST">
        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($employee['name']); ?>" required>
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($employee['email']); ?>" required>
        </div>
        <div class="form-group">
            <label>Position</label>
            <input type="text" name="position" value="<?php echo htmlspecialchars($employee['position']); ?>" required>
        </div>
        <div class="form-group">
            <label>Salary (KSH)</label>
            <input type="number" step="0.01" name="salary" value="<?php echo $employee['salary']; ?>" required>
        </div>
        <div class="form-group">
            <label>Hire Date</label>
            <input type="date" name="hire_date" value="<?php echo $employee['hire_date']; ?>" required>
        </div>
        <div style="display: flex; gap: 1rem; justify-content: flex-end;">
            <a href="index.php" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Update</button>
        </div>
    </form>
</div>
</body>
</html>