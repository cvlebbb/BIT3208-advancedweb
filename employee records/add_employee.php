<?php
require_once 'config.php';
requireLogin();

$errors = [];
$name = $email = $position = $salary = $hire_date = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $position = trim($_POST['position']);
    $salary = (float)$_POST['salary'];
    $hire_date = $_POST['hire_date'];
    
    // Validation
    if (empty($name)) $errors[] = "Name is required.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
    if (empty($position)) $errors[] = "Position is required.";
    if ($salary <= 0) $errors[] = "Salary must be greater than zero.";
    if (empty($hire_date) || !strtotime($hire_date)) $errors[] = "Valid hire date is required.";
    
    // Check duplicate email
    if (empty($errors)) {
        $check = $conn->prepare("SELECT id FROM employees WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $errors[] = "Email already exists.";
        }
        $check->close();
    }
    
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO employees (name, email, position, salary, hire_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssds", $name, $email, $position, $salary, $hire_date);
        if ($stmt->execute()) {
            header("Location: index.php?msg=added");
            exit();
        } else {
            $errors[] = "Database error: " . $conn->error;
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
    <title>Add Employee</title>
    <link rel="stylesheet" href="style.css">
    <script>
        function validateForm() {
            let salary = document.getElementById('salary').value;
            if (salary <= 0) {
                alert("Salary must be positive.");
                return false;
            }
            let email = document.getElementById('email').value;
            if (!email.includes('@')) {
                alert("Enter a valid email address.");
                return false;
            }
            return true;
        }
    </script>
</head>
<body>
<div class="form-container">
    <h2>➕ Add New Employee</h2>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $err): ?>
                <div><?php echo htmlspecialchars($err); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <form method="POST" onsubmit="return validateForm()">
        <div class="form-group">
            <label>Full Name *</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
        </div>
        <div class="form-group">
            <label>Email *</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
        </div>
        <div class="form-group">
            <label>Position *</label>
            <input type="text" name="position" value="<?php echo htmlspecialchars($position); ?>" required>
        </div>
        <div class="form-group">
            <label>Salary (KSH) *</label>
            <input type="number" step="0.01" id="salary" name="salary" value="<?php echo htmlspecialchars($salary); ?>" required>
        </div>
        <div class="form-group">
            <label>Hire Date *</label>
            <input type="date" name="hire_date" value="<?php echo htmlspecialchars($hire_date); ?>" required>
        </div>
        <div style="display: flex; gap: 1rem; justify-content: flex-end;">
            <a href="index.php" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Employee</button>
        </div>
    </form>
</div>
</body>
</html>