<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- SEO Meta -->
    <title>JudolSimulator - Website Simulasi Judi Pertama! Judi Online Tanpa Risiko</title>
    <meta name="description" content="JudolSimulator: Simulasi judi online untuk edukasi. Pelajari strategi, manajemen risiko, dan teknik permainan dalam lingkungan yang aman.">
    <meta name="keywords" content="judol simulator, simulasi judi online, edukasi judi, poker virtual, slot gratis, roulette online, blackjack simulator, judol, JudolSimulator, simulator">
    <meta name="author" content="VoksiDoksi Sigma Team">

    <!-- Open Graph / Facebook -->
    <meta property="og:title" content="Simulasi Judi Online - Edukasi">
    <meta property="og:description" content="Simulasi judi online untuk edukasi. Pelajari strategi, manajemen risiko, dan teknik permainan dalam lingkungan yang aman.">
    <meta property="og:image" content="URL_GAMBAR_UTAMA_ANDA">
    <meta property="og:url" content="URL_WEBSITE_ANDA">
    <meta property="og:type" content="website">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Simulasi Judi Online - Edukasi">
    <meta name="twitter:description" content="Simulasi judi online untuk edukasi. Pelajari strategi, manajemen risiko, dan teknik permainan dalam lingkungan yang aman.">
    <meta name="twitter:image" content="URL_GAMBAR_UTAMA_ANDA">

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="includes/style.css">
    <style>

        .card-game {
            aspect-ratio: 16 / 9;
            width: 100%;
            max-width: 320px;
            transition: transform 0.3s ease;

            &:hover {
                transform: scale(1.05);
            }
        }

        .version {
            position: absolute;
            bottom: 20px;
            right: 20px;
        }
    </style>
    </style>
</head>

<body class="min-h-screen">
    <?php
    session_start();
require_once 'db_connect.php';
    include 'includes/navbar.php';
    ?>

    <header class="container mx-auto text-center py-16">
        <h1 class="text-4xl font-bold mb-4">Judol Simulator: Belajar Tentang Judi Online Tanpa Risiko</h1>
        <p class="text-xl">Edukasi dan Hiburan Tanpa Risiko Nyata</p>
    </header>

    <main class="container mx-auto py-8">
        <h2 class="text-2xl font-bold mb-4">Daftar Game</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <a href="games/guessthecard.php" class="glassmorphism card-game p-4 cursor-pointer">
                <h3 class="text-lg font-semibold mb-2">Guess The Card</h3>
                <p>Mainkan poker klasik dalam simulasi aman.</p>
            </a>
            <a href="games/plinko.php" class="glassmorphism card-game p-4 cursor-pointer">
                <h3 class="text-lg font-semibold mb-2">Plinko</h3>
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

    <p class="text-xl version">Alpha 1.0</p>

    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "SoftwareApplication",
            "name": "Judol Simulator",
            "url": "URL_WEBSITE_ANDA",
            "logo": "URL_LOGO_ANDA",
            "description": "JudolSimulator: Simulasi judi online untuk tujuan edukasi.",
            "applicationCategory": "EducationalApplication",
            "offers": {
                "@type": "Offer",
                "price": "0"
            },
            "aggregateRating": {
                "@type": "AggregateRating",
                "ratingValue": "4.8",
                "ratingCount": "1000"
            }
            "sameAs": [
                "https://www.facebook.com/PROFIL_FB_ANDA",
                "https://www.instagram.com/PROFIL_IG_ANDA",
                "https://www.twitter.com/PROFIL_TWITTER_ANDA"
            ]
        }
    </script>
</body>

</html>