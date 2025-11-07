<?php
include 'includes/header.php';
include 'includes/db.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $email = trim($_POST['email']);
    $name = trim($_POST['name']);
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);
    $kota = trim($_POST['kota']);
    $kode_pos = trim($_POST['kode_pos']);

    // Cek apakah username sudah digunakan
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);

    if ($stmt->rowCount() > 0) {
        $error = "Username sudah digunakan!";
    } else {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("
                INSERT INTO users (name, username, email, password, phone, address, kota, kode_pos, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$name, $username, $email, $hashed_password, $phone, $address, $kota, $kode_pos]);
            $success = "Registrasi berhasil! Silakan login.";
        } catch(PDOException $e) {
            $error = "Terjadi kesalahan saat registrasi: " . $e->getMessage();
        }
    }
}
?>

<div class="auth-container">
   <div class="auth-card">
        <div class="auth-card-header">
            <h2>Registrasi Pengguna</h2>
        </div>
        <div class="auth-card-body">
            <?php if($error): ?>
                <div class="alert alert-danger"><?= $error; ?></div>
            <?php endif; ?>
            <?php if($success): ?>
                <div class="alert alert-success"><?= $success; ?></div>
           <?php endif; ?>

            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="name" required>
                    </div>
<div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                   <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <div class="form-group">
                    <label>No. Telepon</label>
                    <input type="text" name="phone">
                </div>
                <div class="form-group">
                    <label>Alamat</label>
                    <textarea name="address"></textarea>
                </div>
                <div class="form-group">
                    <label>Kota</label>
                    <input type="text" name="kota">
                </div>
                <div class="form-group">
                    <label>Kode Pos</label>
                    <input type="text" name="kode_pos">
                </div>
                <button type="submit" class="btn-submit">Daftar</button>
            </form>
                <div class="auth-links">
                Sudah punya akun? <a href="login.php">Login di sini</a>                    
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
