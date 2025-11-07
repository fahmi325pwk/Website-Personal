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

// Generate tracking number if not exists
if (empty($order['tracking_number'])) {
  $tracking_number = 'JNE' . str_pad($order['id'], 8, '0', STR_PAD_LEFT) . rand(1000, 9999);
  $stmt = $pdo->prepare("UPDATE orders SET tracking_number = ? WHERE id = ?");
  $stmt->execute([$tracking_number, $order_id]);
  $order['tracking_number'] = $tracking_number;
}

// Allow tracking for all order statuses

// Function untuk generate tracking timeline
function generateTrackingTimeline($order) {
  $timeline = [];
  $created_at = strtotime($order['created_at']);
  $status = $order['status'];

  // Step 1: Pesanan Diterima
  $timeline[] = [
    'status' => 'Pesanan Diterima',
    'description' => 'Pesanan Anda telah diterima dan sedang diproses.',
    'date' => date('d M Y, H:i', $created_at),
    'completed' => true
  ];

  // Step 2: Diproses
  $processed_time = $created_at + (2 * 3600); // 2 jam setelah diterima
  $timeline[] = [
    'status' => 'Diproses',
    'description' => 'Pesanan sedang dipersiapkan untuk pengiriman.',
    'date' => date('d M Y, H:i', $processed_time),
    'completed' => in_array($status, ['processing', 'shipped', 'delivered'])
  ];

  // Step 3: Dikirim
  $shipped_time = $created_at + (24 * 3600); // 1 hari setelah diterima
  $timeline[] = [
    'status' => 'Dikirim',
    'description' => 'Paket telah dikirim dengan nomor resi: ' . ($order['tracking_number'] ?? 'N/A'),
    'date' => date('d M Y, H:i', $shipped_time),
    'completed' => in_array($status, ['shipped', 'delivered'])
  ];

  // Step 4: Terkirim (jika status delivered)
  if ($status === 'delivered') {
    $delivered_time = $created_at + (3 * 24 * 3600); // 3 hari setelah diterima
    $timeline[] = [
      'status' => 'Terkirim',
      'description' => 'Paket telah diterima oleh penerima.',
      'date' => date('d M Y, H:i', $delivered_time),
      'completed' => true
    ];
  }

  return $timeline;
}

$timeline = generateTrackingTimeline($order);
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
        <h2 style="color:#2563eb;margin-bottom:4px;">Lacak Pengiriman</h2>
        <p style="color:#6b7280;margin:0;font-size:14px;">Pesanan #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></p>
      </div>
      <div style="text-align:right;">
        <p style="color:#6b7280;margin:0;font-size:14px;">Nomor Resi</p>
        <p style="color:#2563eb;margin:0;font-weight:600;"><?php echo htmlspecialchars($order['tracking_number'] ?? 'N/A'); ?></p>
      </div>
    </div>

    <!-- Tracking Timeline -->
    <div style="margin-bottom:24px;">
      <h4 style="margin-bottom:16px;color:#111827;">Status Pengiriman</h4>
      <div style="position:relative;">
        <?php foreach ($timeline as $index => $step): ?>
          <div style="display:flex;gap:16px;margin-bottom:24px;">
            <div style="flex-shrink:0;">
              <div style="width:40px;height:40px;border-radius:50%;background:<?php echo $step['completed'] ? '#10b981' : '#e5e7eb'; ?>;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:600;">
                <?php if ($step['completed']): ?>
                  ✓
                <?php else: ?>
                  <?php echo $index + 1; ?>
                <?php endif; ?>
              </div>
              <?php if ($index < count($timeline) - 1): ?>
                <div style="width:2px;height:24px;background:<?php echo $step['completed'] ? '#10b981' : '#e5e7eb'; ?>;margin:8px auto 0;"></div>
              <?php endif; ?>
            </div>
            <div style="flex:1;">
              <h5 style="margin:0 0 4px 0;color:#111827;font-size:16px;"><?php echo htmlspecialchars($step['status']); ?></h5>
              <p style="margin:0 0 4px 0;color:#6b7280;font-size:14px;"><?php echo htmlspecialchars($step['description']); ?></p>
              <p style="margin:0;color:#9ca3af;font-size:12px;"><?php echo htmlspecialchars($step['date']); ?></p>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
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
        <h4 style="margin:0 0 12px 0;color:#111827;font-size:14px;">Kurir Pengiriman</h4>
        <p style="margin:0;color:#6b7280;font-size:14px;">
          <?php echo !empty($order['shipping_method']) ? htmlspecialchars($order['shipping_method']) : 'JNE Regular'; ?>
        </p>
      </div>
    </div>

    <!-- Action Buttons -->
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;">
      <a href="orders.php?tab=orders" onclick="switchTab('orders'); return false;"
         style="background:#f3f4f6;color:#374151;padding:12px 20px;border-radius:8px;text-decoration:none;font-weight:500;border:1px solid #d1d5db;transition:all 0.3s;">
         ← Kembali
      </a>

      <div style="display:flex;gap:12px;">
        <a href="contact.php"
           style="background:#6366f1;color:#fff;padding:12px 20px;border-radius:8px;text-decoration:none;font-weight:500;transition:all 0.3s;">
           Hubungi Support
        </a>
      </div>
    </div>

  </div>

  <!-- Section - Bantuan Pelanggan -->
  <div style="margin-top:30px;padding:20px;background:#fef3c7;border-left:4px solid #f59e0b;border-radius:8px;max-width:900px;margin-left:auto;margin-right:auto;">
    <h4 style="margin:0 0 8px 0;color:#92400e;">Perlu Bantuan?</h4>
    <p style="margin:0;color:#78350f;font-size:14px;">Jika ada pertanyaan tentang pengiriman, silakan <a href="contact.php" style="color:#d97706;font-weight:600;text-decoration:none;">hubungi kami</a> atau cek <a href="faq.php" style="color:#d97706;font-weight:600;text-decoration:none;">FAQ</a>.</p>
  </div>

</div>

<style>
  @media (max-width: 768px) {
    .order-detail-card {
      padding: 16px !important;
    }
  }
</style>

<?php require 'includes/footer.php'; ?>
