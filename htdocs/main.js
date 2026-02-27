// main.js
let coins = 0;
const coinsPerTap = 0.50; // keep this!
let tapPower = 1; // Player's tap power level (upgradeable)
let luck = 0; // Player's luck level (upgradeable)

let coinSaveTimeout;
const COIN_SAVE_DELAY = 500;


// Upgrade Window
document.querySelector('.ButtonUpgrade').addEventListener('click', function() {
    document.getElementById('upgradeWindow').style.display = 'flex';
});
document.querySelector('.close-btn').addEventListener('click', function() {
    document.getElementById('upgradeWindow').style.display = 'none';
});

// Shop Window
document.querySelector('.ButtonBuyPots').addEventListener('click', function() {
    document.getElementById('shopWindow').style.display = 'flex';
});
document.querySelector('.close-shop').addEventListener('click', function() {
    document.getElementById('shopWindow').style.display = 'none';
});

// Achievements Window
document.querySelector('.ButtonAchievements').addEventListener('click', function() {
    loadAchievements();
    document.getElementById('achievementsWindow').style.display = 'flex';
});
document.querySelector('.close-achievements').addEventListener('click', function() {
    document.getElementById('achievementsWindow').style.display = 'none';
});

// Collection Window
document.querySelector('.Button').addEventListener('click', function() {
    document.getElementById('collectionWindow').style.display = 'flex';
});
document.querySelector('.close-collection').addEventListener('click', function() {
    document.getElementById('collectionWindow').style.display = 'none';
});

//                       // Coin Auto-Update                       commenting this to test new coin update function at the bottom of this code
//                       function updateCoins() {
  //                          fetch("get_coins.php")
    ///                            .then(response => response.text())
       //                         .then(data => {
         //                           document.getElementById("coinAmount").innerText = data;
           //                     })
             //                   .catch(err => console.error(err));
              //          }

                        // Initial fetch
               //         updateCoins();

                        // Auto-update every 3 seconds
                  //      setInterval(updateCoins, 3000);


let stage = 1;
let taps = 0;
let animationInterval;
let selectedFlower = null;
let tapMessageTimeout;
let isProgressLoaded = false;
let saveProgressTimeout;
const SAVE_DEBOUNCE_MS = 700;

const seed = document.getElementById("seed");
const tapCount = document.getElementById("tap-count");
const plantContainer = document.getElementById("plant-container");

const rarityEmojis = {
    legendary: '🌟',
    epic: '💎',
    rare: '⭐',
    common: '🌸'
};

const collectionCards = new Map();
const collectionCounts = {};
const collectionNames = new Map();

// Define flower types with rarities
const flowerTypes = {
    legendary: {
        chance: 0.01, // 1%
        flowers: [
            {
                name: "Legendary Flower",
                frames: 16,
                path: "assets/floweranimation/Plant3_Idle_full_"
            }
        ]
    },
    epic: {
        chance: 0.10, // 10%
        flowers: [
            "epic2.png",
            "epic8.png"
        ]
    },
    rare: {
        chance: 0.25, // 25%
        flowers: [
            "rare9.png",
            "rare10.png"
        ]
    },
    common: {
        chance: 0.64, // 64%
        flowers: [
            "common3.png",
            "common6.png",
            "common7.png"
        ]
    }
};

function getRandomFlower() {
    const random = Math.random();
    let cumulativeChance = 0;
    
    // Apply luck multiplier: Each luck level multiplies rare drop chances
    // Base chances: legendary 1%, epic 10%, rare 25%, common 64%
    // Max luck (20): legendary ~21%, epic ~30%, rare ~35%, common ~14%
    const luckMultiplier = 1 + luck; // +1 multiplier per luck level (luck 1 = 2x, luck 20 = 21x)
    
    const adjustedLegendaryChance = flowerTypes.legendary.chance * luckMultiplier;
    const adjustedEpicChance = flowerTypes.epic.chance * luckMultiplier;
    const adjustedRareChance = flowerTypes.rare.chance * luckMultiplier;
    
    // Calculate total to normalize (make sure all chances add up to 1.0)
    const total = adjustedLegendaryChance + adjustedEpicChance + adjustedRareChance + flowerTypes.common.chance;
    
    // Normalize all chances
    const normalizedLegendary = adjustedLegendaryChance / total;
    const normalizedEpic = adjustedEpicChance / total;
    const normalizedRare = adjustedRareChance / total;
    const normalizedCommon = flowerTypes.common.chance / total;
    
    // Check legendary first (most rare)
    cumulativeChance += normalizedLegendary;
    if (random < cumulativeChance) {
        const legendary = flowerTypes.legendary.flowers[
            Math.floor(Math.random() * flowerTypes.legendary.flowers.length)
        ];
        return {
            type: 'legendary',
            rarity: 'legendary',
            data: legendary
        };
    }
    
    // Check epic
    cumulativeChance += normalizedEpic;
    if (random < cumulativeChance) {
        const epic = flowerTypes.epic.flowers[
            Math.floor(Math.random() * flowerTypes.epic.flowers.length)
        ];
        return {
            type: 'common',
            rarity: 'epic',
            data: epic
        };
    }
    
    // Check rare
    cumulativeChance += normalizedRare;
    if (random < cumulativeChance) {
        const rare = flowerTypes.rare.flowers[
            Math.floor(Math.random() * flowerTypes.rare.flowers.length)
        ];
        return {
            type: 'common',
            rarity: 'rare',
            data: rare
        };
    }
    
    // Default to common
    const common = flowerTypes.common.flowers[
        Math.floor(Math.random() * flowerTypes.common.flowers.length)
    ];
    return {
        type: 'common',
        rarity: 'common',
        data: common
    };
}
// Define each stage
const stages = [
    {
        emoji: "🌱",
        maxTaps: 50,
        fontSize: "60px",
        text: "Taps: 0/50"
    },
    {
        emoji: "",
        image: "assets/tiles/medicinal herb.png",
        maxTaps: 100,
        fontSize: "120px",
        text: "Stage 2: Growing..."
    },
    {
        emoji: "<img src='assets/floweranimation/Plant3_Idle_full_01.png' alt='Legendary Flower'>",   // Stage 3
        maxTaps: 100, // Default (will be overridden by rarity)
        fontSize: "140px",
        text: "Stage 3: Almost Bloom!"
    }
];

// Rarity-based tap requirements for Stage 3
const stage3RarityTaps = {
    common: 150,
    rare: 250,
    epic: 375,
    legendary: 500
};

function updateStage({ rollForFlower = true, suppressCounter = false, preserveTaps = false } = {}) {
    const current = stages[Math.max(0, stage - 1)];
    if (!current) {
        return;
    }

    seed.classList.remove("stage2", "stage3");
    if (plantContainer) {
        plantContainer.classList.remove("stage1-active", "stage2-active", "stage3-active");
        const normalizedStage = Math.max(1, Math.min(stage, 3));
        plantContainer.classList.add(`stage${normalizedStage}-active`);
    }

    if (animationInterval && stage < 3) {
        clearInterval(animationInterval);
        animationInterval = null;
    }

    if (stage === 1) {
        seed.textContent = current.emoji;
    } else if (stage === 2) {
        seed.classList.add("stage2");
        if (current.image) {
            seed.innerHTML = `<img src="${current.image}" alt="Stage 2 plant" />`;
        } else {
            seed.textContent = current.emoji;
        }
    } else if (stage === 3) {
        seed.classList.add("stage3");
        if (rollForFlower || !selectedFlower) {
            selectedFlower = getRandomFlower();
        }
        renderSelectedFlowerAppearance();
    }

    seed.style.fontSize = current.fontSize;

    if (!preserveTaps) {
        taps = 0;
    }
    const tapDisplayValue = taps;

    if (stage < 3) {
        if (!suppressCounter) {
            refreshTapCounter();
        }
    } else if (stage === 3 && selectedFlower) {
        const emoji = rarityEmojis[selectedFlower.rarity] || '🌸';
        const maxTapsForFlower = getMaxTapsForStage3();
        tapCount.textContent = `${emoji} ${selectedFlower.rarity.toUpperCase()}! - Taps: ${tapDisplayValue}/${maxTapsForFlower}`;
    }
}

function renderSelectedFlowerAppearance() {
    if (!selectedFlower) {
        seed.textContent = '';
        return;
    }
    if (selectedFlower.type === 'legendary') {
        startLegendaryAnimation(selectedFlower.data);
    } else {
        if (animationInterval) {
            clearInterval(animationInterval);
            animationInterval = null;
        }
        seed.innerHTML = `<img src="assets/tiles/${selectedFlower.data}" alt="${selectedFlower.rarity} flower" />`;
    }
    
    // Apply rarity glow
    applyRarityGlow(selectedFlower.rarity);
}

function applyRarityGlow(rarity) {
    // Remove any existing rarity glow classes
    seed.classList.remove('rarity-common', 'rarity-rare', 'rarity-epic', 'rarity-legendary');
    
    // Add the appropriate glow class
    if (rarity) {
        seed.classList.add(`rarity-${rarity}`);
    }
}

function startLegendaryAnimation(flowerData) {
    let frame = 1;

    // Remove previous interval if any
    if(animationInterval) clearInterval(animationInterval);

    seed.innerHTML = `<img src="${flowerData.path}01.png" />`;

    animationInterval = setInterval(() => {
        frame++;
        if(frame > flowerData.frames) frame = 1; // loop animation
        seed.querySelector("img").src = `${flowerData.path}${frame.toString().padStart(2,'0')}.png`;
    }, 200);
}

function initializeCollectionTracker() {
    document.querySelectorAll('.collection-window .flower').forEach(card => {
        const img = card.querySelector('img');
        const label = card.querySelector('span');
        if (!img || !label) {
            return;
        }
        const src = img.getAttribute('src');
        if (!src) {
            return;
        }
        const name = label.textContent.trim();
        collectionCards.set(src, card);
        collectionNames.set(src, name);
    });
}

function getFlowerAssetPath(flowerResult) {
    if (!flowerResult) {
        return null;
    }
    if (flowerResult.type === 'legendary') {
        return `${flowerResult.data.path}01.png`;
    }
    return `assets/tiles/${flowerResult.data}`;
}

function updateCollectionCard(assetPath, count) {
    const card = collectionCards.get(assetPath);
    if (!card) {
        return;
    }
    card.classList.add('collected');
    let badge = card.querySelector('.collection-count');
    if (!badge) {
        badge = document.createElement('span');
        badge.className = 'collection-count';
        card.appendChild(badge);
    }
    badge.textContent = `x${count}`;
}

function collectCurrentFlower() {
    if (!selectedFlower) {
        return;
    }
    const assetPath = getFlowerAssetPath(selectedFlower);
    if (!assetPath) {
        return;
    }
    collectionCounts[assetPath] = (collectionCounts[assetPath] || 0) + 1;
    updateCollectionCard(assetPath, collectionCounts[assetPath]);
    const displayName = collectionNames.get(assetPath) || (selectedFlower.type === 'legendary' ? selectedFlower.data.name : selectedFlower.data);
    const emoji = rarityEmojis[selectedFlower.rarity] || '🌸';
    showTemporaryMessage(`${emoji} Collected ${displayName}! (x${collectionCounts[assetPath]})`);
    queueProgressSave();
}

function refreshTapCounter() {
    const currentStage = stages[Math.max(0, stage - 1)];
    if (!currentStage) {
        return;
    }
    tapCount.textContent = `Taps: ${taps}/${currentStage.maxTaps}`;
}

function showTemporaryMessage(message, duration = 2000) {
    tapCount.textContent = message;
    if (tapMessageTimeout) {
        clearTimeout(tapMessageTimeout);
    }
    tapMessageTimeout = setTimeout(() => {
        refreshTapCounter();
        tapMessageTimeout = null;
    }, duration);
}

function resetPlant({ delayCounter = false } = {}) {
    stage = 1;
    taps = 0;
    selectedFlower = null;
    // Clear rarity glow
    seed.classList.remove('rarity-common', 'rarity-rare', 'rarity-epic', 'rarity-legendary');
    updateStage({ rollForFlower: false, suppressCounter: delayCounter });
}

function getMaxTapsForStage3() {
    if (stage !== 3 || !selectedFlower) {
        return stages[2].maxTaps;
    }
    return stage3RarityTaps[selectedFlower.rarity] || stage3RarityTaps.common;
}

function getSelectedFlowerKey() {
    if (!selectedFlower) {
        return null;
    }
    if (selectedFlower.type === 'legendary') {
        return `legendary:${selectedFlower.data.name}`;
    }
    return selectedFlower.data;
}

function inferRarityFromAsset(fileName) {
    if (flowerTypes.legendary.flowers.some(flower => `${flower.path}01.png` === fileName)) {
        return 'legendary';
    }
    if (flowerTypes.epic.flowers.includes(fileName)) {
        return 'epic';
    }
    if (flowerTypes.rare.flowers.includes(fileName)) {
        return 'rare';
    }
    if (flowerTypes.common.flowers.includes(fileName)) {
        return 'common';
    }
    return 'common';
}

function hydrateSelectedFlowerFromKey(key, rarity) {
    if (!key) {
        return null;
    }
    if (key.startsWith('legendary:')) {
        const [, name] = key.split(':');
        const legendary = flowerTypes.legendary.flowers.find(flower => flower.name === name);
        if (!legendary) {
            return null;
        }
        return {
            type: 'legendary',
            rarity: 'legendary',
            data: legendary
        };
    }
    const derivedRarity = rarity || inferRarityFromAsset(key);
    return {
        type: 'common',
        rarity: derivedRarity,
        data: key
    };
}

function serializeCollectionCountsPayload() {
    const result = {};
    Object.entries(collectionCounts).forEach(([assetPath, count]) => {
        result[assetPath] = {
            count,
            label: collectionNames.get(assetPath) || ''
        };
    });
    return result;
}

function applyCollectionCountsFromServer(serverCounts) {
    Object.entries(serverCounts).forEach(([assetPath, payload]) => {
        const count = typeof payload === 'object' && payload !== null ? Number(payload.count) : Number(payload);
        if (Number.isNaN(count)) {
            return;
        }
        const label = (typeof payload === 'object' && payload !== null ? payload.label : null) || collectionNames.get(assetPath);
        if (label) {
            collectionNames.set(assetPath, label);
        }
        collectionCounts[assetPath] = count;
        updateCollectionCard(assetPath, count);
    });
}

function queueProgressSave() {
    if (!isProgressLoaded) {
        return;
    }
    if (saveProgressTimeout) {
        clearTimeout(saveProgressTimeout);
    }
    saveProgressTimeout = setTimeout(() => {
        saveProgressTimeout = null;
        saveProgress();
    }, SAVE_DEBOUNCE_MS);
}

async function saveProgress() {
    const payload = {
        stage,
        taps,
        selectedFlowerKey: getSelectedFlowerKey(),
        selectedFlowerRarity: selectedFlower?.rarity || null,
        collectionCounts: serializeCollectionCountsPayload()
    };

    try {
        const response = await fetch('progress_save.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        });
        if (!response.ok) {
            throw new Error('Failed to save progress');
        }
        
        const data = await response.json();
        if (data.newAchievements && data.newAchievements.length > 0) {
            setTimeout(() => showAchievementNotification(data.newAchievements), 1000);
        }
    } catch (error) {
        console.error('Unable to save progress', error);
    }
}

async function loadPlayerProgress() {
    try {
        const response = await fetch('progress_load.php');
        if (!response.ok) {
            throw new Error('Failed to load progress');
        }
        const data = await response.json();
        if (!data.success) {
            throw new Error(data.message || 'Invalid progress payload');
        }
		
        // ----------LOAD COINS -----------------
        coins = Number(data.coins) || 0;
		document.getElementById("coinAmount").innerText = coins.toFixed(2);
        
        // ----------LOAD TAP POWER -----------------
        tapPower = Number(data.tapPower) || 1;
        updateUpgradeUI();
        
        // ----------LOAD LUCK -----------------
        luck = Number(data.luck) || 0;
        updateLuckUI();
        
        // ------LOAD PLANT PROGRESS -------------
        const progress = data.progress || {};
        stage = Number(progress.current_stage) || 1;
        taps = Number(progress.current_taps) || 0;
        selectedFlower = hydrateSelectedFlowerFromKey(progress.current_flower, progress.current_flower_rarity);

        applyCollectionCountsFromServer(data.collectionCounts || {});

        if (stage < 1 || stage > stages.length) {
            stage = 1;
        }

        updateStage({ rollForFlower: false, preserveTaps: true });
    } catch (error) {
        console.warn('Falling back to fresh progress state', error);
        resetPlant();
    } finally {
        isProgressLoaded = true;
    }
}

// Fullscreen tap handler (except UI) 								this patch of code im testing makes almost the full screen clickable (MC)
plantContainer.addEventListener("click", (e) => {
    console.log("FULLSCREEN TAP TRIGGERED!", e.target);
    if (
        e.target.closest(".button-bar") ||
        e.target.closest(".logout-button") ||
        e.target.closest(".upgrade-window") ||
        e.target.closest(".shop-window") ||
        e.target.closest(".achievements-window") ||
        e.target.closest(".collection-window")
    ) {
        return; 
    }
    handleTap(e);
});


seed.addEventListener("click", (e) => {
    e.stopPropagation(); // prevents double tap
    handleTap(e);
});

// ========== KEYBOARD SUPPORT ==========
document.addEventListener("keydown", (e) => {
    // Ignore if typing in an input field
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
        return;
    }
    
    // Ignore if any modal window is open
    if (
        document.getElementById('upgradeWindow').style.display === 'flex' ||
        document.getElementById('shopWindow').style.display === 'flex' ||
        document.getElementById('achievementsWindow').style.display === 'flex' ||
        document.getElementById('collectionWindow').style.display === 'flex'
    ) {
        return;
    }
    
    // Spacebar or Enter to tap
    if (e.code === 'Space' || e.code === 'Enter') {
        e.preventDefault(); // Prevent page scroll on spacebar
        handleTap(null);
    }
});
// ========== END KEYBOARD SUPPORT ==========


function handleTap(event) {
    // 🎨 CLICK EFFECTS
    createClickEffects(event);

    // 1️⃣ ADD COINS
    coins += coinsPerTap;
    document.getElementById("coinAmount").innerText = coins.toFixed(2);

	queueCoinSave(); // <--- IMPORTANT

    // 2️⃣ GROWTH TAPS (now uses tap power!)
    taps += tapPower;
    const current = stages[stage - 1];
    
    // Get correct maxTaps for Stage 3 based on rarity
    const maxTaps = (stage === 3) ? getMaxTapsForStage3() : current.maxTaps;

    if (stage === 1 || stage === 2) {
        refreshTapCounter();
    } else if (stage === 3 && selectedFlower) {
        const emoji = rarityEmojis[selectedFlower.rarity] || '🌸';
        const rarityText = selectedFlower.rarity.toUpperCase();
        tapCount.textContent = `${emoji} ${rarityText}! - Taps: ${taps}/${maxTaps}`;
    }

    // 3️⃣ STAGE UP
    if (taps >= maxTaps) {
        stage++;

        if (stage <= stages.length) {
            updateStage();
            queueProgressSave();
        } else {
            collectCurrentFlower();
            resetPlant({ delayCounter: true });
            queueProgressSave();
        }
    } else {
        queueProgressSave();
    }
}
//===================Data base coin saving====
function queueCoinSave() {
    if (coinSaveTimeout) clearTimeout(coinSaveTimeout);

    coinSaveTimeout = setTimeout(() => {
        saveCoins();
    }, COIN_SAVE_DELAY);
}


function saveCoins() {
    fetch("update_coins.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "coins=" + coins
    })
    .then(res => res.text())
    .then(text => {
        if (text !== "OK") {
            console.warn("Coin save failed:", text);
        }
    })
    .catch(err => console.error("Coin save error:", err));
}

//=============================================================================



// ========== UPGRADE SYSTEM ==========

function calculateUpgradeCost(currentLevel) {
    // Same formula as PHP: 25 + (level * level * 5)
    const cost = 25 + (currentLevel * currentLevel * 5);
    return Math.round(cost * 100) / 100; // Round to 2 decimals
}

function updateUpgradeUI() {
    const currentLevel = tapPower;
    const maxLevel = 20;
    
    document.getElementById('currentTapLevel').textContent = currentLevel;
    document.getElementById('currentTapValue').textContent = currentLevel;
    
    if (currentLevel >= maxLevel) {
        document.getElementById('upgradeCost').textContent = 'MAX LEVEL';
        document.getElementById('buyUpgradeBtn').disabled = true;
        document.getElementById('buyUpgradeBtn').querySelector('.btn-text').textContent = 'MAX LEVEL';
        document.getElementById('buyUpgradeBtn').querySelector('.btn-subtext').textContent = 'Fully Upgraded!';
    } else {
        const cost = calculateUpgradeCost(currentLevel);
        document.getElementById('upgradeCost').textContent = cost.toFixed(2) + ' 💰';
        document.getElementById('buyUpgradeBtn').disabled = false;
        document.getElementById('buyUpgradeBtn').querySelector('.btn-text').textContent = 'UPGRADE';
        document.getElementById('buyUpgradeBtn').querySelector('.btn-subtext').textContent = '+1 Tap Power';
    }
    
    // Update progress bar
    const progressPercentage = (currentLevel / maxLevel) * 100;
    document.getElementById('upgradeProgressBar').style.width = progressPercentage + '%';
    document.getElementById('upgradeProgressText').textContent = `Level ${currentLevel} / ${maxLevel}`;
}

async function purchaseUpgrade() {
    const buyBtn = document.getElementById('buyUpgradeBtn');
    
    // Prevent double-clicking
    if (buyBtn.disabled) {
        return;
    }
    
    // Check if max level
    if (tapPower >= 20) {
        showTemporaryMessage('⚡ Already at MAX level!', 2000);
        return;
    }
    
    // Check if player has enough coins
    const cost = calculateUpgradeCost(tapPower);
    if (coins < cost) {
        showTemporaryMessage(`💰 Need ${cost.toFixed(2)} coins!`, 2000);
        return;
    }
    
    // Disable button during purchase
    buyBtn.disabled = true;
    const originalText = buyBtn.querySelector('.btn-text').textContent;
    buyBtn.querySelector('.btn-text').textContent = 'UPGRADING...';
    
    try {
        const response = await fetch('upgrade_purchase.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error('Failed to purchase upgrade');
        }
        
        const data = await response.json();
        
        if (data.success) {
            // Update local state
            tapPower = data.newTapPower;
            coins = data.newCoins;
            
            // Update UI
            document.getElementById('coinAmount').textContent = coins.toFixed(2);
            updateUpgradeUI();
            
            // Show success message as temporary overlay
            showTemporaryMessage(`⚡ UPGRADED! Tap Power: ${tapPower}`, 2000);
            
            // Show achievement notifications
            if (data.newAchievements && data.newAchievements.length > 0) {
                setTimeout(() => showAchievementNotification(data.newAchievements), 2100);
            }
            
            // Re-enable button
            buyBtn.disabled = false;
        } else {
            showTemporaryMessage(data.message || 'Upgrade failed', 2000);
            buyBtn.disabled = false;
            buyBtn.querySelector('.btn-text').textContent = originalText;
        }
    } catch (error) {
        console.error('Upgrade purchase error:', error);
        showTemporaryMessage('⚠️ Error purchasing upgrade', 2000);
        buyBtn.disabled = false;
        buyBtn.querySelector('.btn-text').textContent = originalText;
    }
}

// Add event listener for upgrade button
document.getElementById('buyUpgradeBtn').addEventListener('click', purchaseUpgrade);

// ========== END UPGRADE SYSTEM ==========

// ========== LUCK SYSTEM ==========

function calculateLuckCost(currentLevel) {
    // Same formula as PHP: 30 + (level * level * 6)
    const cost = 30 + (currentLevel * currentLevel * 6);
    return Math.round(cost * 100) / 100; // Round to 2 decimals
}

function updateLuckUI() {
    const currentLevel = luck;
    const maxLevel = 20;
    
    document.getElementById('currentLuckLevel').textContent = currentLevel;
    document.getElementById('currentLuckValue').textContent = (currentLevel + 1) + 'x';
    
    if (currentLevel >= maxLevel) {
        document.getElementById('luckCost').textContent = 'MAX LEVEL';
        document.getElementById('buyLuckBtn').disabled = true;
        document.getElementById('buyLuckBtn').querySelector('.btn-text').textContent = 'MAX LEVEL';
        document.getElementById('buyLuckBtn').querySelector('.btn-subtext').textContent = 'Fully Purchased!';
    } else {
        const cost = calculateLuckCost(currentLevel);
        document.getElementById('luckCost').textContent = cost.toFixed(2) + ' 💰';
        document.getElementById('buyLuckBtn').disabled = false;
        document.getElementById('buyLuckBtn').querySelector('.btn-text').textContent = 'BUY';
        document.getElementById('buyLuckBtn').querySelector('.btn-subtext').textContent = '+1 Luck';
    }
    
    // Update progress bar
    const progressPercentage = (currentLevel / maxLevel) * 100;
    document.getElementById('luckProgressBar').style.width = progressPercentage + '%';
    document.getElementById('luckProgressText').textContent = `Level ${currentLevel} / ${maxLevel}`;
}

async function purchaseLuck() {
    const buyBtn = document.getElementById('buyLuckBtn');
    
    // Prevent double-clicking
    if (buyBtn.disabled) {
        return;
    }
    
    // Check if max level
    if (luck >= 20) {
        showTemporaryMessage('🍀 Already at MAX luck!', 2000);
        return;
    }
    
    // Check if player has enough coins
    const cost = calculateLuckCost(luck);
    if (coins < cost) {
        showTemporaryMessage(`💰 Need ${cost.toFixed(2)} coins!`, 2000);
        return;
    }
    
    // Disable button during purchase
    buyBtn.disabled = true;
    const originalText = buyBtn.querySelector('.btn-text').textContent;
    buyBtn.querySelector('.btn-text').textContent = 'BUYING...';
    
    try {
        const response = await fetch('luck_purchase.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error('Failed to purchase luck');
        }
        
        const data = await response.json();
        
        if (data.success) {
            // Update local state
            luck = data.newLuck;
            coins = data.newCoins;
            
            // Update UI
            document.getElementById('coinAmount').textContent = coins.toFixed(2);
            updateLuckUI();
            
            // Show success message as temporary overlay
            showTemporaryMessage(`🍀 LUCK UP! Luck: ${luck}`, 2000);
            
            // Show achievement notifications
            if (data.newAchievements && data.newAchievements.length > 0) {
                setTimeout(() => showAchievementNotification(data.newAchievements), 2100);
            }
            
            // Re-enable button
            buyBtn.disabled = false;
        } else {
            showTemporaryMessage(data.message || 'Purchase failed', 2000);
            buyBtn.disabled = false;
            buyBtn.querySelector('.btn-text').textContent = originalText;
        }
    } catch (error) {
        console.error('Luck purchase error:', error);
        showTemporaryMessage('⚠️ Error purchasing luck', 2000);
        buyBtn.disabled = false;
        buyBtn.querySelector('.btn-text').textContent = originalText;
    }
}

// Add event listener for luck button
document.getElementById('buyLuckBtn').addEventListener('click', purchaseLuck);

// ========== END LUCK SYSTEM ==========

// ========== ACHIEVEMENT SYSTEM ==========

async function loadAchievements() {
    try {
        const response = await fetch('get_achievements.php');
        if (!response.ok) throw new Error('Failed to load achievements');
        
        const data = await response.json();
        console.log('Achievement data:', data);
        
        if (data.success) {
            renderAchievements(data.achievements);
        } else {
            console.error('Achievement error:', data.message);
            showFallbackAchievements();
        }
    } catch (error) {
        console.error('Failed to load achievements:', error);
        showFallbackAchievements();
    }
}

function showFallbackAchievements() {
    // Show all achievements as locked if fetch fails
    const fallback = [
        { title: 'First Bloom', description: 'Grew your first plant to completion!', icon: '🌱', unlocked: false },
        { title: 'First Tap Upgrade', description: 'Upgraded your tap power for the first time!', icon: '⚡', unlocked: false },
        { title: 'First Luck Boost', description: 'Purchased your first luck upgrade!', icon: '🍀', unlocked: false },
        { title: 'Max Tap Power', description: 'Reached maximum tap power level!', icon: '💪', unlocked: false },
        { title: 'Max Luck', description: 'Reached maximum luck level!', icon: '✨', unlocked: false },
        { title: 'Plant of Legend', description: 'Acquired a legendary plant for the first time!', icon: '🌟', unlocked: false },
        { title: 'Master Collector', description: 'Collected 100+ of every flower in the game!', icon: '🏆', unlocked: false },
        { title: 'Millionaire!', description: 'Accumulated 1,000,000 coins!', icon: '💰', unlocked: false }
    ];
    renderAchievements(fallback);
}

function renderAchievements(achievements) {
    const achievementList = document.querySelector('.achievement-list');
    
    if (!achievementList) {
        console.error('Achievement list element not found');
        return;
    }
    
    achievementList.innerHTML = '';
    
    if (!achievements || achievements.length === 0) {
        achievementList.innerHTML = '<p style="text-align:center;color:#fff;padding:20px;">No achievements available</p>';
        return;
    }
    
    achievements.forEach(ach => {
        const item = document.createElement('div');
        item.className = 'achievement-item' + (ach.unlocked ? ' unlocked' : ' locked');
        
        item.innerHTML = `
            <div class="achievement-icon">${ach.icon}</div>
            <div class="achievement-info">
                <h3>${ach.title}</h3>
                <p>${ach.description}</p>
                ${ach.unlocked ? '<span class="achievement-badge">✓ Unlocked</span>' : '<span class="achievement-badge locked-badge">🔒 Locked</span>'}
            </div>
        `;
        
        achievementList.appendChild(item);
    });
}

function showAchievementNotification(achievements) {
    if (!achievements || achievements.length === 0) return;
    
    const notification = document.getElementById('achievementNotification');
    
    achievements.forEach((ach, index) => {
        setTimeout(() => {
            notification.textContent = `🏆 Achievement Unlocked: ${ach.title}!`;
            notification.classList.add('show');
            
            setTimeout(() => {
                notification.classList.remove('show');
            }, 3000);
        }, index * 3500);
    });
}

// ========== END ACHIEVEMENT SYSTEM ==========

// ========== CLICK EFFECTS SYSTEM ==========

function createClickEffects(event) {
    // Get click position
    let x, y;
    if (event && event.clientX !== undefined) {
        x = event.clientX;
        y = event.clientY;
    } else {
        // Fallback to seed position
        const seedRect = seed.getBoundingClientRect();
        x = seedRect.left + seedRect.width / 2;
        y = seedRect.top + seedRect.height / 2;
    }

    // 1. Pulse animation on seed
    seed.classList.remove('click-pulse');
    void seed.offsetWidth; // Trigger reflow
    seed.classList.add('click-pulse');
    setTimeout(() => seed.classList.remove('click-pulse'), 150);

    // 2. Floating coin text
    createFloatingText(x, y, `+${coinsPerTap.toFixed(2)}`);

    // 3. Particle burst
    createParticles(x, y, 6);

    // 4. Ripple effect
    createRipple(x, y);
}

function createFloatingText(x, y, text) {
    const floatText = document.createElement('div');
    floatText.className = 'float-text';
    floatText.textContent = text;
    floatText.style.left = `${x}px`;
    floatText.style.top = `${y - 30}px`;
    document.body.appendChild(floatText);

    // Remove after animation
    setTimeout(() => floatText.remove(), 1000);
}

function createParticles(x, y, count) {
    const colors = ['#ffd700', '#ffec8b', '#fff8dc', '#90EE90', '#98FB98'];
    
    for (let i = 0; i < count; i++) {
        const particle = document.createElement('div');
        particle.className = 'tap-particle';
        
        // Random direction
        const angle = (Math.PI * 2 * i) / count;
        const distance = 40 + Math.random() * 30;
        const tx = Math.cos(angle) * distance;
        const ty = Math.sin(angle) * distance;
        
        particle.style.left = `${x - 4}px`;
        particle.style.top = `${y - 4}px`;
        particle.style.setProperty('--tx', `${tx}px`);
        particle.style.setProperty('--ty', `${ty}px`);
        particle.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
        
        document.body.appendChild(particle);
        setTimeout(() => particle.remove(), 600);
    }
}

function createRipple(x, y) {
    const ripple = document.createElement('div');
    ripple.className = 'tap-ripple';
    ripple.style.left = `${x - 75}px`;
    ripple.style.top = `${y - 75}px`;
    document.body.appendChild(ripple);
    
    setTimeout(() => ripple.remove(), 500);
}

// ========== END CLICK EFFECTS SYSTEM ==========

initializeCollectionTracker();
refreshTapCounter();
loadPlayerProgress();



/*seed.addEventListener("click", () => {
    taps++;
    const current = stages[stage - 1];
    
    // Update tap counter if still growing
    if (stage === 1) {
        tapCount.textContent = `Taps: ${taps}/${current.maxTaps}`;
    } else {
        tapCount.textContent = `Taps: ${taps}/${current.maxTaps}`;
    }

    if (taps >= current.maxTaps) {
        stage++;
        if (stage <= stages.length) {
            updateStage();
        } else {
            tapCount.textContent = "🌸 Fully Bloomed!"; // final stage message
        }
    }
});*/