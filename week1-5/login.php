<?php


session_start();
require_once 'config/database.php';

if (isset($_SESSION['username'])) {
    header("Location: dashboard.php");
    exit;
}

$success_message = '';
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($username) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
            $stmt->execute(['username' => $username]);
            $user_data = $stmt->fetch();

            if ($user_data && password_verify($password, $user_data['password'])) {
                $_SESSION['user_id'] = $user_data['id'];
                $_SESSION['username'] = $user_data['username'];
                $_SESSION['email'] = $user_data['email'];
                $_SESSION['login_time'] = time();

                header("Location: dashboard.php");
                exit;
            } else {
                $error = "Invalid credentials. Please try again.";
            }
        } catch (\PDOException $e) {
            $error = "Database authentication error: " . htmlspecialchars($e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PHP CRUD System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <main class="app-container">
        <div class="simple-card">
            <h1 class="card-title">Welcome Back</h1>
            <p class="card-subtitle">Sign in to manage your items</p>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <span><?php echo htmlspecialchars($success_message); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST" autocomplete="off">
                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <input 
                        type="text" 
                        name="username" 
                        id="username" 
                        class="form-input <?php echo !empty($error) ? 'is-invalid' : ''; ?>"
                        placeholder="Enter your username" 
                        value="<?php echo htmlspecialchars($username); ?>" 
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input 
                        type="password" 
                        name="password" 
                        id="password" 
                        class="form-input <?php echo !empty($error) ? 'is-invalid' : ''; ?>"
                        placeholder="Enter your password" 
                        required
                    >
                </div>

                <button type="submit" class="btn">
                    Sign In
                </button>
            </form>

            <p class="footer-text">
                Don't have an account? <a href="register.php">Register</a>
            </p>
            <p class="footer-text" style="margin-top: 12px; font-size: 0.8rem;">
                <a href="index.php">&larr; Back to Landing Page</a>
            </p>
        </div>
    </main>
</body>
</html>
