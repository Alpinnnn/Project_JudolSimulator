<?php
session_start();
require_once '../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "Password tidak cocok.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM user WHERE username = ?");
        $stmt->execute([$username]);

        if ($stmt->rowCount() > 0) {
            $error = "Username sudah digunakan.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO user (username, password) VALUES (?, ?)");

            if ($stmt->execute([$username, $hashed_password])) {
                $_SESSION['user_id'] = $pdo->lastInsertId();
                $_SESSION['username'] = $username;
                header("Location: /");
                exit;
            } else {
                $error = "Terjadi kesalahan saat mendaftar.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - Simulasi Judi Online</title>
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
    </style>
</head>

<body class="min-h-screen flex items-center justify-center">
    <div class="glassmorphism p-8 w-full max-w-md">
        <h2 class="text-2xl font-bold mb-6 text-center">Registrasi</h2>
        <?php if (isset($error)): ?>
            <div class="bg-red-500 text-white p-3 rounded mb-4"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="mb-4">
                <label for="username" class="block mb-2">Username</label>
                <input type="text" id="username" name="username" required class="w-full p-2 rounded bg-gray-700 text-white">
            </div>
            <div class="mb-4">
                <label for="password" class="block mb-2">Password</label>
                <input type="password" id="password" name="password" required class="w-full p-2 rounded bg-gray-700 text-white">
            </div>
            <div class="mb-6">
                <label for="confirm_password" class="block mb-2">Konfirmasi Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required class="w-full p-2 rounded bg-gray-700 text-white">
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Daftar
            </button>
        </form>
        <p class="mt-4 text-center">Sudah punya akun? <a href="login.php" class="text-blue-400 hover:underline">Login di sini</a></p>
    </div>
</body>

</html>
<!--  -->