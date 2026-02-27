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
    $stmt = $pdo->prepare("SELECT coins, tap_power FROM players WHERE player_id = ?");
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
    $currentTapPower = intval($player['tap_power']);
    
    // Max level is 20
    if ($currentTapPower >= 20) {
        echo json_encode([
            'success' => false,
            'message' => 'Maximum upgrade level reached!'
        ]);
        exit;
    }
    
    // Calculate cost based on current level
    // Formula: Base cost increases exponentially for challenging progression
    // Level 1: 25 coins, Level 2: 45, Level 3: 70, Level 4: 105, Level 5: 150, etc.
    // Formula: 25 + (level * level * 5)
    $upgradeCost = 25 + ($currentTapPower * $currentTapPower * 5);
    $upgradeCost = round($upgradeCost, 2);
    
    // Check if player has enough coins
    if ($currentCoins < $upgradeCost) {
        echo json_encode([
            'success' => false,
            'message' => 'Not enough coins! Need ' . $upgradeCost . ' coins.'
        ]);
        exit;
    }
    
    // Perform the upgrade
    $newCoins = $currentCoins - $upgradeCost;
    $newTapPower = $currentTapPower + 1;
    
    syncExecute("UPDATE players SET coins = ?, tap_power = ? WHERE player_id = ?", [$newCoins, $newTapPower, $playerId]);
    
    // Check for achievements (mimics trigger behavior)
    require_once 'achievement_trigger.php';
    $newAchievements = checkAndAwardAchievements($pdo, $playerId);
    
    echo json_encode([
        'success' => true,
        'message' => 'Upgrade successful!',
        'newTapPower' => $newTapPower,
        'newCoins' => $newCoins,
        'costPaid' => $upgradeCost,
        'newAchievements' => $newAchievements
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
