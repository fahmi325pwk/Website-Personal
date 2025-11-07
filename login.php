<?php
session_start();
include 'includes/header.php';
include 'includes/db.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
        
        // Redirect ke halaman sebelumnya jika ada
        if (isset($_GET['redirect'])) {
            header("Location: " . $_GET['redirect']);
        } else {
            header("Location: index.php");
        }
        exit();
    } else {
        $error = "Username atau password salah!";
    }
}
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-card-header">
            <h2><i class="bi bi-person-circle"></i> Login</h2>
        </div>
        <div class="auth-card-body">
            <?php if($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="" class="auth-form">
                <div class="form-group">
                    <label><i class="bi bi-person"></i> Username</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group">
                    <label><i class="bi bi-lock"></i> Password</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="btn-submit">Login</button>
            </form>
            <div class="auth-links">
                Belum punya akun? <a href="register.php">Daftar di sini</a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>