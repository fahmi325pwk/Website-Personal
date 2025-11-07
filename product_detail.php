<?php
session_start();
include 'includes/header.php';
?>

<style>
.navbar .input-group {
  display: none;
}
</style>



<div class="container">
  <?php
  require 'includes/db.php';
  $id = intval($_GET['id'] ?? 0);
  $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
  $stmt->execute([$id]);
  $product = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$product) {
    echo "<div class='error-page'><h2>Produk tidak ditemukan</h2><p>Produk yang Anda cari tidak tersedia.</p><a href='products.php' class='btn'>Kembali ke Produk</a></div>";
  } else {
    // Get related products (random products excluding current one)
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id != ? ORDER BY RAND() LIMIT 4");
    $stmt->execute([$product['id']]);
    $related_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get rating statistics
    $stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM reviews WHERE product_id = ?");
    $stmt->execute([$product['id']]);
    $rating_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    $avg_rating = round($rating_stats['avg_rating'], 1);
    $total_reviews = $rating_stats['total_reviews'];
  ?>
  <link rel="stylesheet" href="assets/css/product_detail.css">
  
  <!-- Breadcrumb Navigation -->
  <nav class="breadcrumb">
    <a href="index.php">🏠 Beranda</a> 
    <span class="separator">›</span>
    <a href="products.php">📦 Produk</a>
    <span class="separator">›</span>
    <span class="current"><?= htmlspecialchars($product['name']) ?></span>
  </nav>
    <!-- Product Main Section -->
    <div class="product-detail">
      <div class="product-image-gallery">
        <div class="main-image">
          <img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" id="mainImage">
          <div class="image-zoom-overlay" id="zoomOverlay">
            <img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
          </div>
        </div>
        <div class="image-thumbnails">
          <img src="<?= htmlspecialchars($product['image']) ?>" alt="Thumbnail 1" class="thumbnail active" onclick="changeImage(this.src)">
          <!-- Additional thumbnails can be added here -->
        </div>
      </div>

      <div class="product-info">
        <div class="product-header">
          <h1><?= htmlspecialchars($product['name']) ?></h1>
          <div class="product-meta">
            <span class="category">📂 Komputer</span>
            <span class="stock-status <?= $product['stock'] > 0 ? 'in-stock' : 'out-of-stock' ?>">
              <?= $product['stock'] > 0 ? '✅ Tersedia' : '❌ Stok Habis' ?>
            </span>
          </div>
        </div>

        <div class="rating-summary">
          <div class="rating-stars">
            <?php for($i = 1; $i <= 5; $i++): ?>
              <span class="star <?= $i <= $avg_rating ? 'filled' : '' ?>">★</span>
            <?php endfor; ?>
          </div>
          <span class="rating-text">
            <?= $avg_rating > 0 ? $avg_rating : 'Belum ada rating' ?> 
            (<?= $total_reviews ?> ulasan)
          </span>
        </div>

        <div class="price-section">
          <span class="current-price">Rp <?= number_format($product['price'], 0, ',', '.') ?></span>
          <?php if($product['price'] && $product['price'] > $product['price']): ?>
            <span class="original-price">Rp <?= number_format($product['original_price'], 0, ',', '.') ?></span>
            <span class="discount">-<?= round((($product['original_price'] - $product['price']) / $product['original_price']) * 100) ?>%</span>
          <?php endif; ?>
        </div>

        <div class="product-description">
          <h3>📝 Deskripsi Produk</h3>
          <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
        </div>

        <div class="product-specifications">
          <h3>⚙️ Spesifikasi</h3>
          <div class="specs-grid">
            <div class="spec-item">
              <span class="spec-label">Kategori:</span>
              <span class="spec-value">Komputer</span>
            </div>
            <div class="spec-item">
              <span class="spec-label">Stok:</span>
              <span class="spec-value"><?= $product['stock'] ?> unit</span>
            </div>
            <div class="spec-item">
              <span class="spec-label">Berat:</span>
              <span class="spec-value">1.2 kg</span>
            </div>
            <div class="spec-item">
              <span class="spec-label">Garansi:</span>
              <span class="spec-value">1 Tahun</span>
            </div>
          </div>
        </div>

        <div class="action-buttons">
          <?php if(isset($_SESSION['user_id'])): ?>
            <form method="POST" action="cart.php" class="add-to-cart-form" id="add-to-cart-form">
              <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
              <div class="quantity-selector">
                <label for="quantity">Jumlah:</label>
                <div class="quantity-controls">
                  <button type="button" class="qty-btn" onclick="decreaseQty()">-</button>
                  <input type="number" name="quantity" id="quantity" value="1" min="1" max="<?= $product['stock'] ?>" class="qty-input">
                  <button type="button" class="qty-btn" onclick="increaseQty()">+</button>
                </div>
              </div>
              <button type="submit" class="btn btn-primary" <?= $product['stock'] <= 0 ? 'disabled' : '' ?>>
                🛒 <?= $product['stock'] > 0 ? 'Tambah ke Keranjang' : 'Stok Habis' ?>
              </button>
            </form>
            <div class="secondary-actions">
              <button type="submit" form="add-to-cart-form" name="action" value="buy_now" class="btn btn-secondary" <?= $product['stock'] <= 0 ? 'disabled' : '' ?>>
               🛍️ Beli Sekarang
              </button>
              <button class="btn btn-secondary" onclick="shareProduct()">
                📤 Bagikan
              </button>
            </div>
          <?php else: ?>
            <div class="login-alert">
              <p>🔒 Anda harus login terlebih dahulu untuk menambahkan produk ke keranjang</p>
              <a href="login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn btn-primary">
                Login untuk Membeli
              </a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

<!-- Enhanced Review Section -->
<div class="review-section">
  <div class="review-header">
    <h3>💬 Ulasan & Rating</h3>
    <div class="review-stats">
      <div class="rating-breakdown">
        <?php for($i = 5; $i >= 1; $i--): ?>
          <div class="rating-bar">
            <span class="rating-label"><?= $i ?> ⭐</span>
            <div class="rating-progress">
              <div class="rating-fill" style="width: <?= $total_reviews > 0 ? (($i == 5 ? 60 : ($i == 4 ? 25 : ($i == 3 ? 10 : ($i == 2 ? 3 : 2))))) : 0 ?>%"></div>
            </div>
            <span class="rating-count"><?= $total_reviews > 0 ? ($i == 5 ? 6 : ($i == 4 ? 2 : ($i == 3 ? 1 : ($i == 2 ? 0 : 0)))) : 0 ?></span>
          </div>
        <?php endfor; ?>
      </div>
    </div>
  </div>
</div>

<!-- Daftar Ulasan -->
<div class="review-list">
  <h3>Ulasan Pembeli</h3>
  <?php
  $stmt = $pdo->prepare("SELECT * FROM reviews WHERE product_id = ? ORDER BY created_at DESC");
  $stmt->execute([$product['id']]);
  $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
  if (count($reviews) > 0) {
    foreach ($reviews as $review) {
      echo "<div class='review-item'>";
      echo "<div class='review-author'>".htmlspecialchars($review['user_name'])."</div>";
      echo "<div class='review-rating'>".str_repeat('★', $review['rating']).str_repeat('☆', 5-$review['rating'])."</div>";
      echo "<div class='review-text'>".nl2br(htmlspecialchars($review['review_text']))."</div>";
      echo "<div class='review-date'>".date('d M Y H:i', strtotime($review['created_at']))."</div>";
      echo "</div>";
    }
  } else {
    echo "<p>Belum ada ulasan untuk produk ini.</p>";
  }
  ?>
</div>

     <!-- Related Products Section -->
     <?php if(count($related_products) > 0): ?>
     <div class="related-products">
       <h3>🔗 Produk Terkait</h3>
       <div class="related-grid">
         <?php foreach($related_products as $related): ?>
         <div class="related-item">
           <a href="product_detail.php?id=<?= $related['id'] ?>">
             <img src="<?= htmlspecialchars($related['image']) ?>" alt="<?= htmlspecialchars($related['name']) ?>">
             <h4><?= htmlspecialchars($related['name']) ?></h4>
             <p class="related-price">Rp <?= number_format($related['price'], 0, ',', '.') ?></p>
           </a>
         </div>
         <?php endforeach; ?>
       </div>
     </div>
     <?php endif; ?>
  <?php } ?>
</div>

<!-- JavaScript for Enhanced Functionality -->
<script>
// Image Gallery Functions
function changeImage(src) {
  document.getElementById('mainImage').src = src;
  document.querySelectorAll('.thumbnail').forEach(thumb => thumb.classList.remove('active'));
  event.target.classList.add('active');
}

// Image Zoom Functionality
document.getElementById('mainImage').addEventListener('click', function() {
  const overlay = document.getElementById('zoomOverlay');
  overlay.style.display = 'flex';
  overlay.addEventListener('click', function() {
    overlay.style.display = 'none';
  });
});

// Quantity Controls
function increaseQty() {
  const qtyInput = document.getElementById('quantity');
  const max = parseInt(qtyInput.getAttribute('max'));
  const current = parseInt(qtyInput.value);
  if (current < max) {
    qtyInput.value = current + 1;
  }
}

function decreaseQty() {
  const qtyInput = document.getElementById('quantity');
  const current = parseInt(qtyInput.value);
  if (current > 1) {
    qtyInput.value = current - 1;
  }
}



// Share Product Function
function shareProduct() {
  if (navigator.share) {
    navigator.share({
      title: '<?= htmlspecialchars($product['name']) ?>',
      text: 'Lihat produk ini di BackKomputer',
      url: window.location.href
    });
  } else {
    // Fallback for browsers that don't support Web Share API
    const url = window.location.href;
    navigator.clipboard.writeText(url).then(() => {
      alert('Link produk disalin ke clipboard!');
    });
  }
}

// Star Rating Animation
document.querySelectorAll('.star-rating input[type="radio"]').forEach(radio => {
  radio.addEventListener('change', function() {
    const rating = this.value;
    const labels = document.querySelectorAll('.star-rating label');
    labels.forEach((label, index) => {
      if (index < rating) {
        label.classList.add('active');
      } else {
        label.classList.remove('active');
      }
    });
  });
});

// Smooth Scroll for Anchor Links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
  anchor.addEventListener('click', function (e) {
    e.preventDefault();
    const target = document.querySelector(this.getAttribute('href'));
    if (target) {
      target.scrollIntoView({
        behavior: 'smooth',
        block: 'start'
      });
    }
  });
});


</script>

<?php include 'includes/footer.php'; ?>