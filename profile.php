<?php
session_start();
include 'includes/header.php';
include 'includes/db.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';
$tab = $_GET['tab'] ?? 'profile';

// Ambil data user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Update profil
if ($_SERVER["REQUEST_METHOD"] == "POST" && $tab === 'profile') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $kota = trim($_POST['kota']);
    $kode_pos = trim($_POST['kode_pos']);

    if (empty($name) || empty($email)) {
        $error = "Nama dan email harus diisi!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid!";
    } elseif (!empty($phone) && !preg_match('/^[0-9\-\+\(\)\s]+$/', $phone)) {
        $error = "Format nomor telepon tidak valid!";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, address = ?, kota = ?, kode_pos = ? WHERE id = ?");
            $stmt->execute([$name, $email, $phone, $address, $kota, $kode_pos, $user_id]);
            $success = "Profil berhasil diperbarui!";
            $_SESSION['name'] = $name;

            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
        } catch(PDOException $e) {
            $error = "Terjadi kesalahan saat memperbarui profil.";
        }
    }
}

// Ubah password
if ($_SERVER["REQUEST_METHOD"] == "POST" && $tab === 'password') {
    $password_lama = $_POST['password_lama'] ?? '';
    $password_baru = $_POST['password_baru'] ?? '';
    $password_konfirmasi = $_POST['password_konfirmasi'] ?? '';

    if (empty($password_lama) || empty($password_baru) || empty($password_konfirmasi)) {
        $error = "Semua field password harus diisi!";
    } elseif (strlen($password_baru) < 6) {
        $error = "Password baru minimal 6 karakter!";
    } elseif ($password_baru !== $password_konfirmasi) {
        $error = "Password baru dan konfirmasi tidak cocok!";
    } elseif (!password_verify($password_lama, $user['password'])) {
        $error = "Password lama salah!";
    } else {
        try {
            $password_hashed = password_hash($password_baru, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$password_hashed, $user_id]);
            $success = "Password berhasil diubah!";
        } catch(PDOException $e) {
            $error = "Terjadi kesalahan saat mengubah password.";
        }
    }
}

// Ambil statistik user
$stmt = $pdo->prepare("SELECT COUNT(*) as total_order FROM orders WHERE user_id = ?");
$stmt->execute([$user_id]);
$order_stats = $stmt->fetch();

$stmt = $pdo->prepare("SELECT COUNT(*) as total_review FROM reviews WHERE user_name = ?");
$stmt->execute([$user_id]);
$review_stats = $stmt->fetch();

// Ambil pesanan terbaru dengan nama produk pertama
$stmt = $pdo->prepare("SELECT o.*, (SELECT p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = o.id LIMIT 1) as product_name FROM orders o WHERE o.user_id = ? ORDER BY o.created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$recent_orders = $stmt->fetchAll();
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link rel="stylesheet" href="assets/css/profile.css">

<div class="profile-wrapper">
    <div class="profile-sidebar">
        <div class="sidebar-header">
            <div class="avatar-large">
                <?php
                  $initial = strtoupper(substr($user['name'] ?? 'U', 0, 1));
                  echo "<span>$initial</span>";
                ?>
                <button class="edit-avatar-btn" title="Ubah Avatar">
                    <i class="bi bi-camera-fill"></i>
                </button>
            </div>
            <h3><?= htmlspecialchars($user['name'] ?? 'User') ?></h3>
            <p class="user-email"><?= htmlspecialchars($user['email']) ?></p>
            <span class="member-badge">
                <i class="bi bi-patch-check-fill"></i> Premium Member
            </span>
        </div>

        <nav class="sidebar-nav">
            <button class="nav-item active" onclick="switchTab('dashboard')">
                <i class="bi bi-grid-fill"></i>
                <span>Dashboard</span>
                <i class="bi bi-chevron-right"></i>
            </button>
            <button class="nav-item" onclick="switchTab('profile')">
                <i class="bi bi-person-fill"></i>
                <span>Edit Profil</span>
                <i class="bi bi-chevron-right"></i>
            </button>
            <a href="orders.php" class="nav-item" style="text-decoration: none;">
                <i class="bi bi-bag-fill"></i>
                <span>Pesanan Saya</span>
            </a>
            </button>
            <button class="nav-item" onclick="switchTab('password')">
                <i class="bi bi-shield-lock-fill"></i>
                <span>Keamanan</span>
                <i class="bi bi-chevron-right"></i>
            </button>
            <button class="nav-item" onclick="switchTab('settings')">
                <i class="bi bi-gear-fill"></i>
                <span>Pengaturan</span>
                <i class="bi bi-chevron-right"></i>
            </button>
        </nav>

        <div class="sidebar-footer">
            <a href="index.php" class="btn-back">
                <i class="bi bi-house-door-fill"></i> Kembali ke Beranda
            </a>
            <a href="logout.php" class="btn-logout-sidebar">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </div>

    <div class="profile-main">
        <!-- TAB: DASHBOARD -->
        <div id="dashboard" class="tab-content active">
            <div class="content-header">
                <div>
                    <h2>Dashboard</h2>
                    <p>Selamat datang kembali, <?= htmlspecialchars($user['name'] ?? 'User') ?>! 👋</p>
                </div>
                <button class="btn-refresh" onclick="location.reload()">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="bi bi-cart-check-fill"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $order_stats['total_order'] ?? 0 ?></h3>
                        <p>Total Pesanan</p>
                    </div>
                    <div class="stat-trend up">
                        <i class="bi bi-arrow-up"></i> 12%
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon purple">
                        <i class="bi bi-star-fill"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $review_stats['total_review'] ?? 0 ?></h3>
                        <p>Ulasan Diberikan</p>
                    </div>
                    <div class="stat-trend up">
                        <i class="bi bi-arrow-up"></i> 8%
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Rp 0</h3>
                        <p>Total Belanja</p>
                    </div>
                    <div class="stat-trend">
                        <i class="bi bi-dash"></i> 0%
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="bi bi-gift-fill"></i>
                    </div>
                    <div class="stat-info">
                        <h3>250</h3>
                        <p>Poin Reward</p>
                    </div>
                    <div class="stat-trend up">
                        <i class="bi bi-arrow-up"></i> 5%
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <h3>Aksi Cepat</h3>
                <div class="action-grid">
                    <a href="products.php" class="action-card">
                        <i class="bi bi-shop"></i>
                        <span>Belanja Sekarang</span>
                    </a>
                    <a href="cart.php" class="action-card">
                        <i class="bi bi-cart3"></i>
                        <span>Lihat Keranjang</span>
                    </a>
                    <a href="#" onclick="switchTab('orders'); return false;" class="action-card">
                        <i class="bi bi-clock-history"></i>
                        <span>Riwayat Pesanan</span>
                    </a>
                    <a href="#" onclick="switchTab('settings'); return false;" class="action-card">
                        <i class="bi bi-headset"></i>
                        <span>Bantuan</span>
                    </a>
                </div>
            </div>

            <!-- Recent Orders -->
            <?php if (!empty($recent_orders)): ?>
            <div class="recent-section">
                <h3>Pesanan Terbaru</h3>
                <div class="orders-list">
                    <?php foreach($recent_orders as $order): ?>
                    <div class="order-item">
                        <div class="order-icon">
                            <i class="bi bi-box-seam"></i>
                        </div>
                        <div class="order-details">
                            <h4>Order #<?= $order['id'] ?></h4>
                            <p><?= date('d M Y', strtotime($order['created_at'])) ?></p>
                        </div>
                        <span class="order-status <?= strtolower($order['status'] ?? 'pending') ?>">
                            <?= htmlspecialchars($order['status'] ?? 'Pending') ?>
                        </span>
                        <span class="order-total">
                            Rp <?= number_format($order['total_price'] ?? 0, 0, ',', '.') ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Activity Timeline -->
            <div class="timeline-section">
                <h3>Aktivitas Terbaru</h3>
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-dot blue"></div>
                        <div class="timeline-content">
                            <h4>Profil Diperbarui</h4>
                            <p><?= date('d M Y, H:i', strtotime($user['updated_at'] ?? $user['created_at'])) ?></p>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-dot green"></div>
                        <div class="timeline-content">
                            <h4>Akun Dibuat</h4>
                            <p><?= date('d M Y, H:i', strtotime($user['created_at'])) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB: EDIT PROFILE -->
        <div id="profile" class="tab-content">
            <div class="content-header">
                <div>
                    <h2>Edit Profil</h2>
                    <p>Kelola informasi profil Anda untuk mengontrol, melindungi, dan mengamankan akun</p>
                </div>
            </div>

            <?php if($success && $tab === 'profile'): ?>
                <div class="alert success">
                    <i class="bi bi-check-circle-fill"></i>
                    <span><?= $success ?></span>
                </div>
            <?php endif; ?>
            <?php if($error && $tab === 'profile'): ?>
                <div class="alert error">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <span><?= $error ?></span>
                </div>
            <?php endif; ?>

            <div class="form-card">
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="bi bi-person-badge-fill"></i> Username</label>
                            <input type="text" value="<?= htmlspecialchars($user['username']) ?>" readonly>
                            <small>Username tidak dapat diubah</small>
                        </div>

                        <div class="form-group">
                            <label><i class="bi bi-envelope-fill"></i> Email</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="bi bi-card-text"></i> Nama Lengkap</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
                        </div>

                        <div class="form-group">
                            <label><i class="bi bi-telephone-fill"></i> No. Telepon</label>
                            <input type="text" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="08xx-xxxx-xxxx">
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="bi bi-geo-alt-fill"></i> Alamat Lengkap</label>
                        <textarea name="address" rows="3" placeholder="Masukkan alamat lengkap Anda"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="bi bi-building"></i> Kota</label>
                            <input type="text" name="kota" value="<?= htmlspecialchars($user['kota'] ?? '') ?>" placeholder="Contoh: Jakarta">
                        </div>

                        <div class="form-group">
                            <label><i class="bi bi-mailbox"></i> Kode Pos</label>
                            <input type="text" name="kode_pos" value="<?= htmlspecialchars($user['kode_pos'] ?? '') ?>" placeholder="Contoh: 12345">
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn-secondary" onclick="location.reload()">
                            <i class="bi bi-x-circle"></i> Batal
                        </button>
                        <button type="submit" class="btn-primary">
                            <i class="bi bi-check-circle-fill"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- TAB: ORDERS -->
        <div id="orders" class="tab-content">
            <div class="content-header">
                <div>
                    <h2>Pesanan Saya</h2>
                    <p>Kelola dan lacak semua pesanan Anda</p>
                </div>
                <div class="order-filters">
                    <button class="filter-btn active">Semua</button>
                    <button class="filter-btn">Diproses</button>
                    <button class="filter-btn">Dikirim</button>
                    <button class="filter-btn">Selesai</button>
                </div>
            </div>

            <?php if (empty($recent_orders)): ?>
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <h3>Belum Ada Pesanan</h3>
                <p>Anda belum memiliki pesanan. Mulai belanja sekarang!</p>
                <a href="products.php" class="btn-primary">
                    <i class="bi bi-shop"></i> Mulai Belanja
                </a>
            </div>
            <?php else: ?>
            <div class="orders-grid">
                <?php foreach($recent_orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <span class="order-id">#<?= $order['id'] ?></span>
                            <span class="order-date"><?= date('d M Y', strtotime($order['created_at'])) ?></span>
                        </div>
                        <span class="status-badge <?= strtolower($order['status'] ?? 'pending') ?>">
                            <?= htmlspecialchars($order['status'] ?? 'Pending') ?>
                        </span>
                    </div>
                    <div class="order-body">
                        <div class="order-info">
                            <i class="bi bi-box-seam"></i>
                            <span><?= htmlspecialchars($order['product_name'] ?? 'Produk') ?></span>
                        </div>
                        <div class="order-price">
                            Rp <?= number_format($order['total'] ?? 0, 0, ',', '.') ?>
                        </div>
                    </div>
                    <div class="order-footer">
                        <a href="order_detail.php?id=<?= $order['id'] ?>" class="btn-outline">Detail</a>
                        <?php if (in_array($order['status'], ['shipped', 'delivered'])): ?>
                            <a href="track_order.php?id=<?= $order['id'] ?>" class="btn-primary-sm">Lacak Pesanan</a>
                        <?php else: ?>
                            <button class="btn-primary-sm disabled" disabled>Lacak Pesanan</button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- TAB: PASSWORD -->
        <div id="password" class="tab-content">
            <div class="content-header">
                <div>
                    <h2>Keamanan Akun</h2>
                    <p>Kelola password dan pengaturan keamanan akun Anda</p>
                </div>
            </div>

            <?php if($success && $tab === 'password'): ?>
                <div class="alert success">
                    <i class="bi bi-check-circle-fill"></i>
                    <span><?= $success ?></span>
                </div>
            <?php endif; ?>
            <?php if($error && $tab === 'password'): ?>
                <div class="alert error">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <span><?= $error ?></span>
                </div>
            <?php endif; ?>

            <div class="form-card">
                <h3><i class="bi bi-key-fill"></i> Ubah Password</h3>
                <form method="POST">
                    <div class="form-group">
                        <label><i class="bi bi-lock-fill"></i> Password Lama</label>
                        <div class="password-input">
                            <input type="password" name="password_lama" class="pwd-field" required>
                            <button type="button" class="toggle-pwd" onclick="togglePassword(this)">
                                <i class="bi bi-eye-fill"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="bi bi-shield-lock-fill"></i> Password Baru</label>
                        <div class="password-input">
                            <input type="password" name="password_baru" class="pwd-field" required>
                            <button type="button" class="toggle-pwd" onclick="togglePassword(this)">
                                <i class="bi bi-eye-fill"></i>
                            </button>
                        </div>
                        <div class="password-strength" id="passwordStrength"></div>
                        <small>Minimal 6 karakter, kombinasi huruf dan angka</small>
                    </div>

                    <div class="form-group">
                        <label><i class="bi bi-shield-check"></i> Konfirmasi Password Baru</label>
                        <div class="password-input">
                            <input type="password" name="password_konfirmasi" class="pwd-field" required>
                            <button type="button" class="toggle-pwd" onclick="togglePassword(this)">
                                <i class="bi bi-eye-fill"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            <i class="bi bi-check-circle-fill"></i> Ubah Password
                        </button>
                    </div>
                </form>
            </div>

            <div class="security-info">
                <h3><i class="bi bi-shield-fill-check"></i> Informasi Keamanan</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <i class="bi bi-clock-history"></i>
                        <div>
                            <strong>Terakhir Diubah</strong>
                            <p><?= date('d M Y, H:i', strtotime($user['updated_at'] ?? $user['created_at'])) ?></p>
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="bi bi-shield-check"></i>
                        <div>
                            <strong>Status Keamanan</strong>
                            <p class="text-success">Aman</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB: SETTINGS -->
        <div id="settings" class="tab-content">
            <div class="content-header">
                <div>
                    <h2>Pengaturan</h2>
                    <p>Kelola preferensi dan pengaturan akun Anda</p>
                </div>
            </div>

            <div class="settings-section">
                <h3><i class="bi bi-bell-fill"></i> Notifikasi</h3>
                <div class="setting-item">
                    <div class="setting-info">
                        <strong>Notifikasi Email</strong>
                        <p>Terima pembaruan pesanan via email</p>
                    </div>
                    <label class="switch">
                        <input type="checkbox" checked>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="setting-item">
                    <div class="setting-info">
                        <strong>Promosi & Penawaran</strong>
                        <p>Dapatkan info promo dan diskon terbaru</p>
                    </div>
                    <label class="switch">
                        <input type="checkbox" checked>
                        <span class="slider"></span>
                    </label>
                </div>
            </div>

            <div class="settings-section">
                <h3><i class="bi bi-eye-fill"></i> Privasi</h3>
                <div class="setting-item">
                    <div class="setting-info">
                        <strong>Profil Publik</strong>
                        <p>Tampilkan profil di direktori publik</p>
                    </div>
                    <label class="switch">
                        <input type="checkbox">
                        <span class="slider"></span>
                    </label>
                </div>
            </div>

            <div class="danger-zone">
                <h3><i class="bi bi-exclamation-triangle-fill"></i> Zona Bahaya</h3>
                <p>Tindakan berikut bersifat permanen dan tidak dapat dibatalkan</p>
                <div class="danger-actions">
                    <button class="btn-danger" onclick="confirmDelete()">
                        <i class="bi bi-trash-fill"></i> Hapus Akun
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function switchTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
    
    document.getElementById(tabName).classList.add('active');
    event.target.closest('.nav-item').classList.add('active');
    
    window.scrollTo({top: 0, behavior: 'smooth'});
}

function togglePassword(button) {
    const input = button.closest('.password-input').querySelector('input');
    const icon = button.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye-fill');
        icon.classList.add('bi-eye-slash-fill');
    } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash-fill');
        icon.classList.add('bi-eye-fill');
    }
}

// Password strength checker
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.querySelector('input[name="password_baru"]');
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            const strength = checkPasswordStrength(this.value);
            const strengthBar = document.getElementById('passwordStrength');
            strengthBar.className = 'password-strength ' + strength.class;
            strengthBar.innerHTML = '<span class="strength-bar"></span><span>' + strength.text + '</span>';
        });
    }
});

function checkPasswordStrength(password) {
    if (password.length === 0) return {class: '', text: ''};
    if (password.length < 6) return {class: 'weak', text: 'Lemah'};
    if (password.length < 10) return {class: 'medium', text: 'Sedang'};
    if (password.match(/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])/)) {
        return {class: 'strong', text: 'Kuat'};
    }
    return {class: 'medium', text: 'Sedang'};
}

function confirmDelete() {
    if (confirm('Apakah Anda yakin ingin menghapus akun? Tindakan ini tidak dapat dibatalkan!')) {
        alert('Fitur hapus akun akan segera tersedia.');
    }
}
</script>

<?php include 'includes/footer.php'; ?>