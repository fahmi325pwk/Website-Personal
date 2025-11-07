<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}
require '../includes/db.php';

$id = $_GET['id'];

try {
    $pdo->beginTransaction();
    
    // Check if product exists
    $stmt = $pdo->prepare("SELECT name FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        $_SESSION['error_message'] = "Produk tidak ditemukan.";
        header("Location: dashboard.php");
        exit;
    }
    
    // Check if product has existing orders
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM order_items WHERE product_id = ?");
    $stmt->execute([$id]);
    $orderCount = $stmt->fetch()['count'];
    
    if ($orderCount > 0) {
        // Product has existing orders - prevent deletion for data integrity
        $_SESSION['error_message'] = "Produk '{$product['name']}' tidak dapat dihapus karena memiliki {$orderCount} pesanan terkait. Untuk menjaga integritas data pesanan, silakan hapus pesanan terkait terlebih dahulu atau hubungi administrator.";
        $pdo->rollBack();
        header("Location: dashboard.php");
        exit;
    }
    
    // No existing orders, safe to delete
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() > 0) {
        $_SESSION['success_message'] = "Produk '{$product['name']}' berhasil dihapus.";
    } else {
        $_SESSION['error_message'] = "Gagal menghapus produk. Produk mungkin sudah tidak ada.";
    }
    
    $pdo->commit();
    
} catch (PDOException $e) {
    $pdo->rollBack();
    $_SESSION['error_message'] = "Terjadi kesalahan: " . $e->getMessage();
}

header("Location: dashboard.php");
exit;
