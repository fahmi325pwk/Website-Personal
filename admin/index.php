<?php
session_start();
require '../includes/db.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = md5(trim($_POST['password']));

    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username=? AND password=?");
    $stmt->execute([$username, $password]);
    $admin = $stmt->fetch();

    if ($admin) {
        $_SESSION['admin'] = $admin['username'];
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Username atau password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Admin | Nano Komputer</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      position: relative;
      overflow: hidden;
    }

    /* Animated background particles */
    body::before {
      content: '';
      position: absolute;
      width: 200%;
      height: 200%;
      background-image: 
        radial-gradient(circle at 20% 30%, rgba(255,255,255,0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 70%, rgba(255,255,255,0.1) 0%, transparent 50%),
        radial-gradient(circle at 40% 80%, rgba(255,255,255,0.05) 0%, transparent 50%);
      animation: float 20s ease-in-out infinite;
    }

    @keyframes float {
      0%, 100% { transform: translate(0, 0) rotate(0deg); }
      33% { transform: translate(30px, -30px) rotate(120deg); }
      66% { transform: translate(-20px, 20px) rotate(240deg); }
    }

    .login-container {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      padding: 50px 45px;
      border-radius: 20px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
      width: 420px;
      max-width: 90%;
      position: relative;
      z-index: 1;
      animation: slideUp 0.6s ease-out;
    }

    @keyframes slideUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .logo-section {
      text-align: center;
      margin-bottom: 35px;
    }

    .logo-icon {
      width: 80px;
      height: 80px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border-radius: 50%;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 20px;
      box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
      animation: pulse 2s ease-in-out infinite;
    }

    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.05); }
    }

    .logo-icon i {
      font-size: 40px;
      color: white;
    }

    h2 {
      color: #333;
      font-size: 28px;
      font-weight: 600;
      margin-bottom: 8px;
    }

    .subtitle {
      color: #777;
      font-size: 14px;
      margin-bottom: 25px;
    }

    .input-group {
      position: relative;
      margin-bottom: 25px;
    }

    .input-group i {
      position: absolute;
      left: 18px;
      top: 50%;
      transform: translateY(-50%);
      color: #667eea;
      font-size: 18px;
      transition: all 0.3s ease;
    }

    .input-group input {
      width: 100%;
      padding: 16px 18px 16px 52px;
      border: 2px solid #e0e0e0;
      border-radius: 12px;
      font-size: 15px;
      transition: all 0.3s ease;
      background: white;
      color: #333;
    }

    .input-group input:focus {
      outline: none;
      border-color: #667eea;
      box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
    }

    .input-group input:focus + i {
      color: #764ba2;
    }

    .input-group input::placeholder {
      color: #999;
    }

    button[type="submit"] {
      width: 100%;
      padding: 16px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border: none;
      border-radius: 12px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
      margin-top: 10px;
    }

    button[type="submit"]:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 30px rgba(102, 126, 234, 0.4);
    }

    button[type="submit"]:active {
      transform: translateY(0);
    }

    .error-message {
      background: #fee;
      color: #c33;
      padding: 12px 16px;
      border-radius: 10px;
      margin-top: 20px;
      font-size: 14px;
      border-left: 4px solid #c33;
      animation: shake 0.5s ease;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      25% { transform: translateX(-10px); }
      75% { transform: translateX(10px); }
    }

    .error-message i {
      font-size: 18px;
    }

    .footer-text {
      text-align: center;
      margin-top: 25px;
      color: #777;
      font-size: 13px;
    }

    /* Loading animation */
    .btn-loading {
      position: relative;
      pointer-events: none;
    }

    .btn-loading::after {
      content: '';
      position: absolute;
      width: 20px;
      height: 20px;
      top: 50%;
      left: 50%;
      margin-left: -10px;
      margin-top: -10px;
      border: 3px solid rgba(255,255,255,0.3);
      border-top-color: white;
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
      to { transform: rotate(360deg); }
    }

    @media (max-width: 480px) {
      .login-container {
        padding: 40px 30px;
      }
      
      h2 {
        font-size: 24px;
      }
    }
  </style>
</head>
<body>
  <div class="login-container">
    <div class="logo-section">
      <div class="logo-icon">
        <i class="fas fa-laptop-code"></i>
      </div>
      <h2>Admin Panel</h2>
      <p class="subtitle">Nano Komputer</p>
    </div>
    
    <form method="post" id="loginForm">
      <div class="input-group">
        <input type="text" name="username" id="username" placeholder="Username" required>
        <i class="fas fa-user"></i>
      </div>
      
      <div class="input-group">
        <input type="password" name="password" id="password" placeholder="Password" required>
        <i class="fas fa-lock"></i>
      </div>
      
      <button type="submit" id="loginBtn">
        <span>Masuk</span>
      </button>
      
      <?php if($error): ?>
        <div class="error-message">
          <i class="fas fa-exclamation-circle"></i>
          <span><?= htmlspecialchars($error) ?></span>
        </div>
      <?php endif; ?>
    </form>
    
    <div class="footer-text">
      <i class="fas fa-shield-alt"></i> Secure Admin Access
    </div>
  </div>

  <script>
    // Add loading state to button on submit
    document.getElementById('loginForm').addEventListener('submit', function() {
      const btn = document.getElementById('loginBtn');
      btn.classList.add('btn-loading');
      btn.innerHTML = '';
    });

    // Add focus animations
    const inputs = document.querySelectorAll('input');
    inputs.forEach(input => {
      input.addEventListener('focus', function() {
        this.parentElement.style.transform = 'scale(1.02)';
      });
      input.addEventListener('blur', function() {
        this.parentElement.style.transform = 'scale(1)';
      });
    });
  </script>
</body>
</html>