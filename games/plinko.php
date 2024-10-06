<?php
session_start();
require_once '../db_connect.php'; // Sesuaikan path jika diperlukan

// Periksa apakah pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../accounts/login.php');
    exit();
}

// Fungsi untuk mengambil data pengguna
function getUserData($pdo, $userId)
{
    $stmt = $pdo->prepare("SELECT * FROM user WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Ambil data pengguna
$userData = getUserData($pdo, $_SESSION['user_id']);

// Fungsi untuk memperbarui saldo pengguna
function updateBalance($pdo, $userId, $amount)
{
    $stmt = $pdo->prepare("UPDATE user SET balance = balance + ? WHERE id = ?");
    return $stmt->execute([$amount, $userId]);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Realistic Plinko Game</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/matter-js/0.18.0/matter.min.js"></script>
    <style>
        #plinko-canvas {
            border: 2px solid #4a5568;
            margin: 0 auto;
            display: block;
        }

        #resultHistory {
            position: fixed;
            bottom: 20px;
            right: 20px;
            max-width: 200px;
            background-color: rgba(0, 0, 0, 0.7);
            padding: 10px;
            border-radius: 5px;
        }

        .result-item {
            transition: opacity 0.5s ease-out;
        }
    </style>
</head>

<body class="bg-gray-900 text-white">

    <body class="min-h-screen">
        <?php include '../includes/navbar.php'; ?>

        <div class="container mx-auto px-4 py-8">
            <h1 class="text-4xl font-bold mb-8 text-center text-white">Realistic Plinko Game</h1>

            <div class="max-w-md mx-auto glassmorphism p-6 mb-8">
                <h2 class="text-2xl font-semibold mb-4">Your Balance: $<span id="userBalance"><?php echo number_format($userData['balance'], 2); ?></span></h2>

                <form id="plinkoForm" class="mb-4">
                    <div class="mb-4">
                        <label for="bet_amount" class="block mb-2">Bet Amount:</label>
                        <input type="number" id="bet_amount" name="bet_amount" min="1" max="<?php echo $userData['balance']; ?>" step="0.01" required class="w-full px-3 py-2 bg-gray-700 rounded-md">
                    </div>
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Spawn Ball
                    </button>
                </form>
            </div>

            <div id="resultHistory"></div>

            <canvas id="plinko-canvas" width="400" height="600" class="glassmorphism"></canvas>
        </div>

        <script src="javascript/plinko.js"></script>
    </body>

</html>