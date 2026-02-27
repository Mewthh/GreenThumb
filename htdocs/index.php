<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

require_once 'db_connect.php';
$pdo = connect();
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="styles.css?v=5">
    <link rel="icon" type="image/png" href="images/green thumb.png">
	<title>Green Thumb</title>
	<!-- Optional: for icons -->
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
	<!-- Achievement Notification -->
	<div id="achievementNotification" class="achievement-notification"></div>
	
	<div class="TileContainer">
		<div class="TileToon"></div>
		<div class="TileToon"></div>
		<div class="TileToon"></div>
		<div class="TileToon"></div>
	</div>

	<!-- Button Group -->
	<div class="button-bar">
		<button class="brown-button ButtonAchievements"><i class="fa-solid fa-trophy"></i> <span>Achievements</span></button>
		<button class="brown-button Button"><i class="fa-solid fa-leaf"></i> <span>Collections</span></button>
		<button class="brown-button ButtonBuyPots"><i class="fa-solid fa-store"></i> <span>ㅤㅤㅤShopㅤ</span></button>
		<button class="brown-button ButtonUpgrade"><i class="fa-solid fa-arrow-up"></i> <span>ㅤㅤㅤㅤUpgradeㅤ</span></button>
	</div>
        
        <a href="logout.php">
		<button class="logout-button"><span>LOGOUT</span></button>
	</a>
    
    <div id="upgradeWindow" class="upgrade-window"> 
        <div class="upgrade-content"> 
            <span class="close-btn">&times;</span> 
            	<!-- Upgrade Window -->
			<div class="upgrade-title">Tap Power Upgrade</div>

				<div class="upgrade-strip">
    				<div class="upgrade-strip-header">
        				<div class="upgrade-icon-large">⚡</div>
        				<div class="upgrade-strip-info">
            				<h2>Tap Power</h2>
            				<p class="upgrade-description">Increase your tap strength to grow plants faster!</p>
        				</div>
    				</div>
    
    				<div class="upgrade-strip-stats">
        				<div class="stat-box">
            				<div class="stat-label">Current Level</div>
            				<div class="stat-value" id="currentTapLevel">1</div>
        				</div>
        				<div class="stat-box">
            				<div class="stat-label">Taps per Click</div>
            				<div class="stat-value" id="currentTapValue">1</div>
        				</div>
        				<div class="stat-box">
            				<div class="stat-label">Next Level Cost</div>
            				<div class="stat-value" id="upgradeCost">5.00 💰</div>
        				</div>
    				</div>
    
    				<div class="upgrade-strip-action">
        				<button class="upgrade-buy-btn-main" id="buyUpgradeBtn">
            				<span class="btn-text">UPGRADE</span>
            				<span class="btn-subtext">+1 Tap Power</span>
        				</button>
        				<div class="upgrade-progress-bar">
            				<div class="upgrade-progress-fill" id="upgradeProgressBar"></div>
            				<span class="upgrade-progress-text" id="upgradeProgressText">Level 1 / 20</span>
        				</div>
    				</div>
				</div>
    	</div>
	</div>
        </div> 
    </div>
    <div id="shopWindow" class="shop-window">
    	<div class="shop-content">
        	<span class="close-shop">&times;</span>
        	<!-- Luck Shop -->
            <div class="shop-title">Luck Shop</div>

        <div class="shop-strip">
    			<div class="shop-strip-header">
        			<div class="shop-icon-large">🍀</div>
        			<div class="shop-strip-info">
            			<h2>Luck</h2>
            			<p class="shop-description">Increase your luck to find rarer flowers!</p>
        			</div>
    			</div>
    
    			<div class="shop-strip-stats">
        			<div class="stat-box">
            			<div class="stat-label">Current Level</div>
            			<div class="stat-value" id="currentLuckLevel">0</div>
        			</div>
        			<div class="stat-box">
            			<div class="stat-label">Luck Bonus</div>
            			<div class="stat-value" id="currentLuckValue">0%</div>
        			</div>
        			<div class="stat-box">
            			<div class="stat-label">Purchase Cost</div>
            			<div class="stat-value" id="luckCost">30.00 💰</div>
        			</div>
    			</div>
    
    			<div class="shop-strip-action">
        			<button class="shop-buy-btn-main" id="buyLuckBtn">
            			<span class="btn-text">BUY</span>
            			<span class="btn-subtext">+1 Luck</span>
        			</button>
        			<div class="shop-progress-bar">
            			<div class="shop-progress-fill" id="luckProgressBar"></div>
            			<span class="shop-progress-text" id="luckProgressText">Level 0 / 20</span>
        			</div>
    			</div>
			</div>
    	</div>
	</div>
    <div id="achievementsWindow" class="achievements-window">
    	<div class="achievements-content">
        	<span class="close-achievements">&times;</span>
        	<!-- Achievements content will go here later -->
             <h2 class="achievements-title">Achievements</h2>

        <div class="achievement-list">
            <!-- Achievements will be loaded dynamically -->
        </div>
    	</div>
	</div>
	<div id="collectionWindow" class="collection-window">
    <div class="collection-content">
        <span class="close-collection">&times;</span>

        <div class="collection-title">Flower Collection</div>

        <div class="collection-scroll">

            <!-- COMMON -->
            <div class="collection-section">
                <div class="collection-subtitle common">COMMON</div>
                <div class="flower-list">
                    <div class="flower">
                        <img src="assets/tiles/common3.png" class="flower-icon">
                        <span>Dewpetal</span>
                    </div>
                    <div class="flower">
                        <img src="assets/tiles/common6.png" class="flower-icon">
                        <span>Snowblossom</span>
                    </div>
                    <div class="flower">
                        <img src="assets/tiles/common7.png" class="flower-icon">
                        <span>Windlily</span>
                    </div>
                </div>
            </div>

            <!-- RARE -->
            <div class="collection-section">
                <div class="collection-subtitle rare">RARE</div>
                <div class="flower-list">
                    <div class="flower">
                        <img src="assets/tiles/rare9.png" class="flower-icon">
                        <span>Lunawisp Orchid</span>
                    </div>
                    <div class="flower">
                        <img src="assets/tiles/rare10.png" class="flower-icon">
                        <span>Crimson Veil</span>
                    </div>
                </div>
            </div>

            <!-- EPIC -->
            <div class="collection-section">
                <div class="collection-subtitle epic">EPIC</div>
                <div class="flower-list">
                    <div class="flower">
                        <img src="assets/tiles/epic2.png" class="flower-icon">
                        <span>Frostvine Ivy</span>
                    </div>
                    <div class="flower">
                        <img src="assets/tiles/epic8.png" class="flower-icon">
                        <span>Asterose</span>
                    </div>
                </div>
            </div>

            <!-- LEGENDARY -->
            <div class="collection-section">
                <div class="collection-subtitle legendary">LEGENDARY</div>
                <div class="flower-list">
                    <div class="flower">
                        <img src="assets/floweranimation/Plant3_Idle_full_01.png" class="flower-icon legendary-icon">
                        <span>Voidthorn Lotus</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
    <div class="coin-window">
    	<div class="coin-icon"></div>
    	<span id="coinAmount">0</span>
	</div>
	<div id="plant-container">
    	<span id="seed" class="seed-stage">🌱</span>
    	<p id="tap-count"></p>
	</div>
    <script src="main.js?v=1"></script>
	</body>
</html>
