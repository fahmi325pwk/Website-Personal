<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'db.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nano Komputer | Toko Sparepart Komputer</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <header class="navbar">
    <div class="container navbar-container">
      <!-- LOGO -->
      <h1 class="logo"><a href="index.php">Nano Komputer</a></h1>

      <!-- NAV LINKS -->
      <nav class="nav-links">
        <a href="index.php">Beranda</a>
        <a href="products.php">Produk</a>
        <a href="index.php#tentang-kami">Tentang Kami</a>
      </nav>


      <!-- SEARCH BAR & USER MENU -->
      <div class="d-flex align-items-center">
        <form action="products.php" method="get" class="d-flex align-items-center me-2">
          <div class="input-group">
            <input type="text" class="form-control form-control-sm" name="q" placeholder="Cari produk...">
            <button class="btn btn-outline-secondary btn-sm" type="submit"><i class="bi bi-search"></i></button>
          </div>
        </form>

        <!-- USER MENU -->
        <div class="user-menu d-flex align-items-center">
          <?php if(isset($_SESSION['user_id'])): ?>
            <div class="dropdown">
              <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?>
              </button>
              <ul class="dropdown-menu" aria-labelledby="userDropdown">
                <li><a class="dropdown-item" href="profile.php">Profil Saya</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
              </ul>
            </div>
          <?php else: ?>
            <a href="login.php" class="btn btn-outline-primary btn-sm me-1">Login</a>
            <a href="register.php" class="btn btn-primary btn-sm me-1">Daftar</a>
          <?php endif; ?>
          <a href="cart.php" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-cart4"></i>
          </a>
        </div>
      </div>
    </div>
  </header>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
