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
    // Get all achievements for this player
    $stmt = $pdo->prepare(
        "SELECT achievement_id, title, description, reward, unlocked_at 
         FROM achievements 
         WHERE player_id = ? 
         ORDER BY unlocked_at DESC"
    );
    $stmt->execute([$playerId]);
    $achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Define all possible achievements in the game
    $allAchievements = [
        ['title' => 'First Bloom', 'description' => 'Grew your first plant to completion!', 'icon' => '🌱', 'reward' => 25],
        ['title' => 'First Tap Upgrade', 'description' => 'Upgraded your tap power for the first time!', 'icon' => '⚡', 'reward' => 10],
        ['title' => 'First Luck Boost', 'description' => 'Purchased your first luck upgrade!', 'icon' => '🍀', 'reward' => 10],
        ['title' => 'POOOOWEEEER!', 'description' => 'Reached maximum tap power level!', 'icon' => '💪', 'reward' => 100],
        ['title' => 'I\'m Feeling Lucky', 'description' => 'Reached maximum luck level!', 'icon' => '✨', 'reward' => 100],
        ['title' => 'Plant of Legend', 'description' => 'Acquired a legendary plant for the first time!', 'icon' => '🌟', 'reward' => 200],
        ['title' => 'Master Collector', 'description' => 'Collected 100+ of every flower in the game!', 'icon' => '🏆', 'reward' => 1000],
        ['title' => 'Millionaire!', 'description' => 'Accumulated 1,000,000 coins!', 'icon' => '💰', 'reward' => 500]
    ];
    
    // Create a map of unlocked achievements
    $unlockedMap = [];
    foreach ($achievements as $ach) {
        $unlockedMap[$ach['title']] = true;
    }
    
    // Build final list with locked/unlocked status
    $achievementList = [];
    foreach ($allAchievements as $ach) {
        $achievementList[] = [
            'title' => $ach['title'],
            'description' => $ach['description'],
            'icon' => $ach['icon'],
            'reward' => $ach['reward'],
            'unlocked' => isset($unlockedMap[$ach['title']])
        ];
    }
    
    echo json_encode([
        'success' => true,
        'achievements' => $achievementList
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
