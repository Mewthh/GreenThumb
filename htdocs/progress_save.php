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

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid payload'
    ]);
    exit;
}

$playerId = (int) $_SESSION['player_id'];
$currentStage = max(1, (int) ($payload['stage'] ?? 1));
$currentTaps = max(0, (int) ($payload['taps'] ?? 0));
$currentFlower = $payload['selectedFlowerKey'] ?? null;
$currentFlowerRarity = $payload['selectedFlowerRarity'] ?? null;
$collectionCounts = $payload['collectionCounts'] ?? [];

$pdo = connect();
if (!$pdo) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed'
    ]);
    exit;
}

try {
    $pdo->beginTransaction();

    // Save to both databases
    syncExecute(
        'INSERT INTO player_progress (player_id, current_stage, current_taps, current_flower, current_flower_rarity)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
             current_stage = VALUES(current_stage),
             current_taps = VALUES(current_taps),
             current_flower = VALUES(current_flower),
             current_flower_rarity = VALUES(current_flower_rarity),
             updated_at = CURRENT_TIMESTAMP',
        [
            $playerId,
            $currentStage,
            $currentTaps,
            $currentFlower,
            $currentFlowerRarity
        ]
    );

    if (!empty($collectionCounts) && is_array($collectionCounts)) {
        foreach ($collectionCounts as $flowerKey => $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $count = isset($entry['count']) ? (int) $entry['count'] : 0;
            $label = $entry['label'] ?? $flowerKey;
            
            // Save to both databases
            syncExecute(
                'INSERT INTO flower_collection (player_id, flower_key, flower_label, collected_count, collected_at)
                 VALUES (?, ?, ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE collected_count = VALUES(collected_count), flower_label = VALUES(flower_label)',
                [
                    $playerId,
                    $flowerKey,
                    $label,
                    $count
                ]
            );
        }
    }

    $pdo->commit();
    
    // Check for achievements (mimics trigger behavior)
    require_once 'achievement_trigger.php';
    $newAchievements = checkAndAwardAchievements($pdo, $playerId);

    echo json_encode([
        'success' => true,
        'newAchievements' => $newAchievements
    ]);
} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to save progress'
    ]);
}
