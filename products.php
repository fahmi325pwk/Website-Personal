<?php include 'includes/header.php'; ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<?php require 'includes/db.php'; ?>
<link rel="stylesheet" href="assets/css/products.css">
<div class="products-page container">

  <!-- ===== PAGE HEADER ===== -->
  <div class="page-header">
    <?php
    $category = $_GET['category'] ?? '';
    $q = trim($_GET['q'] ?? '');
    $sort = $_GET['sort'] ?? 'newest';
    $page = (int)($_GET['page'] ?? 1);
    $per_page = 12; // Jumlah produk per halaman

    // Tentukan judul halaman berdasarkan kategori atau pencarian
    if ($category) {
      $pageTitle = 'Kategori: ' . ucwords(str_replace('-', ' ', $category));
      $pageSubtitle = 'Menampilkan produk untuk kategori ' . htmlspecialchars($category);
    } elseif ($q) {
      $pageTitle = 'Hasil Pencarian';
      $pageSubtitle = 'Menampilkan hasil untuk kata kunci: "' . htmlspecialchars($q) . '"';
    } else {
      $pageTitle = 'Semua Produk';
      $pageSubtitle = 'Temukan komponen komputer terbaik untuk kebutuhan rakit PC kamu';
    }
    ?>
    <h2 class="page-title"><i class="bi bi-cart4"></i> <?= $pageTitle ?></h2>
    <p class="page-subtitle"><?= $pageSubtitle ?></p>

    <!-- Sorting Dropdown -->
    <div class="sort-filter">
      <form method="GET" action="">
        <?php if ($category): ?>
          <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>">
        <?php endif; ?>
        <?php if ($q): ?>
          <input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>">
        <?php endif; ?>
        <label for="sort">Urutkan:</label>
        <select name="sort" id="sort" onchange="this.form.submit()">
          <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Terbaru</option>
          <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Nama A-Z</option>
          <option value="name_desc" <?= $sort === 'name_desc' ? 'selected' : '' ?>>Nama Z-A</option>
          <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Harga Terendah</option>
          <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Harga Tertinggi</option>
        </select>
      </form>
    </div>
  </div>

  <!-- ===== PRODUK GRID ===== -->
  <div class="products-grid">
    <?php
    // Tentukan ORDER BY berdasarkan sort
    $orderBy = 'id DESC'; // default
    if ($sort === 'name_asc') {
      $orderBy = 'name ASC';
    } elseif ($sort === 'name_desc') {
      $orderBy = 'name DESC';
    } elseif ($sort === 'price_asc') {
      $orderBy = 'price ASC';
    } elseif ($sort === 'price_desc') {
      $orderBy = 'price DESC';
    } elseif ($sort === 'newest') {
      $orderBy = 'id DESC';
    }

    // Hitung total produk untuk pagination
    if ($category) {
      $countStmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_slug = ?");
      $countStmt->execute([$category]);
    } elseif ($q) {
      $countStmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE name LIKE ?");
      $countStmt->execute(["%$q%"]);
    } else {
      $countStmt = $pdo->query("SELECT COUNT(*) FROM products");
    }
    $total_products = $countStmt->fetchColumn();
    $total_pages = ceil($total_products / $per_page);
    $offset = ($page - 1) * $per_page;

    // Logika query produk dengan sorting dan pagination
    if ($category) {
      $stmt = $pdo->prepare("SELECT * FROM products WHERE category_slug = ? ORDER BY $orderBy LIMIT $per_page OFFSET $offset");
      $stmt->execute([$category]);
    } elseif ($q) {
      $stmt = $pdo->prepare("SELECT * FROM products WHERE name LIKE ? ORDER BY $orderBy LIMIT $per_page OFFSET $offset");
      $stmt->execute(["%$q%"]);
    } else {
      $stmt = $pdo->prepare("SELECT * FROM products ORDER BY $orderBy LIMIT $per_page OFFSET $offset");
      $stmt->execute();
    }

    if ($stmt->rowCount() > 0) {
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
        <div class="product-card">
          <div class="product-image">
            <a href="product_detail.php?id=<?= $row['id'] ?>">
              <img src="<?= htmlspecialchars($row['image']) ?>" alt="<?= htmlspecialchars($row['name']) ?>">
            </a>
          </div>
          <div class="product-info">
            <h3><?= htmlspecialchars($row['name']) ?></h3>
            <p class="price">Rp <?= number_format($row['price'], 0, ',', '.') ?></p>
            <div class="product-buttons">
              <form action="cart.php" method="POST" style="display: inline;">
                <input type="hidden" name="product_id" value="<?= $row['id'] ?>">
                <input type="hidden" name="action" value="buy_now">
                <button type="submit" class="btn-buy-now">
                  <i class="bi bi-bag-check"></i> Beli Sekarang
                </button>
              </form>
              <form action="cart.php" method="POST" style="display: inline;">
                <input type="hidden" name="product_id" value="<?= $row['id'] ?>">
                <button type="submit" class="btn-add-cart">
                  <i class="bi bi-cart-plus"></i> Keranjang
                </button>
              </form>
            </div>
          </div>
        </div>
      <?php endwhile;
    } else { ?>
      <p class="no-result">❌ Produk tidak ditemukan.</p>
    <?php } ?>
  </div>

  <!-- ===== PAGINATION ===== -->
  <?php if ($total_pages > 1): ?>
    <div class="pagination">
      <?php
      $base_url = '?';
      if ($category) $base_url .= "category=$category&";
      if ($q) $base_url .= "q=$q&";
      if ($sort !== 'newest') $base_url .= "sort=$sort&";
      $base_url = rtrim($base_url, '&');
      ?>
      <?php if ($page > 1): ?>
        <a href="<?= $base_url ?>&page=<?= $page - 1 ?>" class="page-link">&laquo; Sebelumnya</a>
      <?php endif; ?>

      <?php for ($p = max(1, $page - 2); $p <= min($total_pages, $page + 2); $p++): ?>
        <a href="<?= $base_url ?>&page=<?= $p ?>" class="page-link <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
      <?php endfor; ?>

      <?php if ($page < $total_pages): ?>
        <a href="<?= $base_url ?>&page=<?= $page + 1 ?>" class="page-link">Selanjutnya &raquo;</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
