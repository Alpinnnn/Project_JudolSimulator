<?php
session_start();
require_once 'db_connect.php';

$user = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT u.*, r.name as role_name FROM user u JOIN role r ON u.role_id = r.id WHERE u.id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
}

// Function to get leaderboard data
function getLeaderboard($pdo, $type = 'balance', $limit = 10)
{
    $column = $type === 'xp' ? 'xp' : 'balance';
    $stmt = $pdo->query("SELECT username, $column as score FROM user ORDER BY $column DESC LIMIT $limit");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch leaderboard data
$balanceLeaderboard = getLeaderboard($pdo, 'balance');
$xpLeaderboard = getLeaderboard($pdo, 'xp');
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
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(10px);
            margin: auto;
            padding: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            width: 90%;
            max-width: 800px;
            border-radius: 10px;
        }

        .leaderboard-modal {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(10px);
        }

        .leaderboard-table {
            border-collapse: separate;
            border-spacing: 0 0.5rem;
        }

        .leaderboard-table tr {
            background: rgba(30, 41, 59, 0.7);
            transition: all 0.3s ease;
        }

        .leaderboard-table tr:hover {
            transform: scale(1.02);
            background: rgba(30, 41, 59, 0.9);
        }

        .leaderboard-table td,
        .leaderboard-table th {
            padding: 1rem;
            text-align: left;
        }

        .leaderboard-table td:first-child,
        .leaderboard-table th:first-child {
            border-top-left-radius: 0.5rem;
            border-bottom-left-radius: 0.5rem;
        }

        .leaderboard-table td:last-child,
        .leaderboard-table th:last-child {
            border-top-right-radius: 0.5rem;
            border-bottom-right-radius: 0.5rem;
        }

        #leaderboardModal {
            display: none;
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
                                    <a href="accounts/logout.php" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-600">Logout</a>
                                </div>
                            </div>
                        </div>
                        <span class="inline-block bg-green-500 text-white px-3 py-1 rounded-full text-sm">$<?php echo number_format($user['balance'], 2); ?></span>
                    <?php else: ?>
                        <a href="accounts/login.php" class="px-3 py-2 rounded-md text-sm font-medium bg-gray-700 hover:bg-gray-600 text-white mb-2 lg:mb-0 lg:mr-2">Login</a>
                        <a href="accounts/register.php" class="px-3 py-2 rounded-md text-sm font-medium bg-blue-600 hover:bg-blue-500 text-white">Register</a>
                    <?php endif; ?>
                    <?php if ($user && ($user['role_name'] === 'admin' || $user['role_name'] === 'moderator')): ?>
                        <a href="admin/panel.php" class="px-3 py-2 rounded-md text-sm font-medium bg-red-600 hover:bg-red-500 text-white mb-2 lg:mb-0 lg:mr-2">Admin Panel</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <div id="leaderboardModal" class="modal">
        <div class="modal-content leaderboard-modal w-full max-w-4xl">
            <h2 class="text-3xl font-bold mb-6 text-center text-white">Leaderboard</h2>
            <div class="mb-6 flex justify-center">
                <button id="balanceLeaderboardBtn" class="px-6 py-2 bg-blue-600 text-white rounded-l-md focus:outline-none">Balance</button>
                <button id="xpLeaderboardBtn" class="px-6 py-2 bg-gray-600 text-white rounded-r-md focus:outline-none">XP</button>
            </div>
            <div id="balanceLeaderboard">
                <h3 class="text-2xl font-semibold mb-4 text-center text-white">Top 10 Balance</h3>
                <table class="leaderboard-table w-full">
                    <thead>
                        <tr>
                            <th class="text-white">Rank</th>
                            <th class="text-white">Username</th>
                            <th class="text-white">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($balanceLeaderboard as $index => $user): ?>
                            <tr>
                                <td class="text-white"><?php echo $index + 1; ?></td>
                                <td class="text-white"><?php echo htmlspecialchars($user['username']); ?></td>
                                <td class="text-white">$<?php echo number_format($user['score'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div id="xpLeaderboard" class="hidden">
                <h3 class="text-2xl font-semibold mb-4 text-center text-white">Top 10 XP</h3>
                <table class="leaderboard-table w-full">
                    <thead>
                        <tr>
                            <th class="text-white">Rank</th>
                            <th class="text-white">Username</th>
                            <th class="text-white">XP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($xpLeaderboard as $index => $user): ?>
                            <tr>
                                <td class="text-white"><?php echo $index + 1; ?></td>
                                <td class="text-white"><?php echo htmlspecialchars($user['username']); ?></td>
                                <td class="text-white"><?php echo number_format($user['score']); ?> XP</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <button id="closeLeaderboardModal" class="mt-6 bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded focus:outline-none">
                Tutup
            </button>
        </div>
    </div>

    <header class="container mx-auto text-center py-16">
        <h1 class="text-4xl font-bold mb-4">Selamat Datang di Simulasi Judi Online</h1>
        <p class="text-xl">Edukasi dan Hiburan Tanpa Risiko Nyata</p>
    </header>

    <main class="container mx-auto py-8">
        <h2 class="text-2xl font-bold mb-4">Daftar Game</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <a href="games/guessthecard.php" class="glassmorphism card-game p-4 cursor-pointer">
                <h3 class="text-lg font-semibold mb-2">Guess The Card</h3>
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

    <div id="alertModal" class="modal" style="display: none;">
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
        document.addEventListener('DOMContentLoaded', function() {
            // Fungsi untuk memeriksa apakah elemen ada sebelum menambahkan event listener
            function addEventListenerIfElementExists(elementId, eventType, listener) {
                const element = document.getElementById(elementId);
                if (element) {
                    element.addEventListener(eventType, listener);
                }
            }

            // Modal peringatan
            const modal = document.getElementById('alertModal');
            const closeModal = document.getElementById('closeModal');
            const dontShowAgain = document.getElementById('dontShowAgain');

            // Fungsi untuk menampilkan modal
            function showModal() {
                if (modal) {
                    modal.style.display = 'block';
                }
            }

            // Fungsi untuk menyembunyikan modal
            function hideModal() {
                if (modal) {
                    modal.style.display = 'none';
                    if (dontShowAgain && dontShowAgain.checked) {
                        localStorage.setItem('alertDismissed', 'true');
                    }
                }
            }

            // Cek apakah modal sudah pernah ditampilkan sebelumnya
            if (!localStorage.getItem('alertDismissed')) {
                showModal();
            }

            // Event listener untuk tombol tutup
            if (closeModal) {
                closeModal.addEventListener('click', hideModal);
            }

            // Event listener untuk klik di luar modal
            window.addEventListener('click', function(event) {
                if (event.target === modal) {
                    hideModal();
                }
            });

            // Responsive navbar
            addEventListenerIfElementExists('menuToggle', 'click', () => {
                const navMenu = document.getElementById('navMenu');
                if (navMenu) {
                    navMenu.classList.toggle('hidden');
                }
            });

            // Games menu
            addEventListenerIfElementExists('gamesButton', 'click', () => {
                const gamesMenu = document.getElementById('gamesMenu');
                if (gamesMenu) {
                    gamesMenu.classList.toggle('hidden');
                }
            });

            // Profile menu
            addEventListenerIfElementExists('profileButton', 'click', () => {
                const profileMenu = document.getElementById('profileMenu');
                if (profileMenu) {
                    profileMenu.classList.toggle('hidden');
                }
            });

            // Leaderboard
            const leaderboardModal = document.getElementById('leaderboardModal');
            addEventListenerIfElementExists('leaderboardButton', 'click', () => {
                if (leaderboardModal) {
                    leaderboardModal.style.display = 'block';
                }
            });

            addEventListenerIfElementExists('closeLeaderboardModal', 'click', () => {
                if (leaderboardModal) {
                    leaderboardModal.style.display = 'none';
                }
            });

            addEventListenerIfElementExists('balanceLeaderboardBtn', 'click', () => {
                const balanceLeaderboard = document.getElementById('balanceLeaderboard');
                const xpLeaderboard = document.getElementById('xpLeaderboard');
                const balanceLeaderboardBtn = document.getElementById('balanceLeaderboardBtn');
                const xpLeaderboardBtn = document.getElementById('xpLeaderboardBtn');
                if (balanceLeaderboard && xpLeaderboard && balanceLeaderboardBtn && xpLeaderboardBtn) {
                    balanceLeaderboard.classList.remove('hidden');
                    xpLeaderboard.classList.add('hidden');
                    balanceLeaderboardBtn.classList.add('bg-blue-600');
                    balanceLeaderboardBtn.classList.remove('bg-gray-600');
                    xpLeaderboardBtn.classList.add('bg-gray-600');
                    xpLeaderboardBtn.classList.remove('bg-blue-600');
                }
            });

            addEventListenerIfElementExists('xpLeaderboardBtn', 'click', () => {
                const balanceLeaderboard = document.getElementById('balanceLeaderboard');
                const xpLeaderboard = document.getElementById('xpLeaderboard');
                const balanceLeaderboardBtn = document.getElementById('balanceLeaderboardBtn');
                const xpLeaderboardBtn = document.getElementById('xpLeaderboardBtn');
                if (balanceLeaderboard && xpLeaderboard && balanceLeaderboardBtn && xpLeaderboardBtn) {
                    xpLeaderboard.classList.remove('hidden');
                    balanceLeaderboard.classList.add('hidden');
                    xpLeaderboardBtn.classList.add('bg-blue-600');
                    xpLeaderboardBtn.classList.remove('bg-gray-600');
                    balanceLeaderboardBtn.classList.add('bg-gray-600');
                    balanceLeaderboardBtn.classList.remove('bg-blue-600');
                }
            });

            window.addEventListener('click', (event) => {
                if (event.target == leaderboardModal) {
                    leaderboardModal.style.display = 'none';
                }
            });
        });
    </script>
</body>

</html>