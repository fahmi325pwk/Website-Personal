<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}
require '../includes/db.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard | Nano Komputer</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/admin-style.css">

</head>
<body>
  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="sidebar-header">
      <div class="logo">
        <i class="fas fa-laptop-code"></i>
        <span>Nano Komputer</span>
      </div>
    </div>
    
    <nav class="sidebar-nav">
      <a href="dashboard.php" class="nav-item active">
        <i class="fas fa-box"></i>
        <span>Produk</span>
      </a>
      <a href="add_product.php" class="nav-item">
        <i class="fas fa-plus-circle"></i>
        <span>Tambah Produk</span>
      </a>
      <a href="orders.php" class="nav-item">
        <i class="fas fa-receipt"></i>
        <span>Pesanan</span>
      </a>
      <a href="sales_report.php" class="nav-item">
        <i class="fas fa-chart-line"></i>
        <span>Laporan Penjualan</span>
      </a>
      <a href="logout.php" class="nav-item logout">
        <i class="fas fa-sign-out-alt"></i>
        <span>Logout</span>
      </a>
    </nav>
    
    <div class="sidebar-footer">
      <div class="admin-info">
        <i class="fas fa-user-shield"></i>
        <span><?= htmlspecialchars($_SESSION['admin']) ?></span>
      </div>
    </div>
  </aside>

  <!-- Main Content -->
  <div class="main-content">
    <header class="top-header">
      <div class="header-left">
        <button class="menu-toggle" id="menuToggle">
          <i class="fas fa-bars"></i>
        </button>
        <h1>Dashboard Admin</h1>
      </div>
      <div class="header-right">
        <div>
          <div class="user-info">
            <span>Selamat datang, <strong><?= htmlspecialchars($_SESSION['admin']) ?></strong></span>
            <div class="avatar">
              <i class="fas fa-user-circle"></i>
            </div>
          </div>
        </div>
      </div>
    </header>

    <main class="content">
      <?php
      // Display success/error messages
      if (isset($_SESSION['success_message'])) {
        echo '<div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span>' . htmlspecialchars($_SESSION['success_message']) . '</span>
              </div>';
        unset($_SESSION['success_message']);
      }

      if (isset($_SESSION['error_message'])) {
        echo '<div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span>' . htmlspecialchars($_SESSION['error_message']) . '</span>
              </div>';
        unset($_SESSION['error_message']);
      }

      // --- New: dashboard stats queries (overall totals) ---
      try {
        $total_products_overall = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
        $total_sales_overall = (int)$pdo->query("SELECT IFNULL(SUM(total),0) FROM orders WHERE status='delivered'")->fetchColumn();
        $pending_orders_overall = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn();
      } catch (Exception $e) {
        $total_products_overall = 0;
        $total_sales_overall = 0;
        $pending_orders_overall = 0;
      }

      // Server-side search & pagination
      $search = trim($_GET['search'] ?? '');
      $page = max(1, (int)($_GET['page'] ?? 1));
      $per_page = 20;
      $offset = ($page - 1) * $per_page;

      $whereSql = '';
      $params = [];
      if ($search !== '') {
        $whereSql = " WHERE name LIKE ? OR CAST(id AS CHAR) LIKE ?";
        $params = ["%$search%", "%$search%"]; 
      }

      $countStmt = $pdo->prepare("SELECT COUNT(*) FROM products" . $whereSql);
      $countStmt->execute($params);
      $total_products = (int)$countStmt->fetchColumn();

      $listStmt = $pdo->prepare("SELECT * FROM products" . $whereSql . " ORDER BY id DESC LIMIT $per_page OFFSET $offset");
      $listStmt->execute($params);

      // --- Additional: Recent Orders ---
      $recentOrdersStmt = $pdo->prepare("SELECT id, customer_name, total, status, created_at FROM orders ORDER BY created_at DESC LIMIT 5");
      $recentOrdersStmt->execute();
      $recentOrders = $recentOrdersStmt->fetchAll(PDO::FETCH_ASSOC);

      // --- Additional: Low Stock Alerts ---
      $lowStockStmt = $pdo->prepare("SELECT id, name, stock FROM products WHERE stock <= 5 ORDER BY stock ASC LIMIT 5");
      $lowStockStmt->execute();
      $lowStockProducts = $lowStockStmt->fetchAll(PDO::FETCH_ASSOC);
      ?>

      <!-- Dashboard Stats (new) -->
      <div class="dashboard-stats">
        <div class="stat-card">
          <i class="fas fa-box"></i>
          <div>
            <h3><?= $total_products_overall ?></h3>
            <p>Produk Terdaftar</p>
          </div>
        </div>
        <div class="stat-card">
          <i class="fas fa-shopping-cart"></i>
          <div>
            <h3><?= $pending_orders_overall ?></h3>
            <p>Pesanan Pending</p>
          </div>
        </div>
        <div class="stat-card">
          <i class="fas fa-money-bill-wave"></i>
          <div>
            <h3>Rp <?= number_format($total_sales_overall,0,',','.') ?></h3>
            <p>Total Penjualan</p>
          </div>
        </div>
      </div>

      <!-- Dashboard Sections -->
      <div class="dashboard-sections">
        <div class="section">
          <h3><i class="fas fa-clock"></i> Pesanan Terbaru</h3>
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Pelanggan</th>
                <th>Total</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentOrders as $order): ?>
              <tr>
                <td><span class="badge">#<?= $order['id'] ?></span></td>
                <td><?= htmlspecialchars($order['customer_name']) ?></td>
                <td>Rp <?= number_format($order['total'], 0, ',', '.') ?></td>
                <td><span class="status <?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="section">
          <h3><i class="fas fa-exclamation-triangle"></i> Stok Rendah</h3>
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Produk</th>
                <th>Stok</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($lowStockProducts as $product): ?>
              <tr>
                <td><span class="badge">#<?= $product['id'] ?></span></td>
                <td><?= htmlspecialchars($product['name']) ?></td>
                <td><span class="low-stock"><?= $product['stock'] ?></span></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="content-header">
        <h2><i class="fas fa-boxes"></i> Daftar Produk</h2>
        <form method="get" class="search-form">
          <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari ID/Nama produk...">
          <button class="btn" type="submit" title="Cari"><i class="fas fa-search"></i></button>
          <?php if ($search !== ''): ?>
            <a class="btn" href="dashboard.php" title="Reset"><i class="fas fa-undo"></i></a>
          <?php endif; ?>
        </form>
        <a href="add_product.php" class="btn btn-primary">
          <i class="fas fa-plus"></i> Tambah Produk Baru
        </a>
      </div>

      <div class="card">
        <div class="card-body">
          <div class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Gambar</th>
                  <th>Nama Produk</th>
                  <th>Stok</th>
                  <th>Harga</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php
                foreach ($listStmt as $row) {
                    $lowStock = isset($row['stock']) ? (int)$row['stock'] <= 5 : false;
                    echo "<tr>\n" .
                      "  <td><span class='badge'>{$row['id']}</span></td>\n" .
                      "  <td>\n" .
                      "    <div class='product-img'>\n" .
                      "      <img src='../{$row['image']}' alt='" . htmlspecialchars($row['name']) . "'>\n" .
                      "    </div>\n" .
                      "  </td>\n" .
                      "  <td class='product-name'>" . htmlspecialchars($row['name']) . "</td>\n" .
                      "  <td>" . (isset($row['stock'])
                        ? ("<span " . ($lowStock ? "style='color:#b45309;background:#fffbeb;padding:4px 8px;border-radius:12px;font-weight:600;'" : "") . ">" . (int)$row['stock'] . "</span>")
                        : "-") . "</td>\n" .
                      "  <td class='price'>Rp " . number_format($row['price'], 0, ',', '.') . "</td>\n" .
                      "  <td class='action-buttons'>\n" .
                      "    <a href='edit_product.php?id={$row['id']}' class='btn-action btn-edit' title='Edit'>\n" .
                      "      <i class='fas fa-edit'></i>\n" .
                      "    </a>\n" .
                      "    <a href='delete_product_advanced.php?id={$row['id']}' class='btn-action btn-delete' title='Hapus' onclick=\"return confirm('Apakah Anda yakin ingin menghapus produk ini?')\">\n" .
                      "      <i class='fas fa-trash'></i>\n" .
                      "    </a>\n" .
                      "  </td>\n" .
                      "</tr>\n";
                }
                ?>
              </tbody>
            </table>
            <?php
              $total_pages = (int)ceil($total_products / $per_page);
              if ($total_pages > 1):
            ?>
            <div class="pagination">
              <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                <?php
                  $qs = http_build_query([
                    'search' => $search,
                    'page' => $p
                  ]);
                ?>
                <a href="dashboard.php?<?= $qs ?>" class="btn <?= $p==$page ? 'btn-primary' : '' ?>"><?= $p ?></a>
              <?php endfor; ?>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </main>

    <footer class="footer">
      <p>&copy; <?= date('Y') ?> Nano Komputer. All rights reserved.</p>
    </footer>
  </div>

  <script>
    // Toggle Sidebar
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');

    menuToggle.addEventListener('click', function() {
      sidebar.classList.toggle('collapsed');
      mainContent.classList.toggle('expanded');
    });

    // Add animation on load
    document.addEventListener('DOMContentLoaded', function() {
      const rows = document.querySelectorAll('.data-table tbody tr');
      rows.forEach((row, index) => {
        setTimeout(() => {
          row.style.opacity = '1';
          row.style.transform = 'translateY(0)';
        }, index * 50);
      });
      
      // Auto-hide alerts after 5 seconds
      const alerts = document.querySelectorAll('.alert');
      alerts.forEach(alert => {
        setTimeout(() => {
          alert.style.transition = 'opacity 0.5s ease';
          alert.style.opacity = '0';
          setTimeout(() => { alert.remove(); }, 500);
        }, 5000);
      });
    });
  </script>
</body>
</html>