<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}
require '../includes/db.php';

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];

    if (!empty($_FILES['image']['name'])) {
        $target_dir = "../assets/images/";
        $image = $target_dir . basename($_FILES["image"]["name"]);
        move_uploaded_file($_FILES["image"]["tmp_name"], $image);
        $image_path = "assets/images/" . basename($_FILES["image"]["name"]);
    } else {
        $image_path = $product['image'];
    }

    $stmt = $pdo->prepare("UPDATE products SET name=?, description=?, price=?, image=?, stock=? WHERE id=?");
    $stmt->execute([$name, $desc, $price, $image_path, $stock, $id]);
    header("Location: dashboard.php");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Produk</title>
  <link rel="stylesheet" href="../assets/css/edit_product.css">
</head>
<body>
  <div class="container">
    <h2>Edit Produk</h2>

    <form method="post" enctype="multipart/form-data">
      <div class="form-group">
        <label>Nama Produk:</label>
        <input type="text" name="name" value="<?= htmlspecialchars($product['name']); ?>" required>
      </div>

      <div class="form-group">
        <label>Deskripsi:</label>
        <textarea name="description" rows="4" required><?= htmlspecialchars($product['description']); ?></textarea>
      </div>

      <div class="form-group">
        <label>Harga (Rp):</label>
        <input type="number" name="price" value="<?= htmlspecialchars($product['price']); ?>" required>
      </div>

      <div class="form-group">
        <label>Stok:</label>
        <input type="number" name="stock" value="<?= htmlspecialchars($product['stock'] ?? 0); ?>" min="0" required>
      </div>

      <div class="form-group">
        <label>Gambar (kosongkan jika tidak diganti):</label>
        <?php if (!empty($product['image'])): ?>
          <img src="../<?= htmlspecialchars($product['image']); ?>" alt="Gambar Produk" class="preview">
        <?php endif; ?>
        <input type="file" name="image">
      </div>

      <div class="buttons">
        <a href="dashboard.php" class="btn-secondary">← Kembali</a>
        <button type="submit" class="btn-primary">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</body>
</html>
