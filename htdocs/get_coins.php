<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require 'db_connect.php';

// Make sure username exists
if (!isset($_SESSION['username'])) {
    echo 0;
    exit;
}

// Create PDO connection
$pdo = connect(); // <-- store connection in $pdo

$username = $_SESSION['username'];

try {
    $sql = "SELECT coins FROM players WHERE username = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$username]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    echo $row ? $row['coins'] : 0;
} catch (PDOException $e) {
    echo 0; // fallback
}
?>