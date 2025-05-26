<?php
include "config.php";
session_start();

// Set theme based on session, default to light
$theme = isset($_SESSION['theme']) ? $_SESSION['theme'] : 'light';

$message = "";

// Handle delete category
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $sql_delete = "DELETE FROM categories WHERE id = $delete_id";
    if ($conn->query($sql_delete) === TRUE) {
        $message = "Kategori berhasil dihapus!";
    } else {
        $message = "Error: " . $conn->error;
    }
}

// Handle add category
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $conn->real_escape_string($_POST["name"]);
    $description = $conn->real_escape_string($_POST["description"]);
    $sql = "INSERT INTO categories (name, description) VALUES ('$name', '$description')";
    if ($conn->query($sql) === TRUE) {
        $message = "Kategori berhasil ditambahkan!";
    } else {
        $message = "Error: " . $conn->error;
    }
}

$sql_categories = "SELECT * FROM categories";
$result_categories = $conn->query($sql_categories);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kelola Kategori</title>
  <style>
    /* Mode Terang */
    body.light { background: #f4f7f6; color: #333; }
    body.light .container { background: #fff; }
    body.light h1, body.light h2 { color: #469ced; }
    body.light form button, body.light table th { background: #469ced; color: #fff; }

    /* Mode Gelap */
    body.dark { background-color: #121212; color: #e0e0e0; }
    body.dark .container { background: #1e1e1e; }
    body.dark h1, body.dark h2 { color: #1f78d1; }
    body.dark form button, body.dark table th { background: #1f78d1; color: #e0e0e0; }
    body.dark .container { box-shadow: 0 2px 6px rgba(0,0,0,0.5); }
    body.dark table tr:nth-child(even) { background-color: #2a2a2a; }

    /* Umum */
    body, input, button, textarea, table { font-family: Arial, sans-serif; }
    .container { max-width: 800px; margin: 20px auto; padding: 20px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
    h1, h2 { text-align: center; margin-bottom: 15px; }
    form { text-align: center; margin-bottom: 30px; }
    form label { font-weight: bold; display: block; margin: 10px 0 5px; }
    form input[type="text"], form textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; margin-bottom: 15px; }
    form button { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; transition: background 0.3s ease; }
    .message { text-align: center; font-weight: bold; margin-bottom: 15px; }
    .message.error { color: #d32f2f; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    table th, table td { padding: 12px; border: 1px solid #ddd; }
    table th { text-align: left; }
    table a { text-decoration: none; font-weight: bold; }
    table a:hover { text-decoration: underline; }
  </style>
</head>
<body class="<?php echo $theme; ?>">
  <div class="container">
    <h1>Kelola Kategori</h1>
    <?php if ($message !== ""): ?>
      <p class="message<?php echo strpos($message, 'Error') === 0 ? ' error' : ''; ?>"><?php echo $message; ?></p>
    <?php endif; ?>
    <form method="post">
      <label for="name">Nama Kategori:</label>
      <input type="text" name="name" placeholder="Nama Kategori" required>
      <label for="description">Deskripsi:</label>
      <textarea name="description" placeholder="Deskripsi Kategori" rows="3" required></textarea>
      <button type="submit">Tambah Kategori</button>
    </form>

    <h2>Daftar Kategori</h2>
    <table>
      <tr>
        <th>Nama Kategori</th>
        <th>Deskripsi</th>
        <th>Aksi</th>
      </tr>
      <?php if ($result_categories && $result_categories->num_rows > 0): ?>
        <?php while ($row = $result_categories->fetch_assoc()): ?>
          <tr>
            <td><?php echo htmlspecialchars($row['name']); ?></td>
            <td><?php echo htmlspecialchars($row['description']); ?></td>
            <td>
              <a href="edit_category.php?id=<?php echo $row['id']; ?>">Edit</a> |
              <a href="?delete_id=<?php echo $row['id']; ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus kategori ini?');">Hapus</a>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="3" style="text-align:center;">Tidak ada kategori</td></tr>
      <?php endif; ?>
    </table>
  </div>
</body>
</html>
