<?php
session_start();
require_once 'db_connect.php';

$user = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM user WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
}

// Fetch leaderboard data
$stmt = $pdo->query("SELECT username, balance FROM user ORDER BY balance DESC LIMIT 10");
$leaderboard = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simulasi Judi Online - Edukasi</title>
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

        .card-game {
            aspect-ratio: 16 / 9;
            width: 100%;
            max-width: 320px;
            transition: transform 0.3s ease;

            &:hover {
                transform: scale(1.05);
            }
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);

            &-content {
                background: rgba(30, 30, 30, 0.9);
                backdrop-filter: blur(10px);
                margin: 15% auto;
                padding: 20px;
                border: 1px solid rgba(255, 255, 255, 0.1);
                width: 80%;
                max-width: 500px;
                border-radius: 10px;
                color: #ffffff;
            }
        }
    </style>
    </style>
</head>

<body class="min-h-screen">
    <nav class="glassmorphism p-4 sticky top-0 z-50">
        <div class="container mx-auto">
            <div class="flex flex-wrap justify-between items-center">
                <div class="text-xl font-bold">Logo</div>
                <button id="menuToggle" class="lg:hidden">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
                    </svg>
                </button>
                <div id="navMenu" class="hidden lg:flex flex-col lg:flex-row items-center w-full lg:w-auto mt-4 lg:mt-0">
                    <div class="relative inline-block text-left mb-2 lg:mb-0 lg:mr-4">
                        <button id="leaderboardButton" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-800 focus:ring-white">
                            Leaderboard
                        </button>
                    </div>
                    <div class="relative inline-block text-left mb-2 lg:mb-0 lg:mr-4">
                        <button id="gamesButton" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-800 focus:ring-white">
                            Games
                        </button>
                        <div id="gamesMenu" class="hidden absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-gray-700 ring-1 ring-black ring-opacity-5">
                            <div class="py-1" role="menu" aria-orientation="vertical" aria-labelledby="options-menu">
                                <a href="#" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-600" role="menuitem">Poker</a>
                                <a href="#" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-600" role="menuitem">Slot</a>
                                <a href="#" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-600" role="menuitem">Roulette</a>
                            </div>
                        </div>
                    </div>
                    <div class="relative inline-block text-left mb-2 lg:mb-0 lg:mr-4">
                        <button id="giveawayButton" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-800 focus:ring-white">
                            Giveaway
                        </button>
                    </div>
                    <?php if ($user): ?>
                        <div class="relative inline-block text-left mb-2 lg:mb-0 lg:mr-4">
                            <button id="profileButton" class="flex items-center justify-center w-10 h-10 rounded-full bg-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-800 focus:ring-white">
                                <span class="text-xl font-bold"><?php echo strtoupper(substr($user['username'], 0, 1)); ?></span>
                            </button>
                            <div id="profileMenu" class="hidden absolute right-0 w-48 mt-2 origin-top-right bg-gray-700 divide-y divide-gray-600 rounded-md shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                                <div class="px-4 py-3">
                                    <p class="text-sm leading-5 text-gray-300">XP: <?php echo $user['xp']; ?></p>
                                    <p class="text-sm leading-5 text-gray-300">Level: <?php echo floor($user['xp'] / 1000) + 1; ?></p>
                                </div>
                                <div class="py-1">
                                    <a href="logout.php" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-600">Logout</a>
                                </div>
                            </div>
                        </div>
                        <span class="inline-block bg-green-500 text-white px-3 py-1 rounded-full text-sm">$<?php echo number_format($user['balance'], 2); ?></span>
                    <?php else: ?>
                        <a href="accounts/login.php" class="px-3 py-2 rounded-md text-sm font-medium bg-gray-700 hover:bg-gray-600 text-white mb-2 lg:mb-0 lg:mr-2">Login</a>
                        <a href="accounts/register.php" class="px-3 py-2 rounded-md text-sm font-medium bg-blue-600 hover:bg-blue-500 text-white">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <header class="container mx-auto text-center py-16">
        <h1 class="text-4xl font-bold mb-4">Selamat Datang di Simulasi Judi Online</h1>
        <p class="text-xl">Edukasi dan Hiburan Tanpa Risiko Nyata</p>
    </header>

    <main class="container mx-auto py-8">
        <h2 class="text-2xl font-bold mb-4">Daftar Game</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <a href="/poker" class="glassmorphism card-game p-4 cursor-pointer">
                <h3 class="text-lg font-semibold mb-2">Poker</h3>
                <p>Mainkan poker klasik dalam simulasi aman.</p>
            </a>
            <a href="/slot" class="glassmorphism card-game p-4 cursor-pointer">
                <h3 class="text-lg font-semibold mb-2">Slot</h3>
                <p>Putar slot virtual tanpa risiko kehilangan uang sungguhan.</p>
            </a>
            <a href="/roulette" class="glassmorphism card-game p-4 cursor-pointer">
                <h3 class="text-lg font-semibold mb-2">Roulette</h3>
                <p>Nikmati keseruan roulette dalam lingkungan simulasi.</p>
            </a>
            <a href="/blackjack" class="glassmorphism card-game p-4 cursor-pointer">
                <h3 class="text-lg font-semibold mb-2">Blackjack</h3>
                <p>Uji strategi blackjack Anda tanpa risiko.</p>
            </a>
        </div>
    </main>

    <div id="alertModal" class="modal">
        <div class="modal-content">
            <h2 class="text-xl font-bold mb-4">Peringatan</h2>
            <p>Website ini hanya untuk hiburan dan edukasi, tidak ada data yang diambil berdasarkan dunia nyata.</p>
            <div class="mt-4">
                <label class="inline-flex items-center">
                    <input type="checkbox" id="dontShowAgain" class="form-checkbox bg-gray-700 border-gray-600 text-blue-600">
                    <span class="ml-2">Jangan tampilkan lagi</span>
                </label>
            </div>
            <button id="closeModal" class="mt-4 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Mengerti
            </button>
        </div>
    </div>

    <script>
        // Responsive navbar
        const menuToggle = document.getElementById('menuToggle');
        const navMenu = document.getElementById('navMenu');
        menuToggle.addEventListener('click', () => {
            navMenu.classList.toggle('hidden');
        });

        // Games menu
        const gamesButton = document.getElementById('gamesButton');
        const gamesMenu = document.getElementById('gamesMenu');
        gamesButton.addEventListener('click', () => {
            gamesMenu.classList.toggle('hidden');
        });

        // Profile menu
        const profileButton = document.getElementById('profileButton');
        const profileMenu = document.getElementById('profileMenu');
        profileButton.addEventListener('click', () => {
            profileMenu.classList.toggle('hidden');
        });

        // Alert modal
        const modal = document.getElementById('alertModal');
        const closeModal = document.getElementById('closeModal');
        const dontShowAgain = document.getElementById('dontShowAgain');

        if (!localStorage.getItem('alertDismissed')) {
            modal.style.display = 'block';
        }

        closeModal.addEventListener('click', () => {
            modal.style.display = 'none';
            if (dontShowAgain.checked) {
                localStorage.setItem('alertDismissed', 'true');
            }
        });

        window.addEventListener('click', (event) => {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        });
    </script>
</body>

</html>