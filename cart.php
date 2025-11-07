<?php
session_start();
require 'includes/db.php';
include 'includes/header.php';

// Inisialisasi keranjang jika belum ada
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// === TAMBAH PRODUK KE KERANJANG ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity'] ?? 1);

    // Ambil data produk dari database
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        // Jika produk sudah ada di keranjang → tambah jumlahnya
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]['quantity'] += $quantity;
        } else {
            // Tambah produk baru
            $_SESSION['cart'][$product_id] = [
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'image' => $product['image'],
                'quantity' => $quantity
            ];
        }

        // Set success message
        $_SESSION['cart_message'] = "Produk berhasil ditambahkan ke keranjang!";
        $_SESSION['message_type'] = "success";
    }

    // Redirect: jika buy_now, ke checkout, else ke cart
    if (isset($_POST['action']) && $_POST['action'] === 'buy_now') {
        header("Location: checkout.php");
    } else {
        header("Location: cart.php");
    }
    exit;
}

// === UPDATE QUANTITY PRODUK ===
if (isset($_GET['action']) && $_GET['action'] === 'update' && isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
    $new_quantity = intval($_GET['qty']);
    
    if (isset($_SESSION['cart'][$product_id]) && $new_quantity > 0) {
        $_SESSION['cart'][$product_id]['quantity'] = $new_quantity;
        $_SESSION['cart_message'] = "Jumlah produk berhasil diupdate!";
        $_SESSION['message_type'] = "info";
    } elseif ($new_quantity <= 0) {
        unset($_SESSION['cart'][$product_id]);
        $_SESSION['cart_message'] = "Produk berhasil dihapus dari keranjang!";
        $_SESSION['message_type'] = "warning";
    }
    
    header("Location: cart.php");
    exit;
}

// === HAPUS PRODUK DARI KERANJANG ===
if (isset($_GET['remove'])) {
    $remove_id = intval($_GET['remove']);
    
    if (isset($_SESSION['cart'][$remove_id])) {
        $product_name = $_SESSION['cart'][$remove_id]['name'];
        unset($_SESSION['cart'][$remove_id]);
        $_SESSION['cart_message'] = "$product_name berhasil dihapus dari keranjang!";
        $_SESSION['message_type'] = "warning";
    }
    
    header("Location: cart.php");
    exit;
}

// === HAPUS SEMUA PRODUK ===
if (isset($_GET['clear'])) {
    $_SESSION['cart'] = [];
    $_SESSION['cart_message'] = "Semua produk berhasil dihapus dari keranjang!";
    $_SESSION['message_type'] = "warning";
    header("Location: cart.php");
    exit;
}
?>
<!-- Hilangkan search bar dengan CSS khusus di halaman ini -->
<style>
.d-flex.align-items-center { display: none !important; }
</style>

<link rel="stylesheet" href="assets/css/cart.css">

<section class="cart-section">
  <div class="cart-container container shadow-lg rounded-4 p-4">
    <h2 class="cart-title"><i class="bi bi-cart4"></i> Keranjang Belanja</h2>

    <?php 
    // Tampilkan pesan notifikasi
    if (isset($_SESSION['cart_message'])): 
    ?>
      <div class="alert alert-<?= $_SESSION['message_type'] ?> alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($_SESSION['cart_message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php 
      unset($_SESSION['cart_message']);
      unset($_SESSION['message_type']);
    endif; 
    ?>

    <?php if (empty($_SESSION['cart'])): ?>
      <div class="empty-cart">
        <i class="bi bi-emoji-frown"></i>
        <p>Keranjang Anda masih kosong.</p>
        <a href="products.php" class="btn-back"><i class="bi bi-shop"></i> Lihat Produk</a>
      </div>
    <?php else: ?>
      <div class="cart-header-actions">
        <span class="total-items">
          <i class="bi bi-bag-check"></i> Total Item: <strong><?= count($_SESSION['cart']) ?></strong>
        </span>
        <a href="cart.php?clear=1" class="btn-clear-all" onclick="return confirm('Yakin ingin mengosongkan keranjang?')">
          <i class="bi bi-trash3"></i> Kosongkan Keranjang
        </a>
      </div>

      <div class="table-responsive">
        <table class="cart-table">
          <thead>
            <tr>
              <th>Gambar</th>
              <th>Produk</th>
              <th>Harga</th>
              <th>Jumlah</th>
              <th>Total</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $grand_total = 0;
            $total_items = 0;
            foreach ($_SESSION['cart'] as $item):
              $subtotal = $item['price'] * $item['quantity'];
              $grand_total += $subtotal;
              $total_items += $item['quantity'];
            ?>
            <tr>
              <td class="product-image">
                <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
              </td>
              <td class="product-name">
                <?= htmlspecialchars($item['name']) ?>
              </td>
              <td class="product-price">
                Rp <?= number_format($item['price'], 0, ',', '.') ?>
              </td>
              <td class="quantity-control">
                <div class="qty-wrapper">
                  <a href="cart.php?action=update&id=<?= $item['id'] ?>&qty=<?= $item['quantity'] - 1 ?>" class="qty-btn qty-minus">
                    <i class="bi bi-dash"></i>
                  </a>
                  <span class="qty-display"><?= $item['quantity'] ?></span>
                  <a href="cart.php?action=update&id=<?= $item['id'] ?>&qty=<?= $item['quantity'] + 1 ?>" class="qty-btn qty-plus">
                    <i class="bi bi-plus"></i>
                  </a>
                </div>
              </td>
              <td class="product-total">
                Rp <?= number_format($subtotal, 0, ',', '.') ?>
              </td>
              <td class="action-cell">
                <a href="cart.php?remove=<?= $item['id'] ?>" class="btn-remove" onclick="return confirm('Hapus produk ini dari keranjang?')" title="Hapus">
                  <i class="bi bi-trash3"></i>
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="cart-summary">
        <div class="summary-left">
          <a href="products.php" class="btn-continue-shopping">
            <i class="bi bi-arrow-left"></i> Lanjut Belanja
          </a>
        </div>
        
        <div class="summary-right">
          <div class="summary-details">
            <div class="summary-row">
              <span>Subtotal (<?= $total_items ?> item):</span>
              <span class="subtotal-price">Rp <?= number_format($grand_total, 0, ',', '.') ?></span>
            </div>
            <div class="summary-row">
              <span>Ongkos Kirim:</span>
              <span class="shipping-price">Dihitung di checkout</span>
            </div>
            <div class="summary-row total-row">
              <span>Total:</span>
              <span class="grand-total">Rp <?= number_format($grand_total, 0, ',', '.') ?></span>
            </div>
          </div>
          
          <a href="checkout.php" class="btn-checkout">
            <i class="bi bi-credit-card"></i> Lanjut ke Pembayaran
          </a>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php include 'includes/footer.php'; ?>