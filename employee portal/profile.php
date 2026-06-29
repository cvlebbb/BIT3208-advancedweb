<?php
require_once 'config.php';
requireLogin();

$employee_id = $_SESSION['employee_id'];
$error = '';
$success = '';

// Fetch current data
$stmt = $conn->prepare("SELECT fullname, email, department, phone FROM employees WHERE id = ?");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname']);
    $department = trim($_POST['department']);
    $phone = trim($_POST['phone']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($fullname)) {
        $error = "Full name is required.";
    } else {
        // Update basic info
        $update = $conn->prepare("UPDATE employees SET fullname = ?, department = ?, phone = ? WHERE id = ?");
        $update->bind_param("sssi", $fullname, $department, $phone, $employee_id);
        if ($update->execute()) {
            $_SESSION['employee_name'] = $fullname;
            $_SESSION['employee_department'] = $department;
            $success = "Profile updated successfully.";
            // Refresh profile data
            $profile['fullname'] = $fullname;
            $profile['department'] = $department;
            $profile['phone'] = $phone;
        } else {
            $error = "Update failed.";
        }
        $update->close();
        
        // Password change if provided
        if (!empty($new_password)) {
            if (strlen($new_password) < 6) {
                $error = "Password must be at least 6 characters.";
            } elseif ($new_password !== $confirm_password) {
                $error = "New passwords do not match.";
            } else {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $pwdUpdate = $conn->prepare("UPDATE employees SET password = ? WHERE id = ?");
                $pwdUpdate->bind_param("si", $hashed, $employee_id);
                if ($pwdUpdate->execute()) {
                    $success = "Profile and password updated successfully.";
                } else {
                    $error = "Password update failed.";
                }
                $pwdUpdate->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="form-container" style="max-width: 600px;">
    <h2 style="margin-bottom: 1.5rem;">👤 My Profile</h2>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php elseif ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="form-group">
            <label>Full Name *</label>
            <input type="text" name="fullname" value="<?php echo htmlspecialchars($profile['fullname']); ?>" required>
        </div>
        <div class="form-group">
            <label>Email (cannot change)</label>
            <input type="email" value="<?php echo htmlspecialchars($profile['email']); ?>" disabled style="background:#edf2f7;">
        </div>
        <div class="form-group">
            <label>Department</label>
            <input type="text" name="department" value="<?php echo htmlspecialchars($profile['department']); ?>">
        </div>
        <div class="form-group">
            <label>Phone</label>
            <input type="text" name="phone" value="<?php echo htmlspecialchars($profile['phone']); ?>">
        </div>
        <hr style="margin: 1.5rem 0;">
        <h3 style="margin-bottom: 1rem;">Change Password (optional)</h3>
        <div class="form-group">
            <label>New Password</label>
            <input type="password" name="new_password" placeholder="Leave blank to keep current">
        </div>
        <div class="form-group">
            <label>Confirm New Password</label>
            <input type="password" name="confirm_password">
        </div>
        <div style="display: flex; gap: 1rem; justify-content: flex-end;">
            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
    </form>
</div>
</body>
</html>