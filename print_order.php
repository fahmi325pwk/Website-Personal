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

// Ambil detail pesanan
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();

if (!$order) {
  die("<h1>Pesanan tidak ditemukan.</h1>");
}

// Ambil item pesanan
$stmt_items = $pdo->prepare("SELECT oi.*, p.name FROM order_items oi 
                             JOIN products p ON oi.product_id = p.id 
                             WHERE oi.order_id = ?");
$stmt_items->execute([$order_id]);
$items = $stmt_items->fetchAll();

// Hitung total items
$total_items = array_sum(array_column($items, 'quantity'));

// Format tanggal
$order_date = date('d M Y, H:i', strtotime($order['created_at']));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Pesanan #<?php echo $order_id; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 850px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 40px;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 20px;
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
        }
        
        .invoice-title {
            text-align: right;
        }
        
        .invoice-title h1 {
            font-size: 28px;
            color: #111827;
            margin-bottom: 5px;
        }
        
        .invoice-title p {
            color: #6b7280;
            font-size: 14px;
        }
        
        .invoice-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }
        
        .invoice-meta-section h3 {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .invoice-meta-section p {
            color: #111827;
            margin-bottom: 4px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        thead {
            background-color: #f3f4f6;
        }
        
        th {
            padding: 15px 10px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            font-size: 13px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        td {
            padding: 15px 10px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .product-name {
            font-weight: 500;
            color: #111827;
        }
        
        .qty {
            text-align: center;
        }
        
        .price {
            text-align: right;
            color: #374151;
        }
        
        .subtotal {
            text-align: right;
            color: #2563eb;
            font-weight: 600;
        }
        
        .summary {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 40px;
        }
        
        .summary-box {
            width: 100%;
            max-width: 400px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }
        
        .summary-row.total {
            border-bottom: 3px solid #2563eb;
            font-size: 18px;
            font-weight: bold;
            color: #2563eb;
            padding: 15px 0;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 12px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            background-color: #eff6ff;
            color: #2563eb;
            border-radius: 20px;
            font-weight: 600;
            font-size: 12px;
            margin-bottom: 15px;
        }
        
        .notes {
            background-color: #f9fafb;
            padding: 15px;
            border-radius: 8px;
            margin-top: 30px;
            font-size: 13px;
            color: #6b7280;
        }
        
        @media print {
            body {
                padding: 0;
                margin: 0;
            }
            .container {
                padding: 0;
                margin: 0;
            }
            a {
                color: inherit;
                text-decoration: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo">Nano Komputer</div>
            <div class="invoice-title">
                <h1>INVOICE</h1>
                <p>Pesanan #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></p>
            </div>
        </div>
        
        <!-- Meta Information -->
        <div class="invoice-meta">
            <div class="invoice-meta-section">
                <h3>Informasi Pesanan</h3>
                <p><strong>Tanggal:</strong> <?php echo $order_date; ?></p>
                <p><strong>Status:</strong> <span class="status-badge">
                    <?php 
                        $status_text = ['pending' => 'Menunggu Pembayaran', 'processing' => 'Diproses', 'shipped' => 'Dikirim', 'delivered' => 'Terkirim', 'cancelled' => 'Dibatalkan'];
                        echo $status_text[strtolower($order['status'])] ?? ucfirst($order['status']);
                    ?>
                </span></p>
                <?php if (!empty($order['tracking_number'])): ?>
                    <p><strong>Resi:</strong> <?php echo htmlspecialchars($order['tracking_number']); ?></p>
                <?php endif; ?>
            </div>
            
            <div class="invoice-meta-section">
                <h3>Alamat Pengiriman</h3>
                <p><?php echo !empty($order['shipping_address']) ? nl2br(htmlspecialchars($order['shipping_address'])) : 'Tidak ada informasi'; ?></p>
            </div>
        </div>
        
        <!-- Items Table -->
        <table>
            <thead>
                <tr>
                    <th>Produk</th>
                    <th class="qty">Jumlah</th>
                    <th class="price">Harga Satuan</th>
                    <th class="price">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td class="product-name"><?php echo htmlspecialchars($item['name']); ?></td>
                    <td class="qty"><?php echo $item['quantity']; ?></td>
                    <td class="price">Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></td>
                    <td class="subtotal">Rp <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Summary -->
        <div class="summary">
            <div class="summary-box">
                <?php if (!empty($order['shipping_cost'])): ?>
                <div class="summary-row">
                    <span>Ongkos Kirim:</span>
                    <span>Rp <?php echo number_format($order['shipping_cost'], 0, ',', '.'); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($order['discount'])): ?>
                <div class="summary-row">
                    <span>Diskon:</span>
                    <span style="color: #10b981;">-Rp <?php echo number_format($order['discount'], 0, ',', '.'); ?></span>
                </div>
                <?php endif; ?>
                
                <div class="summary-row total">
                    <span>TOTAL PEMBAYARAN:</span>
                    <span>Rp <?php echo number_format($order['total'], 0, ',', '.'); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Notes -->
        <div class="notes">
            <strong>Catatan Penting:</strong>
            <ul style="margin-left: 20px; margin-top: 8px;">
                <li>Simpan invoice ini untuk keperluan claim atau retur produk</li>
                <li>Pesanan akan dikirim sesuai dengan alamat yang telah diberikan</li>
                <li>Untuk informasi lebih lanjut, hubungi customer service kami</li>
            </ul>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p>Terima kasih telah berbelanja! | Generated on <?php echo date('d M Y H:i:s'); ?></p>
        </div>
    </div>

    <!-- Print Button (hidden when printing) -->
    <div style="position: fixed; top: 20px; right: 20px; z-index: 9999; display: flex; gap: 10px;">
        <button onclick="window.print()" style="
            background-color: #2563eb;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
        " onmouseover="this.style.backgroundColor='#1d4ed8'" onmouseout="this.style.backgroundColor='#2563eb'">
            🖨️ Cetak
        </button>
        <button onclick="window.close()" style="
            background-color: #6b7280;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
        " onmouseover="this.style.backgroundColor='#4b5563'" onmouseout="this.style.backgroundColor='#6b7280'">
            ✕ Tutup
        </button>
    </div>

    <style>
        @media print {
            div[style*="position: fixed"] {
                display: none !important;
            }
        }
    </style>
</body>
</html>