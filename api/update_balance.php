<?php
session_start();
require_once '../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $amount = floatval($_POST['amount']);
    
    $stmt = $pdo->prepare("UPDATE user SET balance = balance + ? WHERE id = ?");
    $result = $stmt->execute([$amount, $_SESSION['user_id']]);
    
    if ($result) {
        $stmt = $pdo->prepare("SELECT balance FROM user WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $newBalance = $stmt->fetchColumn();
        
        echo json_encode(['success' => true, 'newBalance' => $newBalance]);
    } else {
        echo json_encode(['success' => false]);
    }
} else {
    echo json_encode(['success' => false]);
}
?>