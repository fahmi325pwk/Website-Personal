<?php
session_start();
require '../includes/db.php'; // koneksi ke database

// Gunakan PDO dengan error mode aktif
if (!isset($pdo)) {
    // Jika file db.php masih pakai MySQLi, ubah ke PDO berikut:
    $pdo = new PDO("mysql:host=localhost;dbname=rental_camping;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}

// Proteksi halaman admin
if (!isset($_SESSION['admin'])) {
    $_SESSION['admin'] = 'Admin Demo';
}

// === FILTER ===
$where = [];
$params = [];

$search = trim($_GET['search'] ?? '');
$filter_status = $_GET['status'] ?? '';

if (!empty($_GET['start_date'])) {
    $where[] = "DATE(created_at) >= ?";
    $params[] = $_GET['start_date'];
}
if (!empty($_GET['end_date'])) {
    $where[] = "DATE(created_at) <= ?";
    $params[] = $_GET['end_date'];
}
if (!empty($filter_status)) {
    $where[] = "status = ?";
    $params[] = $filter_status;
}

if ($search !== '') {
    $where[] = "(customer_name LIKE ? OR customer_email LIKE ? OR CAST(id AS CHAR) LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}

$whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

// === QUERY UTAMA ===
try {
    $stmt = $pdo->prepare("SELECT * FROM orders $whereSQL ORDER BY created_at DESC");
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Query gagal: " . $e->getMessage());
}

// === HITUNG STATISTIK ===
$total_transaksi = count($orders);
$total_pendapatan = array_sum(array_column($orders, 'total') ?: []);
$today = date('Y-m-d');

$transaksi_hari_ini = 0;
$pendapatan_hari_ini = 0;
foreach ($orders as $o) {
    if (date('Y-m-d', strtotime($o['created_at'])) == $today) {
        $transaksi_hari_ini++;
        $pendapatan_hari_ini += $o['total'];
    }
}

// === Data untuk grafik (7 hari terakhir) ===
try {
    $chart_stmt = $pdo->query("
        SELECT DATE(created_at) AS tanggal, SUM(total) AS pendapatan
        FROM orders
        GROUP BY DATE(created_at)
        ORDER BY tanggal DESC
        LIMIT 7
    ");
    $chart_data = $chart_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $chart_data = [];
}
$chart_labels = array_column($chart_data, 'tanggal');
$chart_values = array_column($chart_data, 'pendapatan');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Laporan Penjualan | Nano Komputer</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/admin-style.css">

<style>
/* ==================== FILTER STYLES ==================== */
.filter-form {
    display: flex;
    gap: 12px;
    align-items: flex-end;
    flex-wrap: wrap;
    padding: 20px;
    background: #f8fafc;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    margin-bottom: 20px;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
    flex: 1;
    min-width: 180px;
}

.filter-group label {
    font-size: 12px;
    font-weight: 600;
    color: #475569;
    margin-bottom: 4px;
}

.filter-form input[type="text"],
.filter-form input[type="date"],
.filter-form select {
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    background: white;
    transition: all 0.3s ease;
    width: 100%;
}

.filter-form input[type="text"]:focus,
.filter-form input[type="date"]:focus,
.filter-form select:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.filter-form input[type="text"]::placeholder {
    color: #9ca3af;
}

.filter-actions {
    display: flex;
    gap: 8px;
    align-items: center;
}

.filter-btn {
    background: #3b82f6;
    color: white;
    border: none;
    padding: 10px 16px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 6px;
}

.filter-btn:hover {
    background: #2563eb;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(59, 130, 246, 0.3);
}

.filter-btn.reset {
    background: #6b7280;
}

.filter-btn.reset:hover {
    background: #4b5563;
}

.filter-btn.export {
    background: #10b981;
}

.filter-btn.export:hover {
    background: #059669;
}

.filter-btn.print {
    background: #ef4444;
}

.filter-btn.print:hover {
    background: #dc2626;
}

/* Responsive Design */
@media (max-width: 768px) {
    .filter-form {
        flex-direction: column;
        align-items: stretch;
        gap: 15px;
    }
    
    .filter-group {
        min-width: 100%;
    }
    
    .filter-actions {
        justify-content: space-between;
        width: 100%;
    }
    
    .filter-btn {
        flex: 1;
        justify-content: center;
    }
}

/* Filter Results Info */
.filter-results {
    background: #dbeafe;
    padding: 12px 16px;
    border-radius: 6px;
    margin-bottom: 20px;
    border-left: 4px solid #3b82f6;
}

.filter-results p {
    margin: 0;
    color: #1e40af;
    font-size: 14px;
    font-weight: 500;
}

.filter-results .highlight {
    background: #3b82f6;
    color: white;
    padding: 2px 6px;
    border-radius: 4px;
    font-weight: bold;
}

/* Active Filter Tags */
.active-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 15px;
}

.filter-tag {
    background: #3b82f6;
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 6px;
}

.filter-tag .remove {
    background: none;
    border: none;
    color: white;
    cursor: pointer;
    padding: 2px;
    border-radius: 50%;
    width: 16px;
    height: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.filter-tag .remove:hover {
    background: rgba(255, 255, 255, 0.2);
}

/* Quick Date Filters */
.quick-filters {
    display: flex;
    gap: 8px;
    margin-bottom: 15px;
    flex-wrap: wrap;
}

.quick-filter-btn {
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 12px;
    color: #475569;
    transition: all 0.3s ease;
}

.quick-filter-btn:hover {
    background: #e2e8f0;
    border-color: #cbd5e1;
}

.quick-filter-btn.active {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
}

/* Animation for filter changes */
.filter-transition {
    transition: all 0.3s ease-in-out;
}

/* Loading state for filters */
.filter-loading {
    position: relative;
    pointer-events: none;
}

.filter-loading::after {
    content: '';
    position: absolute;
    top: 50%;
    right: 10px;
    width: 16px;
    height: 16px;
    border: 2px solid transparent;
    border-top: 2px solid #3b82f6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: translateY(-50%) rotate(0deg); }
    100% { transform: translateY(-50%) rotate(360deg); }
}

/* Print styles untuk filter */
@media print {
    .filter-form,
    .filter-results,
    .active-filters,
    .quick-filters {
        display: none !important;
    }
}
/* ==================== PRINT STYLE ==================== */
.print-header { display: none; }
.print-info-section { display: none; }
.print-stats-section { display: none; }
.print-footer-section { display: none; }
.print-summary { display: none; }

@media print {
  body {
    background: white !important;
    color: #000 !important;
    font-family: 'Times New Roman', Times, serif !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }

  /* Hilangkan elemen non-print */
  .sidebar,
  .top-header,
  .footer,
  .card:has(#salesChart),
  .filter-form,
  .card:has(.filter-form),
  .btn,
  .menu-toggle,
  .header-right,
  .sidebar-footer,
  .stats-grid,
  .card-header > div {
    display: none !important;
  }

  .main-content {
    margin: 0 !important;
    padding: 0 !important;
    width: 100% !important;
  }

  .content {
    padding: 0 !important;
  }

  /* ========== HEADER LAPORAN ========== */
  .print-header {
    display: block !important;
    border: 3px solid #1e40af;
    padding: 0 !important;
    margin-bottom: 25px !important;
    page-break-after: avoid;
  }

  .print-company-header {
    background: linear-gradient(135deg, #1e40af 0%, #2563eb 100%);
    padding: 25px;
    text-align: center;
    border-bottom: 3px solid #1e3a8a;
  }

  .print-company-logo {
    font-size: 36px;
    color: #fbbf24;
    margin-bottom: 10px;
  }

  .print-company-name {
    font-size: 28px;
    font-weight: bold;
    color: white;
    margin: 0;
    letter-spacing: 2px;
  }

  .print-company-tagline {
    font-size: 12px;
    color: #bfdbfe;
    margin: 5px 0 0 0;
    font-style: italic;
  }

  .print-report-title {
    background: #f8fafc;
    padding: 15px;
    text-align: center;
    border-bottom: 2px solid #e2e8f0;
  }

  .print-report-title h1 {
    margin: 0;
    font-size: 22px;
    color: #1e40af;
    font-weight: bold;
    letter-spacing: 1px;
  }

  .print-report-title .print-date {
    font-size: 11px;
    color: #64748b;
    margin-top: 8px;
  }

  /* ========== INFO SECTION ========== */
  .print-info-section {
    display: block !important;
    background: #f1f5f9;
    padding: 15px 20px;
    margin-bottom: 20px;
    border-left: 4px solid #2563eb;
  }

  .print-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    font-size: 11px;
  }

  .print-info-item {
    display: flex;
    gap: 8px;
  }

  .print-info-item strong {
    color: #1e40af;
    min-width: 120px;
  }

  /* ========== STATISTIK RINGKASAN ========== */
  .print-stats-section {
    display: block !important;
    margin-bottom: 25px;
    page-break-inside: avoid;
  }

  .print-stats-title {
    background: #1e40af;
    color: white;
    padding: 10px 15px;
    font-size: 14px;
    font-weight: bold;
    margin-bottom: 0;
    border-radius: 4px 4px 0 0;
  }

  .print-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0;
    border: 2px solid #1e40af;
    border-top: none;
    border-radius: 0 0 4px 4px;
    overflow: hidden;
  }

  .print-stat-box {
    background: white;
    padding: 15px;
    text-align: center;
    border-right: 1px solid #e2e8f0;
  }

  .print-stat-box:last-child {
    border-right: none;
  }

  .print-stat-icon {
    font-size: 24px;
    margin-bottom: 8px;
  }

  .print-stat-icon.blue { color: #2563eb; }
  .print-stat-icon.green { color: #10b981; }
  .print-stat-icon.orange { color: #f59e0b; }
  .print-stat-icon.purple { color: #8b5cf6; }

  .print-stat-value {
    font-size: 18px;
    font-weight: bold;
    color: #1e293b;
    margin: 5px 0;
  }

  .print-stat-label {
    font-size: 10px;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  /* ========== TABEL DATA ========== */
  .card {
    box-shadow: none !important;
    border: none !important;
    margin: 0 !important;
    page-break-inside: avoid;
  }

  .card h2 {
    background: #1e40af !important;
    color: white !important;
    padding: 12px 15px !important;
    margin: 0 0 15px 0 !important;
    font-size: 14px !important;
    border: none !important;
    border-radius: 4px;
  }

  .card-header {
    display: none !important;
  }

  .data-table {
    width: 100% !important;
    border-collapse: collapse !important;
    font-size: 10px !important;
    border: 2px solid #1e40af !important;
  }

  .data-table thead tr {
    background: #1e40af !important;
  }

  .data-table th {
    background: #1e40af !important;
    color: white !important;
    padding: 10px 8px !important;
    text-align: left !important;
    font-weight: bold !important;
    border: 1px solid #1e3a8a !important;
    font-size: 10px !important;
  }

  .data-table td {
    border: 1px solid #cbd5e1 !important;
    padding: 8px !important;
    color: #1e293b !important;
  }

  .data-table tbody tr:nth-child(odd) {
    background-color: #ffffff !important;
  }

  .data-table tbody tr:nth-child(even) {
    background-color: #f8fafc !important;
  }

  /* Badge styling */
  .badge {
    padding: 3px 8px !important;
    border-radius: 3px !important;
    font-weight: bold !important;
    font-size: 9px !important;
    display: inline-block !important;
    border: 1px solid !important;
  }

  .badge.pending {
    background-color: #fef3c7 !important;
    color: #92400e !important;
    border-color: #fbbf24 !important;
  }

  .badge.proses {
    background-color: #dbeafe !important;
    color: #1e40af !important;
    border-color: #3b82f6 !important;
  }

  .badge.dikirim {
    background-color: #ede9fe !important;
    color: #6d28d9 !important;
    border-color: #8b5cf6 !important;
  }

  .badge.selesai {
    background-color: #d1fae5 !important;
    color: #065f46 !important;
    border-color: #10b981 !important;
  }

  /* ========== SUMMARY SECTION ========== */
  .print-summary {
    display: block !important;
    margin-top: 20px;
    padding: 15px;
    background: #fef3c7;
    border: 2px solid #f59e0b;
    border-radius: 4px;
    page-break-inside: avoid;
  }

  .print-summary-title {
    font-size: 13px;
    font-weight: bold;
    color: #92400e;
    margin: 0 0 10px 0;
    text-transform: uppercase;
    letter-spacing: 1px;
  }

  .print-summary-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .print-summary-item {
    text-align: center;
  }

  .print-summary-label {
    font-size: 10px;
    color: #78350f;
    margin-bottom: 5px;
  }

  .print-summary-value {
    font-size: 16px;
    font-weight: bold;
    color: #92400e;
  }

  /* ========== FOOTER ========== */
  .print-footer-section {
    display: block !important;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 2px solid #e2e8f0;
    page-break-inside: avoid;
  }

  .print-signature-area {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
  }

  .print-signature-box {
    text-align: center;
  }

  .print-signature-label {
    font-size: 11px;
    color: #64748b;
    margin-bottom: 60px;
    font-weight: bold;
  }

  .print-signature-name {
    border-top: 2px solid #1e293b;
    padding-top: 8px;
    font-size: 11px;
    font-weight: bold;
    color: #1e293b;
  }

  .print-footer-info {
    text-align: center;
    padding: 12px;
    background: #f8fafc;
    border-radius: 4px;
    font-size: 9px;
    color: #64748b;
    line-height: 1.6;
  }

  .print-footer-info strong {
    color: #1e40af;
  }

  /* ========== PAGE SETTINGS ========== */
  @page {
    size: A4 portrait;
    margin: 15mm 15mm 20mm 15mm;
  }

  /* Prevent page breaks inside important elements */
  .print-header,
  .print-info-section,
  .print-stats-section,
  .data-table thead {
    page-break-inside: avoid;
    page-break-after: avoid;
  }

  .data-table tbody tr {
    page-break-inside: avoid;
  }
}</style>  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
      <a href="orders.php" class="nav-item">
        <i class="fas fa-receipt"></i>
        <span>Pesanan</span>
      </a>
      <a href="sales_report.php" class="nav-item active">
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

  <!-- Main -->
  <!-- Main Content -->
  <div class="main-content">
    <header class="top-header">
      <div class="header-left">
        <button class="menu-toggle" id="menuToggle">
          <i class="fas fa-bars"></i>
        </button>
        <h1>Laporan Penjualan</h1>
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
      <!-- Print Header (hidden on screen, visible on print) -->
      <div class="print-header">
        <h1>LAPORAN PENJUALAN - NANO KOMPUTER</h1>
        <p class="print-date">Dicetak pada: <span id="printDateTime"></span></p>
      </div>

<!-- Statistik -->
<div class="stats-grid">
    <div class="stat-card blue">
        <i class="fas fa-shopping-cart"></i>
        <div>
            <h3><?= $total_transaksi ?></h3>
            <p>Total Transaksi</p>
        </div>
    </div>
    
    <div class="stat-card green">
        <i class="fas fa-money-bill-wave"></i>
        <div>
            <h3>Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></h3>
            <p>Total Pendapatan</p>
        </div>
    </div>
    
    <div class="stat-card orange">
        <i class="fas fa-calendar-day"></i>
        <div>
            <h3><?= $transaksi_hari_ini ?></h3>
            <p>Transaksi Hari Ini</p>
        </div>
    </div>
    
    <div class="stat-card purple">
        <i class="fas fa-chart-pie"></i>
        <div>
            <h3>Rp <?= number_format($pendapatan_hari_ini, 0, ',', '.') ?></h3>
            <p>Pendapatan Hari Ini</p>
        </div>
    </div>
</div>

      <!-- Grafik Pendapatan -->
      <div class="card">
        <h2><i class="fas fa-chart-bar"></i> Grafik Pendapatan (7 Hari Terakhir)</h2>
        <canvas id="salesChart" height="100"></canvas>
      </div>

<!-- Filter -->
<div class="card">
    <h2><i class="fas fa-filter"></i> Filter Laporan</h2>
    
    <!-- Quick Filters -->
    <div class="quick-filters">
        <button type="button" class="quick-filter-btn" data-days="1">Hari Ini</button>
        <button type="button" class="quick-filter-btn" data-days="7">7 Hari</button>
        <button type="button" class="quick-filter-btn" data-days="30">30 Hari</button>
        <button type="button" class="quick-filter-btn" data-days="90">3 Bulan</button>
    </div>
    
    <!-- Main Filter Form -->
    <form method="get" class="filter-form">
        <div class="filter-group">
            <label for="search">Cari</label>
            <input type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>" 
                   placeholder="ID Order, Nama, atau Email...">
        </div>
        
        <div class="filter-group">
            <label for="start_date">Dari Tanggal</label>
            <input type="date" id="start_date" name="start_date" 
                   value="<?= htmlspecialchars($_GET['start_date'] ?? '') ?>">
        </div>
        
        <div class="filter-group">
            <label for="end_date">Sampai Tanggal</label>
            <input type="date" id="end_date" name="end_date" 
                   value="<?= htmlspecialchars($_GET['end_date'] ?? '') ?>">
        </div>
        
        <div class="filter-group">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">Semua Status</option>
                <option value="pending" <?= $filter_status === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="processing" <?= $filter_status === 'processing' ? 'selected' : '' ?>>Diproses</option>
                <option value="shipped" <?= $filter_status === 'shipped' ? 'selected' : '' ?>>Dikirim</option>
                <option value="delivered" <?= $filter_status === 'delivered' ? 'selected' : '' ?>>Terkirim</option>
                <option value="cancelled" <?= $filter_status === 'cancelled' ? 'selected' : '' ?>>Dibatalkan</option>
            </select>
        </div>
        
        <div class="filter-actions">
            <button type="submit" class="filter-btn">
                <i class="fas fa-filter"></i> Terapkan Filter
            </button>
            
            <?php if ($search !== '' || $filter_status !== '' || !empty($_GET['start_date']) || !empty($_GET['end_date'])): ?>
            <a href="sales_report.php" class="filter-btn reset">
                <i class="fas fa-undo"></i> Reset
            </a>
            <?php endif; ?>
        </div>
    </form>
    
    <!-- Active Filters Display -->
    <?php if ($search !== '' || $filter_status !== '' || !empty($_GET['start_date']) || !empty($_GET['end_date'])): ?>
    <div class="filter-results">
        <p>
            Menampilkan hasil filter: 
            <?php if ($search !== ''): ?>
                <span class="highlight">Pencarian: "<?= htmlspecialchars($search) ?>"</span>
            <?php endif; ?>
            <?php if ($filter_status !== ''): ?>
                <span class="highlight">Status: <?= ucfirst($filter_status) ?></span>
            <?php endif; ?>
            <?php if (!empty($_GET['start_date'])): ?>
                <span class="highlight">Dari: <?= htmlspecialchars($_GET['start_date']) ?></span>
            <?php endif; ?>
            <?php if (!empty($_GET['end_date'])): ?>
                <span class="highlight">Sampai: <?= htmlspecialchars($_GET['end_date']) ?></span>
            <?php endif; ?>
            - Total: <?= count($orders) ?> transaksi
        </p>
    </div>
    <?php endif; ?>
</div>
      <!-- Tabel -->
      <div class="card">
        <div class="card-header">
          <h2><i class="fas fa-receipt"></i> Daftar Transaksi</h2>
          <div>
            <button class="btn btn-success" onclick="exportExcel()"><i class="fas fa-file-excel"></i> Export Excel</button>
            <button class="btn btn-danger" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
          </div>
        </div>
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Nama</th>
                <th>Email</th>
                <th>Total</th>
                <th>Tanggal</th>
                <th>Bayar</th>
                <th>Pengiriman</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($orders): ?>
                <?php foreach ($orders as $row): ?>
                  <tr class="status-<?= $row['status'] ?>">
                    <td>#<?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['customer_name']) ?></td>
                    <td><?= htmlspecialchars($row['customer_email']) ?></td>
                    <td>Rp <?= number_format($row['total'], 0, ',', '.') ?></td>
                    <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                    <td><?= htmlspecialchars($row['payment_method']) ?></td>
                    <td><?= htmlspecialchars($row['shipping_method']) ?></td>
                    <td><span class="badge <?= $row['status'] ?>"><?= ucfirst($row['status']) ?></span></td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="8" class="no-data">Tidak ada data transaksi</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>

    <footer class="footer">
      <p>&copy; 2025 Nano Komputer. All rights reserved.</p>
    </footer>
  </div>

  <!-- Chart JS -->
  <script>
    const ctx = document.getElementById('salesChart');
    const salesChart = new Chart(ctx, {
      type: 'line',
      data: {
        labels: <?= json_encode(array_reverse($chart_labels)) ?>,
        datasets: [{
          label: 'Pendapatan (Rp)',
          data: <?= json_encode(array_reverse($chart_values)) ?>,
          borderColor: '#2563eb',
          backgroundColor: 'rgba(37,99,235,0.1)',
          fill: true,
          tension: 0.3
        }]
      },
      options: {
        scales: { y: { beginAtZero: true } },
        plugins: { legend: { display: false } }
      }
    });

function exportExcel() {
    // Ambil data dari tabel
    const table = document.querySelector('.data-table');
    const rows = table.querySelectorAll('tbody tr');
    
    // Cek jika tidak ada data
    if (rows.length === 0 || rows[0].querySelector('.no-data')) {
        alert('Tidak ada data untuk di-export!');
        return;
    }

    // Header statistik
    const totalTransaksi = document.querySelector('.stat-card.blue h3').textContent;
    const totalPendapatan = document.querySelector('.stat-card.green h3').textContent;
    const transaksiHariIni = document.querySelector('.stat-card.orange h3').textContent;
    const pendapatanHariIni = document.querySelector('.stat-card.purple h3').textContent;

    // Buat HTML Excel dengan styling
    let excelContent = `
        <html xmlns:x="urn:schemas-microsoft-com:office:excel">
        <head>
            <meta charset="UTF-8">
            <style>
                /* Styling untuk Excel */
                table {
                    border-collapse: collapse;
                    width: 100%;
                    font-family: Arial, sans-serif;
                }
                
                .header-section {
                    background-color: #2563eb;
                    color: white;
                    padding: 20px;
                    text-align: center;
                    font-size: 24px;
                    font-weight: bold;
                    margin-bottom: 20px;
                }
                
                .info-section {
                    margin: 20px 0;
                    font-size: 12px;
                }
                
                .info-section td {
                    padding: 5px 10px;
                }
                
                .stats-table {
                    margin: 20px 0;
                    background-color: #f8f9fa;
                }
                
                .stats-table th {
                    background-color: #4caf50;
                    color: white;
                    padding: 12px;
                    font-weight: bold;
                    text-align: center;
                }
                
                .stats-table td {
                    padding: 10px;
                    text-align: center;
                    border: 1px solid #ddd;
                    font-weight: bold;
                }
                
                .data-table {
                    margin-top: 30px;
                }
                
                .data-table th {
                    background-color: #2563eb;
                    color: white;
                    padding: 12px;
                    font-weight: bold;
                    text-align: left;
                    border: 1px solid #1e40af;
                }
                
                .data-table td {
                    padding: 10px;
                    border: 1px solid #ddd;
                }
                
                .data-table tr:nth-child(even) {
                    background-color: #f8f9fa;
                }
                
                .data-table tr:hover {
                    background-color: #e3f2fd;
                }
                
                .badge {
                    padding: 5px 10px;
                    border-radius: 4px;
                    font-weight: bold;
                    display: inline-block;
                }
                
                .badge.pending {
                    background-color: #fef3c7;
                    color: #92400e;
                }
                
                .badge.proses {
                    background-color: #dbeafe;
                    color: #1e40af;
                }
                
                .badge.dikirim {
                    background-color: #e0e7ff;
                    color: #4338ca;
                }
                
                .badge.selesai {
                    background-color: #d1fae5;
                    color: #065f46;
                }
                
                .footer-section {
                    margin-top: 30px;
                    padding: 15px;
                    background-color: #f1f5f9;
                    text-align: center;
                    font-size: 11px;
                    color: #64748b;
                }
                
                .total-row {
                    background-color: #fef3c7 !important;
                    font-weight: bold;
                }
                
                .total-row td {
                    border-top: 2px solid #2563eb;
                }
            </style>
        </head>
        <body>
            <!-- Header -->
            <div class="header-section">
                📊 LAPORAN PENJUALAN - NANO KOMPUTER
            </div>
            
            <!-- Info Tanggal Export -->
            <table class="info-section">
                <tr>
                    <td><strong>Tanggal Export:</strong></td>
                    <td>${new Date().toLocaleDateString('id-ID', { 
                        weekday: 'long', 
                        year: 'numeric', 
                        month: 'long', 
                        day: 'numeric' 
                    })}</td>
                </tr>
                <tr>
                    <td><strong>Waktu:</strong></td>
                    <td>${new Date().toLocaleTimeString('id-ID')}</td>
                </tr>
            </table>
            
            <!-- Statistik -->
            <table class="stats-table">
                <thead>
                    <tr>
                        <th>Total Transaksi</th>
                        <th>Total Pendapatan</th>
                        <th>Transaksi Hari Ini</th>
                        <th>Pendapatan Hari Ini</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>🛒 ${totalTransaksi}</td>
                        <td>💰 ${totalPendapatan}</td>
                        <td>📅 ${transaksiHariIni}</td>
                        <td>💵 ${pendapatanHariIni}</td>
                    </tr>
                </tbody>
            </table>
            
            <!-- Data Transaksi -->
            <table class="data-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>ID Order</th>
                        <th>Nama Pelanggan</th>
                        <th>Email</th>
                        <th>Total Pembelian</th>
                        <th>Tanggal Transaksi</th>
                        <th>Metode Pembayaran</th>
                        <th>Metode Pengiriman</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>`;

    // Tambahkan data dari tabel
    let totalPembelian = 0;
    rows.forEach((row, index) => {
        const cells = row.querySelectorAll('td');
        if (cells.length > 0) {
            const id = cells[0].textContent;
            const nama = cells[1].textContent;
            const email = cells[2].textContent;
            const total = cells[3].textContent;
            const tanggal = cells[4].textContent;
            const pembayaran = cells[5].textContent;
            const pengiriman = cells[6].textContent;
            const status = cells[7].textContent.trim();
            
            // Hitung total
            const nominalStr = total.replace(/[^0-9]/g, '');
            totalPembelian += parseInt(nominalStr) || 0;
            
            excelContent += `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${id}</td>
                        <td>${nama}</td>
                        <td>${email}</td>
                        <td style="text-align: right;">${total}</td>
                        <td>${tanggal}</td>
                        <td>${pembayaran}</td>
                        <td>${pengiriman}</td>
                        <td><span class="badge ${status.toLowerCase()}">${status}</span></td>
                    </tr>`;
        }
    });

    // Baris total
    excelContent += `
                    <tr class="total-row">
                        <td colspan="4" style="text-align: right;"><strong>GRAND TOTAL:</strong></td>
                        <td style="text-align: right;"><strong>Rp ${totalPembelian.toLocaleString('id-ID')}</strong></td>
                        <td colspan="4"></td>
                    </tr>
                </tbody>
            </table>
            
            <!-- Footer -->
            <div class="footer-section">
                <p><strong>© 2025 Nano Komputer - Laporan ini digenerate otomatis</strong></p>
                <p>Dokumen ini bersifat rahasia dan hanya untuk keperluan internal</p>
            </div>
        </body>
        </html>
    `;

    // Buat Blob dan download
    const blob = new Blob([excelContent], { 
        type: 'application/vnd.ms-excel;charset=utf-8;' 
    });
    
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    const fileName = `Laporan_Penjualan_${new Date().toISOString().split('T')[0]}.xls`;
    
    link.setAttribute('href', url);
    link.setAttribute('download', fileName);
    link.style.display = 'none';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    // Notifikasi sukses
    alert(`✅ File berhasil di-export!\n\nNama file: ${fileName}`);
}

// Set print date for print styles
document.addEventListener('DOMContentLoaded', function() {
    const now = new Date();
    const printDate = now.toLocaleDateString('id-ID', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    }) + ' ' + now.toLocaleTimeString('id-ID');
    document.querySelector('.main-content').setAttribute('data-print-date', printDate);

    // Update print header date
    const printDateTimeElement = document.getElementById('printDateTime');
    if (printDateTimeElement) {
        printDateTimeElement.textContent = printDate;
    }
});
// Quick Filters Functionality
document.addEventListener('DOMContentLoaded', function() {
    const quickFilterBtns = document.querySelectorAll('.quick-filter-btn');
    
    quickFilterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const days = parseInt(this.getAttribute('data-days'));
            const endDate = new Date();
            const startDate = new Date();
            startDate.setDate(endDate.getDate() - days + 1);
            
            // Format dates to YYYY-MM-DD
            const formatDate = (date) => {
                return date.toISOString().split('T')[0];
            };
            
            // Set form values
            document.getElementById('start_date').value = formatDate(startDate);
            document.getElementById('end_date').value = formatDate(endDate);
            
            // Remove active class from all buttons
            quickFilterBtns.forEach(b => b.classList.remove('active'));
            // Add active class to clicked button
            this.classList.add('active');
            
            // Submit the form
            document.querySelector('.filter-form').submit();
        });
    });
    
    // Set active quick filter based on current dates
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    
    if (startDate && endDate) {
        const start = new Date(startDate);
        const end = new Date(endDate);
        const diffTime = Math.abs(end - start);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
        
        quickFilterBtns.forEach(btn => {
            if (parseInt(btn.getAttribute('data-days')) === diffDays) {
                btn.classList.add('active');
            }
        });
    }
});
  </script>
</body>
</html>
