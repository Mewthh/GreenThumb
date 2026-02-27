<?php
session_start();
header('Content-Type: application/json');

require_once 'db_connect.php';
require_once 'db_sync.php';

if (!isset($_SESSION['player_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Not authenticated'
    ]);
    exit;
}

$pdo = connect();
if (!$pdo) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed'
    ]);
    exit;
}

$playerId = (int) $_SESSION['player_id'];

try {
    // Get current player stats
    $stmt = $pdo->prepare("SELECT coins, luck FROM players WHERE player_id = ?");
    $stmt->execute([$playerId]);
    $player = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$player) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Player not found'
        ]);
        exit;
    }
    
    $currentCoins = floatval($player['coins']);
    $currentLuck = intval($player['luck']);
    
    // Max luck level is 20
    if ($currentLuck >= 20) {
        echo json_encode([
            'success' => false,
            'message' => 'Maximum luck level reached!'
        ]);
        exit;
    }
    
    // Calculate cost based on current level
    // Formula: 30 + (level * level * 6) - More expensive than tap power
    $luckCost = 30 + ($currentLuck * $currentLuck * 6);
    $luckCost = round($luckCost, 2);
    
    // Check if player has enough coins
    if ($currentCoins < $luckCost) {
        echo json_encode([
            'success' => false,
            'message' => 'Not enough coins! Need ' . $luckCost . ' coins.'
        ]);
        exit;
    }
    
    // Perform the upgrade
    $newCoins = $currentCoins - $luckCost;
    $newLuck = $currentLuck + 1;
    
    syncExecute("UPDATE players SET coins = ?, luck = ? WHERE player_id = ?", [$newCoins, $newLuck, $playerId]);
    
    // Check for achievements (mimics trigger behavior)
    require_once 'achievement_trigger.php';
    $newAchievements = checkAndAwardAchievements($pdo, $playerId);
    
    echo json_encode([
        'success' => true,
        'message' => 'Luck upgraded!',
        'newLuck' => $newLuck,
        'newCoins' => $newCoins,
        'costPaid' => $luckCost,
        'newAchievements' => $newAchievements
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
