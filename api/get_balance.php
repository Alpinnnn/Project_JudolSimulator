<?php
// File: api/get_balance.php

session_start();
require_once '../db_connect.php'; // Sesuaikan path sesuai struktur proyek Anda

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

$stmt = $pdo->prepare("SELECT balance FROM user WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if ($user) {
    echo json_encode(['balance' => $user['balance']]);
} else {
    echo json_encode(['error' => 'User not found']);
}