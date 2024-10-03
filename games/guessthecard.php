<?php
session_start();
require_once '../db_connect.php';

// Fungsi untuk mendapatkan data user
function getUser($pdo, $userId)
{
    $stmt = $pdo->prepare("SELECT * FROM user WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

// Fungsi untuk mengupdate balance dan XP user
function updateUserStats($pdo, $userId, $balanceChange, $xpChange)
{
    $stmt = $pdo->prepare("UPDATE user SET balance = balance + ?, xp = xp + ? WHERE id = ?");
    $stmt->execute([$balanceChange, $xpChange, $userId]);
}

// Fungsi untuk mengacak kartu
function shuffleCards()
{
    $suits = ['♠', '♥', '♦', '♣'];
    $values = ['A', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K'];
    $deck = [];
    foreach ($suits as $suit) {
        foreach ($values as $value) {
            $deck[] = $value . $suit;
        }
    }
    shuffle($deck);
    return $deck;
}

// Inisialisasi variabel
$user = null;
$message = '';
$gameState = 'bet';
$selectedCards = [];
$drawnCards = [];
$betPerCard = 0;
$totalBet = 0;
$winnings = 0;
$isAutoSpin = false;
$autoSpinCount = 0;

// Cek apakah user sudah login
if (isset($_SESSION['user_id'])) {
    $user = getUser($pdo, $_SESSION['user_id']);
} else {
    header("Location: login.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['bet_per_card']) && !isset($_POST['play'])) {
        $betPerCard = floatval($_POST['bet_per_card']);
        $gameState = 'select_cards';
        $isAutoSpin = isset($_POST['auto_spin']) && $_POST['auto_spin'] == '1';
        $autoSpinCount = isset($_POST['auto_spin_count']) ? intval($_POST['auto_spin_count']) : 0;
    } elseif (isset($_POST['selected_cards']) && isset($_POST['play'])) {
        $selectedCards = json_decode($_POST['selected_cards'], true);
        $betPerCard = floatval($_POST['bet_per_card']);
        $totalBet = count($selectedCards) * $betPerCard;
        $isAutoSpin = isset($_POST['auto_spin']) && $_POST['auto_spin'] == '1';
        $autoSpinCount = isset($_POST['auto_spin_count']) ? intval($_POST['auto_spin_count']) : 0;

        if (empty($selectedCards)) {
            $message = "Silakan pilih setidaknya satu kartu.";
            $gameState = 'select_cards';
        } elseif ($totalBet > $user['balance']) {
            $message = "Saldo tidak mencukupi untuk taruhan ini.";
            $gameState = 'bet';
        } else {
            $gameState = 'play';
            $drawnCards = array_slice(shuffleCards(), 0, 5);

            // Hitung kemenangan
            $winningCards = array_intersect($selectedCards, $drawnCards);
            $winnings = count($winningCards) * ($betPerCard * 2);

            if (count($winningCards) === count($selectedCards) && count($selectedCards) > 0) {
                $winnings = $totalBet * 15; // Jackpot
            }

            // Update balance dan XP
            $balanceChange = $winnings - $totalBet;
            $xpChange = $totalBet;
            if ($winnings > 0) {
                $xpChange *= 2;
            }
            updateUserStats($pdo, $user['id'], $balanceChange, $xpChange);

            // Refresh user data
            $user = getUser($pdo, $user['id']);
        }
    }
}

function processGame($pdo, $user, $selectedCards, $betPerCard)
{
    $totalBet = count($selectedCards) * $betPerCard;
    $drawnCards = array_slice(shuffleCards(), 0, 5);

    // Hitung kemenangan
    $winningCards = array_intersect($selectedCards, $drawnCards);
    $winnings = count($winningCards) * ($betPerCard * 2);

    if (count($winningCards) === count($selectedCards) && count($selectedCards) > 0) {
        $winnings = $totalBet * 15; // Jackpot
    }

    // Update balance dan XP
    $balanceChange = $winnings - $totalBet;
    $xpChange = $totalBet;
    if ($winnings > 0) {
        $xpChange *= 2;
    }
    updateUserStats($pdo, $user['id'], $balanceChange, $xpChange);

    // Refresh user data
    $user = getUser($pdo, $user['id']);

    return [
        'user' => [
            'balance' => number_format($user['balance'], 2, '.', ''),
            'xp' => (string)$user['xp']
        ],
        'drawnCards' => $drawnCards,
        'winnings' => number_format($winnings, 2, '.', ''),
        'totalBet' => number_format($totalBet, 2, '.', '')
    ];
}

// Handle AJAX request for auto spin
if (isset($_POST['auto_spin_action'])) {
    $selectedCards = json_decode($_POST['selected_cards'], true);
    $betPerCard = floatval($_POST['bet_per_card']);
    $user = getUser($pdo, $_SESSION['user_id']);

    $result = processGame($pdo, $user, $selectedCards, $betPerCard);

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choose The Card - Judol Simulator</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="includes/style.css">
    <style>

        .glassmorphism {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .card {
            width: 70px;
            height: 105px;
            perspective: 1000px;
            cursor: pointer;
        }

        @media (min-width: 640px) {
            .card {
                width: 100px;
                height: 150px;
            }

            .card-front,
            .card-back {
                font-size: 24px;
            }
        }

        .card-inner {
            width: 100%;
            height: 100%;
            transition: transform 0.3s;
            transform-style: preserve-3d;
        }

        .card-front,
        .card-back {
            position: absolute;
            width: 100%;
            height: 100%;
            backface-visibility: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 18px;
            border-radius: 10px;
        }

        @media (min-width: 640px) {

            .card-front,
            .card-back {
                font-size: 24px;
            }
        }

        /* Styles for selection phase */
        .card-front {
            background-color: white;
            color: black;
        }

        .card-back {
            background-color: #2c3e50;
            color: white;
            transform: rotateY(180deg);
        }

        .card.selected {
            box-shadow: 0 0 10px 5px #ffd700;
        }

        /* Styles for play phase */
        .play-phase .card-front {
            background-color: #2c3e50;
            color: white;
        }

        .play-phase .card-back {
            background-color: white;
            color: black;
        }

        .play-phase .card.flipped .card-inner {
            transform: rotateY(180deg);
        }

        .play-phase .card.matched .card-back {
            background-color: #4CAF50;
            color: white;
        }
    </style>
</head>
</head>

<body class="min-h-screen">
<?php
    include '../includes/navbar.php';
    ?>
    <div class="container mx-auto py-8 px-4">
        <h1 class="text-4xl font-bold mb-8 text-center">Guess The Card</h1>

        <?php if ($message): ?>
            <div class="bg-red-500 text-white p-4 mb-4 rounded"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if ($gameState === 'bet'): ?>
            <form method="POST" class="mb-4 glassmorphism p-4">
                <label for="bet_per_card" class="block mb-2">Taruhan per kartu:</label>
                <input type="number" id="bet_per_card" name="bet_per_card" min="1" step="0.01" required class="p-2 bg-gray-800 text-white rounded w-full sm:w-auto">

                <div class="mt-4">
                    <input type="checkbox" id="auto_spin" name="auto_spin" value="1" class="mr-2">
                    <label for="auto_spin">Auto Spin</label>
                </div>

                <div id="auto_spin_options" class="mt-4 hidden">
                    <label for="auto_spin_count" class="block mb-2">Jumlah Auto Spin:</label>
                    <input type="number" id="auto_spin_count" name="auto_spin_count" min="1" class="p-2 bg-gray-800 text-white rounded w-full sm:w-auto">
                </div>

                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded mt-4 w-full sm:w-auto">Mulai</button>
            </form>
        <?php elseif ($gameState === 'select_cards'): ?>
            <form method="POST" id="card_selection_form" class="glassmorphism p-4">
                <input type="hidden" name="bet_per_card" value="<?php echo $betPerCard; ?>">
                <input type="hidden" name="auto_spin" id="hidden_auto_spin" value="<?php echo $isAutoSpin ? '1' : '0'; ?>">
                <input type="hidden" name="auto_spin_count" id="hidden_auto_spin_count" value="<?php echo $autoSpinCount; ?>">

                <div class="mb-4">
                    <label for="auto_select_count" class="block mb-2">Jumlah Pilih Otomatis:</label>
                    <input type="number" id="auto_select_count" min="1" max="52" class="p-2 bg-gray-800 text-white rounded w-full sm:w-auto">
                    <button type="button" id="auto_select_btn" class="bg-yellow-500 text-white px-4 py-2 rounded mt-2 sm:mt-0 sm:ml-2 w-full sm:w-auto">Pilih Otomatis</button>
                </div>

                <div class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 lg:grid-cols-10 gap-2 sm:gap-4 mb-4">
                    <?php
                    $suits = ['♠', '♥', '♦', '♣'];
                    $values = ['A', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K'];
                    foreach ($suits as $suit) {
                        foreach ($values as $value) {
                            $card = $value . $suit;
                            echo "<div class='card' data-card='$card' onclick='toggleSelectCard(this)'>
                            <div class='card-inner'>
                                <div class='card-front'>$card</div>
                                <div class='card-back'>?</div>
                            </div>
                          </div>";
                        }
                    }
                    ?>
                </div>
                <input type="hidden" name="selected_cards" id="selected_cards">
                <button type="submit" name="play" value="1" class="bg-green-500 text-white px-4 py-2 rounded w-full sm:w-auto">Mainkan</button>
            </form>
        <?php elseif ($gameState === 'play'): ?>
            <div class="mb-4 glassmorphism p-4 play-phase">
                <h2 class="text-2xl font-bold mb-2">Kartu Acak:</h2>
                <div class="flex flex-wrap justify-center gap-2 mb-4">
                    <?php foreach ($drawnCards as $card): ?>
                        <div class="card" data-card="<?php echo $card; ?>" onclick="flipCard(this)">
                            <div class="card-inner">
                                <div class="card-front">?</div>
                                <div class="card-back"><?php echo $card; ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <h2 class="text-2xl font-bold mb-2 mt-4">Kartu Terpilih:</h2>
                <div class="flex flex-wrap justify-center gap-2 mb-4">
                    <?php foreach ($selectedCards as $card): ?>
                        <div class="card selected" data-card="<?php echo $card; ?>">
                            <div class="card-inner">
                                <div class="card-front"><?php echo $card; ?></div>
                                <div class="card-back"><?php echo $card; ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <p>Total Taruhan: $<?php echo number_format($totalBet, 2); ?></p>
                <button id="revealAllBtn" class="bg-blue-500 text-white px-4 py-2 rounded mt-4 w-full sm:w-auto">Buka Semua Kartu</button>
                <div id="resultContainer" class="mt-4 hidden">
                    <p>Kemenangan: $<span id="winningsAmount">0.00</span></p>
                    <p id="jackpotMessage" class="text-3xl font-bold text-yellow-400 hidden">JACKPOT!</p>
                </div>
                <div id="autoSpinInfo" class="mt-4 <?php echo $isAutoSpin ? '' : 'hidden'; ?>">
                    <p>Sisa Auto Spin: <span id="remainingAutoSpins"><?php echo $autoSpinCount; ?></span></p>
                    <button id="restartAutoSpinBtn" class="bg-green-500 text-white px-4 py-2 rounded mt-4 w-full sm:w-auto">Restart Auto Spin</button>

                </div>
            </div>
            <a href="guessthecard.php" id="playAgainBtn" class="bg-blue-500 text-white px-4 py-2 rounded block text-center w-full sm:w-auto <?php echo $isAutoSpin ? 'hidden' : ''; ?>">Main Lagi</a>
        <?php endif; ?>
    </div>

    <script>
        let autoSpinCount = <?php echo $autoSpinCount; ?>;
        let initialAutoSpinCount = autoSpinCount;

        let selectedCards = <?php echo json_encode($selectedCards ?? []); ?>;
        let drawnCards = <?php echo json_encode($drawnCards ?? []); ?>;
        let winnings = <?php echo $winnings ?? 0; ?>;
        let totalBet = <?php echo $totalBet ?? 0; ?>;
        let revealedCount = 0;
        let gameState = '<?php echo $gameState; ?>';
        let isAutoSpin = <?php echo $isAutoSpin ? 'true' : 'false'; ?>;
        let betPerCard = <?php echo $betPerCard ?? 0; ?>;

        let totalOverallBet = 0;
        let totalOverallWinnings = 0;

        function toggleSelectCard(element) {
            if (gameState === 'select_cards') {
                element.classList.toggle('selected');
                const cardValue = element.dataset.card;
                const index = selectedCards.indexOf(cardValue);
                if (index > -1) {
                    selectedCards.splice(index, 1);
                } else {
                    selectedCards.push(cardValue);
                }
                updateSelectedCards();
            }
        }

        function updateSelectedCards() {
            document.getElementById('selected_cards').value = JSON.stringify(selectedCards);
        }

        function flipCard(element) {
            if (gameState === 'play' && !element.classList.contains('flipped')) {
                element.classList.add('flipped');
                revealedCount++;
                if (drawnCards.includes(element.dataset.card)) {
                    element.classList.add('matched');
                }
                checkGameEnd();
            }
        }

        function revealAllCards() {
            const cards = document.querySelectorAll('.play-phase .card');
            cards.forEach(card => {
                if (!card.classList.contains('flipped')) {
                    flipCard(card);
                }
            });
        }

        function checkGameEnd() {
            if (revealedCount === <?php echo count($selectedCards ?? []); ?>) {
                document.getElementById('resultContainer').classList.remove('hidden');
                document.getElementById('winningsAmount').textContent = winnings.toFixed(2);
                if (winnings === totalBet * 15) {
                    document.getElementById('jackpotMessage').classList.remove('hidden');
                }
                document.getElementById('revealAllBtn').style.display = 'none';

                if (isAutoSpin && autoSpinCount > 0) {
                    autoSpinCount--;
                    document.getElementById('remainingAutoSpins').textContent = autoSpinCount;
                    setTimeout(startNewGame, 2000);
                }
            }
        }

        function startNewGame() {
            if (autoSpinCount > 0) {
                // Reset total kemenangan dan taruhan untuk putaran baru
                totalOverallBet = 0;
                totalOverallWinnings = 0;
                updateTotalDisplay();

                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'guessthecard.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response && response.user && response.drawnCards) {
                                updateGameState(response);
                            } else {
                                throw new Error('Invalid response format');
                            }
                        } catch (error) {
                            console.error('Error processing response:', error);
                            alert('Terjadi kesalahan saat memproses respons dari server. Silakan coba lagi.');
                        }
                    } else {
                        console.error('Server returned status:', xhr.status);
                        alert('Terjadi kesalahan saat berkomunikasi dengan server. Silakan coba lagi.');
                    }
                };
                xhr.onerror = function() {
                    console.error('Request failed');
                    alert('Terjadi kesalahan jaringan. Silakan periksa koneksi Anda dan coba lagi.');
                };
                xhr.send('auto_spin_action=1&bet_per_card=' + betPerCard +
                    '&selected_cards=' + JSON.stringify(selectedCards));
            } else {
                document.getElementById('playAgainBtn').classList.remove('hidden');
            }
        }

        function updateGameState(response) {
            drawnCards = response.drawnCards;
            winnings = parseFloat(response.winnings);
            totalBet = parseFloat(response.totalBet);
            revealedCount = 0;

            // Update total keseluruhan
            totalOverallBet += totalBet;
            totalOverallWinnings += winnings;

            // Update UI
            document.querySelector('.mb-4.glassmorphism p:first-child').textContent = 'Balance: $' + response.user.balance;
            document.querySelector('.mb-4.glassmorphism p:last-child').textContent = 'XP: ' + response.user.xp;

            // Reset card display
            const cardContainer = document.querySelector('.flex.flex-wrap.justify-center.gap-2.mb-4');
            cardContainer.innerHTML = '';
            selectedCards.forEach(card => {
                cardContainer.innerHTML += `
                    <div class="card" data-card="${card}" onclick="flipCard(this)">
                        <div class="card-inner">
                            <div class="card-front">?</div>
                            <div class="card-back">${card}</div>
                        </div>
                    </div>
                `;
            });

            // Update result display
            updateTotalDisplay();
            document.getElementById('resultContainer').classList.remove('hidden');
            document.getElementById('jackpotMessage').classList.add('hidden');
            document.getElementById('revealAllBtn').style.display = 'none';

            // Update auto spin info
            autoSpinCount--;
            const remainingSpins = document.getElementById('remainingAutoSpins');
            if (remainingSpins) {
                remainingSpins.textContent = autoSpinCount;
            }

            // Update overall stats
            const completedSpins = initialAutoSpinCount - autoSpinCount;
            const overallStatsElement = document.getElementById('overallStats');
            if (overallStatsElement) {
                overallStatsElement.textContent = `Putaran ke-${completedSpins} dari ${initialAutoSpinCount}`;
            }

            // Automatically reveal all cards
            setTimeout(revealAllCards, 1000);

            // Start next game if auto spin is still active
            if (autoSpinCount > 0) {
                setTimeout(startNewGame, 2000);
            }
        }

        function updateTotalDisplay() {
            document.getElementById('winningsAmount').textContent = totalOverallWinnings.toFixed(2);
            document.getElementById('totalBetAmount').textContent = totalOverallBet.toFixed(2);
        }

        function initializeGame() {
            let selectedCards = <?php echo json_encode($selectedCards ?? []); ?>;
            let drawnCards = <?php echo json_encode($drawnCards ?? []); ?>;
            let winnings = <?php echo $winnings ?? 0; ?>;
            let totalBet = <?php echo $totalBet ?? 0; ?>;
            let revealedCount = 0;
            let gameState = '<?php echo $gameState; ?>';
            let isAutoSpin = <?php echo $isAutoSpin ? 'true' : 'false'; ?>;
            let autoSpinCount = <?php echo $autoSpinCount; ?>;
            let betPerCard = <?php echo $betPerCard ?? 0; ?>;

            // Tambahkan variabel baru untuk melacak total keseluruhan
            let totalOverallBet = 0;
            let totalOverallWinnings = 0;
            let initialAutoSpinCount = autoSpinCount;

            // Re-initialize all event listeners
            const revealAllBtn = document.getElementById('revealAllBtn');
            if (revealAllBtn) {
                revealAllBtn.addEventListener('click', revealAllCards);
            }

            // Auto-select functionality
            const autoSelectBtn = document.getElementById('auto_select_btn');
            const autoSelectCount = document.getElementById('auto_select_count');

            if (autoSelectBtn && autoSelectCount) {
                // Load saved count from localStorage
                const savedCount = localStorage.getItem('autoSelectCount');
                if (savedCount) {
                    autoSelectCount.value = savedCount;
                }

                autoSelectBtn.addEventListener('click', function() {
                    const count = parseInt(autoSelectCount.value);
                    if (isNaN(count) || count < 1 || count > 52) {
                        alert('Masukkan jumlah yang valid (1-52)');
                        return;
                    }

                    // Save count to localStorage
                    localStorage.setItem('autoSelectCount', count);

                    // Clear previous selection
                    selectedCards = [];
                    document.querySelectorAll('.card.selected').forEach(card => card.classList.remove('selected'));

                    // Randomly select cards
                    const allCards = Array.from(document.querySelectorAll('.card:not(.selected)'));
                    for (let i = 0; i < count && allCards.length > 0; i++) {
                        const randomIndex = Math.floor(Math.random() * allCards.length);
                        const selectedCard = allCards.splice(randomIndex, 1)[0];
                        toggleSelectCard(selectedCard);
                    }
                });
            }
        }

        function restartAutoSpin() {
            autoSpinCount = initialAutoSpinCount;
            document.getElementById('remainingAutoSpins').textContent = autoSpinCount;

            // Reset total keseluruhan
            totalOverallBet = 0;
            totalOverallWinnings = 0;
            updateTotalDisplay();

            // Mulai putaran baru
            startNewGame();
        }

        document.addEventListener('DOMContentLoaded', function() {
            const revealAllBtn = document.getElementById('revealAllBtn');
            if (revealAllBtn) {
                revealAllBtn.addEventListener('click', revealAllCards);
            }

            // Auto-select functionality
            const autoSelectBtn = document.getElementById('auto_select_btn');
            const autoSelectCount = document.getElementById('auto_select_count');

            if (autoSelectBtn && autoSelectCount) {
                // Load saved count from localStorage
                const savedCount = localStorage.getItem('autoSelectCount');
                if (savedCount) {
                    autoSelectCount.value = savedCount;
                }

                autoSelectBtn.addEventListener('click', function() {
                    const count = parseInt(autoSelectCount.value);
                    if (isNaN(count) || count < 1 || count > 52) {
                        alert('Masukkan jumlah yang valid (1-52)');
                        return;
                    }

                    // Save count to localStorage
                    localStorage.setItem('autoSelectCount', count);

                    // Clear previous selection
                    selectedCards = [];
                    document.querySelectorAll('.card.selected').forEach(card => card.classList.remove('selected'));

                    // Randomly select cards
                    const allCards = Array.from(document.querySelectorAll('.card:not(.selected)'));
                    for (let i = 0; i < count && allCards.length > 0; i++) {
                        const randomIndex = Math.floor(Math.random() * allCards.length);
                        const selectedCard = allCards.splice(randomIndex, 1)[0];
                        toggleSelectCard(selectedCard);
                    }
                });
            }

            // Auto Spin functionality
            const autoSpinCheckbox = document.getElementById('auto_spin');
            const autoSpinOptions = document.getElementById('auto_spin_options');
            const autoSpinCount = document.getElementById('auto_spin_count');
            const hiddenAutoSpin = document.getElementById('hidden_auto_spin');
            const hiddenAutoSpinCount = document.getElementById('hidden_auto_spin_count');
            const betPerCard = document.getElementById('bet_per_card');

            if (autoSpinCheckbox && autoSpinOptions && autoSpinCount) {
                // Load saved values from localStorage
                const savedAutoSpin = localStorage.getItem('autoSpin');
                const savedAutoSpinCount = localStorage.getItem('autoSpinCount');
                const savedBetPerCard = localStorage.getItem('betPerCard');

                if (savedAutoSpin) {
                    autoSpinCheckbox.checked = savedAutoSpin === 'true';
                    autoSpinOptions.style.display = autoSpinCheckbox.checked ? 'block' : 'none';
                }
                if (savedAutoSpinCount) {
                    autoSpinCount.value = savedAutoSpinCount;
                }
                if (savedBetPerCard) {
                    betPerCard.value = savedBetPerCard;
                }

                autoSpinCheckbox.addEventListener('change', function() {
                    autoSpinOptions.style.display = this.checked ? 'block' : 'none';
                    localStorage.setItem('autoSpin', this.checked);
                });

                autoSpinCount.addEventListener('input', function() {
                    localStorage.setItem('autoSpinCount', this.value);
                });

                betPerCard.addEventListener('input', function() {
                    localStorage.setItem('betPerCard', this.value);
                });
            }

            if (gameState === 'play' && isAutoSpin) {
                document.getElementById('autoSpinInfo').classList.remove('hidden');
                document.getElementById('playAgainBtn').style.display = 'none';
                revealAllCards();
            }

            // Update hidden inputs before form submission
            const cardSelectionForm = document.getElementById('card_selection_form');
            if (cardSelectionForm) {
                cardSelectionForm.addEventListener('submit', function() {
                    hiddenAutoSpin.value = autoSpinCheckbox.checked ? '1' : '0';
                    hiddenAutoSpinCount.value = autoSpinCount.value;
                });
            }

            const resultContainer = document.getElementById('resultContainer');
            if (resultContainer) {
                resultContainer.innerHTML += `
                    <p>Total Taruhan: $<span id="totalBetAmount">0.00</span></p>
                    <p id="overallStats"></p>
                `;
            }
            updateTotalDisplay();

            const restartAutoSpinBtn = document.getElementById('restartAutoSpinBtn');
            if (restartAutoSpinBtn) {
                restartAutoSpinBtn.addEventListener('click', restartAutoSpin);
            }

            initializeGame();
        });
    </script>

</body>

</html>