<?php
session_start();
require 'includes/header.php';
require 'includes/db.php';

// Check if user is logged in and has order success data
if (!isset($_SESSION['user_id']) || !isset($_SESSION['order_success'])) {
    header("Location: checkout.php");
    exit();
}

// Get order success data from session
$order_data = $_SESSION['order_success'];
$order_id = $order_data['order_id'];
$total = $order_data['total'];
$payment_method = $order_data['payment_method'];
$bank_name = $order_data['bank_name'] ?? '';
$estimated_delivery = $order_data['estimated_delivery'];
$email = $order_data['email'];

// Clear the success data from session
unset($_SESSION['order_success']);
?>

<link rel="stylesheet" href="assets/css/checkout.css">

<div class="container mt-5 mb-5">

    <!-- Success Header Section -->
    <div class="success-section success-header-section">
        <div class="success-icon-animated"><i class="bi bi-check-circle-fill"></i></div>
        <h1>Pesanan Berhasil Dibuat!</h1>
        <p class="success-subtitle">Terima kasih telah berbelanja di Nano Komputer</p>
    </div>

    <!-- Order Details Section -->
    <div class="success-section order-details-section">
        <h3><i class="bi bi-receipt"></i> Detail Pesanan</h3>
        <div class="order-details-card">
            <div class="detail-row">
                <div class="detail-label"><i class="bi bi-bag-fill"></i> Nomor Pesanan</div>
                <div class="detail-value order-number">#<?php echo htmlspecialchars($order_id); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label"><i class="bi bi-calendar-event"></i> Estimasi Pengiriman</div>
                <div class="detail-value"><?php echo htmlspecialchars($estimated_delivery); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label"><i class="bi bi-cash-coin"></i> Total Pembayaran</div>
                <div class="detail-value amount">Rp <?php echo number_format($total, 0, ',', '.'); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label"><i class="bi bi-credit-card"></i> Metode Pembayaran</div>
                <div class="detail-value"><?php echo ($payment_method == 'qris' ? '<span class="badge-qris">📱 QRIS</span>' : '<span class="badge-bank">🏦 Transfer Bank</span>'); ?></div>
            </div>
        </div>
    </div>

    <!-- Payment Instructions Section -->
    <div class="success-section payment-section">
        <?php if ($payment_method == 'bank'): ?>
            <h3><i class="bi bi-credit-card-2-back"></i> Instruksi Pembayaran Bank</h3>
            <div class="payment-instruction bank-payment">
                <div class="payment-alert">
                    <i class="bi bi-exclamation-triangle"></i>
                    <p>Silakan transfer ke rekening berikut <strong>dalam 24 jam</strong> untuk menghindari pembatalan otomatis</p>
                </div>
                <div class="bank-info-box">
                    <?php if ($bank_name == 'BCA'): ?>
                        <div class="bank-name"><i class="bi bi-bank"></i> Bank BCA</div>
                        <div class="bank-field"><span>Nomor Rekening:</span> <code>1234567890</code></div>
                    <?php elseif ($bank_name == 'BRI'): ?>
                        <div class="bank-name"><i class="bi bi-bank"></i> Bank BRI</div>
                        <div class="bank-field"><span>Nomor Rekening:</span> <code>9876543210</code></div>
                    <?php elseif ($bank_name == 'Mandiri'): ?>
                        <div class="bank-name"><i class="bi bi-bank"></i> Bank Mandiri</div>
                        <div class="bank-field"><span>Nomor Rekening:</span> <code>1122334455</code></div>
                    <?php elseif ($bank_name == 'BNI'): ?>
                        <div class="bank-name"><i class="bi bi-bank"></i> Bank BNI</div>
                        <div class="bank-field"><span>Nomor Rekening:</span> <code>5566778899</code></div>
                    <?php endif; ?>
                    <div class="bank-field"><span>Atas Nama:</span> <strong>Nano Komputer</strong></div>
                    <div class="bank-field"><span>Jumlah Transfer:</span> <strong>Rp <?php echo number_format($total, 0, ',', '.'); ?></strong></div>
                </div>
                <div class="payment-note">
                    <i class="bi bi-info-circle"></i>
                    <p>Pesanan akan diproses setelah kami menerima konfirmasi pembayaran. Mohon sertakan nomor pesanan (#<?php echo htmlspecialchars($order_id); ?>) pada berita transfer.</p>
                </div>
            </div>
        <?php else: ?>
            <h3><i class="bi bi-qr-code"></i> Pembayaran QRIS</h3>
            <div class="payment-instruction qris-payment">
                <div class="payment-alert">
                    <i class="bi bi-exclamation-triangle"></i>
                    <p>Scan QR code berikut menggunakan aplikasi e-wallet atau mobile banking Anda</p>
                </div>
                <div class="qris-container">
                    <?php $qr_data = urlencode("Pembayaran QRIS untuk Pesanan #" . $order_id . " - Total: Rp " . number_format($total, 0, ',', '.')); ?>
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=<?php echo $qr_data; ?>" alt="QRIS Payment Code" class="qris-qr-code">
                    <div class="qris-info">
                        <div class="qris-amount">Rp <?php echo number_format($total, 0, ',', '.'); ?></div>
                        <div class="qris-order">Pesanan #<?php echo htmlspecialchars($order_id); ?></div>
                    </div>
                </div>
                <div class="payment-note">
                    <i class="bi bi-info-circle"></i>
                    <p>Pembayaran akan otomatis terdeteksi. Jika ada kendala, hubungi customer service kami.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Action Buttons Section -->
    <div class="success-section actions-section">
        <div class="success-actions">
            <a href="orders.php" class="btn btn-primary action-btn"><i class="bi bi-bag-check"></i> Lihat Detail Pesanan</a>
            <a href="products.php" class="btn btn-outline-primary action-btn"><i class="bi bi-shop"></i> Lanjut Belanja</a>
        </div>
    </div>

    <!-- Confirmation Section -->
    <div class="success-section confirmation-section">
        <div class="confirmation-message">
            <i class="bi bi-envelope-check"></i>
            <p>Konfirmasi pesanan telah dikirim ke <strong><?php echo htmlspecialchars($email); ?></strong></p>
            <small>Pastikan untuk memeriksa folder spam jika tidak menemukan email konfirmasi</small>
        </div>
    </div>

</div>

<?php include 'includes/footer.php'; ?>
