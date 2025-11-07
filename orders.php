<?php
session_start();
require 'includes/header.php';
require 'includes/db.php';

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}
$user_id = $_SESSION['user_id'];

// Batalkan pesanan (jika user klik tombol)
if (isset($_GET['cancel'])) {
  $order_id = intval($_GET['cancel']);
  // Hanya bisa batalkan pesanan dengan status 'pending' atau 'processing'
  $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ? AND user_id = ? AND status IN ('pending', 'processing')");
  $result = $stmt->execute([$order_id, $user_id]);
  if ($stmt->rowCount() > 0) {
    $_SESSION['message'] = ['type' => 'success', 'text' => "Pesanan #$order_id berhasil dibatalkan."];
  } else {
    $_SESSION['message'] = ['type' => 'error', 'text' => "Gagal membatalkan pesanan. Pastikan pesanan masih dalam status proses."];
  }
  header("Location: orders.php");
  exit();
}

// Hapus pesan dari session
$message = $_SESSION['message'] ?? null;
unset($_SESSION['message']);

// Filter dan pencarian
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'terbaru';

$query = "SELECT * FROM orders WHERE user_id = ?";
$params = [$user_id];

if ($status_filter && in_array($status_filter, ['pending', 'processing', 'shipped', 'delivered', 'cancelled'])) {
  $query .= " AND status = ?";
  $params[] = $status_filter;
}

if ($search) {
  $query .= " AND (id LIKE ? OR customer_name LIKE ?)";
  $params[] = "%$search%";
  $params[] = "%$search%";
}

// Sorting
if ($sort === 'terlama') {
  $query .= " ORDER BY created_at ASC";
} elseif ($sort === 'harga_tertinggi') {
  $query .= " ORDER BY total DESC";
} elseif ($sort === 'harga_terendah') {
  $query .= " ORDER BY total ASC";
} else {
  $query .= " ORDER BY created_at DESC"; // Default: terbaru
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Hitung statistik pesanan
$stats_query = "SELECT 
  COUNT(*) as total,
  SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
  SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
  SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) as shipped,
  SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered,
  SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
  SUM(total) as total_nilai
  FROM orders WHERE user_id = ?";
$stats_stmt = $pdo->prepare($stats_query);
$stats_stmt->execute([$user_id]);
$stats = $stats_stmt->fetch();
?>

<!-- Sembunyikan search bar header -->
<style>.d-flex.align-items-center {display: none !important;}</style>
<link rel="stylesheet" href="assets/css/orders.css">

<div class="orders-container">
  <div class="orders-header">
    <h2><i class="bi bi-box-seam"></i> Daftar Pesanan Saya</h2>
    <p>Kelola dan pantau status pesanan kamu dengan mudah.</p>
  </div>

  <?php if ($message): ?>
    <div class="alert alert-<?= htmlspecialchars($message['type']) ?>" style="padding: 15px; margin-bottom: 20px; border-radius: 8px; display: flex; align-items: center; gap: 10px; font-size: 14px; <?= $message['type'] === 'success' ? 'background: #d4edda; color: #155724; border: 1px solid #c3e6cb;' : 'background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;' ?>">
      <i class="bi bi-<?= $message['type'] === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill' ?>"></i>
      <?= htmlspecialchars($message['text']) ?>
    </div>
  <?php endif; ?>

  <!-- Statistik Pesanan -->
  <div class="orders-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px;">
    <div class="stat-card" style="padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 15px;">
      <div class="stat-icon" style="background: #e8f4f8; padding: 15px; border-radius: 8px; font-size: 24px; color: #0084ff;">
        <i class="bi bi-box"></i>
      </div>
      <div class="stat-content">
        <p class="stat-label" style="margin: 0; font-size: 12px; color: #666; text-transform: uppercase; letter-spacing: 0.5px;">Total Pesanan</p>
        <h3 style="margin: 5px 0 0 0; font-size: 24px; color: #333;"><?= $stats['total'] ?? 0 ?></h3>
      </div>
    </div>

    <div class="stat-card" style="padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 15px;">
      <div class="stat-icon" style="background: #fff3e0; padding: 15px; border-radius: 8px; font-size: 24px; color: #ff9800;">
        <i class="bi bi-hourglass-split"></i>
      </div>
      <div class="stat-content">
        <p class="stat-label" style="margin: 0; font-size: 12px; color: #666; text-transform: uppercase; letter-spacing: 0.5px;">Pending</p>
        <h3 style="margin: 5px 0 0 0; font-size: 24px; color: #333;"><?= $stats['pending'] ?? 0 ?></h3>
      </div>
    </div>

    <div class="stat-card" style="padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 15px;">
      <div class="stat-icon" style="background: #e3f2fd; padding: 15px; border-radius: 8px; font-size: 24px; color: #2196f3;">
        <i class="bi bi-gear"></i>
      </div>
      <div class="stat-content">
        <p class="stat-label" style="margin: 0; font-size: 12px; color: #666; text-transform: uppercase; letter-spacing: 0.5px;">Diproses</p>
        <h3 style="margin: 5px 0 0 0; font-size: 24px; color: #333;"><?= $stats['processing'] ?? 0 ?></h3>
      </div>
    </div>

    <div class="stat-card" style="padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 15px;">
      <div class="stat-icon" style="background: #e3f2fd; padding: 15px; border-radius: 8px; font-size: 24px; color: #2196f3;">
        <i class="bi bi-truck"></i>
      </div>
      <div class="stat-content">
        <p class="stat-label" style="margin: 0; font-size: 12px; color: #666; text-transform: uppercase; letter-spacing: 0.5px;">Dikirim</p>
        <h3 style="margin: 5px 0 0 0; font-size: 24px; color: #333;"><?= $stats['shipped'] ?? 0 ?></h3>
      </div>
    </div>

    <div class="stat-card" style="padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 15px;">
      <div class="stat-icon" style="background: #e8f5e9; padding: 15px; border-radius: 8px; font-size: 24px; color: #4caf50;">
        <i class="bi bi-check-circle-fill"></i>
      </div>
      <div class="stat-content">
        <p class="stat-label" style="margin: 0; font-size: 12px; color: #666; text-transform: uppercase; letter-spacing: 0.5px;">Terkirim</p>
        <h3 style="margin: 5px 0 0 0; font-size: 24px; color: #333;"><?= $stats['delivered'] ?? 0 ?></h3>
      </div>
    </div>

    <div class="stat-card" style="padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 15px;">
      <div class="stat-icon" style="background: #fce4ec; padding: 15px; border-radius: 8px; font-size: 24px; color: #e91e63;">
        <i class="bi bi-cash-coin"></i>
      </div>
      <div class="stat-content">
        <p class="stat-label" style="margin: 0; font-size: 12px; color: #666; text-transform: uppercase; letter-spacing: 0.5px;">Total Nilai</p>
        <h3 style="margin: 5px 0 0 0; font-size: 20px; color: #333;">Rp <?= number_format($stats['total_nilai'] ?? 0, 0, ',', '.') ?></h3>
      </div>
    </div>
  </div>

  <!-- Filter dan Pencarian -->
  <div class="orders-controls" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 20px;">
    <form method="GET" class="filter-form" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end;">
      <div class="search-box" style="flex: 1; min-width: 250px; display: flex; align-items: center; gap: 8px; background: #f5f5f5; padding: 8px 12px; border-radius: 6px; border: 1px solid #ddd;">
        <i class="bi bi-search" style="color: #999;"></i>
        <input type="text" name="search" placeholder="Cari No. Pesanan atau Nama Pelanggan..." value="<?= htmlspecialchars($search) ?>" style="border: none; background: none; outline: none; flex: 1; font-size: 14px;">
      </div>

      <div class="filter-group" style="display: flex; gap: 8px;">
        <select name="status" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; background: #fff;">
          <option value="">Semua Status</option>
          <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
          <option value="processing" <?= $status_filter === 'processing' ? 'selected' : '' ?>>Diproses</option>
          <option value="shipped" <?= $status_filter === 'shipped' ? 'selected' : '' ?>>Dikirim</option>
          <option value="delivered" <?= $status_filter === 'delivered' ? 'selected' : '' ?>>Terkirim</option>
          <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Dibatalkan</option>
        </select>

        <select name="sort" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; background: #fff;">
          <option value="terbaru" <?= $sort === 'terbaru' ? 'selected' : '' ?>>Terbaru</option>
          <option value="terlama" <?= $sort === 'terlama' ? 'selected' : '' ?>>Terlama</option>
          <option value="harga_tertinggi" <?= $sort === 'harga_tertinggi' ? 'selected' : '' ?>>Harga Tertinggi</option>
          <option value="harga_terendah" <?= $sort === 'harga_terendah' ? 'selected' : '' ?>>Harga Terendah</option>
        </select>
      </div>

      <button type="submit" class="btn-filter" style="padding: 8px 16px; background: #0084ff; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; display: flex; align-items: center; gap: 6px;"><i class="bi bi-funnel"></i> Filter</button>
      <a href="orders.php" class="btn-reset" style="padding: 8px 16px; background: #f0f0f0; color: #333; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; text-decoration: none; display: flex; align-items: center; gap: 6px;"><i class="bi bi-arrow-clockwise"></i> Reset</a>
    </form>
  </div>

  <!-- Daftar Pesanan -->
  <?php if (count($orders) === 0): ?>
    <div class="no-orders" style="text-align: center; padding: 60px 20px; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
      <i class="bi bi-inbox" style="font-size: 48px; color: #ddd; display: block; margin-bottom: 15px;"></i>
      <p style="font-size: 16px; color: #999; margin-bottom: 20px;">Belum ada pesanan ditemukan.</p>
      <a href="shop.php" class="btn-shop" style="padding: 10px 24px; background: #0084ff; color: white; border: none; border-radius: 6px; text-decoration: none; cursor: pointer; font-size: 14px; display: inline-block;">Mulai Berbelanja</a>
    </div>
  <?php else: ?>
    <div class="orders-count" style="margin-bottom: 15px; color: #666; font-size: 14px;">
      <p>Menampilkan <strong><?= count($orders) ?></strong> pesanan</p>
    </div>

    <div class="orders-table" style="background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); overflow: hidden;">
      <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
        <thead style="background: #f9f9f9; border-bottom: 2px solid #ddd;">
          <tr>
            <th style="padding: 15px; text-align: left; font-weight: 600; color: #333;">No. Pesanan</th>
            <th style="padding: 15px; text-align: left; font-weight: 600; color: #333;">Nama Pelanggan</th>
            <th style="padding: 15px; text-align: left; font-weight: 600; color: #333;">Tanggal Pesan</th>
            <th style="padding: 15px; text-align: right; font-weight: 600; color: #333;">Total</th>
            <th style="padding: 15px; text-align: center; font-weight: 600; color: #333;">Status</th>
            <th style="padding: 15px; text-align: center; font-weight: 600; color: #333;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $order): ?>
            <?php
              $status_class = match($order['status']) {
                'pending' => 'status-pending',
                'processing' => 'status-processing',
                'shipped' => 'status-shipped',
                'delivered' => 'status-delivered',
                'cancelled' => 'status-cancelled',
                default => 'status-default'
              };
              
              $status_icon = match($order['status']) {
                'pending' => 'hourglass-split',
                'processing' => 'gear',
                'shipped' => 'truck',
                'delivered' => 'check-circle-fill',
                'cancelled' => 'x-circle-fill',
                default => 'info-circle'
              };

              $status_text = match($order['status']) {
                'pending' => 'Pending',
                'processing' => 'Diproses',
                'shipped' => 'Dikirim',
                'delivered' => 'Terkirim',
                'cancelled' => 'Dibatalkan',
                default => ucfirst($order['status'])
              };
            ?>
            <tr class="order-row" style="border-bottom: 1px solid #eee; transition: background 0.2s;">
              <td style="padding: 15px;"><strong>#<?= htmlspecialchars($order['id']) ?></strong></td>
              <td style="padding: 15px;"><?= htmlspecialchars($order['customer_name']) ?></td>
              <td style="padding: 15px;"><?= date('d M Y H:i', strtotime($order['created_at'] ?? 'now')) ?></td>
              <td style="padding: 15px; text-align: right;"><strong>Rp <?= number_format($order['total'], 0, ',', '.') ?></strong></td>
              <td style="padding: 15px; text-align: center;">
                <span class="<?= $status_class ?>" style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;
                <?php
                  switch ($order['status']) {
                    case 'pending': echo 'background: #fff3cd; color: #856404;'; break;
                    case 'processing': echo 'background: #cfe2ff; color: #084298;'; break;
                    case 'shipped': echo 'background: #e2e3e5; color: #383d41;'; break;
                    case 'delivered': echo 'background: #d1e7dd; color: #0f5132;'; break;
                    case 'cancelled': echo 'background: #f8d7da; color: #842029;'; break;
                    default: echo 'background: #e2e3e5; color: #383d41;'; 
                  }
                ?>">
                  <i class="bi bi-<?= $status_icon ?>"></i>
                  <?= $status_text ?>
                </span>
              </td>
              <td style="padding: 15px; text-align: center;">
                <div style="display: flex; gap: 8px; justify-content: center; flex-wrap: wrap;">
                  <a href="order_detail.php?id=<?= $order['id'] ?>" class="btn-detail" title="Lihat Detail" style="padding: 6px 12px; background: #0084ff; color: white; border: none; border-radius: 6px; text-decoration: none; cursor: pointer; font-size: 12px; display: inline-flex; align-items: center; gap: 4px;">
                    <i class="bi bi-eye"></i> Detail
                  </a>
                  <?php if ($order['status'] === 'pending' || $order['status'] === 'processing'): ?>
                    <a href="?cancel=<?= $order['id'] ?>" class="btn-cancel" title="Batalkan Pesanan" onclick="return confirm('Yakin ingin membatalkan pesanan ini? Tindakan ini tidak dapat dibatalkan.');" style="padding: 6px 12px; background: #dc3545; color: white; border: none; border-radius: 6px; text-decoration: none; cursor: pointer; font-size: 12px; display: inline-flex; align-items: center; gap: 4px;">
                      <i class="bi bi-x-circle"></i> Batalkan
                    </a>
                  <?php elseif ($order['status'] === 'delivered'): ?>
                    <a href="order_review.php?id=<?= $order['id'] ?>" class="btn-review" title="Beri Ulasan" style="padding: 6px 12px; background: #28a745; color: white; border: none; border-radius: 6px; text-decoration: none; cursor: pointer; font-size: 12px; display: inline-flex; align-items: center; gap: 4px;">
                      <i class="bi bi-star"></i> Ulasan
                    </a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require 'includes/footer.php'; ?>