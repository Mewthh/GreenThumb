
<?php
/**
 * Database Sync Repair Tool
 * Identifies and syncs missing records from main database to backup database
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<html><head><title>Database Sync Repair</title>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
    h1 { color: #333; border-bottom: 3px solid #4CAF50; padding-bottom: 10px; }
    h2 { color: #555; margin-top: 30px; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #4CAF50; color: white; }
    .success { background-color: #d4edda; padding: 15px; margin: 10px 0; border-left: 5px solid #28a745; }
    .error { background-color: #f8d7da; padding: 15px; margin: 10px 0; border-left: 5px solid #dc3545; }
    .warning { background-color: #fff3cd; padding: 15px; margin: 10px 0; border-left: 5px solid #ffc107; }
    .info { background-color: #d1ecf1; padding: 15px; margin: 10px 0; border-left: 5px solid #17a2b8; }
    .btn { 
        display: inline-block;
        padding: 10px 20px; 
        background: #28a745; 
        color: white; 
        text-decoration: none; 
        border-radius: 5px; 
        margin: 10px 5px;
        border: none;
        cursor: pointer;
    }
    .btn-danger { background: #dc3545; }
    .btn-primary { background: #007bff; }
</style></head><body><div class='container'>";

echo "<h1>🔧 Database Sync Repair Tool</h1>";

// Direct connection function (bypass failover)
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
        echo "<div class='error'>Connection failed to " . ($useBackup ? "backup" : "main") . " database: " . $e->getMessage() . "</div>";
        return null;
    }
}

$mainConn = directConnect(false);
$backupConn = directConnect(true);

if (!$mainConn || !$backupConn) {
    die("Failed to connect to databases. Cannot proceed.");
}

echo "<div class='info'>✓ Connected to both databases successfully</div>";

// Tables to sync
$tables = [
    'players' => 'player_id',
    'player_progress' => 'progress_id',
    'flower_collection' => 'collection_id',
    'achievements' => 'achievement_id'
];

$totalSynced = 0;
$autoRepair = isset($_GET['repair']) && $_GET['repair'] === 'auto';

foreach ($tables as $table => $primaryKey) {
    echo "<h2>Table: {$table}</h2>";
    
    try {
        // Get counts
        $mainCount = $mainConn->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
        $backupCount = $backupConn->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
        
        echo "<div class='info'>";
        echo "Main DB: <strong>{$mainCount}</strong> records | ";
        echo "Backup DB: <strong>{$backupCount}</strong> records";
        
        if ($mainCount == $backupCount) {
            echo " ✓ <strong>SYNCED</strong>";
            echo "</div>";
            continue;
        } else {
            echo " ✗ <strong>OUT OF SYNC</strong> (Difference: " . abs($mainCount - $backupCount) . " records)";
            echo "</div>";
        }
        
        // Find missing records
        $mainStmt = $mainConn->query("SELECT * FROM {$table}");
        $mainRecords = $mainStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $backupStmt = $backupConn->query("SELECT {$primaryKey} FROM {$table}");
        $backupIds = $backupStmt->fetchAll(PDO::FETCH_COLUMN);
        
        $missingRecords = [];
        foreach ($mainRecords as $record) {
            if (!in_array($record[$primaryKey], $backupIds)) {
                $missingRecords[] = $record;
            }
        }
        
        if (empty($missingRecords)) {
            echo "<div class='warning'>No missing records found (count mismatch may be due to deletions)</div>";
            continue;
        }
        
        echo "<div class='warning'>Found <strong>" . count($missingRecords) . "</strong> missing record(s) in backup database</div>";
        
        // Display missing records
        if (!empty($missingRecords)) {
            echo "<h3>Missing Records:</h3>";
            echo "<table><tr>";
            foreach (array_keys($missingRecords[0]) as $column) {
                echo "<th>{$column}</th>";
            }
            echo "</tr>";
            
            foreach ($missingRecords as $record) {
                echo "<tr>";
                foreach ($record as $value) {
                    echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
            
            // Auto repair if requested
            if ($autoRepair) {
                echo "<h3>Syncing Missing Records...</h3>";
                
                foreach ($missingRecords as $record) {
                    try {
                        $columns = array_keys($record);
                        $placeholders = array_fill(0, count($columns), '?');
                        
                        $sql = "INSERT INTO {$table} (" . implode(', ', $columns) . ") 
                                VALUES (" . implode(', ', $placeholders) . ")";
                        
                        $stmt = $backupConn->prepare($sql);
                        $stmt->execute(array_values($record));
                        
                        echo "<div class='success'>✓ Synced record with {$primaryKey} = {$record[$primaryKey]}</div>";
                        $totalSynced++;
                    } catch (PDOException $e) {
                        echo "<div class='error'>✗ Failed to sync record {$record[$primaryKey]}: " . $e->getMessage() . "</div>";
                    }
                }
            }
        }
        
    } catch (PDOException $e) {
        echo "<div class='error'>Error processing table {$table}: " . $e->getMessage() . "</div>";
    }
}

if (!$autoRepair && $totalSynced == 0) {
    echo "<h2>Actions</h2>";
    echo "<div class='warning'>";
    echo "<p>Review the missing records above. If you want to sync them to the backup database, click the button below:</p>";
    echo "<a href='?repair=auto' class='btn' onclick='return confirm(\"Are you sure you want to sync all missing records to the backup database?\")'>🔧 Repair & Sync Missing Records</a>";
    echo "<a href='verify_sync.php' class='btn btn-primary'>Verify Sync Status</a>";
    echo "<a href='failover_control.php' class='btn btn-primary'>Back to Failover Control</a>";
    echo "</div>";
} else if ($autoRepair) {
    echo "<h2>Sync Complete!</h2>";
    echo "<div class='success'>";
    echo "<p><strong>{$totalSynced}</strong> record(s) have been synced to the backup database.</p>";
    echo "<a href='verify_sync.php' class='btn btn-primary'>Verify Sync Status</a>";
    echo "<a href='sync_repair.php' class='btn btn-primary'>Run Again</a>";
    echo "<a href='failover_control.php' class='btn btn-primary'>Back to Failover Control</a>";
    echo "</div>";
}

echo "</div></body></html>";
?>
