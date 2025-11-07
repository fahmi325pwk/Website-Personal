<?php
session_start();
require 'includes/db.php';

// Pastikan user login
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

$order_id = intval($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];

// Cek apakah pesanan milik user dan status shipped
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();

if (!$order) {
  $_SESSION['error'] = "Pesanan tidak ditemukan.";
  header("Location: orders.php");
  exit();
}

if ($order['status'] !== 'shipped') {
  $_SESSION['error'] = "Pesanan tidak bisa ditandai diterima karena belum dikirim.";
  header("Location: orders.php");
  exit();
}

// Update status menjadi 'delivered'
$update = $pdo->prepare("UPDATE orders SET status = 'delivered' WHERE id = ?");
$update->execute([$order_id]);

$_SESSION['success'] = "Pesanan berhasil ditandai sebagai diterima.";
header("Location: orders.php");
exit();
?>
