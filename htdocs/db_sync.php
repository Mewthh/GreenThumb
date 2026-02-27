<?php
require_once 'db_connect.php';

/**
 * Connect directly to a specific database, bypassing failover mode
 * This ensures sync operations always write to both databases
 */
function directConnect($useBackup = false) {
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

    $db = $useBackup ? $backup : $main;

    try {
        $pdo = new PDO("mysql:host={$db['host']};dbname={$db['dbname']}", $db['username'], $db['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Direct connection failed to " . ($useBackup ? "backup" : "main") . " database: " . $e->getMessage());
        return null;
    }
}

/**
 * Execute a query on both main and backup databases
 * Returns result from main database (or backup if main fails)
 */
function syncExecute($sql, $params = []) {
    // Connect directly to both databases, ignoring failover mode
    $mainConn = directConnect(false);
    $backupConn = directConnect(true);
    
    $mainResult = null;
    $backupResult = null;
    $mainSuccess = false;
    $backupSuccess = false;
    
    // Execute on main database
    try {
        if ($mainConn) {
            $stmt = $mainConn->prepare($sql);
            $stmt->execute($params);
            $mainResult = $stmt;
            $mainSuccess = true;
        } else {
            error_log("Sync failed: Could not connect to main database");
        }
    } catch (PDOException $e) {
        error_log("Main database sync error: " . $e->getMessage());
    }
    
    // Execute on backup database (always attempt, regardless of main result)
    try {
        if ($backupConn) {
            $stmt = $backupConn->prepare($sql);
            $stmt->execute($params);
            $backupResult = $stmt;
            $backupSuccess = true;
        } else {
            error_log("Sync failed: Could not connect to backup database");
        }
    } catch (PDOException $e) {
        error_log("Backup database sync error: " . $e->getMessage());
    }
    
    // Log sync status for monitoring
    if ($mainSuccess && $backupSuccess) {
        error_log("Sync successful: Data written to both databases");
    } elseif ($mainSuccess) {
        error_log("WARNING: Data written to main only - backup sync failed");
    } elseif ($backupSuccess) {
        error_log("WARNING: Data written to backup only - main sync failed");
    } else {
        error_log("CRITICAL: Sync failed to both databases");
    }
    
    // Return main result if available, otherwise backup
    return $mainResult ? $mainResult : $backupResult;
}

/**
 * Execute SELECT query (from main database, auto-failover to backup if main is down)
 */
function syncQuery($sql, $params = []) {
    $mainConn = connect(false); // Will auto-failover to backup if main is down
    
    try {
        if ($mainConn) {
            $stmt = $mainConn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        }
    } catch (PDOException $e) {
        error_log("Query error: " . $e->getMessage());
        return null;
    }
}

/**
 * Get last insert ID from both databases
 */
function syncLastInsertId() {
    $mainConn = connect(false);
    return $mainConn ? $mainConn->lastInsertId() : null;
}
?>
