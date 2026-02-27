<?php
session_start();
header('Content-Type: application/json');

require_once 'db_connect.php';

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
	 // ---- LOAD PLAYER PROGRESS ----
    $progressStmt = $pdo->prepare(
        'SELECT current_stage, current_taps, current_flower, current_flower_rarity FROM player_progress WHERE player_id = ?'
    );
    $progressStmt->execute([$playerId]);
    $progress = $progressStmt->fetch(PDO::FETCH_ASSOC);

    if (!$progress) {
        $progress = [
            'current_stage' => 1,
            'current_taps' => 0,
            'current_flower' => null,
            'current_flower_rarity' => null
        ];
    }
    
    // ---- LOAD PLAYER COINS, TAP POWER, AND LUCK ----
	$coinStmt = $pdo->prepare("SELECT coins, tap_power, luck FROM players WHERE player_id = ?");
    $coinStmt->execute([$playerId]);
    $coinRow = $coinStmt->fetch(PDO::FETCH_ASSOC);
    $coins = $coinRow ? floatval($coinRow['coins']) : 0;
    $tapPower = $coinRow ? intval($coinRow['tap_power']) : 1;
    $luck = $coinRow ? intval($coinRow['luck']) : 0;
    
    
	// ---- FLOWER COLLECTION ----
    $collectionStmt = $pdo->prepare(
        'SELECT flower_key, flower_label, collected_count
         FROM flower_collection
         WHERE player_id = ?'
    );
    $collectionStmt->execute([$playerId]);

    $collectionCounts = [];
    while ($row = $collectionStmt->fetch(PDO::FETCH_ASSOC)) {
        $collectionCounts[$row['flower_key']] = [
            'count' => (int) $row['collected_count'],
            'label' => $row['flower_label']
        ];
    }

    // ---- FINAL OUTPUT ----
    echo json_encode([
        'success' => true,
        'progress' => $progress,
        'collectionCounts' => $collectionCounts,
		'coins' => $coins,
		'tapPower' => $tapPower,
		'luck' => $luck    
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load progress',
        'error' => $e->getMessage()
    ]);
}
