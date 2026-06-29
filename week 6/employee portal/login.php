<?php
require_once 'config.php';

if (isLoggedIn()) {
    redirect("dashboard.php");
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = "Please enter email and password.";
    } else {
        $stmt = $conn->prepare("SELECT id, fullname, email, password, department, phone FROM employees WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($employee = $result->fetch_assoc()) {
            if (password_verify($password, $employee['password'])) {
                $_SESSION['employee_id'] = $employee['id'];
                $_SESSION['employee_name'] = $employee['fullname'];
                $_SESSION['employee_email'] = $employee['email'];
                $_SESSION['employee_department'] = $employee['department'];
                redirect("dashboard.php");
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "Invalid email or password.";
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
    <title>Employee Portal - Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="form-container">
    <h2 style="margin-bottom: 1.5rem; color:#2d3748;">🔐 Employee Login</h2>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required autofocus>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;">Login</button>
        <p style="text-align: center; margin-top: 1rem;">Don't have an account? <a href="register.php" style="color:#4c51bf;">Register here</a></p>
    </form>
</div>
</body>
</html>