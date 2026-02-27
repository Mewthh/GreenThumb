<?php
/**
 * Database Failover Control Panel
 * Use this to manually switch between main and backup databases
 */

session_start();

// Simple password protection (change this!)
define('ADMIN_PASSWORD', 'admin123');

$message = '';
$currentMode = '';

// Check current failover mode
$configFile = __DIR__ . '/failover_mode.txt';
if (file_exists($configFile)) {
    $currentMode = trim(file_get_contents($configFile));
} else {
    $currentMode = 'auto'; // Default: automatic failover
}

// Handle login
if (isset($_POST['password'])) {
    if ($_POST['password'] === ADMIN_PASSWORD) {
        $_SESSION['admin_logged_in'] = true;
    } else {
        $message = 'Incorrect password!';
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    unset($_SESSION['admin_logged_in']);
    header('Location: failover_control.php');
    exit;
}

// Handle mode change
if (isset($_POST['mode']) && isset($_SESSION['admin_logged_in'])) {
    $newMode = $_POST['mode'];
    file_put_contents($configFile, $newMode);
    $currentMode = $newMode;
    $message = "Failover mode changed to: " . strtoupper($newMode);
}

// Check database status
require_once 'db_connect.php';

function checkDatabase($useBackup) {
    try {
        $db = $useBackup ? 'backup' : 'main';
        $config = [
            'main' => [
                'host' => 'sql200.infinityfree.com',
                'dbname' => 'if0_40320564_greenthumb_main_db',
                'username' => 'if0_40320564',
                'password' => 'UdKHnSrNkpF26s'
            ],
            'backup' => [
                'host' => 'sql200.infinityfree.com',
                'dbname' => 'if0_40320564_greenthumb_backup_db',
                'username' => 'if0_40320564',
                'password' => 'UdKHnSrNkpF26s'
            ]
        ];
        
        $dbConfig = $useBackup ? $config['backup'] : $config['main'];
        $pdo = new PDO("mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']}", 
                       $dbConfig['username'], $dbConfig['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Test query
        $count = $pdo->query("SELECT COUNT(*) FROM players")->fetchColumn();
        return ['status' => 'online', 'players' => $count];
    } catch (PDOException $e) {
        return ['status' => 'offline', 'error' => $e->getMessage()];
    }
}

$mainStatus = checkDatabase(false);
$backupStatus = checkDatabase(true);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Failover Control</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #333; border-bottom: 3px solid #4CAF50; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; }
        .status-box {
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            border-left: 5px solid;
        }
        .online { background: #d4edda; border-color: #28a745; }
        .offline { background: #f8d7da; border-color: #dc3545; }
        .mode-box {
            padding: 15px;
            margin: 20px 0;
            background: #d1ecf1;
            border-left: 5px solid #17a2b8;
            border-radius: 5px;
        }
        .btn {
            padding: 10px 20px;
            margin: 5px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-warning { background: #ffc107; color: black; }
        .btn:hover { opacity: 0.8; }
        input[type="password"] {
            padding: 10px;
            width: 200px;
            margin-right: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .message {
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            background: #fff3cd;
            border-left: 5px solid #ffc107;
        }
        .logout {
            float: right;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (isset($_SESSION['admin_logged_in'])): ?>
            <a href="?logout" class="logout btn btn-danger">Logout</a>
        <?php endif; ?>
        
        <h1>🔄 Database Failover Control Panel</h1>
        
        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if (!isset($_SESSION['admin_logged_in'])): ?>
            <form method="POST">
                <h2>Admin Login Required</h2>
                <input type="password" name="password" placeholder="Enter password" required>
                <button type="submit" class="btn btn-primary">Login</button>
            </form>
        <?php else: ?>
            
            <h2>📊 Database Status</h2>
            
            <div class="status-box <?= $mainStatus['status'] === 'online' ? 'online' : 'offline' ?>">
                <strong>MAIN Database:</strong> 
                <?php if ($mainStatus['status'] === 'online'): ?>
                    ✓ ONLINE (<?= $mainStatus['players'] ?> players)
                <?php else: ?>
                    ✗ OFFLINE - <?= htmlspecialchars($mainStatus['error']) ?>
                <?php endif; ?>
            </div>
            
            <div class="status-box <?= $backupStatus['status'] === 'online' ? 'online' : 'offline' ?>">
                <strong>BACKUP Database:</strong> 
                <?php if ($backupStatus['status'] === 'online'): ?>
                    ✓ ONLINE (<?= $backupStatus['players'] ?> players)
                <?php else: ?>
                    ✗ OFFLINE - <?= htmlspecialchars($backupStatus['error']) ?>
                <?php endif; ?>
            </div>
            
            <div class="mode-box">
                <strong>Current Mode:</strong> <?= strtoupper($currentMode) ?>
                <?php if ($currentMode === 'auto'): ?>
                    <br><small>Automatic failover enabled - if main fails, backup is used automatically</small>
                <?php elseif ($currentMode === 'main'): ?>
                    <br><small>Forced to use MAIN database only (no failover)</small>
                <?php elseif ($currentMode === 'backup'): ?>
                    <br><small>Forced to use BACKUP database (manual failover activated)</small>
                <?php endif; ?>
            </div>
            
            <h2>⚙️ Failover Controls</h2>
            
            <form method="POST" style="margin: 20px 0;">
                <button type="submit" name="mode" value="auto" class="btn btn-success">
                    🔄 Automatic Failover (Recommended)
                </button>
                <button type="submit" name="mode" value="main" class="btn btn-primary">
                    📍 Force MAIN Database
                </button>
                <button type="submit" name="mode" value="backup" class="btn btn-warning">
                    ⚠️ Switch to BACKUP Database
                </button>
            </form>
            
            <h2>📝 How to Demonstrate Failover</h2>
            <ol>
                <li><strong>Test Automatic Failover:</strong> Keep mode as "Auto", simulate main database down (rename main db in phpMyAdmin), application automatically uses backup</li>
                <li><strong>Manual Switch:</strong> Click "Switch to BACKUP Database" to manually failover</li>
                <li><strong>Recovery:</strong> Once main is fixed, click "Automatic Failover" to restore normal operation</li>
            </ol>
            
            <h2>🔧 Database Maintenance</h2>
            <p>
                <a href="verify_sync.php" class="btn btn-primary">Check Database Sync Status →</a>
                <a href="sync_repair.php" class="btn btn-warning">Repair Database Sync →</a>
            </p>
            
        <?php endif; ?>
    </div>
</body>
</html>
