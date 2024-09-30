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
    } elseif (isset($_POST['selected_cards']) && isset($_POST['play'])) {
        $selectedCards = json_decode($_POST['selected_cards'], true);
        $betPerCard = floatval($_POST['bet_per_card']);
        $totalBet = count($selectedCards) * $betPerCard;

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
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choose The Card - Judol Simulator</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background-color: #121212;
            color: #ffffff;
            background-image: url('https://images.unsplash.com/photo-1477346611705-65d1883cee1e?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D');
            background-attachment: fixed;
            background-size: cover;
        }

        .glassmorphism {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .card {
            width: 100px;
            height: 150px;
            perspective: 1000px;
            cursor: pointer;
        }

        .card-inner {
            width: 100%;
            height: 100%;
            transition: transform 0.6s;
            transform-style: preserve-3d;
        }

        .card.flipped .card-inner {
            transform: rotateY(180deg);
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
            font-size: 24px;
            border-radius: 10px;
        }

        .card-front {
            background-color: #2c3e50;
            color: white;
        }

        .card-back {
            background-color: white;
            color: black;
            transform: rotateY(180deg);
        }

        .card.matched .card-back {
            background-color: #4CAF50;
            color: white;
        }

        .card.selected .card-inner {
            box-shadow: 0 0 10px 5px #ffd700;
        }
    </style>
</head>
</head>

<body class="min-h-screen">
    <div class="container mx-auto py-8">
        <h1 class="text-4xl font-bold mb-8 text-center">Guess The Card</h1>

        <?php if ($message): ?>
            <div class="bg-red-500 text-white p-4 mb-4 rounded"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="mb-4 glassmorphism p-4">
            <p>Balance: $<?php echo number_format($user['balance'], 2); ?></p>
            <p>XP: <?php echo $user['xp']; ?></p>
        </div>

        <?php if ($gameState === 'bet'): ?>
            <form method="POST" class="mb-4 glassmorphism p-4">
                <label for="bet_per_card" class="block mb-2">Taruhan per kartu:</label>
                <input type="number" id="bet_per_card" name="bet_per_card" min="1" step="0.01" required class="p-2 bg-gray-800 text-white rounded">
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded ml-2">Mulai</button>
            </form>
        <?php elseif ($gameState === 'select_cards'): ?>
            <form method="POST" id="card_selection_form" class="glassmorphism p-4">
                <input type="hidden" name="bet_per_card" value="<?php echo $betPerCard; ?>">
                <div class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 lg:grid-cols-10 gap-4 mb-4">
                    <?php
                    $suits = ['♠', '♥', '♦', '♣'];
                    $values = ['A', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K'];
                    foreach ($suits as $suit) {
                        foreach ($values as $value) {
                            $card = $value . $suit;
                            echo "<div class='card flipped' data-card='$card' onclick='toggleSelectCard(this)'>
                            <div class='card-inner'>
                                <div class='card-front'>?</div>
                                <div class='card-back'>$card</div>
                            </div>
                          </div>";
                        }
                    }
                    ?>
                </div>
                <input type="hidden" name="selected_cards" id="selected_cards">
                <button type="submit" name="play" value="1" class="bg-green-500 text-white px-4 py-2 rounded">Mainkan</button>
            </form>
        <?php elseif ($gameState === 'play'): ?>
            <div class="mb-4 glassmorphism p-4">
                <h2 class="text-2xl font-bold mb-2">Kartu Terpilih:</h2>
                <div class="flex flex-wrap justify-center mb-4">
                    <?php foreach ($selectedCards as $card): ?>
                        <div class="card" data-card="<?php echo $card; ?>" onclick="flipCard(this)">
                            <div class="card-inner">
                                <div class="card-front">?</div>
                                <div class="card-back"><?php echo $card; ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p>Total Taruhan: $<?php echo number_format($totalBet, 2); ?></p>
                <button id="revealAllBtn" class="bg-blue-500 text-white px-4 py-2 rounded mt-4">Buka Semua Kartu</button>
                <div id="resultContainer" class="mt-4 hidden">
                    <p>Kemenangan: $<span id="winningsAmount">0.00</span></p>
                    <p id="jackpotMessage" class="text-3xl font-bold text-yellow-400 hidden">JACKPOT!</p>
                </div>
            </div>
            <a href="guessthecard.php" class="bg-blue-500 text-white px-4 py-2 rounded">Main Lagi</a>
        <?php endif; ?>
    </div>

    <script>
        let selectedCards = [];
        let drawnCards = <?php echo json_encode($drawnCards ?? []); ?>;
        let winnings = <?php echo $winnings ?? 0; ?>;
        let totalBet = <?php echo $totalBet ?? 0; ?>;
        let revealedCount = 0;
        let gameState = '<?php echo $gameState; ?>';

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
            const cards = document.querySelectorAll('.card');
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
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const revealAllBtn = document.getElementById('revealAllBtn');
            if (revealAllBtn) {
                revealAllBtn.addEventListener('click', revealAllCards);
            }

            if (gameState === 'play') {
                // Initialize the game state for the play phase
                const cards = document.querySelectorAll('.card');
                cards.forEach(card => {
                    card.classList.remove('flipped');
                });
            }
        });
    </script>
</body>

</html>