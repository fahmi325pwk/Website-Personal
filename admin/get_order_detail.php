<?php
session_start();
if (!isset($_SESSION['admin'])) {
    echo '<p style="color: red;">Akses ditolak!</p>';
    exit;
}

require '../includes/db.php';

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($order_id <= 0) {
    echo '<p style="color: red;">ID pesanan tidak valid!</p>';
    exit;
}

try {
    // Get order details
    $orderStmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $orderStmt->execute([$order_id]);
    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo '<p style="color: red;">Pesanan tidak ditemukan!</p>';
        exit;
    }

    // Get order items
    $itemsStmt = $pdo->prepare("SELECT oi.*, p.name, p.price FROM order_items oi 
                                LEFT JOIN products p ON oi.product_id = p.id 
                                WHERE oi.order_id = ?");
    $itemsStmt->execute([$order_id]);
    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

    $statusClass = 'status-' . strtolower($order['status']);
    $statusText = ucfirst($order['status']);
    
    ?>
    <style>
        .order-detail-content { font-size: 14px; line-height: 1.6; }
        .detail-section { margin-bottom: 20px; }
        .detail-section h4 { margin: 0 0 12px 0; color: #2563eb; font-size: 14px; font-weight: 600; text-transform: uppercase; }
        .detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; }
        .detail-row strong { min-width: 150px; }
        .detail-row span { text-align: right; }
        
        .items-table { width: 100%; border-collapse: collapse; margin: 12px 0; }
        .items-table th { background: #f3f4f6; padding: 10px; text-align: left; font-weight: 600; font-size: 13px; border-bottom: 2px solid #ddd; }
        .items-table td { padding: 10px; border-bottom: 1px solid #eee; }
        .items-table tr:last-child td { border-bottom: none; }
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #cfe2ff; color: #084298; }
        .status-shipped { background: #e2e3e5; color: #383d41; }
        .status-delivered { background: #d1e7dd; color: #0f5132; }
        .status-cancelled { background: #f8d7da; color: #842029; }
        
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 600; font-size: 13px; }
        .form-group select { width: 100%; padding: 8px 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; }
        
        .button-group { display: flex; gap: 8px; margin-top: 16px; justify-content: flex-end; }
        .btn-update { background: #2563eb; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600; }
        .btn-update:hover { background: #1d4ed8; }
        
        .total-row { font-weight: 600; background: #f9fafb; }
        .total-row td { padding: 12px 10px !important; }
        
        .form-row { display: flex; gap: 10px; align-items: flex-end; margin-bottom: 0; }
        .form-row .form-group { flex: 1; margin-bottom: 0; }
        
        body.dark-mode .detail-row { border-bottom-color: #333; }
        body.dark-mode .items-table th { background: #374151; color: #e6eef8; }
        body.dark-mode .items-table td { border-bottom-color: #333; }
        body.dark-mode .form-group select { background: #1f2937; color: #e6eef8; border-color: #444; }
        body.dark-mode .total-row { background: #374151; }
        body.dark-mode .status-badge { }
        body.dark-mode .detail-section h4 { color: #60a5fa; }
    </style>

    <div class="order-detail-content">
        <!-- Status Update Form -->
        <div class="detail-section">
            <form method="POST" action="orders.php" class="form-row">
                <div class="form-group">
                    <label for="status">Ubah Status Pesanan:</label>
                    <select name="status" id="status" required>
                        <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>>Diproses</option>
                        <option value="shipped" <?= $order['status'] === 'shipped' ? 'selected' : '' ?>>Dikirim</option>
                        <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Terkirim</option>
                        <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Dibatalkan</option>
                    </select>
                </div>
                <input type="hidden" name="order_id" value="<?= $order_id ?>">
                <button type="submit" class="btn-update"><i class="fas fa-save"></i> Simpan</button>
            </form>
        </div>

        <!-- Current Status -->
        <div class="detail-section">
            <h4><i class="fas fa-info-circle"></i> Status Saat Ini</h4>
            <div style="padding: 12px;">
                <span class="status-badge <?= $statusClass ?>"><?= $statusText ?></span>
            </div>
        </div>

        <!-- Customer Information -->
        <div class="detail-section">
            <h4><i class="fas fa-user"></i> Informasi Pelanggan</h4>
            <div class="detail-row">
                <strong>Nama:</strong>
                <span><?= htmlspecialchars($order['customer_name']) ?></span>
            </div>
            <div class="detail-row">
                <strong>Email:</strong>
                <span><?= htmlspecialchars($order['customer_email']) ?></span>
            </div>
            <?php if (!empty($order['customer_phone'])): ?>
            <div class="detail-row">
                <strong>Telepon:</strong>
                <span><?= htmlspecialchars($order['customer_phone']) ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($order['customer_address'])): ?>
            <div class="detail-row">
                <strong>Alamat:</strong>
                <span><?= htmlspecialchars($order['customer_address']) ?></span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Order Information -->
        <div class="detail-section">
            <h4><i class="fas fa-receipt"></i> Informasi Pesanan</h4>
            <div class="detail-row">
                <strong>Nomor Pesanan:</strong>
                <span>#<?= str_pad($order_id, 6, '0', STR_PAD_LEFT) ?></span>
            </div>
            <div class="detail-row">
                <strong>Tanggal Pesanan:</strong>
                <span><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></span>
            </div>
            <?php if (!empty($order['updated_at']) && $order['updated_at'] !== $order['created_at']): ?>
            <div class="detail-row">
                <strong>Terakhir Diupdate:</strong>
                <span><?= date('d/m/Y H:i', strtotime($order['updated_at'])) ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($order['notes'])): ?>
            <div class="detail-row">
                <strong>Catatan:</strong>
                <span><?= htmlspecialchars($order['notes']) ?></span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Order Items -->
        <div class="detail-section">
            <h4><i class="fas fa-boxes"></i> Item Pesanan</h4>
            <?php if (!empty($items)): ?>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th style="text-align: center;">Jumlah</th>
                        <th style="text-align: right;">Harga Satuan</th>
                        <th style="text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $subtotal = 0;
                    foreach ($items as $item): 
                        $itemTotal = $item['quantity'] * $item['price'];
                        $subtotal += $itemTotal;
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($item['name'] ?? 'Produk Tidak Ditemukan') ?></td>
                        <td style="text-align: center;"><?= (int)$item['quantity'] ?></td>
                        <td style="text-align: right;">Rp <?= number_format($item['price'], 0, ',', '.') ?></td>
                        <td style="text-align: right;">Rp <?= number_format($itemTotal, 0, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td colspan="3" style="text-align: right;">Subtotal:</td>
                        <td style="text-align: right;">Rp <?= number_format($subtotal, 0, ',', '.') ?></td>
                    </tr>
                </tbody>
            </table>
            <?php else: ?>
            <p style="color: #999; padding: 12px;">Tidak ada item dalam pesanan ini.</p>
            <?php endif; ?>
        </div>

        <!-- Order Summary -->
        <div class="detail-section">
            <h4><i class="fas fa-calculator"></i> Ringkasan Pesanan</h4>
            <div class="detail-row">
                <strong>Subtotal:</strong>
                <span>Rp <?= number_format($subtotal ?? 0, 0, ',', '.') ?></span>
            </div>
            <?php if (!empty($order['shipping_cost'])): ?>
            <div class="detail-row">
                <strong>Biaya Pengiriman:</strong>
                <span>Rp <?= number_format($order['shipping_cost'], 0, ',', '.') ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($order['discount'])): ?>
            <div class="detail-row" style="color: #22c55e;">
                <strong>Diskon:</strong>
                <span>- Rp <?= number_format($order['discount'], 0, ',', '.') ?></span>
            </div>
            <?php endif; ?>
            <div class="detail-row" style="font-weight: 600; font-size: 16px; padding: 12px 0; border-top: 2px solid #2563eb; border-bottom: 2px solid #2563eb;">
                <strong>Total Pembayaran:</strong>
                <span style="color: #2563eb;">Rp <?= number_format($order['total'], 0, ',', '.') ?></span>
            </div>
        </div>

        <!-- Payment Information -->
        <?php if (!empty($order['payment_method']) || !empty($order['payment_status'])): ?>
        <div class="detail-section">
            <h4><i class="fas fa-credit-card"></i> Informasi Pembayaran</h4>
            <?php if (!empty($order['payment_method'])): ?>
            <div class="detail-row">
                <strong>Metode Pembayaran:</strong>
                <span><?= htmlspecialchars($order['payment_method']) ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($order['payment_status'])): ?>
            <div class="detail-row">
                <strong>Status Pembayaran:</strong>
                <span><?= htmlspecialchars($order['payment_status']) ?></span>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <?php
} catch (Exception $e) {
    echo '<p style="color: red;">Terjadi kesalahan: ' . htmlspecialchars($e->getMessage()) . '</p>';
}
?>