<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);    
    
session_start();
require_once 'db_connect.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address!';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long!';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match!';
    } else {
        require_once 'db_sync.php';
        $pdo = connect();

        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT * FROM players WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = 'Username or email already exists!';
        } else {
            // Hash password and insert into DB (both databases)
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            syncExecute("INSERT INTO players (username, email, password) VALUES (?, ?, ?)", [$username, $email, $hashed_password]);

            $success = 'Registration successful! Redirecting to login...';
            header('refresh:2;url=login.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="register.css?v=1">
    <title>Green Thumb - Register</title>
</head>
<body>
    <div class="login-container">
        <div class="login-panel">
            <h1 class="login-title">GREEN THUMB</h1>
            <div class="login-subtitle">Create Your Account</div>

            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="POST" action="register.php" class="login-form">
                <div class="input-group">
                    <label for="username">Username</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        required 
                        autocomplete="username"
                        placeholder="Choose a username"
                        value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                    >
                </div>

                <div class="input-group">
                    <label for="email">Email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        required 
                        autocomplete="email"
                        placeholder="Enter your email"
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                    >
                </div>

                <div class="input-group">
                    <label for="password">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required 
                        autocomplete="new-password"
                        placeholder="Create a password (min. 6 characters)"
                        minlength="6"
                    >
                </div>

                <div class="input-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        required 
                        autocomplete="new-password"
                        placeholder="Confirm your password"
                        minlength="6"
                    >
                </div>

                <button type="submit" class="login-button">
                    <span>REGISTER</span>
                </button>
            </form>

            <div class="login-footer">
                <a href="login.php" class="back-link">← Back to Login</a>
            </div>
        </div>
    </div>
</body>
</html>
