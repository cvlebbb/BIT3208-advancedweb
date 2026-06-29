<?php
// login.php - Secure Student Login Page
session_start();

// Redirect to dashboard if already logged in
if (isset($_SESSION['student_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (!preg_match('/@student\.mku\.ac\.ke$/i', $email)) {
        // Strict domain restriction validation
        $error = "Access denied: Only @student.mku.ac.ke email addresses are allowed.";
    } else {
        require_once 'db.php';
        try {
            // Prepared statement to prevent SQL injection
            $stmt = $pdo->prepare("SELECT * FROM students WHERE email = ?");
            $stmt->execute([$email]);
            $student = $stmt->fetch();

            if ($student && password_verify($password, $student['password_hash'])) {
                // Successful login - regenerate session ID to prevent session fixation
                session_regenerate_id(true);
                $_SESSION['student_id'] = $student['id'];
                header("Location: dashboard.php");
                exit;
            } else {
                $error = "Invalid email or password.";
            }
        } catch (\PDOException $e) {
            $error = "An error occurred during authentication. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal - Secure Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="split-container">
        <div class="split-hero">
            <div class="hero-content">
                <h1>Student Portal</h1>
                <p>Welcome to Mount Kenya University's student portal. Access your student dashboard, view your major details, manage your academic year configuration, and communicate securely.</p>
            </div>
        </div>
        <div class="split-form">
            <div class="form-wrapper">
                <h2>Secure Login</h2>
                <p class="subtitle">Log in using your MKU student email</p>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="margin-right: 8px; flex-shrink: 0; display: inline-block; vertical-align: middle;">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="8" x2="12" y2="12" />
                            <line x1="12" y1="16" x2="12.01" y2="16" />
                        </svg>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>
                
                <form action="login.php" method="POST" autocomplete="off">
                    <div class="form-group">
                        <label for="email">Student Email</label>
                        <input type="email" name="email" id="email" class="form-control" placeholder="username@student.mku.ac.ke" required value="<?php echo htmlspecialchars($email); ?>">
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
                    </div>
                    <button type="submit" class="btn">
                        <span>Secure Login</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                        </svg>
                    </button>
                </form>
                <div class="card-footer">
                    Don't have an account? <a href="signup.php">Sign Up</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
