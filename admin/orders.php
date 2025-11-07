<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}
require '../includes/db.php';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id']) && isset($_POST['status'])) {
    try {
        $updateStmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $updateStmt->execute([$_POST['status'], $_POST['order_id']]);
        $_SESSION['success_message'] = 'Status pesanan berhasil diperbarui!';
    } catch (Exception $e) {
        $_SESSION['error_message'] = 'Gagal memperbarui status pesanan.';
    }
    header("Location: orders.php");
    exit;
}

// Delete order
if (isset($_GET['delete_id'])) {
    try {
        // First delete order items
        $deleteItemsStmt = $pdo->prepare("DELETE FROM order_items WHERE order_id = ?");
        $deleteItemsStmt->execute([$_GET['delete_id']]);

        // Then delete the order
        $deleteStmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
        $deleteStmt->execute([$_GET['delete_id']]);
        $_SESSION['success_message'] = 'Pesanan berhasil dihapus!';
    } catch (Exception $e) {
        $_SESSION['error_message'] = 'Gagal menghapus pesanan.';
    }
    header("Location: orders.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pesanan | Nano Komputer</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/admin-style.css">
  <style>
    .orders-stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 16px;
      margin-bottom: 20px;
    }
    .stat-card {
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.05);
      padding: 18px;
      display: flex;
      align-items: center;
      gap: 14px;
      transition: transform 0.18s ease;
    }
    .stat-card:hover { transform: translateY(-4px); }
    .stat-card i { font-size: 22px; color: #2563eb; }
    .stat-card h3 { margin:0; font-size:14px; }
    .stat-card p { margin:0; color:#666; font-size:13px; }
    
    
    .status-badge {
      display: inline-block;
      padding: 5px 12px;
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
    
    .filter-form {
      display: flex;
      gap: 12px;
      align-items: center;
      flex-wrap: wrap;
      margin-bottom: 16px;
    }
    .filter-form select, .filter-form input {
      padding: 8px 10px;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-size: 14px;
    }
    
    .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
    .modal-content { background: #fefefe; margin: 5% auto; padding: 20px; border-radius: 8px; width: 90%; max-width: 600px; max-height: 80vh; overflow-y: auto; }
    .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
    .modal-header h2 { margin: 0; }
    .close-modal { background: none; border: none; font-size: 24px; cursor: pointer; }
    
    .data-table tbody tr { opacity: 0; transform: translateY(8px); transition: all 0.35s ease; }
    
    .action-buttons { display: flex; gap: 6px; }
    
    .status-select {
      padding: 6px 10px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 13px;
      cursor: pointer;
    }
    
    .order-info-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
      margin-bottom: 16px;
    }
    
    .info-item {
      padding: 10px;
      background: #f5f5f5;
      border-radius: 6px;
      font-size: 13px;
    }
    
    .info-item strong {
      display: block;
      color: #333;
      margin-bottom: 4px;
    }

    .status-form {
      display: flex;
      gap: 8px;
      align-items: center;
    }

    .status-form select {
      padding: 6px 10px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 13px;
    }

    .status-form button {
      padding: 6px 12px;
      background: #2563eb;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 13px;
    }

    .status-form button:hover {
      background: #1d4ed8;
    }

    body.dark-mode .modal-content {
      background: #1f2937;
      color: #e6eef8;
    }

    body.dark-mode .info-item {
      background: #111827;
      color: #e6eef8;
    }

    body.dark-mode .status-form select,
    body.dark-mode .filter-form select,
    body.dark-mode .filter-form input {
      background: #111827;
      color: #e6eef8;
      border-color: #374151;
    }
  </style>
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
      <a href="dashboard.php" class="nav-item">
        <i class="fas fa-box"></i>
        <span>Produk</span>
      </a>
      <a href="add_product.php" class="nav-item">
        <i class="fas fa-plus-circle"></i>
        <span>Tambah Produk</span>
      </a>
      <a href="orders.php" class="nav-item active">
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
        <h1>Manajemen Pesanan</h1>
      </div>
      <div class="header-right">
        <div style="display:flex;gap:12px;align-items:center;">
          </button>
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
      if (isset($_SESSION['success_message'])) {
        echo '<div class="alert alert-success" style="margin-bottom: 20px; padding: 15px 20px; border-radius: 8px; background: #d4edda; color: #155724; border: 1px solid #c3e6cb; display: flex; align-items: center; gap: 12px;">
                <i class="fas fa-check-circle" style="color: #28a745; font-size: 20px;"></i>
                <span>' . htmlspecialchars($_SESSION['success_message']) . '</span>
              </div>';
        unset($_SESSION['success_message']);
      }
      
      if (isset($_SESSION['error_message'])) {
        echo '<div class="alert alert-error" style="margin-bottom: 20px; padding: 15px 20px; border-radius: 8px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; display: flex; align-items: center; gap: 12px;">
                <i class="fas fa-exclamation-circle" style="color: #dc3545; font-size: 20px;"></i>
                <span>' . htmlspecialchars($_SESSION['error_message']) . '</span>
              </div>';
        unset($_SESSION['error_message']);
      }

      try {
        $total_orders = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
        $pending_orders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn();
        $processing_orders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status='processing'")->fetchColumn();
        $shipped_orders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status='shipped'")->fetchColumn();
        $total_revenue = (float)$pdo->query("SELECT IFNULL(SUM(total),0) FROM orders")->fetchColumn();
      } catch (Exception $e) {
        $total_orders = 0;
        $pending_orders = 0;
        $processing_orders = 0;
        $shipped_orders = 0;
        $total_revenue = 0;
      }

      $search = trim($_GET['search'] ?? '');
      $filter_status = $_GET['status'] ?? '';
      $page = max(1, (int)($_GET['page'] ?? 1));
      $per_page = 15;
      $offset = ($page - 1) * $per_page;

      $whereSql = '';
      $params = [];
      if ($search !== '' || $filter_status !== '') {
        $conditions = [];
        if ($search !== '') {
          $conditions[] = "(customer_name LIKE ? OR customer_email LIKE ? OR CAST(id AS CHAR) LIKE ?)";
          $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
        }
        if ($filter_status !== '') {
          $conditions[] = "status = ?";
          $params[] = $filter_status;
        }
        $whereSql = " WHERE " . implode(" AND ", $conditions);
      }

      $countStmt = $pdo->prepare("SELECT COUNT(*) FROM orders" . $whereSql);
      $countStmt->execute($params);
      $total_products = (int)$countStmt->fetchColumn();

      $listStmt = $pdo->prepare("SELECT o.*, GROUP_CONCAT(p.name SEPARATOR ', ') as product_names FROM orders o LEFT JOIN order_items oi ON o.id = oi.order_id LEFT JOIN products p ON oi.product_id = p.id" . $whereSql . " GROUP BY o.id ORDER BY o.id DESC LIMIT $per_page OFFSET $offset");
      $listStmt->execute($params);
      ?>

      <!-- Orders Stats -->
      <div class="orders-stats">
        <div class="stat-card">
          <i class="fas fa-shopping-cart"></i>
          <div>
            <h3><?= $total_orders ?></h3>
            <p>Total Pesanan</p>
          </div>
        </div>
        <div class="stat-card">
          <i class="fas fa-hourglass-end"></i>
          <div>
            <h3><?= $pending_orders ?></h3>
            <p>Pesanan Pending</p>
          </div>
        </div>
        <div class="stat-card">
          <i class="fas fa-spinner"></i>
          <div>
            <h3><?= $processing_orders ?></h3>
            <p>Sedang Diproses</p>
          </div>
        </div>
        <div class="stat-card">
          <i class="fas fa-truck"></i>
          <div>
            <h3><?= $shipped_orders ?></h3>
            <p>Dikirim</p>
          </div>
        </div>
       <div class="stat-card">
          <i class="fas fa-money-bill-wave"></i>
          <div>
            <h3>Rp <?= number_format($total_revenue, 0, ',', '.') ?></h3>
            <p>Total Pendapatan</p>
          </div>
        </div>
      </div>

      <div class="content-header" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:16px;">
        <h2 style="margin:0;display:flex;align-items:center;gap:8px;"><i class="fas fa-receipt"></i> Daftar Pesanan</h2>
      </div>

      <div class="card">
        <div class="card-body">
          <div class="filter-form">
            <form method="get" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
              <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari ID/Nama/Email..." style="min-width:200px;">
              <select name="status">
                <option value="">Semua Status</option>
                <option value="pending" <?= $filter_status === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="processing" <?= $filter_status === 'processing' ? 'selected' : '' ?>>Diproses</option>
                <option value="shipped" <?= $filter_status === 'shipped' ? 'selected' : '' ?>>Dikirim</option>
                <option value="delivered" <?= $filter_status === 'delivered' ? 'selected' : '' ?>>Terkirim</option>
                <option value="cancelled" <?= $filter_status === 'cancelled' ? 'selected' : '' ?>>Dibatalkan</option>
              </select>
              <button class="btn" type="submit" title="Filter"><i class="fas fa-filter"></i></button>
              <?php if ($search !== '' || $filter_status !== ''): ?>
                <a class="btn" href="orders.php" title="Reset"><i class="fas fa-undo"></i></a>
              <?php endif; ?>
            </form>
          </div>

          <div class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Nama Pelanggan</th>
                  <th>Email</th>
                  <th>Produk</th>
                  <th>Total</th>
                  <th>Status</th>
                  <th>Tanggal</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php
                foreach ($listStmt as $row) {
                    $statusClass = 'status-' . strtolower($row['status']);
                    echo "<tr>\n" .
                      "  <td><span class='badge'>{$row['id']}</span></td>\n" .
                      "  <td>" . htmlspecialchars($row['customer_name']) . "</td>\n" .
                      "  <td>" . htmlspecialchars($row['customer_email']) . "</td>\n" .
                      "  <td>" . htmlspecialchars($row['product_names'] ?? '') . "</td>\n" .
                      "  <td class='price'>Rp " . number_format($row['total'], 0, ',', '.') . "</td>\n" .
                      "  <td><span class='status-badge $statusClass'>" . ucfirst($row['status']) . "</span></td>\n" .
                      "  <td>" . date('d/m/Y', strtotime($row['created_at'])) . "</td>\n" .
                      "  <td class='action-buttons'>\n" .
                      "    <button class='btn-action btn-edit' onclick=\"openModal({$row['id']})\" title='Lihat Detail'>\n" .
                      "      <i class='fas fa-eye'></i>\n" .
                      "    </button>\n" .
                      "    <a href='orders.php?delete_id={$row['id']}' class='btn-action btn-delete' title='Hapus' onclick=\"return confirm('Apakah Anda yakin ingin menghapus pesanan ini?')\">\n" .
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
            <div class="pagination" style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px;">
              <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                <?php
                  $qs = http_build_query([
                    'search' => $search,
                    'status' => $filter_status,
                    'page' => $p
                  ]);
                ?>
                <a href="orders.php?<?= $qs ?>" class="btn <?= $p==$page ? 'btn-primary' : '' ?>" style="padding:6px 12px;border-radius:6px;"><?= $p ?></a>
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

  <!-- Order Detail Modal -->
  <div id="orderModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Detail Pesanan</h2>
        <button class="close-modal" onclick="closeModal()">&times;</button>
      </div>
      <div id="modalBody">
        <p>Loading...</p>
      </div>
    </div>
  </div>

  <script>
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');

    menuToggle.addEventListener('click', function() {
      sidebar.classList.toggle('collapsed');
      mainContent.classList.toggle('expanded');
    });

    function openModal(orderId) {
      const modal = document.getElementById('orderModal');
      const modalBody = document.getElementById('modalBody');
      
      fetch('get_order_detail.php?id=' + orderId)
        .then(response => response.text())
        .then(data => {
          modalBody.innerHTML = data;
          modal.style.display = 'block';
        })
        .catch(error => {
          modalBody.innerHTML = '<p style="color:red;">Gagal memuat detail pesanan.</p>';
          modal.style.display = 'block';
        });
    }

    function closeModal() {
      document.getElementById('orderModal').style.display = 'none';
    }

    window.onclick = function(event) {
      const modal = document.getElementById('orderModal');
      if (event.target == modal) {
        modal.style.display = 'none';
      }
    }

    function changeStatus(orderId, newStatus) {
      if (!newStatus) return;
      
      const formData = new FormData();
      formData.append('order_id', orderId);
      formData.append('status', newStatus);
      
      fetch('orders.php', {
        method: 'POST',
        body: formData
      })
      .then(response => {
        if (response.ok) {
          location.reload();
        } else {
          alert('Gagal mengubah status pesanan');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat mengubah status');
      });
    }

    document.addEventListener('DOMContentLoaded', function() {
      const rows = document.querySelectorAll('.data-table tbody tr');
      rows.forEach((row, index) => {
        setTimeout(() => {
          row.style.opacity = '1';
          row.style.transform = 'translateY(0)';
        }, index * 50);
      });
      
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