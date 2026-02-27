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

    if (empty($username) || empty($email) || empty($password)) {
        $error = 'All fields are required!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address!';
    } else {
        $pdo = connect();

        // Check credentials
        $stmt = $pdo->prepare("SELECT * FROM players WHERE username = ? AND email = ?");
        $stmt->execute([$username, $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['player_id'] = $user['player_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $success = 'Login successful! Redirecting...';
            header('refresh:2;url=index.php');
        } else {
            $error = 'Invalid credentials!';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="login.css?v=1">
    <title>Green Thumb - Login</title>
</head>
<body>
    <div class="login-container">
        <div class="login-panel">
            <h1 class="login-title">GREEN THUMB</h1>
            <div class="login-subtitle">Enter Your Credentials</div>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="login.php" class="login-form">
                <div class="input-group">
                    <label for="username">Username</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        required 
                        autocomplete="username"
                        placeholder="Enter username"
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
                        placeholder="Enter email"
                    >
                </div>
                
                <div class="input-group">
                    <label for="password">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required 
                        autocomplete="current-password"
                        placeholder="Enter password"
                    >
                </div>
                
                <button type="submit" class="login-button">
                    <span>LOGIN</span>
                </button>
            </form>
            
            <div class="login-footer">
                <a href="register.php" class="back-link">Create Account</a>
            </div>
        </div>
    </div>
</body>
</html>

