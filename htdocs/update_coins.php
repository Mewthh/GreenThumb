<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['username'])) {
    echo "ERR";
    exit();
}

require_once 'db_sync.php';
$pdo = connect();
$username = $_SESSION['username'];

if (!isset($_POST['coins'])) {
    echo "ERR";
    exit();
}

$newCoins = floatval($_POST['coins']);

syncExecute("UPDATE players SET coins = ? WHERE username = ?", [$newCoins, $username]);

// Check for coin achievements (mimics trigger behavior)
if (isset($_SESSION['player_id'])) {
    require_once 'achievement_trigger.php';
    checkAndAwardAchievements($pdo, $_SESSION['player_id']);
}

echo "OK";
