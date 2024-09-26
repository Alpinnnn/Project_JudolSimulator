<?php
session_start();
require_once '../db_connect.php';

// Check if user is logged in and has admin or moderator role
function checkAdminAccess($pdo) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: accounts/login.php");
        exit();
    }

    $stmt = $pdo->prepare("SELECT u.*, r.name as role_name FROM user u JOIN role r ON u.role_id = r.id WHERE u.id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if ($user['role_name'] !== 'admin' && $user['role_name'] !== 'moderator') {
        header("Location: /");
        exit();
    }

    return $user;
}

$user = checkAdminAccess($pdo);

// Handle user search
$searchResult = null;
if (isset($_POST['search'])) {
    $searchTerm = $_POST['search'];
    $stmt = $pdo->prepare("SELECT u.*, r.name as role_name FROM user u JOIN role r ON u.role_id = r.id WHERE u.username LIKE ?");
    $stmt->execute(["%$searchTerm%"]);
    $searchResult = $stmt->fetchAll();
}

// Handle user ban/unban
if (isset($_POST['ban_user']) && $user['role_name'] === 'admin' || ($user['role_name'] === 'moderator' && $_POST['user_role'] === 'player')) {
    $userId = $_POST['user_id'];
    $isBanned = $_POST['is_banned'];
    $stmt = $pdo->prepare("UPDATE user SET is_banned = ? WHERE id = ?");
    $stmt->execute([$isBanned, $userId]);
}

// Handle user delete (admin only)
if (isset($_POST['delete_user']) && $user['role_name'] === 'admin') {
    $userId = $_POST['user_id'];
    $stmt = $pdo->prepare("DELETE FROM user WHERE id = ?");
    $stmt->execute([$userId]);
}

// Handle user edit (admin only)
if (isset($_POST['edit_user']) && $user['role_name'] === 'admin') {
    $userId = $_POST['user_id'];
    $username = $_POST['username'];
    $balance = $_POST['balance'];
    $xp = $_POST['xp'];
    $stmt = $pdo->prepare("UPDATE user SET username = ?, balance = ?, xp = ? WHERE id = ?");
    $stmt->execute([$username, $balance, $xp, $userId]);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8">Admin Panel</h1>
        
        <form method="POST" class="mb-8">
            <input type="text" name="search" placeholder="Search users" class="px-4 py-2 border rounded">
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Search</button>
        </form>

        <?php if ($searchResult): ?>
            <table class="w-full bg-white shadow-md rounded mb-8">
                <thead>
                    <tr>
                        <th class="border-b p-2">Username</th>
                        <th class="border-b p-2">Role</th>
                        <th class="border-b p-2">Balance</th>
                        <th class="border-b p-2">XP</th>
                        <th class="border-b p-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($searchResult as $result): ?>
                        <tr>
                            <td class="border-b p-2"><?php echo htmlspecialchars($result['username']); ?></td>
                            <td class="border-b p-2"><?php echo htmlspecialchars($result['role_name']); ?></td>
                            <td class="border-b p-2">$<?php echo number_format($result['balance'], 2); ?></td>
                            <td class="border-b p-2"><?php echo number_format($result['xp']); ?></td>
                            <td class="border-b p-2">
                                <?php if ($user['role_name'] === 'admin' || ($user['role_name'] === 'moderator' && $result['role_name'] === 'player')): ?>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="user_id" value="<?php echo $result['id']; ?>">
                                        <input type="hidden" name="user_role" value="<?php echo $result['role_name']; ?>">
                                        <input type="hidden" name="is_banned" value="<?php echo $result['is_banned'] ? '0' : '1'; ?>">
                                        <button type="submit" name="ban_user" class="bg-<?php echo $result['is_banned'] ? 'green' : 'red'; ?>-500 text-white px-2 py-1 rounded"><?php echo $result['is_banned'] ? 'Unban' : 'Ban'; ?></button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($user['role_name'] === 'admin'): ?>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="user_id" value="<?php echo $result['id']; ?>">
                                        <button type="submit" name="delete_user" class="bg-red-500 text-white px-2 py-1 rounded ml-2" onclick="return confirm('Are you sure you want to delete this user?')">Delete</button>
                                    </form>
                                    <button onclick="showEditForm(<?php echo htmlspecialchars(json_encode($result)); ?>)" class="bg-yellow-500 text-white px-2 py-1 rounded ml-2">Edit</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <div id="editForm" class="hidden bg-white p-4 rounded shadow-md">
            <h2 class="text-xl font-bold mb-4">Edit User</h2>
            <form method="POST">
                <input type="hidden" name="user_id" id="editUserId">
                <div class="mb-4">
                    <label for="editUsername" class="block mb-2">Username:</label>
                    <input type="text" id="editUsername" name="username" class="w-full px-2 py-1 border rounded">
                </div>
                <div class="mb-4">
                    <label for="editBalance" class="block mb-2">Balance:</label>
                    <input type="number" id="editBalance" name="balance" step="0.01" class="w-full px-2 py-1 border rounded">
                </div>
                <div class="mb-4">
                    <label for="editXP" class="block mb-2">XP:</label>
                    <input type="number" id="editXP" name="xp" class="w-full px-2 py-1 border rounded">
                </div>
                <button type="submit" name="edit_user" class="bg-blue-500 text-white px-4 py-2 rounded">Save Changes</button>
                <button type="button" onclick="hideEditForm()" class="bg-gray-500 text-white px-4 py-2 rounded ml-2">Cancel</button>
            </form>
        </div>
    </div>

    <script>
        function showEditForm(user) {
            document.getElementById('editForm').classList.remove('hidden');
            document.getElementById('editUserId').value = user.id;
            document.getElementById('editUsername').value = user.username;
            document.getElementById('editBalance').value = user.balance;
            document.getElementById('editXP').value = user.xp;
        }

        function hideEditForm() {
            document.getElementById('editForm').classList.add('hidden');
        }
    </script>
</body>
</html>