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
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Validasi order_id
if ($order_id <= 0) {
  header("Location: orders.php");
  exit();
}

// Ambil data pesanan
$orderStmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ? AND status = 'delivered'");
$orderStmt->execute([$order_id, $user_id]);
$order = $orderStmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
  $_SESSION['message'] = ['type' => 'error', 'text' => 'Pesanan tidak ditemukan atau belum dapat diulas.'];
  header("Location: orders.php");
  exit();
}

// Ambil item pesanan
$itemsStmt = $pdo->prepare("SELECT oi.*, p.id as product_id, p.name, p.image FROM order_items oi 
                             LEFT JOIN products p ON oi.product_id = p.id 
                             WHERE oi.order_id = ?");
$itemsStmt->execute([$order_id]);
$items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil user_name dari session atau database
$userStmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$userStmt->execute([$user_id]);
$userData = $userStmt->fetch(PDO::FETCH_ASSOC);
$user_name = $userData['name'] ?? $_SESSION['user_name'] ?? 'Anonymous';

// Proses submit ulasan
$message = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
  $review_text = isset($_POST['review_text']) ? trim($_POST['review_text']) : '';
  $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

  // Validasi
  if ($rating < 1 || $rating > 5) {
    $message = ['type' => 'error', 'text' => 'Rating harus antara 1-5.'];
  } elseif (strlen($review_text) < 10) {
    $message = ['type' => 'error', 'text' => 'Ulasan minimal 10 karakter.'];
  } elseif ($product_id <= 0) {
    $message = ['type' => 'error', 'text' => 'Produk tidak valid.'];
  } else {
    try {
      // Cek apakah produk ada di order ini
      $checkStmt = $pdo->prepare("SELECT id FROM order_items WHERE order_id = ? AND product_id = ?");
      $checkStmt->execute([$order_id, $product_id]);
      
      if ($checkStmt->rowCount() === 0) {
        $message = ['type' => 'error', 'text' => 'Produk tidak ditemukan dalam pesanan ini.'];
      } else {
        // Cek apakah sudah ada review dari user untuk produk ini
        $existingReview = $pdo->prepare("SELECT id FROM reviews WHERE product_id = ? AND user_name = ?");
        $existingReview->execute([$product_id, $user_name]);

        if ($existingReview->rowCount() > 0) {
          // Update review jika sudah ada
          $updateReview = $pdo->prepare("UPDATE reviews SET rating = ?, review_text = ?, created_at = NOW() WHERE product_id = ? AND user_name = ?");
          $updateReview->execute([$rating, $review_text, $product_id, $user_name]);
          $message = ['type' => 'success', 'text' => 'Ulasan berhasil diperbarui!'];
        } else {
          // Insert review baru
          $insertReview = $pdo->prepare("INSERT INTO reviews (product_id, user_name, rating, review_text, created_at) 
                                         VALUES (?, ?, ?, ?, NOW())");
          $insertReview->execute([$product_id, $user_name, $rating, $review_text]);
          $message = ['type' => 'success', 'text' => 'Ulasan berhasil ditambahkan!'];
        }
      }
    } catch (Exception $e) {
      $message = ['type' => 'error', 'text' => 'Terjadi kesalahan: ' . $e->getMessage()];
    }
  }

  if ($message['type'] === 'success') {
    header("Location: orders.php");
    exit();
  }
}

// Ambil review yang sudah ada
$reviewsStmt = $pdo->prepare("SELECT * FROM reviews WHERE user_name = ?");
$reviewsStmt->execute([$user_name]);
$existingReviews = $reviewsStmt->fetchAll(PDO::FETCH_ASSOC);
$reviewsMap = [];
foreach ($existingReviews as $review) {
  $reviewsMap[$review['product_id']] = $review;
}
?>

<link rel="stylesheet" href="assets/css/review.css">

<div class="review-container" style="max-width: 900px; margin: 40px auto; padding: 20px;">
  <div class="review-header" style="margin-bottom: 30px;">
    <h2 style="margin: 0 0 10px 0; color: #333;"><i class="bi bi-chat-square-text"></i> Ulasan Pesanan #<?= htmlspecialchars($order_id) ?></h2>
    <p style="margin: 0; color: #666; font-size: 14px;">Berikan ulasan untuk produk yang telah Anda terima</p>
  </div>

  <?php if ($message): ?>
    <div class="alert alert-<?= htmlspecialchars($message['type']) ?>" style="padding: 15px; margin-bottom: 20px; border-radius: 8px; display: flex; align-items: center; gap: 10px; font-size: 14px; <?= $message['type'] === 'success' ? 'background: #d4edda; color: #155724; border: 1px solid #c3e6cb;' : 'background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;' ?>">
      <i class="bi bi-<?= $message['type'] === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill' ?>"></i>
      <?= htmlspecialchars($message['text']) ?>
    </div>
  <?php endif; ?>

  <!-- Informasi Pesanan -->
  <div class="order-info" style="background: #f9f9f9; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
    <h3 style="margin: 0 0 15px 0; font-size: 16px; color: #333;">Informasi Pesanan</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; font-size: 14px;">
      <div>
        <p style="margin: 0 0 5px 0; color: #666; font-size: 12px; text-transform: uppercase;">No. Pesanan</p>
        <p style="margin: 0; font-weight: 600; color: #333;">#<?= htmlspecialchars($order_id) ?></p>
      </div>
      <div>
        <p style="margin: 0 0 5px 0; color: #666; font-size: 12px; text-transform: uppercase;">Tanggal Pesanan</p>
        <p style="margin: 0; font-weight: 600; color: #333;"><?= date('d M Y H:i', strtotime($order['created_at'])) ?></p>
      </div>
      <div>
        <p style="margin: 0 0 5px 0; color: #666; font-size: 12px; text-transform: uppercase;">Total Pesanan</p>
        <p style="margin: 0; font-weight: 600; color: #0084ff;">Rp <?= number_format($order['total'], 0, ',', '.') ?></p>
      </div>
    </div>
  </div>

  <!-- Form Ulasan untuk Setiap Produk -->
  <div class="reviews-list">
    <?php if (empty($items)): ?>
      <div style="text-align: center; padding: 40px 20px; background: #f9f9f9; border-radius: 8px;">
        <i class="bi bi-inbox" style="font-size: 32px; color: #ddd; margin-bottom: 10px;"></i>
        <p style="color: #999; margin: 0;">Tidak ada produk dalam pesanan ini.</p>
      </div>
    <?php else: ?>
      <?php foreach ($items as $item): ?>
        <?php $existingReview = $reviewsMap[$item['product_id']] ?? null; ?>
        <div class="review-form-card" style="background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
          <div style="display: flex; gap: 15px; margin-bottom: 20px;">
            <!-- Gambar Produk -->
            <div style="flex-shrink: 0;">
              <?php if (!empty($item['image'])): ?>
                <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" style="width: 80px; height: 80px; object-fit: cover; border-radius: 6px;">
              <?php else: ?>
                <div style="width: 80px; height: 80px; background: #f0f0f0; border-radius: 6px; display: flex; align-items: center; justify-content: center;">
                  <i class="bi bi-image" style="font-size: 24px; color: #ddd;"></i>
                </div>
              <?php endif; ?>
            </div>

            <!-- Info Produk -->
            <div style="flex: 1;">
              <h4 style="margin: 0 0 8px 0; color: #333;"><?= htmlspecialchars($item['name']) ?></h4>
              <p style="margin: 0; color: #666; font-size: 14px;">
                Jumlah: <strong><?= $item['quantity'] ?></strong> | 
                Harga: <strong>Rp <?= number_format($item['price'], 0, ',', '.') ?></strong>
              </p>
              <?php if ($existingReview): ?>
                <p style="margin: 5px 0 0 0; color: #0084ff; font-size: 13px;">
                  <i class="bi bi-check-circle"></i> Sudah diulas (Rating: <?= $existingReview['rating'] ?>/5)
                </p>
              <?php endif; ?>
            </div>
          </div>

          <!-- Form Ulasan -->
          <form method="POST" style="background: #f9f9f9; padding: 15px; border-radius: 6px;">
            <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">

            <!-- Rating -->
            <div style="margin-bottom: 15px;">
              <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333; font-size: 14px;">Rating Produk <span style="color: #dc3545;">*</span></label>
              <div class="rating-input" style="display: flex; gap: 10px; font-size: 24px;">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                  <label style="cursor: pointer; display: flex; align-items: center; gap: 5px;">
                    <input type="radio" name="rating" value="<?= $i ?>" <?= ($existingReview && $existingReview['rating'] == $i) ? 'checked' : '' ?> style="cursor: pointer;">
                    <i class="bi bi-star-fill rating-star" style="color: #ddd; transition: color 0.2s;" data-value="<?= $i ?>"></i>
                  </label>
                <?php endfor; ?>
              </div>
            </div>

            <!-- Review Text -->
            <div style="margin-bottom: 15px;">
              <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333; font-size: 14px;">Ulasan <span style="color: #dc3545;">*</span></label>
              <textarea name="review_text" placeholder="Bagikan pengalaman Anda dengan produk ini... (minimal 10 karakter)" style="width: 100%; min-height: 100px; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-family: Arial, sans-serif; font-size: 14px; resize: vertical;"><?= htmlspecialchars($existingReview['review_text'] ?? '') ?></textarea>
              <p style="margin: 5px 0 0 0; font-size: 12px; color: #999;">Karakter: <span id="char-count-<?= $item['product_id'] ?>">0</span>/500</p>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn-submit" style="padding: 10px 24px; background: #0084ff; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 6px;">
              <i class="bi bi-send"></i> <?= $existingReview ? 'Perbarui Ulasan' : 'Kirim Ulasan' ?>
            </button>
          </form>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Tombol Kembali -->
  <div style="text-align: center; margin-top: 30px;">
    <a href="orders.php" class="btn-back" style="padding: 10px 24px; background: #f0f0f0; color: #333; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; font-size: 14px;">
      <i class="bi bi-arrow-left"></i> Kembali ke Pesanan
    </a>
  </div>
</div>

<script>
// Rating star interaktif
document.querySelectorAll('.rating-input').forEach(ratingInput => {
  const stars = ratingInput.querySelectorAll('.rating-star');
  const radioButtons = ratingInput.querySelectorAll('input[type="radio"]');

  stars.forEach((star, index) => {
    star.addEventListener('mouseover', function() {
      stars.forEach((s, i) => {
        if (i <= index) {
          s.style.color = '#ffc107';
        } else {
          s.style.color = '#ddd';
        }
      });
    });
  });

  ratingInput.addEventListener('mouseout', function() {
    const checkedValue = ratingInput.querySelector('input[type="radio"]:checked')?.value || 0;
    stars.forEach((s, i) => {
      if (i < checkedValue) {
        s.style.color = '#ffc107';
      } else {
        s.style.color = '#ddd';
      }
    });
  });

  radioButtons.forEach((radio, index) => {
    if (radio.checked) {
      stars[index].style.color = '#ffc107';
    }
  });
});

// Character counter
document.querySelectorAll('textarea[name="review_text"]').forEach(textarea => {
  const form = textarea.closest('form');
  const productId = form.querySelector('input[name="product_id"]').value;
  const counterElement = document.getElementById(`char-count-${productId}`);

  textarea.addEventListener('input', function() {
    counterElement.textContent = this.value.length;
    if (this.value.length > 500) {
      this.value = this.value.substring(0, 500);
      counterElement.textContent = 500;
    }
  });

  // Set initial count
  counterElement.textContent = textarea.value.length;
});
</script>

<?php require 'includes/footer.php'; ?>