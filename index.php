<?php 
session_start();
include 'includes/header.php'; 
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<?php require 'includes/db.php'; ?>

<!-- ===== HERO SECTION ===== -->
<section class="hero">
  <div class="hero-overlay"></div>
  <div class="hero-content container">
      <div class="hero-box">
      <div class="hero-box-content">
        <p class="hero-box-label">Produk Baru</p>
        <h2 class="hero-box-title">Temukan Pilihan Terbaru!</h2>
        <p class="hero-box-description">
          Saatnya upgrade perangkatmu dengan produk unggulan berkualitas tinggi.
        </p>
        <a href="products.php" class="hero-box-btn">BELI SEKARANG</a>
      </div>
    </div>
  </div>
</section>

<!-- ===== FEATURES SECTION ===== -->
<section class="features-section">
  <div class="container">
    <div class="features-grid">
      <div class="feature-item">
        <i class="bi bi-truck feature-icon"></i>
        <div class="feature-text">
          <h3>Gratis Ongkir</h3>
          <p>Untuk pembelian tertentu</p>
        </div>
      </div>
      <div class="feature-item">
        <i class="bi bi-shield-check feature-icon"></i>
        <div class="feature-text">
          <h3>Garansi Produk</h3>
          <p>Produk bergaransi resmi</p>
        </div>
      </div>
      <div class="feature-item">
        <i class="bi bi-patch-check-fill feature-icon"></i>
        <div class="feature-text">
          <h3>100% Autentik</h3>
          <p>Produk asli dan terpercaya</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ===== KATEGORI PRODUK ===== -->
<section class="categories container">
  <div class="section-header">
    <h2 class="section-title"><i class="bi bi-grid-fill"></i> Kategori Produk</h2>
    <p class="section-subtitle">Temukan sparepart komputer sesuai kebutuhanmu</p>
  </div>

  <div class="category-grid">
    <?php
    $categories = [
      ["name" => "Prosesor", "image" => "assets/images/prosesor.jpg", "slug" => "prosesor", "icon" => "bi-cpu-fill"],
      ["name" => "Motherboard", "image" => "assets/images/motherboard.jpg", "slug" => "motherboard", "icon" => "bi-motherboard-fill"],
      ["name" => "Memori (RAM)", "image" => "assets/images/ram.jpg", "slug" => "ram", "icon" => "bi-memory"],
      ["name" => "Penyimpanan", "image" => "assets/images/ssd.jpg", "slug" => "storage", "icon" => "bi-hdd-fill"],
      ["name" => "VGA & GPU", "image" => "assets/images/gpu.png", "slug" => "vga", "icon" => "bi-gpu-card"],
      ["name" => "Power Supply", "image" => "assets/images/psu.jpg", "slug" => "psu", "icon" => "bi-plug-fill"],
      ["name" => "Casing", "image" => "assets/images/casing.jpg", "slug" => "casing", "icon" => "bi-pc-display"],
      ["name" => "Cooler", "image" => "assets/images/cooler.jpg", "slug" => "cooler", "icon" => "bi-fan"],
      ["name" => "Monitor", "image" => "assets/images/monitor.jpg", "slug" => "monitor", "icon" => "bi-display"],
      ["name" => "Keyboard", "image" => "assets/images/keyboard.jpg", "slug" => "keyboard", "icon" => "bi-keyboard-fill"],
      ["name" => "Mouse", "image" => "assets/images/mouse.jpg", "slug" => "mouse", "icon" => "bi-mouse-fill"]
    ];

    foreach ($categories as $cat): ?>
      <a href="products.php?category=<?= $cat['slug'] ?>" class="category-card">
        <div class="category-image">
          <img src="<?= $cat['image'] ?>" alt="<?= htmlspecialchars($cat['name']) ?>">
          <div class="category-icon"><i class="bi <?= $cat['icon'] ?>"></i></div>
        </div>
        <h3 class="category-name"><?= htmlspecialchars($cat['name']) ?></h3>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<!-- ===== PRODUK TERBARU ===== -->
<section class="products container">
  <div class="section-header">
    <h2><i class="bi bi-stars"></i> Komponen Performa Tinggi</h2>
    <p>Tingkatkan kinerja PC kamu dengan pilihan sparepart berkualitas dan terpercaya.</p>
  </div>

  <div class="products-grid">
    <?php
    $stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC LIMIT 8");
    while ($product = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
      <div class="product-card">
        <div class="product-image">
          <img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
        </div>
        <div class="product-info">
          <h3><?= htmlspecialchars($product['name']) ?></h3>
          <p class="price">Rp <?= number_format($product['price'], 0, ',', '.') ?></p>
          <div class="product-actions">
            <?php if(isset($_SESSION['user_id'])): ?>
              <form method="POST" action="cart.php" class="cart-form">
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                <button type="submit" class="btn-cart" title="Tambahkan ke Keranjang">
                  <i class="bi bi-cart3"></i>
                </button>
              </form>
            <?php else: ?>
              <a href="login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn-cart" title="Login untuk menambahkan ke keranjang">
                <i class="bi bi-cart3"></i>
              </a>
            <?php endif; ?>
            <a href="product_detail.php?id=<?= $product['id'] ?>" class="btn-detail">
              <i class="bi bi-eye-fill"></i> Lihat Detail
            </a>
          </div>
        </div>
      </div>
    <?php endwhile; ?>
  </div>
</section>

<div class="view-all">
  <a href="products.php" class="btn-view-all">
    <i class="bi bi-box-arrow-up-right"></i> Lihat Semua Produk
  </a>
</div>

<!-- ===== TESTIMONIAL SECTION ===== -->
<section class="testimonials container">
  <div class="section-header">
    <h2 class="section-title"><i class="bi bi-chat-quote-fill"></i> Apa Kata Pelanggan Kami</h2>
    <p class="section-subtitle">Pengalaman pelanggan yang telah mempercayai produk kami</p>
  </div>

  <div class="testimonials-grid">
    <div class="testimonial-card">
      <div class="testimonial-content">
        <div class="testimonial-stars">
          <i class="bi bi-star-fill"></i>
          <i class="bi bi-star-fill"></i>
          <i class="bi bi-star-fill"></i>
          <i class="bi bi-star-fill"></i>
          <i class="bi bi-star-fill"></i>
        </div>
        <p class="testimonial-text">"Produk yang sangat berkualitas dan pengiriman cepat. Sudah beberapa kali belanja di sini dan selalu puas dengan pelayanannya."</p>
      </div>
      <div class="testimonial-author">
        <div class="author-avatar">
          <i class="bi bi-person-circle"></i>
        </div>
        <div class="author-info">
          <h4>Ahmad Rahman</h4>
          <span>Gamer Enthusiast</span>
        </div>
      </div>
    </div>

    <div class="testimonial-card">
      <div class="testimonial-content">
        <div class="testimonial-stars">
          <i class="bi bi-star-fill"></i>
          <i class="bi bi-star-fill"></i>
          <i class="bi bi-star-fill"></i>
          <i class="bi bi-star-fill"></i>
          <i class="bi bi-star-fill"></i>
        </div>
        <p class="testimonial-text">"Harga kompetitif dengan kualitas premium. Sparepart komputer yang saya beli berfungsi dengan baik dan garansinya jelas."</p>
      </div>
      <div class="testimonial-author">
        <div class="author-avatar">
          <i class="bi bi-person-circle"></i>
        </div>
        <div class="author-info">
          <h4>Sari Dewi</h4>
          <span>Content Creator</span>
        </div>
      </div>
    </div>

    <div class="testimonial-card">
      <div class="testimonial-content">
        <div class="testimonial-stars">
          <i class="bi bi-star-fill"></i>
          <i class="bi bi-star-fill"></i>
          <i class="bi bi-star-fill"></i>
          <i class="bi bi-star-fill"></i>
          <i class="bi bi-star-half"></i>
        </div>
        <p class="testimonial-text">"Website mudah digunakan dan informasi produk lengkap. Customer service responsif saat ada pertanyaan."</p>
      </div>
      <div class="testimonial-author">
        <div class="author-avatar">
          <i class="bi bi-person-circle"></i>
        </div>
        <div class="author-info">
          <h4>Budi Santoso</h4>
          <span>IT Professional</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ===== NEWSLETTER SECTION ===== -->
<section class="newsletter">
  <div class="container">
    <div class="newsletter-content">
      <div class="newsletter-text">
        <h2><i class="bi bi-envelope-paper-fill"></i> Tetap Update dengan Produk Terbaru</h2>
        <p>Dapatkan informasi promo, diskon, dan produk baru langsung di email Anda</p>
      </div>
      <div class="newsletter-form">
        <form class="subscribe-form">
          <div class="input-group">
            <input type="email" placeholder="Masukkan email Anda" required>
            <button type="submit" class="btn-subscribe">
              <i class="bi bi-send-fill"></i> Subscribe
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>

<!-- ===== BACK TO TOP BUTTON ===== -->
<button id="back-to-top" class="back-to-top" title="Kembali ke atas">
  <i class="bi bi-chevron-up"></i>
</button>

<?php include 'includes/footer.php'; ?>
