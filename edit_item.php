<?php
session_start();
include "config.php";

// Set theme from session, default to light
$theme = isset($_SESSION['theme']) ? $_SESSION['theme'] : 'light';

// Pastikan user sudah login dan ambil user_id
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$current_user_id = $_SESSION['user_id'] ?? null;

// Check item ID
if (!isset($_GET['id'])) {
    echo "ID item tidak ditemukan.";
    exit;
}
$item_id = intval($_GET['id']);

// Fetch item data for form display
$sql_item = "SELECT * FROM items WHERE id = $item_id";
$result_item = $conn->query($sql_item);
if (!$result_item || $result_item->num_rows == 0) {
    echo "Item tidak ditemukan.";
    exit;
}
$item = $result_item->fetch_assoc();

// Handle update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $conn->real_escape_string($_POST["name"]);
    $category_id = intval($_POST["category_id"]);
    $room_id = intval($_POST["room_id"]);
    $quantity = intval($_POST["quantity"]);
    $item_condition = $conn->real_escape_string($_POST["item_condition"]);
    $purchase_date = $conn->real_escape_string($_POST["purchase_date"]);
    $price = floatval($_POST["price"]);

    // Handle image upload
    $image_path = $item['image'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "uploads/";
        $tmp = $_FILES['image']['tmp_name'];
        $name_img = basename($_FILES['image']['name']);
        $dest = $target_dir . $name_img;
        if (move_uploaded_file($tmp, $dest)) {
            $image_path = $name_img;
        }
    }

    // --- Logging: Ambil data item sebelum diupdate ---
    // Ambil ulang data dari database untuk memastikan old_data sesuai saat ini
    $stmt_old_item = $conn->prepare("SELECT * FROM items WHERE id = ?");
    $stmt_old_item->bind_param("i", $item_id);
    $stmt_old_item->execute();
    $old_item_data_for_log = $stmt_old_item->get_result()->fetch_assoc();
    $stmt_old_item->close();
    // --- Akhir Logging ---

    // Update SQL
    $update_sql = "UPDATE items SET
        name=?, category_id=?, room_id=?,
        item_condition=?, purchase_date=?,
        price=?, quantity=?, image=?
        WHERE id=?";

    $stmt_update = $conn->prepare($update_sql);
    $stmt_update->bind_param(
        "siisdsisi",
        $name, $category_id, $room_id, $item_condition, $purchase_date, $price, $quantity, $image_path, $item_id
    );


    if ($stmt_update->execute()) {
        // --- Logging: Catat aksi edit item ---
        // Ambil data item setelah diupdate untuk new_data log
        $stmt_new_item = $conn->prepare("SELECT * FROM items WHERE id = ?");
        $stmt_new_item->bind_param("i", $item_id);
        $stmt_new_item->execute();
        $new_item_data_for_log = $stmt_new_item->get_result()->fetch_assoc();
        $stmt_new_item->close();

        log_action($conn, $current_user_id, 'edit_item', 'items', $item_id, $old_item_data_for_log, $new_item_data_for_log);
        // --- Akhir Logging ---

        header("Location: item.php");
        exit;
    } else {
        $error = $conn->error;
         // Log error jika update gagal?
    }
    $stmt_update->close();
}

// Fetch categories and rooms for form
$categories = $conn->query("SELECT id, name FROM categories");
$rooms = $conn->query("SELECT id, name FROM rooms");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Item</title>
  <style>
    /* Mode Terang */
    body.light { background: #f4f7f6; color: #333; }
    body.light .form-card { background: #fff; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
    body.light label, body.light button { color: #333; }
    body.light input, body.light select, body.light textarea { background: #fff; color: #333; border: 1px solid #ccc; }
    body.light button { background: #469ced; color: #fff; }

    /* Mode Gelap */
    body.dark { background-color: #121212; color: #e0e0e0; }
    body.dark .form-card { background: #1e1e1e; box-shadow: 0 2px 6px rgba(0,0,0,0.5); }
    body.dark label, body.dark button { color: #e0e0e0; }
    body.dark input, body.dark select, body.dark textarea { background: #2a2a2a; color: #e0e0e0; border: 1px solid #444; }
    body.dark button { background: #1f78d1; color: #e0e0e0; }

    /* Umum */
    body, input, select, textarea, button { font-family: Arial, sans-serif; }
    .container { max-width: 600px; margin: 30px auto; padding: 20px; border-radius: 8px; }
    h1 { text-align: center; margin-bottom: 20px; }
    .form-card { padding: 20px; border-radius: 8px; }
    label { display: block; margin-top: 10px; margin-bottom: 5px; font-weight: bold; }
    input[type="text"], input[type="date"], input[type="number"], select, textarea { width: 100%; padding: 8px; border-radius: 4px; }
    img.preview { display: block; margin: 10px 0; max-width: 100px; }
    button { margin-top: 15px; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
    .error { color: #d32f2f; text-align: center; }
  </style>
</head>
<body class="<?php echo $theme; ?>">
  <div class="container form-card">
    <h1>Edit Item</h1>
    <?php if (isset($error)): ?>
      <p class="error"><?php echo $error; ?></p>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data">
      <label>Nama:</label>
      <input type="text" name="name" value="<?php echo htmlspecialchars($item['name']); ?>" required>

      <label>Kategori:</label>
      <select name="category_id" required>
        <option value="">Pilih Kategori</option>
        <?php while ($row = $categories->fetch_assoc()): ?>
          <option value="<?php echo $row['id']; ?>" <?php if ($row['id']==$item['category_id']) echo 'selected'; ?>>
            <?php echo htmlspecialchars($row['name']); ?>
          </option>
        <?php endwhile; ?>
      </select>

      <label>Ruangan:</label>
      <select name="room_id" required>
        <option value="">Pilih Ruangan</option>
        <?php while ($row = $rooms->fetch_assoc()): ?>
          <option value="<?php echo $row['id']; ?>" <?php if ($row['id']==$item['room_id']) echo 'selected'; ?>>
            <?php echo htmlspecialchars($row['name']); ?>
          </option>
        <?php endwhile; ?>
      </select>

      <label>Kondisi:</label>
      <select name="item_condition" required>
        <?php foreach ([
          'baik'=>'Baik', 'rusak ringan'=>'Rusak Ringan', 'rusak berat'=>'Rusak Berat'
        ] as $val=>$label): ?>
          <option value="<?php echo $val;?>" <?php if ($item['item_condition']==$val) echo 'selected';?>><?php echo $label;?></option>
        <?php endforeach; ?>
      </select>

      <label>Tanggal Beli:</label>
      <input type="date" name="purchase_date" value="<?php echo $item['purchase_date']; ?>" required>

      <label>Harga:</label>
      <input type="number" name="price" step="0.01" value="<?php echo $item['price']; ?>" required>

      <label>Jumlah:</label>
      <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" required>

      <label>Gambar Lama:</label>
      <img src="uploads/<?php echo htmlspecialchars($item['image']); ?>" class="preview">

      <label>Ganti Gambar:</label>
      <input type="file" name="image" accept="image/*">

      <button type="submit">Update</button>
    </form>
  </div>
</body>
</html>