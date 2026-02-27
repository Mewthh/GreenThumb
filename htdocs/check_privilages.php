<?php
// Check MySQL privileges to see what's allowed
require_once 'db_connect.php';

$pdo = connect();

if ($pdo) {
    try {
        // Check user privileges
        $stmt = $pdo->query("SHOW GRANTS");
        echo "<h2>Your MySQL Privileges:</h2>";
        echo "<pre>";
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            echo $row[0] . "\n";
        }
        echo "</pre>";
        
        // Try to check if CREATE ROUTINE is available
        echo "<h2>Checking for specific privileges:</h2>";
        $grants = $pdo->query("SHOW GRANTS")->fetchAll(PDO::FETCH_COLUMN);
        
        $hasCreateRoutine = false;
        $hasTrigger = false;
        $hasEvent = false;
        
        foreach ($grants as $grant) {
            if (stripos($grant, 'CREATE ROUTINE') !== false) {
                $hasCreateRoutine = true;
            }
            if (stripos($grant, 'TRIGGER') !== false) {
                $hasTrigger = true;
            }
            if (stripos($grant, 'EVENT') !== false) {
                $hasEvent = true;
            }
        }
        
        echo "<ul>";
        echo "<li>CREATE ROUTINE (Stored Procedures): " . ($hasCreateRoutine ? "✅ ALLOWED" : "❌ NOT ALLOWED") . "</li>";
        echo "<li>TRIGGER: " . ($hasTrigger ? "✅ ALLOWED" : "❌ NOT ALLOWED") . "</li>";
        echo "<li>EVENT: " . ($hasEvent ? "✅ ALLOWED" : "❌ NOT ALLOWED") . "</li>";
        echo "</ul>";
        
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "Database connection failed.";
}
?>
