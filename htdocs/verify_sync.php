<?php
/**
 * Database Sync Verification Tool
 * Use this to check if main and backup databases are in sync
 */

require_once 'db_connect.php';

echo "<h1>Database Sync Verification</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #4CAF50; color: white; }
    .match { background-color: #d4edda; }
    .mismatch { background-color: #f8d7da; }
    .info { background-color: #d1ecf1; padding: 10px; margin: 10px 0; }
</style>";

// Connect to both databases
$mainConn = connect(false);
$backupConn = connect(true);

if (!$mainConn || !$backupConn) {
    die("Failed to connect to one or both databases.");
}

// Get list of tables
$tables = ['players', 'player_progress', 'flower_collection', 'achievements'];

foreach ($tables as $table) {
    echo "<h2>Table: {$table}</h2>";
    
    try {
        // Count rows in both databases
        $mainCount = $mainConn->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
        $backupCount = $backupConn->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
        
        $matchClass = ($mainCount == $backupCount) ? 'match' : 'mismatch';
        
        echo "<div class='info {$matchClass}'>";
        echo "Main DB: {$mainCount} rows | Backup DB: {$backupCount} rows";
        if ($mainCount == $backupCount) {
            echo " ✓ SYNCED";
        } else {
            echo " ✗ OUT OF SYNC (Difference: " . abs($mainCount - $backupCount) . " rows)";
        }
        echo "</div>";
        
        // Show sample of first 5 rows from main
        echo "<h3>Sample Data (Main DB)</h3>";
        $mainStmt = $mainConn->query("SELECT * FROM {$table} LIMIT 5");
        $mainRows = $mainStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($mainRows)) {
            echo "<table>";
            echo "<tr>";
            foreach (array_keys($mainRows[0]) as $column) {
                echo "<th>{$column}</th>";
            }
            echo "</tr>";
            
            foreach ($mainRows as $row) {
                echo "<tr>";
                foreach ($row as $value) {
                    echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No data in this table.</p>";
        }
        
    } catch (PDOException $e) {
        echo "<div class='mismatch'>Error: " . $e->getMessage() . "</div>";
    }
}

echo "<hr>";
echo "<h2>Test Results</h2>";
echo "<div class='info'>";
echo "If all tables show '✓ SYNCED', your databases are properly synchronized.<br>";
echo "If you see '✗ OUT OF SYNC', you may need to re-import the main database to backup.";
echo "</div>";
?>
