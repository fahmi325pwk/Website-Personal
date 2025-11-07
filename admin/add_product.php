<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}
require '../includes/db.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];

    $target_dir = "../assets/images/";
    $image = $target_dir . basename($_FILES["image"]["name"]);

    if(move_uploaded_file($_FILES["image"]["tmp_name"], $image)) {
        $image_path = "assets/images/" . basename($_FILES["image"]["name"]);

        $stmt = $pdo->prepare("INSERT INTO products (name, description, price, image, stock) VALUES (?,?,?,?,?)");
        if($stmt->execute([$name, $desc, $price, $image_path, $stock])) {
            $success = "Produk berhasil ditambahkan!";
            // Redirect setelah 2 detik
            header("refresh:2;url=dashboard.php");
        } else {
            $error = "Gagal menyimpan produk ke database!";
        }
    } else {
        $error = "Gagal mengupload gambar!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tambah Produk | Nano Komputer</title>
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
      <a href="dashboard.php" class="nav-item">
        <i class="fas fa-box"></i>
        <span>Produk</span>
      </a>
      <a href="add_product.php" class="nav-item active">
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
        <h1>Tambah Produk</h1>
      </div>
      <div class="header-right">
        <div class="user-info">
          <span>Selamat datang, <strong><?= htmlspecialchars($_SESSION['admin']) ?></strong></span>
          <div class="avatar">
            <i class="fas fa-user-circle"></i>
          </div>
        </div>
      </div>
    </header>

    <main class="content">
      <div class="content-header">
        <h2><i class="fas fa-plus-square"></i> Tambah Produk Baru</h2>
        <a href="dashboard.php" class="btn btn-secondary">
          <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
        </a>
      </div>

      <?php if($success): ?>
        <div class="alert alert-success">
          <i class="fas fa-check-circle"></i>
          <span><?= htmlspecialchars($success) ?></span>
        </div>
      <?php endif; ?>

      <?php if($error): ?>
        <div class="alert alert-error">
          <i class="fas fa-exclamation-circle"></i>
          <span><?= htmlspecialchars($error) ?></span>
        </div>
      <?php endif; ?>

      <div class="card">
        <div class="card-header">
          <h3><i class="fas fa-info-circle"></i> Informasi Produk</h3>
        </div>
        <div class="card-body">
          <form method="post" enctype="multipart/form-data" id="productForm" class="product-form">
            <div class="form-row">
              <div class="form-group">
                <label for="name">
                  <i class="fas fa-tag"></i> Nama Produk
                  <span class="required">*</span>
                </label>
                <input type="text" id="name" name="name" placeholder="Masukkan nama produk" required>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="description">
                  <i class="fas fa-align-left"></i> Deskripsi
                  <span class="required">*</span>
                </label>
                <textarea id="description" name="description" rows="5" placeholder="Masukkan deskripsi produk" required></textarea>
                <small class="form-text">Berikan deskripsi yang jelas dan menarik</small>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="price">
                  <i class="fas fa-dollar-sign"></i> Harga
                  <span class="required">*</span>
                </label>
                <div class="input-group">
                  <span class="input-prefix">Rp</span>
                  <input type="number" id="price" name="price" placeholder="0" min="0" required>
                </div>
                <small class="form-text">Masukkan harga dalam Rupiah</small>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="stock">
                  <i class="fas fa-boxes"></i> Stok
                  <span class="required">*</span>
                </label>
                <input type="number" id="stock" name="stock" placeholder="0" min="0" required>
                <small class="form-text">Masukkan jumlah stok produk</small>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="image">
                  <i class="fas fa-image"></i> Gambar Produk
                  <span class="required">*</span>
                </label>
                <div class="file-upload-wrapper">
                  <input type="file" id="image" name="image" accept="image/*" required>
                  <label for="image" class="file-upload-label">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <span class="file-upload-text">Pilih gambar atau drag & drop disini</span>
                    <span class="file-name"></span>
                  </label>
                  <div class="image-preview" id="imagePreview"></div>
                </div>
                <small class="form-text">Format: JPG, PNG, GIF (Max: 2MB)</small>
              </div>
            </div>

            <div class="form-actions">
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Simpan Produk
              </button>
              <button type="reset" class="btn btn-secondary">
                <i class="fas fa-redo"></i> Reset Form
              </button>
            </div>
          </form>
        </div>
      </div>
    </main>

    <footer class="footer">
      <p>&copy; 2025 Nano Komputer. All rights reserved.</p>
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

    // File Upload Preview
    const fileInput = document.getElementById('image');
    const fileUploadLabel = document.querySelector('.file-upload-label');
    const fileNameSpan = document.querySelector('.file-name');
    const imagePreview = document.getElementById('imagePreview');

    fileInput.addEventListener('change', function(e) {
      const file = e.target.files[0];
      
      if (file) {
        // Update file name
        fileNameSpan.textContent = file.name;
        fileUploadLabel.classList.add('has-file');
        
        // Show preview
        const reader = new FileReader();
        reader.onload = function(e) {
          imagePreview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
          imagePreview.style.display = 'block';
        };
        reader.readAsDataURL(file);
      }
    });

    // Form validation and animation
    const form = document.getElementById('productForm');
    form.addEventListener('submit', function(e) {
      const submitBtn = form.querySelector('button[type="submit"]');
      submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
      submitBtn.disabled = true;
    });

    // Format price input
    const priceInput = document.getElementById('price');
    priceInput.addEventListener('input', function(e) {
      // Remove non-numeric characters
      this.value = this.value.replace(/[^0-9]/g, '');
    });

    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
      setTimeout(() => {
        alert.style.animation = 'fadeOut 0.5s ease';
        setTimeout(() => {
          alert.remove();
        }, 500);
      }, 5000);
    });
  </script>

  <style>
    /* Additional Styles for Add Product Page */
    .btn-secondary {
      background: #6c757d;
      color: white;
    }

    .btn-secondary:hover {
      background: #5a6268;
      transform: translateY(-2px);
    }

    .card-header {
      padding: 20px 25px;
      border-bottom: 1px solid #e0e0e0;
      background: #f8f9fa;
    }

    .card-header h3 {
      font-size: 18px;
      color: #2c3e50;
      display: flex;
      align-items: center;
      gap: 10px;
      margin: 0;
    }

    .card-header i {
      color: #667eea;
    }

    .product-form {
      padding: 30px 25px;
    }

    .form-row {
      margin-bottom: 25px;
    }

    .form-group {
      width: 100%;
    }

    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: #2c3e50;
      font-size: 14px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .form-group label i {
      color: #667eea;
    }

    .required {
      color: #e74c3c;
    }

    .form-group input[type="text"],
    .form-group input[type="number"],
    .form-group textarea {
      width: 100%;
      padding: 12px 15px;
      border: 2px solid #e0e0e0;
      border-radius: 8px;
      font-size: 14px;
      transition: all 0.3s ease;
      font-family: inherit;
    }

    .form-group input:focus,
    .form-group textarea:focus {
      outline: none;
      border-color: #667eea;
      box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
    }

    .form-group textarea {
      resize: vertical;
      min-height: 120px;
    }

    .input-group {
      display: flex;
      align-items: center;
    }

    .input-prefix {
      background: #f0f0f0;
      padding: 12px 15px;
      border: 2px solid #e0e0e0;
      border-right: none;
      border-radius: 8px 0 0 8px;
      font-weight: 600;
      color: #555;
    }

    .input-group input {
      border-radius: 0 8px 8px 0 !important;
    }

    .form-text {
      display: block;
      margin-top: 5px;
      font-size: 12px;
      color: #777;
    }

    /* File Upload Styles */
    .file-upload-wrapper {
      position: relative;
    }

    .file-upload-wrapper input[type="file"] {
      display: none;
    }

    .file-upload-label {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 40px 20px;
      border: 2px dashed #ccc;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.3s ease;
      background: #fafafa;
    }

    .file-upload-label:hover {
      border-color: #667eea;
      background: #f0f4ff;
    }

    .file-upload-label.has-file {
      border-color: #27ae60;
      background: #e8f5e9;
    }

    .file-upload-label i {
      font-size: 48px;
      color: #667eea;
      margin-bottom: 10px;
    }

    .file-upload-text {
      font-size: 14px;
      color: #555;
    }

    .file-name {
      margin-top: 10px;
      font-size: 13px;
      color: #27ae60;
      font-weight: 600;
    }

    .image-preview {
      display: none;
      margin-top: 15px;
      text-align: center;
    }

    .image-preview img {
      max-width: 300px;
      max-height: 300px;
      border-radius: 8px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    /* Form Actions */
    .form-actions {
      display: flex;
      gap: 15px;
      margin-top: 30px;
      padding-top: 25px;
      border-top: 1px solid #e0e0e0;
    }

    .form-actions .btn {
      flex: 1;
    }

    /* Alert Messages */
    .alert {
      padding: 15px 20px;
      border-radius: 8px;
      margin-bottom: 25px;
      display: flex;
      align-items: center;
      gap: 12px;
      animation: slideDown 0.5s ease;
    }

    @keyframes slideDown {
      from {
        opacity: 0;
        transform: translateY(-20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes fadeOut {
      to {
        opacity: 0;
        transform: translateY(-10px);
      }
    }

    .alert-success {
      background: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }

    .alert-success i {
      color: #28a745;
      font-size: 20px;
    }

    .alert-error {
      background: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }

    .alert-error i {
      color: #dc3545;
      font-size: 20px;
    }

    /* Responsive */
    @media (max-width: 768px) {
      .form-actions {
        flex-direction: column;
      }

      .product-form {
        padding: 20px 15px;
      }
    }
  </style>
</body>
</html>