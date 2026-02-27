<?php
// This function mimics database triggers by checking and awarding achievements
function checkAndAwardAchievements($pdo, $playerId) {
    $newAchievements = [];
    
    try {
        // Get player stats
        $stmt = $pdo->prepare("SELECT tap_power, luck, coins FROM players WHERE player_id = ?");
        $stmt->execute([$playerId]);
        $player = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$player) return $newAchievements;
        
        // Get flower collection stats
        $flowerStmt = $pdo->prepare("SELECT COUNT(*) as total, COUNT(CASE WHEN collected_count >= 100 THEN 1 END) as at_100 FROM flower_collection WHERE player_id = ?");
        $flowerStmt->execute([$playerId]);
        $flowers = $flowerStmt->fetch(PDO::FETCH_ASSOC);
        
        // Check legendary flower
        $legendaryStmt = $pdo->prepare("SELECT COUNT(*) as count FROM flower_collection WHERE player_id = ? AND flower_key LIKE '%Plant3_Idle_full_%'");
        $legendaryStmt->execute([$playerId]);
        $legendary = $legendaryStmt->fetch(PDO::FETCH_ASSOC);
        
        // Define achievement conditions
        $achievements = [
            [
                'condition' => $player['tap_power'] >= 2,
                'title' => 'First Tap Upgrade',
                'description' => 'Upgraded your tap power for the first time!',
                'icon' => '⚡',
                'reward' => 10
            ],
            [
                'condition' => $player['tap_power'] >= 20,
                'title' => 'Max Tap Power',
                'description' => 'Reached maximum tap power level!',
                'icon' => '💪',
                'reward' => 100
            ],
            [
                'condition' => $player['luck'] >= 1,
                'title' => 'First Luck Boost',
                'description' => 'Purchased your first luck upgrade!',
                'icon' => '🍀',
                'reward' => 10
            ],
            [
                'condition' => $player['luck'] >= 20,
                'title' => 'Max Luck',
                'description' => 'Reached maximum luck level!',
                'icon' => '✨',
                'reward' => 100
            ],
            [
                'condition' => $player['coins'] >= 1000000,
                'title' => 'Millionaire!',
                'description' => 'Accumulated 1,000,000 coins!',
                'icon' => '💰',
                'reward' => 500
            ],
            [
                'condition' => $legendary['count'] > 0,
                'title' => 'Plant of Legend',
                'description' => 'Acquired a legendary plant for the first time!',
                'icon' => '🌟',
                'reward' => 200
            ],
            [
                'condition' => $flowers['total'] >= 1,
                'title' => 'First Bloom',
                'description' => 'Grew your first plant to completion!',
                'icon' => '🌱',
                'reward' => 25
            ],
            [
                'condition' => $flowers['at_100'] >= 7 && $flowers['total'] >= 7,
                'title' => 'Master Collector',
                'description' => 'Collected 100+ of every flower in the game!',
                'icon' => '🏆',
                'reward' => 1000
            ]
        ];
        
        // Check each achievement
        foreach ($achievements as $ach) {
            if ($ach['condition']) {
                // Check if already awarded
                $checkStmt = $pdo->prepare("SELECT achievement_id FROM achievements WHERE player_id = ? AND title = ?");
                $checkStmt->execute([$playerId, $ach['title']]);
                
                if (!$checkStmt->fetch()) {
                    // Award the achievement (to both databases)
                    require_once 'db_sync.php';
                    syncExecute(
                        "INSERT INTO achievements (player_id, title, description, reward, unlocked_at) 
                         VALUES (?, ?, ?, ?, NOW())",
                        [$playerId, $ach['title'], $ach['description'], $ach['reward']]
                    );
                    
                    $newAchievements[] = [
                        'title' => $ach['title'],
                        'description' => $ach['description'],
                        'icon' => $ach['icon'],
                        'reward' => $ach['reward']
                    ];
                }
            }
        }
        
    } catch (PDOException $e) {
        error_log("Achievement check error: " . $e->getMessage());
    }
    
    return $newAchievements;
}
