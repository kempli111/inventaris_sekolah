<?php
include "config.php";
session_start();

// Ambil theme dari session, default light
$theme = isset($_SESSION['theme']) ? $_SESSION['theme'] : 'light';

// Cek ID kategori
if (!isset($_GET['id'])) {
    header("Location: categories.php");
    exit();
}
$id = intval($_GET['id']);

// Ambil data kategori
$sql = "SELECT * FROM categories WHERE id = $id";
$result = $conn->query($sql);
if (!$result || $result->num_rows == 0) {
    echo "Kategori tidak ditemukan.";
    exit();
}
$category = $result->fetch_assoc();

$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $conn->real_escape_string($_POST['name']);
    $description = $conn->real_escape_string($_POST['description']);
    $sql_update = "UPDATE categories SET name='$name', description='$description' WHERE id=$id";
    if ($conn->query($sql_update) === TRUE) {
        $message = "Kategori berhasil diperbarui!";
        // Refresh data
        $result = $conn->query($sql);
        $category = $result->fetch_assoc();
    } else {
        $message = "Error: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Kategori</title>
  <style>
    /* Mode Terang */
    body.light { background: #f4f7f6; color: #333; }
    body.light .container { background: #fff; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
    body.light h1 { color: #469ced; }
    body.light label, body.light button, body.light .back-link a { color: #333; }
    body.light input, body.light textarea { background: #fff; color: #333; border: 1px solid #ccc; }
    body.light button { background: #469ced; color: #fff; }
    body.light button:hover { background: #357ab8; }
    body.light .back-link a:hover { text-decoration: underline; }
    .message.light { color: #2e7d32; }

    /* Mode Gelap */
    body.dark {
        background-color: #000 !important;
        color: #fff !important;
    }
    body.dark .container, 
    body.dark .card, 
    body.dark .form-section, 
    body.dark .iframe-section, 
    body.dark .table-section, 
    body.dark .table-container, 
    body.dark .modal-content, 
    body.dark .tab-content, 
    body.dark .search-box {
        background: #111 !important;
        color: #fff !important;
        box-shadow: 0 2px 8px rgba(52,152,219,0.08);
    }
    body.dark h1 { color: #1f78d1; }
    body.dark label, body.dark button, body.dark .back-link a { color: #e0e0e0; }
    body.dark input, body.dark textarea { background: #2a2a2a; color: #e0e0e0; border: 1px solid #444; }
    body.dark button { background: #1f78d1; color: #e0e0e0; }
    body.dark button:hover { background: #155a9c; }
    body.dark .back-link a { color: #1f78d1; }
    body.dark .back-link a:hover { text-decoration: underline; }
    .message.dark { color: #81c784; }

    /* Umum */
    body, input, textarea, button, a { font-family: Arial, sans-serif; }
    .container { max-width: 600px; margin: 20px auto; padding: 20px; border-radius: 8px; }
    h1 { text-align: center; margin-bottom: 20px; }
    form { text-align: center; }
    label { display: block; margin: 10px 0 5px; font-weight: bold; }
    input[type="text"], textarea { width: 100%; padding: 10px; margin-bottom: 15px; border-radius: 4px; }
    button { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
    .message { text-align: center; font-weight: bold; margin-bottom: 15px; }
    .back-link { text-align: center; margin-top: 15px; }
    .back-link a { font-weight: bold; text-decoration: none; }
  </style>
</head>
<body class="<?php echo $theme; ?>">
  <div class="container">
    <h1>Edit Kategori</h1>
    <?php if ($message !== ""): ?>
      <p class="message <?php echo $theme; ?>"><?php echo $message; ?></p>
    <?php endif; ?>
    <form method="post">
      <label for="name">Nama Kategori:</label>
      <input type="text" name="name" value="<?php echo htmlspecialchars($category['name']); ?>" required>

      <label for="description">Deskripsi:</label>
      <textarea name="description" rows="4" required><?php echo htmlspecialchars($category['description']); ?></textarea>

      <button type="submit">Update Kategori</button>
    </form>
    <div class="back-link">
      <a href="categories.php">&larr; Kembali ke Daftar Kategori</a>
    </div>
  </div>
</body>
</html>
