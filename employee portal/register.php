<?php
require_once 'config.php';

if (isLoggedIn()) {
    redirect("dashboard.php");
}

$error = '';
$success = '';
$fullname = $email = $department = $phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    $department = trim($_POST['department']);
    $phone = trim($_POST['phone']);
    
    // Validation
    if (empty($fullname) || empty($email) || empty($password)) {
        $error = "Full name, email, and password are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Valid email is required.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        // Check if email already exists
        $check = $conn->prepare("SELECT id FROM employees WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $error = "Email already registered. Please login.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO employees (fullname, email, password, department, phone) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $fullname, $email, $hashed, $department, $phone);
            if ($stmt->execute()) {
                $success = "Registration successful! You can now login.";
                // Clear form
                $fullname = $email = $department = $phone = '';
            } else {
                $error = "Database error: " . $conn->error;
            }
            $stmt->close();
        }
        $check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Portal - Register</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="form-container">
    <h2 style="margin-bottom: 1.5rem; color:#2d3748;">📝 Employee Registration</h2>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php elseif ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?> <a href="login.php">Login here</a></div>
    <?php endif; ?>
    <form method="POST">
        <div class="form-group">
            <label>Full Name *</label>
            <input type="text" name="fullname" value="<?php echo htmlspecialchars($fullname); ?>" required autofocus>
        </div>
        <div class="form-group">
            <label>Email *</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
        </div>
        <div class="form-group">
            <label>Password * (min 6 characters)</label>
            <input type="password" name="password" required>
        </div>
        <div class="form-group">
            <label>Confirm Password *</label>
            <input type="password" name="confirm_password" required>
        </div>
        <div class="form-group">
            <label>Department</label>
            <input type="text" name="department" value="<?php echo htmlspecialchars($department); ?>" placeholder="e.g., IT, HR, Sales">
        </div>
        <div class="form-group">
            <label>Phone Number</label>
            <input type="text" name="phone" value="<?php echo htmlspecialchars($phone); ?>" placeholder="Optional">
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;">Register</button>
        <p style="text-align: center; margin-top: 1rem;">Already have an account? <a href="login.php" style="color:#4c51bf;">Login</a></p>
    </form>
</div>
</body>
</html>