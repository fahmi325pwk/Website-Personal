<?php
session_start();
require 'includes/header.php';
require 'includes/db.php';

// Pastikan user login
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

$order_id = intval($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];

// Ambil detail pesanan
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();

if (!$order) {
  echo "<div class='container mt-5'><div class='alert alert-danger'>Pesanan tidak ditemukan.</div></div>";
  require 'includes/footer.php';
  exit();
}

// Ambil item pesanan
$stmt_items = $pdo->prepare("SELECT oi.*, p.name, p.image FROM order_items oi 
                             JOIN products p ON oi.product_id = p.id 
                             WHERE oi.order_id = ?");
$stmt_items->execute([$order_id]);
$items = $stmt_items->fetchAll();

// Hitung total items
$total_items = array_sum(array_column($items, 'quantity'));

// Function untuk validasi dan mendapatkan path gambar
function getProductImage($imageName) {
  if (empty($imageName)) {
    return 'assets/images/placeholder.png';
  }
  
  // Jika imageName sudah path lengkap dengan assets/images/
  if (strpos($imageName, 'assets/images/') === 0) {
    $imagePath = $imageName;
  } else {
    $imagePath = 'assets/images/' . $imageName;
  }
  
  // Validasi file exists
  $fsPath = __DIR__ . '/' . $imagePath;
  if (file_exists($fsPath)) {
    return $imagePath;
  }
  
  // Return placeholder jika file tidak ada
  return 'assets/images/placeholder.png';
}
?>

<!-- Hilangkan search bar -->
<style>.d-flex.align-items-center { display: none !important; }</style>
<link rel="stylesheet" href="assets/css/checkout.css">

<div class="container mt-5">
  <div class="order-detail-card" 
    style="background:#fff;border-radius:14px;box-shadow:0 2px 10px rgba(0,0,0,0.06);padding:24px;max-width:900px;margin:auto;">
    
    <!-- Header -->
    <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:24px;padding-bottom:20px;border-bottom:2px solid #e5e7eb;">
      <div>
        <h2 style="color:#2563eb;margin-bottom:4px;">Pesanan #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></h2>
        <p style="color:#6b7280;margin:0;font-size:14px;">Tanggal: <?php echo date('d M Y, H:i', strtotime($order['created_at'])); ?></p>
      </div>
      <div style="text-align:right;">
        <p style="color:#6b7280;margin:0;font-size:14px;">Total Items: <strong><?php echo $total_items; ?></strong></p>
        <p style="color:#6b7280;margin:0;font-size:14px;">Rp <?php echo number_format($order['total'], 0, ',', '.'); ?></p>
      </div>
    </div>

    <!-- Status Pesanan -->
    <div style="margin-bottom:24px;padding:16px;background:#f9fafb;border-radius:8px;border-left:4px solid #2563eb;">
      <h4 style="margin:0 0 8px 0;color:#111827;">Status Pesanan</h4>
      <span style="padding:6px 14px;border-radius:20px;background:#eff6ff;color:#2563eb;font-weight:600;display:inline-block;">
        <?php 
          $status = strtolower($order['status']);
          $status_text = ['pending' => 'Menunggu Pembayaran', 'processing' => 'Diproses', 'shipped' => 'Dikirim', 'delivered' => 'Terkirim', 'cancelled' => 'Dibatalkan'];
          echo $status_text[$status] ?? ucfirst($status);
        ?>
      </span>
      <?php if ($order['status'] === 'shipped' && !empty($order['tracking_number'])): ?>
        <p style="margin:8px 0 0 0;color:#6b7280;font-size:14px;">Nomor Resi: <strong><?php echo htmlspecialchars($order['tracking_number']); ?></strong></p>
      <?php endif; ?>
    </div>

    <!-- Informasi Pengiriman -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px;">
      <div style="padding:16px;background:#f9fafb;border-radius:8px;">
        <h4 style="margin:0 0 12px 0;color:#111827;font-size:14px;">Alamat Pengiriman</h4>
        <p style="margin:0;color:#6b7280;font-size:14px;">
          <?php echo !empty($order['shipping_address']) ? htmlspecialchars($order['shipping_address']) : 'Tidak ada informasi'; ?>
        </p>
      </div>
      <div style="padding:16px;background:#f9fafb;border-radius:8px;">
        <h4 style="margin:0 0 12px 0;color:#111827;font-size:14px;">Metode Pembayaran</h4>
        <p style="margin:0;color:#6b7280;font-size:14px;">
          <?php echo !empty($order['payment_method']) ? htmlspecialchars($order['payment_method']) : 'Tidak ada informasi'; ?>
        </p>
      </div>
    </div>

    <!-- Daftar Produk -->
    <h4 style="margin-bottom:12px;color:#111827;">Daftar Produk</h4>
    <div style="overflow-x:auto;margin-bottom:24px;">
      <table style="width:100%;border-collapse:collapse;">
        <thead>
          <tr style="background:#f3f4f6;">
            <th style="padding:12px;text-align:left;font-weight:600;color:#374151;">Produk</th>
            <th style="padding:12px;text-align:center;font-weight:600;color:#374151;">Jumlah</th>
            <th style="padding:12px;text-align:right;font-weight:600;color:#374151;">Harga Satuan</th>
            <th style="padding:12px;text-align:right;font-weight:600;color:#374151;">Subtotal</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $item): 
            $imagePath = getProductImage($item['image']);
          ?>
            <tr style="border-bottom:1px solid #e5e7eb;">
              <td style="padding:12px;display:flex;align-items:center;gap:12px;">
                <img src="<?php echo htmlspecialchars($imagePath); ?>" 
                     alt="<?php echo htmlspecialchars($item['name']); ?>" 
                     style="width:50px;height:50px;object-fit:cover;border-radius:8px;"
                     onerror="this.src='assets/images/placeholder.png';">
                <div>
                  <p style="margin:0;font-weight:500;color:#111827;"><?php echo htmlspecialchars($item['name']); ?></p>
                  <p style="margin:0;font-size:12px;color:#6b7280;">SKU: <?php echo htmlspecialchars($item['product_id']); ?></p>
                </div>
              </td>
              <td style="text-align:center;color:#374151;"><?php echo $item['quantity']; ?></td>
              <td style="text-align:right;color:#374151;">Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></td>
              <td style="text-align:right;color:#2563eb;font-weight:600;">Rp <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Ringkasan Pembayaran -->
    <div style="background:#f9fafb;padding:20px;border-radius:8px;margin-bottom:24px;max-width:400px;margin-left:auto;">
      <div style="display:flex;justify-content:space-between;margin-bottom:12px;color:#6b7280;">
        <span>Subtotal</span>
        <span>Rp <?php echo number_format($order['total'], 0, ',', '.'); ?></span>
      </div>
      <?php if (!empty($order['shipping_cost'])): ?>
        <div style="display:flex;justify-content:space-between;margin-bottom:12px;color:#6b7280;">
          <span>Ongkos Kirim</span>
          <span>Rp <?php echo number_format($order['shipping_cost'], 0, ',', '.'); ?></span>
        </div>
      <?php endif; ?>
      <?php if (!empty($order['discount'])): ?>
        <div style="display:flex;justify-content:space-between;margin-bottom:12px;color:#10b981;">
          <span>Diskon</span>
          <span>-Rp <?php echo number_format($order['discount'], 0, ',', '.'); ?></span>
        </div>
      <?php endif; ?>
      <div style="border-top:2px solid #e5e7eb;padding-top:12px;display:flex;justify-content:space-between;">
        <h3 style="margin:0;color:#111827;">Total</h3>
        <h3 style="margin:0;color:#2563eb;">Rp <?php echo number_format($order['total'], 0, ',', '.'); ?></h3>
      </div>
    </div>

    <!-- Action Buttons -->
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;">
      <a href="orders.php" 
         style="background:#f3f4f6;color:#374151;padding:12px 20px;border-radius:8px;text-decoration:none;font-weight:500;border:1px solid #d1d5db;transition:all 0.3s;">
         ← Kembali
      </a>

      <div style="display:flex;gap:12px;">
        <?php if ($order['status'] === 'pending'): ?>
          <a href="cancel_order.php?id=<?php echo $order['id']; ?>" 
             style="background:#ef4444;color:#fff;padding:12px 20px;border-radius:8px;text-decoration:none;font-weight:500;transition:all 0.3s;"
             onclick="return confirm('Apakah Anda yakin ingin membatalkan pesanan ini?');">
             Batalkan Pesanan
          </a>
        <?php elseif ($order['status'] === 'shipped'): ?>
          <a href="track_order.php?id=<?php echo $order['id']; ?>"
             style="background:#3b82f6;color:#fff;padding:12px 20px;border-radius:8px;text-decoration:none;font-weight:500;display:inline-block;transition:all 0.3s;">
            Lacak Pengiriman
          </a>
          <a href="receive_order.php?id=<?php echo $order['id']; ?>"
             style="background:#10b981;color:#fff;padding:12px 20px;border-radius:8px;text-decoration:none;font-weight:500;display:inline-block;transition:all 0.3s;"
             onclick="return confirm('Apakah Anda yakin pesanan sudah diterima?');">
            Pesanan Diterima
          </a>
        <?php endif; ?>

        <a href="print_order.php?id=<?php echo $order['id']; ?>" target="_blank"
           style="background:#6366f1;color:#fff;padding:12px 20px;border-radius:8px;text-decoration:none;font-weight:500;transition:all 0.3s;">
           Cetak Pesanan
        </a>
      </div>
    </div>

  </div>

  <!-- Section - Bantuan Pelanggan -->
  <div style="margin-top:30px;padding:20px;background:#fef3c7;border-left:4px solid #f59e0b;border-radius:8px;max-width:900px;margin-left:auto;margin-right:auto;">
    <h4 style="margin:0 0 8px 0;color:#92400e;">Perlu Bantuan?</h4>
    <p style="margin:0;color:#78350f;font-size:14px;">Jika ada pertanyaan tentang pesanan Anda, silakan <a href="contact.php" style="color:#d97706;font-weight:600;text-decoration:none;">hubungi kami</a> atau cek <a href="faq.php" style="color:#d97706;font-weight:600;text-decoration:none;">FAQ</a>.</p>
  </div>

</div>

<style>
  @media (max-width: 768px) {
    .order-detail-card {
      padding: 16px !important;
    }
    
    table {
      font-size: 12px !important;
    }
    
    table td, table th {
      padding: 8px !important;
    }
  }

  @media print {
    .d-flex, button, a[href*="cancel"], a[href*="contact"], .alert {
      display: none !important;
    }
  }
</style>

<?php require 'includes/footer.php'; ?>