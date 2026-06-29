<?php

session_start();
require_once 'config/database.php';

if (isset($_SESSION['username'])) {
    header("Location: dashboard.php");
    exit;
}

$errors = [];
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : ''; 


    if (empty($username)) {
        $errors['username'] = "Username is required.";
    } elseif (strlen($username) < 3) {
        $errors['username'] = "Username must be at least 3 characters.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors['username'] = "Username can only contain letters, numbers, and underscores.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        if ($stmt->fetch()) {
            $errors['username'] = "Username is already taken.";
        }
    }
    

    if (empty($email)) {
        $errors['email'] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()) {
            $errors['email'] = "Email is already registered.";
        }
    }
    

    if (empty($password)) {
        $errors['password'] = "Password is required.";
    } elseif (strlen($password) < 6) {
        $errors['password'] = "Password must be at least 6 characters.";
    }
   
  
    if (empty($errors)) {
      
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (:username, :email, :password)");
            $stmt->execute([
                'username' => $username,
                'email' => $email,
                'password' => $hashed_password
            ]);
            $_SESSION['success_message'] = "Registration successful! You can now log in.";

            header("Location: login.php");
            exit;
        } catch (\PDOException $e) {
            $errors['db'] = "Database registration failed: " . htmlspecialchars($e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - PHP CRUD System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <main class="app-container">
        <div class="simple-card">
            <h1 class="card-title">Create Account</h1>
            <p class="card-subtitle">Register to manage your custom items</p>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <span>Please correct the errors marked below.</span>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST" autocomplete="off">
                <!-- Username Field -->
                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <input 
                        type="text" 
                        name="username" 
                        id="username" 
                        class="form-input <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>"
                        placeholder="Enter username" 
                        value="<?php echo htmlspecialchars($username); ?>" 
                        required
                    >
                    <?php if (isset($errors['username'])): ?>
                        <span style="color: var(--error-text); font-size: 0.8rem; margin-top: 4px;">
                            <?php echo $errors['username']; ?>
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Email Field -->
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input 
                        type="email" 
                        name="email" 
                        id="email" 
                        class="form-input <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                        placeholder="Enter email address" 
                        value="<?php echo htmlspecialchars($email); ?>" 
                        required
                    >
                    <?php if (isset($errors['email'])): ?>
                        <span style="color: var(--error-text); font-size: 0.8rem; margin-top: 4px;">
                            <?php echo $errors['email']; ?>
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Password Field -->
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input 
                        type="password" 
                        name="password" 
                        id="password" 
                        class="form-input <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>"
                        placeholder="Min. 6 characters" 
                        required
                    >
                    <?php if (isset($errors['password'])): ?>
                        <span style="color: var(--error-text); font-size: 0.8rem; margin-top: 4px;">
                            <?php echo $errors['password']; ?>
                        </span>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn">
                    Create Account
                </button>
            </form>

            <p class="footer-text">
                Already have an account? <a href="login.php">Sign In</a>
            </p>
            <p class="footer-text" style="margin-top: 12px; font-size: 0.8rem;">
                <a href="index.php">&larr; Back to Landing Page</a>
            </p>
        </div>
    </main>
</body>
</html>
