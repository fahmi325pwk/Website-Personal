<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}
require '../includes/db.php';

$id = $_GET['id'];
$force_delete = isset($_GET['force']) && $_GET['force'] === '1';

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
    
    if ($orderCount > 0 && !$force_delete) {
        // Product has existing orders - show confirmation page
        ?>
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Konfirmasi Hapus Produk | Nano Komputer</title>
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
            <link rel="stylesheet" href="../assets/css/admin-style.css">
        </head>
        <body>
            <div class="main-content" style="margin-left: 0; padding: 20px;">
                <div class="card" style="max-width: 600px; margin: 50px auto;">
                    <div class="card-header">
                        <h3><i class="fas fa-exclamation-triangle"></i> Konfirmasi Hapus Produk</h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-error" style="margin-bottom: 20px;">
                            <i class="fas fa-warning"></i>
                            <strong>Peringatan:</strong> Produk "<?= htmlspecialchars($product['name']) ?>" memiliki <?= $orderCount ?> pesanan terkait.
                        </div>
                        
                        <p>Anda memiliki beberapa opsi:</p>
                        <ul style="margin: 20px 0; padding-left: 20px;">
                            <li><strong>Batal:</strong> Kembali ke dashboard tanpa menghapus produk</li>
                            <li><strong>Hapus Paksa:</strong> Hapus produk dan semua data pesanan terkait (TIDAK DIREKOMENDASIKAN)</li>
                        </ul>
                        
                        <div style="display: flex; gap: 10px; margin-top: 30px;">
                            <a href="dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Batal
                            </a>
                            <a href="delete_product_advanced.php?id=<?= $id ?>&force=1" 
                               class="btn btn-danger" 
                               onclick="return confirm('PERINGATAN: Tindakan ini akan menghapus produk dan SEMUA data pesanan terkait. Apakah Anda yakin?')">
                                <i class="fas fa-trash"></i> Hapus Paksa
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
        $pdo->rollBack();
        exit;
    }
    
    if ($orderCount > 0 && $force_delete) {
        // Force delete - delete order_items first, then product
        $stmt = $pdo->prepare("DELETE FROM order_items WHERE product_id = ?");
        $stmt->execute([$id]);
        
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
        
        $_SESSION['success_message'] = "Produk '{$product['name']}' dan {$orderCount} data pesanan terkait berhasil dihapus.";
        
    } else {
        // No existing orders, safe to delete
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
        
        $_SESSION['success_message'] = "Produk '{$product['name']}' berhasil dihapus.";
    }
    
    $pdo->commit();
    
} catch (PDOException $e) {
    $pdo->rollBack();
    $_SESSION['error_message'] = "Terjadi kesalahan: " . $e->getMessage();
}

header("Location: dashboard.php");
exit;
