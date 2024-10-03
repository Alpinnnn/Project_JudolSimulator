<?php
session_start();
require_once '../db_connect.php'; // Sesuaikan path jika diperlukan

// Periksa apakah pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../accounts/login.php');
    exit();
}

// Fungsi untuk mengambil data pengguna
function getUserData($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT * FROM user WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Ambil data pengguna
$userData = getUserData($pdo, $_SESSION['user_id']);

// Fungsi untuk memperbarui saldo pengguna
function updateBalance($pdo, $userId, $amount) {
    $stmt = $pdo->prepare("UPDATE user SET balance = balance + ? WHERE id = ?");
    return $stmt->execute([$amount, $userId]);
}

// Fungsi untuk memperbarui XP pengguna
function updateXP($pdo, $userId, $xp) {
    $stmt = $pdo->prepare("UPDATE user SET xp = xp + ? WHERE id = ?");
    return $stmt->execute([$xp, $userId]);
}

// Logika permainan Plinko
$gameResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $betAmount = isset($_POST['bet_amount']) ? floatval($_POST['bet_amount']) : 0;
    
    if ($betAmount > 0 && $betAmount <= $userData['balance']) {
        // Simulasi jatuhnya bola Plinko
        $paths = 8; // Jumlah jalur
        $multipliers = [0, 0.5, 1, 1.5, 2, 3, 5, 10]; // Multiplikator untuk setiap jalur
        
        $finalPath = rand(0, $paths - 1);
        $winMultiplier = $multipliers[$finalPath];
        $winAmount = $betAmount * $winMultiplier;
        
        // Update saldo
        $profitLoss = $winAmount - $betAmount;
        updateBalance($pdo, $userData['id'], $profitLoss);
        
        // Update XP (1 XP per $1 bertaruh)
        $xpEarned = ceil($betAmount);
        updateXP($pdo, $userData['id'], $xpEarned);
        
        // Refresh data pengguna
        $userData = getUserData($pdo, $_SESSION['user_id']);
        
        $gameResult = [
            'path' => $finalPath,
            'multiplier' => $winMultiplier,
            'winAmount' => $winAmount,
            'profitLoss' => $profitLoss,
            'xpEarned' => $xpEarned
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plinko Game</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .plinko-board {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .plinko-row {
            display: flex;
            justify-content: center;
        }
        .plinko-peg {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background-color: #4a5568;
            margin: 10px;
        }
        .plinko-bucket {
            width: 60px;
            height: 40px;
            background-color: #4a5568;
            margin: 0 5px;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            font-weight: bold;
        }
    </style>
</head>
<body class="bg-gray-900 text-white">
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-4xl font-bold mb-8 text-center">Plinko Game</h1>
        
        <div class="max-w-md mx-auto bg-gray-800 rounded-lg shadow-lg p-6 mb-8">
            <h2 class="text-2xl font-semibold mb-4">Your Balance: $<?php echo number_format($userData['balance'], 2); ?></h2>
            
            <form method="POST" class="mb-4">
                <div class="mb-4">
                    <label for="bet_amount" class="block mb-2">Bet Amount:</label>
                    <input type="number" id="bet_amount" name="bet_amount" min="1" max="<?php echo $userData['balance']; ?>" step="0.01" required class="w-full px-3 py-2 bg-gray-700 rounded-md">
                </div>
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Play Plinko
                </button>
            </form>
        </div>
        
        <?php if ($gameResult): ?>
        <div class="max-w-md mx-auto bg-gray-800 rounded-lg shadow-lg p-6 mb-8">
            <h2 class="text-2xl font-semibold mb-4">Game Result</h2>
            <p>Ball landed in bucket: <?php echo $gameResult['path'] + 1; ?></p>
            <p>Multiplier: <?php echo $gameResult['multiplier']; ?>x</p>
            <p>Win Amount: $<?php echo number_format($gameResult['winAmount'], 2); ?></p>
            <p>Profit/Loss: $<?php echo number_format($gameResult['profitLoss'], 2); ?></p>
            <p>XP Earned: <?php echo $gameResult['xpEarned']; ?></p>
        </div>
        <?php endif; ?>
        
        <div class="plinko-board max-w-lg mx-auto bg-gray-800 rounded-lg shadow-lg p-6">
            <?php
            $rows = 7;
            for ($i = 0; $i < $rows; $i++) {
                echo '<div class="plinko-row">';
                for ($j = 0; $j <= $i; $j++) {
                    echo '<div class="plinko-peg"></div>';
                }
                echo '</div>';
            }
            ?>
            <div class="plinko-row">
                <?php
                $multipliers = [0, 0.5, 1, 1.5, 2, 3, 5, 10];
                foreach ($multipliers as $multiplier) {
                    echo "<div class='plinko-bucket'>{$multiplier}x</div>";
                }
                ?>
            </div>
        </div>
    </div>
    
    <script>
        // Anda dapat menambahkan animasi JavaScript di sini untuk mensimulasikan jatuhnya bola
    </script>
</body>
</html>