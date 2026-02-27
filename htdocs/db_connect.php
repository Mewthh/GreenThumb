<?php

function connect($useBackup = false) 
{
    // Check for manual failover mode
    $configFile = __DIR__ . '/failover_mode.txt';
    if (file_exists($configFile)) {
        $mode = trim(file_get_contents($configFile));
        if ($mode === 'backup') {
            $useBackup = true; // Force backup mode
        } elseif ($mode === 'main') {
            $useBackup = false; // Force main mode
        }
        // 'auto' mode uses normal logic
    }
    
    $main = [
        'host' => 'sql200.infinityfree.com',
        'dbname' => 'if0_40320564_greenthumb_main_db',
        'username' => 'if0_40320564',
        'password' => 'UdKHnSrNkpF26s'
    ];

    $backup = [
        'host' => 'sql200.infinityfree.com',
        'dbname' => 'if0_40320564_greenthumb_backup_db',
        'username' => 'if0_40320564',
        'password' => 'UdKHnSrNkpF26s'
    ];

    // Select which to connect to
    $db = $useBackup ? $backup : $main;

    try {
        $pdo = new PDO("mysql:host={$db['host']};dbname={$db['dbname']}", $db['username'], $db['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        // Check if manual mode is set
        $manualMode = false;
        if (file_exists($configFile)) {
            $mode = trim(file_get_contents($configFile));
            if ($mode === 'main' || $mode === 'backup') {
                $manualMode = true;
            }
        }
        
        // If main database fails and we haven't tried backup yet (and not in manual mode), try backup
        if (!$useBackup && !$manualMode) {
            error_log("Main database failed, auto-switching to backup: " . $e->getMessage());
            return connect(true); // Recursive call with backup
        }
        
        echo "Connection failed: " . $e->getMessage();
        return null;
    }   
}
connect();