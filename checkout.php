<?php
session_start();
require 'includes/header.php';
require 'includes/db.php';


// Handle buy now from product_detail.php
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['product_id'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity'] ?? 1);

    // Validate product and stock
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product || $product['stock'] < $quantity) {
        echo "<div class='container mt-5'><div class='alert alert-danger'>Produk tidak tersedia atau stok tidak mencukupi.</div></div>";
        include 'includes/footer.php';
        exit;
    }

    // Create temporary cart for buy now
    $_SESSION['cart'] = [
        $product_id => [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'image' => $product['image'],
            'quantity' => $quantity
        ]
    ];

    // Redirect to avoid form resubmission
    header("Location: checkout.php");
    exit;
}

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = 'checkout.php';
    header("Location: login.php");
    exit();
}

// Redirect if cart is empty
if (empty($_SESSION['cart'])) {
    echo "<div class='container mt-5'>";
    echo "<div class='alert alert-info'><i class='bi bi-bag-x'></i> Keranjang belanja kosong. <a href='products.php'>Belanja sekarang</a></div>";
    echo "</div>";
    include 'includes/footer.php';
    exit;
}

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Re-validate cart items and calculate total using latest DB price and available stock
$total = 0;
$validatedCart = [];
foreach ($_SESSION['cart'] as $cartItem) {
    $productId = (int)$cartItem['id'];
    $quantityRequested = (int)$cartItem['quantity'];
    if ($quantityRequested <= 0) { continue; }
    $stmt = $pdo->prepare("SELECT id, name, price, image, stock FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $productRow = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$productRow) { continue; }
    $quantityToBuy = min($quantityRequested, (int)$productRow['stock']);
    if ($quantityToBuy <= 0) { continue; }
    $lineTotal = (int)$productRow['price'] * $quantityToBuy;
    $total += $lineTotal;
    $validatedCart[$productId] = [
        'id' => $productRow['id'],
        'name' => $productRow['name'],
        'price' => (int)$productRow['price'],
        'image' => $cartItem['image'] ?? $productRow['image'],
        'quantity' => $quantityToBuy
    ];
}
// Replace session cart with validated cart to reflect any stock adjustments
$_SESSION['cart'] = $validatedCart;

// Calculate total quantity
$total_quantity = array_sum(array_column($validatedCart, 'quantity'));

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $postal_code = trim($_POST['postal_code']);
    $shipping_method = $_POST['shipping_method'];
    $payment_method = $_POST['payment_method'];
    $bank_name = isset($_POST['bank_name']) ? $_POST['bank_name'] : '';
    $gift_wrap = isset($_POST['gift_wrap']) ? 1 : 0;
    $order_notes = isset($_POST['order_notes']) ? $_POST['order_notes'] : '';
    
    // Calculate shipping cost
    $shipping_cost = ($shipping_method == 'express') ? 50000 : 0;
    
    // Add gift wrap cost if selected
    $gift_wrap_cost = $gift_wrap ? 10000 : 0;
    
    // Calculate estimated delivery date
    $estimated_delivery = ($shipping_method == 'express') ? 
        date('Y-m-d', strtotime('+1 day')) : 
        date('Y-m-d', strtotime('+3 days'));
    
    // Basic server-side validations
    $errors = [];
    if ($name === '' || $email === '' || $phone === '' || $address === '' || $city === '' || $postal_code === '') {
        $errors[] = 'Lengkapi semua data yang wajib diisi.';
    }
    if ($payment_method === 'bank' && $bank_name === '') {
        $errors[] = 'Pilih bank untuk metode transfer bank.';
    }
    if (empty($_SESSION['cart'])) {
        $errors[] = 'Keranjang kosong atau stok tidak mencukupi.';
    }

    // Update total with shipping and gift wrap
    $total = $total + $shipping_cost + $gift_wrap_cost;

    if (!empty($errors)) {
        $error = implode('<br>', array_map('htmlspecialchars', $errors));
    } else try {
        $pdo->beginTransaction();

        // Insert into orders table
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, customer_name, customer_email, customer_phone, 
            shipping_address, city, postal_code, shipping_method, shipping_cost, payment_method, 
            gift_wrap, order_notes, estimated_delivery, total, status) 
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,'pending')");
        $stmt->execute([
            $_SESSION['user_id'], $name, $email, $phone, $address, $city, $postal_code,
            $shipping_method, $shipping_cost, $payment_method, $gift_wrap, $order_notes,
            $estimated_delivery, $total
        ]);
        $order_id = $pdo->lastInsertId();

        // Insert order items and atomically decrease stock, verifying availability
        foreach ($_SESSION['cart'] as $item) {
            // Lock and check stock
            $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
            $stmt->execute([$item['quantity'], $item['id'], $item['quantity']]);
            if ($stmt->rowCount() === 0) {
                throw new Exception('Stok untuk ' . $item['name'] . ' tidak mencukupi.');
            }

            // Insert order item with price locked at time of purchase
            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?,?,?,?)");
            $stmt->execute([$order_id, $item['id'], $item['quantity'], $item['price']]);
        }

        $pdo->commit();
        $_SESSION['cart'] = [];

        // Store success data in session and redirect to separate success page
        $_SESSION['order_success'] = [
            'order_id' => $order_id,
            'total' => $total,
            'payment_method' => $payment_method,
            'bank_name' => $bank_name,
            'estimated_delivery' => $estimated_delivery,
            'email' => $email
        ];

        header("Location: checkout_success.php");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Terjadi kesalahan saat memproses pesanan Anda. Silakan coba lagi.<br><span style='color:#dc2626;font-size:0.95em;'>" . htmlspecialchars($e->getMessage()) . "</span>";
    }
}
?>

<link rel="stylesheet" href="assets/css/checkout.css">

<div class="container mt-5">
  <div class="checkout-wrapper">
    
    <!-- FORM CHECKOUT -->
    <div class="checkout-form">
        <h2><i class="bi bi-bag-check"></i> Checkout</h2>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <!-- INFORMASI PELANGGAN -->
            <div class="form-section">
                <h3><i class="bi bi-person-circle"></i> Informasi Pelanggan</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Nama Lengkap <span class="required">*</span></label>
                        <input type="text" name="name" required placeholder="Masukkan nama Anda"
                            value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>No. Telepon <span class="required">*</span></label>
                        <input type="tel" name="phone" required placeholder="628xxxxxxxxx"
                            value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group full-width">
                        <label>Email <span class="required">*</span></label>
                        <input type="email" name="email" required placeholder="email@example.com"
                            value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                    </div>
                </div>
            </div>

            <!-- ALAMAT PENGIRIMAN -->
            <div class="form-section">
                <h3><i class="bi bi-geo-alt-fill"></i> Alamat Pengiriman</h3>
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Alamat Lengkap <span class="required">*</span></label>
                        <textarea name="address" required placeholder="Jl. Contoh No. 123, Apartemen/Rumah Anda"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                    </div>
                    
                <div class="form-group">
                    <label>Kota <span class="required">*</span></label>
                    <input type="text" name="city" required placeholder="Contoh: Jakarta Selatan"
                        value="<?php echo htmlspecialchars($user['kota'] ?? ''); ?>">
                </div>
                                    
                <div class="form-group">
                    <label>Kode Pos <span class="required">*</span></label>
                    <input type="text" name="postal_code" required placeholder="12345"
                        value="<?php echo htmlspecialchars($user['kode_pos'] ?? ''); ?>">
                </div>
                </div>
            </div>

            <!-- METODE PENGIRIMAN -->
            <div class="form-section">
                <h3><i class="bi bi-truck"></i> Metode Pengiriman</h3>
                <div class="shipping-options">
                    <div class="shipping-option">
                        <input type="radio" id="regular" name="shipping_method" value="regular" required checked>
                        <label for="regular">
                            <div class="shipping-info">
                                <span class="shipping-name">Regular (2-3 hari)</span>
                                <span class="shipping-cost">Gratis</span>
                            </div>
                            <small>✓ Estimasi tiba: <?php echo date('d M Y', strtotime('+3 days')); ?></small>
                        </label>
                    </div>
                    <div class="shipping-option">
                        <input type="radio" id="express" name="shipping_method" value="express">
                        <label for="express">
                            <div class="shipping-info">
                                <span class="shipping-name">Express (1 hari)</span>
                                <span class="shipping-cost">Rp 50.000</span>
                            </div>
                            <small>⚡ Estimasi tiba: <?php echo date('d M Y', strtotime('+1 day')); ?></small>
                        </label>
                    </div>
                </div>
            </div>

            <!-- OPSI TAMBAHAN -->
            <div class="form-section">
                <h3><i class="bi bi-gift"></i> Opsi Tambahan</h3>
                <div class="additional-options">
                    <div class="form-group">
                        <div class="checkbox-wrapper">
                            <input type="checkbox" id="gift_wrap" name="gift_wrap" value="1">
                            <label for="gift_wrap">
                                <span>🎁 Bungkus sebagai Hadiah</span>
                                <small>+Rp 10.000 • Termasuk kertas kado dan kartu ucapan</small>
                            </label>
                        </div>
                    </div>
                    <div class="form-group full-width">
                        <label>Catatan Pesanan</label>
                        <textarea name="order_notes" placeholder="Tambahkan catatan khusus (opsional) • Contoh: warna preferensi, instruksi khusus, dll."></textarea>
                    </div>
                </div>
            </div>

            <!-- ORDER NOTICE -->
            <div class="order-notice">
                <p><i class="bi bi-info-circle"></i> Dengan membuat pesanan, Anda menyetujui syarat dan ketentuan kami.</p>
                <p><i class="bi bi-envelope"></i> Konfirmasi pesanan akan dikirim ke email Anda.</p>
            </div>

            <!-- METODE PEMBAYARAN -->
            <div class="form-section">
                <h3><i class="bi bi-credit-card"></i> Metode Pembayaran</h3>
                <div class="payment-methods">
                    <div class="payment-option">
                        <input type="radio" id="qris" name="payment_method" value="qris" required>
                        <label for="qris">
                            <div style="font-size: 1.8rem; margin-bottom: 8px;">📱</div>
                            <span style="font-weight:600;">QRIS</span>
                        </label>
                    </div>
                    
                    <div class="payment-option">
                        <input type="radio" id="bank" name="payment_method" value="bank">
                        <label for="bank">
                            <div style="font-size: 1.8rem; margin-bottom: 8px;">🏦</div>
                            <span style="font-weight:600;">Transfer Bank</span>
                        </label>
                    </div>
                </div>
                <div id="bank-list" style="display:none; margin-top:10px;">
                    <label for="bank_name">Pilih Bank:</label>
                    <select name="bank_name" id="bank_name" class="form-control">
                        <option value="">-- Pilih Bank --</option>
                        <option value="BCA">BCA</option>
                        <option value="BRI">BRI</option>
                        <option value="Mandiri">Mandiri</option>
                        <option value="BNI">BNI</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="checkout-button">
                <i class="bi bi-bag-check-fill"></i>
                Buat Pesanan Sekarang
            </button>
        </form>
    </div>

    <!-- RINGKASAN PESANAN -->
    <div class="order-summary">
        <h3>Ringkasan Pesanan</h3>
        <div class="cart-items">
            <?php foreach ($_SESSION['cart'] as $item): ?>
                <div class="cart-item">
                    <div class="item-image">
                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                    </div>
                    <div class="item-details">
                        <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                        <p><?php echo $item['quantity']; ?> x Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></p>
                    </div>
                    <div class="item-total">
                        Rp <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="order-total">
            <div class="total-items">
                <span>Total Item</span>
                <span><?php echo $total_quantity; ?> produk</span>
            </div>
            <div class="subtotal">
                <span>Subtotal</span>
                <span>Rp <?php echo number_format($total, 0, ',', '.'); ?></span>
            </div>
            <div class="shipping">
                <span>Pengiriman</span>
                <span class="shipping-cost-display">Gratis</span>
            </div>
            <div id="gift-wrap-cost" style="display: none;">
                <span>Gift Wrap</span>
                <span>Rp 10.000</span>
            </div>
            <div class="total">
                <span>Total</span>
                <span class="total-amount">Rp <?php echo number_format($total, 0, ',', '.'); ?></span>
            </div>
            
            <script>
            // Update shipping cost and total
            document.querySelectorAll('input[name="shipping_method"]').forEach(input => {
                input.addEventListener('change', function() {
                    const shippingCostDisplay = document.querySelector('.shipping-cost-display');
                    const totalDisplay = document.querySelector('.total-amount');
                    let currentTotal = <?php echo $total; ?>;
                    
                    if (this.value === 'express') {
                        shippingCostDisplay.textContent = 'Rp 50.000';
                        currentTotal += 50000;
                    } else {
                        shippingCostDisplay.textContent = 'Gratis';
                    }
                    
                    // Check if gift wrap is selected
                    if (document.getElementById('gift_wrap').checked) {
                        currentTotal += 10000;
                    }
                    
                    totalDisplay.textContent = 'Rp ' + currentTotal.toLocaleString('id-ID');
                });
            });

            // Update total when gift wrap is toggled
            document.getElementById('gift_wrap').addEventListener('change', function() {
                const giftWrapCost = document.getElementById('gift-wrap-cost');
                const totalDisplay = document.querySelector('.total-amount');
                let currentTotal = <?php echo $total; ?>;
                
                if (this.checked) {
                    giftWrapCost.style.display = 'flex';
                    currentTotal += 10000;
                } else {
                    giftWrapCost.style.display = 'none';
                }
                
                // Add shipping cost if express is selected
                if (document.getElementById('express').checked) {
                    currentTotal += 50000;
                }
                
                totalDisplay.textContent = 'Rp ' + currentTotal.toLocaleString('id-ID');
            });

            // Bank selection toggle
            document.getElementById('bank').addEventListener('change', function() {
                document.getElementById('bank-list').style.display = 'block';
                document.getElementById('bank_name').required = true;
            });
            document.getElementById('qris').addEventListener('change', function() {
                document.getElementById('bank-list').style.display = 'none';
                document.getElementById('bank_name').required = false;
            });
            </script>
        </div>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>