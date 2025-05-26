<?php
session_start();
include "config.php";
include "phpqrcode/qrlib.php";

$theme = isset($_SESSION['theme']) ? $_SESSION['theme'] : 'light';
$message = "";

// Pastikan user_id ada di sesi untuk logging
$current_user_id = $_SESSION['user_id'] ?? null;

if (!empty($_FILES["image"]["name"])) {
    $ext = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
    $files = glob("uploads/*.*");
    $max_num = 0;
    foreach ($files as $file) {
        $basename = basename($file);
        if (preg_match('/^(\\d+)\\.[a-zA-Z0-9]+$/', $basename, $m)) {
            $num = intval($m[1]);
            if ($num > $max_num) $max_num = $num;
        }
    }
    $new_num = $max_num + 1;
    $image_name = $new_num . '.' . $ext;
    $target_dir = "uploads/";
    $image_path = $target_dir . $image_name;
    // ... proses penamaan dan upload gambar ...
}

// ... existing code ...
// Tambahkan handler perbaikan barang (AJAX)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Handler AJAX untuk perbaikan barang
    if (isset($_POST['get_data'])) {
        header('Content-Type: application/json');
        $data = [];
        // Data barang rusak
        $sql_broken = "
            SELECT i.*, c.name as category_name, r.name as room_name
            FROM items i 
            JOIN categories c ON i.category_id = c.id 
            JOIN rooms r ON i.room_id = r.id 
            WHERE i.item_condition IN ('rusak ringan', 'rusak berat') 
            AND (i.repair_status IS NULL OR i.repair_status NOT IN ('diperbaiki', 'dalam_perbaikan'))
            AND i.id NOT IN (SELECT item_id FROM maintenance WHERE status = 'dalam_perbaikan')";
        $result = $conn->query($sql_broken);
        $data['broken'] = $result->fetch_all(MYSQLI_ASSOC);

        // Data sedang diperbaiki
        $sql_repairing = "
            SELECT i.*, c.name as category_name, r.name as room_name,
                   m.id as maintenance_id, m.maintenance_date, m.description,
                   m.cost, m.technician, m.status as maintenance_status
            FROM items i 
            JOIN categories c ON i.category_id = c.id 
            JOIN rooms r ON i.room_id = r.id 
            JOIN maintenance m ON i.id = m.item_id
            WHERE m.status = 'dalam_perbaikan'";
        $result = $conn->query($sql_repairing);
        $data['repairing'] = $result->fetch_all(MYSQLI_ASSOC);

        // Data history perbaikan dengan paginasi
        $history_page = isset($_POST['history_page']) ? max(1, intval($_POST['history_page'])) : 1;
        $history_per_page = 5;
        $offset = ($history_page-1)*$history_per_page;
        $sql_history_count = "SELECT COUNT(*) as total FROM maintenance m WHERE m.status = 'selesai'";
        $result = $conn->query($sql_history_count);
        $total_history = $result->fetch_assoc()['total'];
        $total_history_pages = ceil($total_history/$history_per_page);
        $sql_history = "
            SELECT i.name, i.item_condition, c.name as category_name, r.name as room_name,
                   m.maintenance_date, m.description, m.cost, m.technician, m.status
            FROM maintenance m
            JOIN items i ON m.item_id = i.id
            JOIN categories c ON i.category_id = c.id
            JOIN rooms r ON i.room_id = r.id
            WHERE m.status = 'selesai'
            ORDER BY m.maintenance_date DESC
            LIMIT $offset, $history_per_page
        ";
        $result = $conn->query($sql_history);
        $data['history'] = [
            'data' => $result->fetch_all(MYSQLI_ASSOC),
            'page' => $history_page,
            'total_pages' => $total_history_pages
        ];

        echo json_encode($data);
        exit;
    }
    if (isset($_POST['add_repair'])) {
        header('Content-Type: application/json');
        $response = ['status' => 'error', 'message' => ''];
        $item_id = intval($_POST['item_id']);
        $maintenance_date = $_POST['maintenance_date'];
        $description = $_POST['description'];
        $cost = floatval($_POST['cost']);
        $technician = $_POST['technician'];

        // --- Logging: Ambil data item sebelum status diubah untuk perbaikan ---
        $stmt_old_item = $conn->prepare("SELECT id, item_condition, repair_status, status FROM items WHERE id = ?");
        $stmt_old_item->bind_param("i", $item_id);
        $stmt_old_item->execute();
        $old_item_data = $stmt_old_item->get_result()->fetch_assoc();
        $stmt_old_item->close();
        // --- Akhir Logging ---

        // Update status barang menjadi dalam perbaikan
        $stmt = $conn->prepare("UPDATE items SET repair_status = 'dalam_perbaikan', status = 'tidakbisadipinjam' WHERE id = ?");
        $stmt->bind_param("i", $item_id);

        if ($stmt->execute()) {
             // --- Logging: Catat aksi update item status untuk perbaikan ---
            $new_item_data = ['repair_status' => 'dalam_perbaikan', 'status' => 'tidakbisadipinjam'];
            log_action($conn, $current_user_id, 'start_item_repair', 'items', $item_id, $old_item_data, $new_item_data);
            // --- Akhir Logging ---

            // Tambah data maintenance
            $stmt = $conn->prepare("INSERT INTO maintenance (item_id, maintenance_date, description, cost, technician, status) VALUES (?, ?, ?, ?, ?, 'dalam_perbaikan')");
            $stmt->bind_param("issds", $item_id, $maintenance_date, $description, $cost, $technician);
            if ($stmt->execute()) {
                $last_maintenance_id = $conn->insert_id; // Ambil ID maintenance yang baru ditambahkan
                $response['status'] = 'success';
                $response['message'] = 'Data perbaikan berhasil ditambahkan';

                // --- Logging: Catat aksi tambah data maintenance ---
                $new_maintenance_data = [
                    'id' => $last_maintenance_id,
                    'item_id' => $item_id,
                    'maintenance_date' => $maintenance_date,
                    'description' => $description,
                    'cost' => $cost,
                    'technician' => $technician,
                    'status' => 'dalam_perbaikan'
                ];
                log_action($conn, $current_user_id, 'add_maintenance_record', 'maintenance', $last_maintenance_id, null, $new_maintenance_data);
                // --- Akhir Logging ---

            } else {
                 $response['message'] = 'Gagal menambahkan data perbaikan.' . $conn->error;
                 // Log error jika insert maintenance gagal, dan pertimbangkan rollback update item?
            }
            $stmt->close(); // Close maintenance insert statement

        } else {
             $response['message'] = 'Gagal mengupdate status barang.' . $conn->error;
        }
        // $stmt->close(); // Close item update statement (sudah dilakukan di dalam if)

        echo json_encode($response);
        exit;
    }
    if (isset($_POST['complete_repair'])) {
        header('Content-Type: application/json');
        $response = ['status' => 'error', 'message' => ''];
        $item_id = intval($_POST['item_id']);
        $maintenance_id = intval($_POST['maintenance_id']);

        // --- Logging: Ambil data maintenance sebelum status diubah ---
        $stmt_old_maintenance = $conn->prepare("SELECT id, status FROM maintenance WHERE id = ?");
        $stmt_old_maintenance->bind_param("i", $maintenance_id);
        $stmt_old_maintenance->execute();
        $old_maintenance_data = $stmt_old_maintenance->get_result()->fetch_assoc();
        $stmt_old_maintenance->close();
        // --- Akhir Logging ---

        // Update status maintenance menjadi selesai
        $stmt = $conn->prepare("UPDATE maintenance SET status = 'selesai' WHERE id = ?");
        $stmt->bind_param("i", $maintenance_id);
        if ($stmt->execute()) {
             // --- Logging: Catat aksi update status maintenance ---
            $new_maintenance_data = ['status' => 'selesai'];
            log_action($conn, $current_user_id, 'complete_maintenance_record', 'maintenance', $maintenance_id, $old_maintenance_data, $new_maintenance_data);
            // --- Akhir Logging ---

            // --- Logging: Ambil data item sebelum status dan kondisi diubah ---
            $stmt_old_item = $conn->prepare("SELECT id, item_condition, repair_status, status FROM items WHERE id = ?");
            $stmt_old_item->bind_param("i", $item_id);
            $stmt_old_item->execute();
            $old_item_data = $stmt_old_item->get_result()->fetch_assoc();
            $stmt_old_item->close();
            // --- Akhir Logging ---

            // Update status item dan repair status
            $stmt_item = $conn->prepare("UPDATE items SET repair_status = 'diperbaiki', item_condition = 'baik', status = 'bisadipinjam' WHERE id = ?");
            $stmt_item->bind_param("i", $item_id);
            if ($stmt_item->execute()) {
                 // --- Logging: Catat aksi update item status setelah perbaikan ---
                $new_item_data = ['repair_status' => 'diperbaiki', 'item_condition' => 'baik', 'status' => 'bisadipinjam'];
                log_action($conn, $current_user_id, 'item_repair_completed', 'items', $item_id, $old_item_data, $new_item_data);
                // --- Akhir Logging ---

                $response['status'] = 'success';
                $response['message'] = 'Perbaikan selesai';
            } else {
                $response['status'] = 'error';
                $response['message'] = 'Gagal mengupdate status barang setelah perbaikan.' . $conn->error;
                // Log error jika update item gagal?
            }
            $stmt_item->close();
        } else {
            $response['status'] = 'error';
            $response['message'] = 'Gagal menyelesaikan perbaikan record maintenance.' . $conn->error;
        }
        $stmt->close(); // Close maintenance update statement

        echo json_encode($response);
        exit;
    }
    if (isset($_POST['update_status'])) {
        header('Content-Type: application/json');
        $item_id = intval($_POST['item_id']);
        $new_status = ($_POST['new_status'] === 'bisadipinjam') ? 'bisadipinjam' : 'tidakbisadipinjam';

        // --- Logging: Ambil status lama item sebelum diupdate ---
        $stmt_old = $conn->prepare("SELECT status FROM items WHERE id = ?");
        $stmt_old->bind_param("i", $item_id);
        $stmt_old->execute();
        $old_status_data = $stmt_old->get_result()->fetch_assoc();
        $stmt_old->close();
        // --- Akhir Logging ---

        $stmt = $conn->prepare("UPDATE items SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $item_id);
        if ($stmt->execute()) {
             // --- Logging: Catat aksi update status item ---
            $new_status_data = ['status' => $new_status];
            log_action($conn, $current_user_id, 'update_item_status', 'items', $item_id, $old_status_data, $new_status_data);
             // --- Akhir Logging ---
            echo json_encode(['success' => true, 'message' => 'Status berhasil diubah.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal mengubah status.' . $conn->error]);
             // Log error jika update status gagal?
        }
        $stmt->close();
        exit;
    }
}

if (isset($_POST['set_theme'])) {
    $_SESSION['theme'] = ($_POST['set_theme'] === 'dark') ? 'dark' : 'light';
    // Opsional: Log perubahan tema jika dianggap penting
    // log_action($conn, $_SESSION['user_id'], 'set_theme', 'users', $_SESSION['user_id'], ['theme' => $_SESSION['theme']=='dark'?'light':'dark'], ['theme' => $_SESSION['theme']]);
    exit;
}

function generateItemCode($name, $category_id) {
    global $conn;
    $nameCode = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $name), 0, 3));
    $date = date('ymd');
    $random = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'), 0, 5);
    $itemCode = $nameCode . '-' . sprintf('%02d', $category_id) . '-' . $date . '-' . $random;
    
    $stmt = $conn->prepare("SELECT id FROM items WHERE item_code = ?");
    $stmt->bind_param("s", $itemCode);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        return generateItemCode($name, $category_id);
    }
    $stmt->close();
    
    return $itemCode;
}

function generateQRCode($item_id, $item_code, $name, $category_name, $room_name, $condition) {
    // Format data untuk QR code
    $qr_data = json_encode([
        'id' => $item_id,
        'kode' => $item_code,
        'nama' => $name,
        'kategori' => $category_name,
        'ruangan' => $room_name,
        'kondisi' => $condition,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
    // Buat direktori jika belum ada
    if (!file_exists('barcodes')) {
        mkdir('barcodes', 0777, true);
    }
    
    // Generate nama file QR unik
    $qr_filename = 'barcodes/qr_' . $item_code . '_' . $item_id . '.png';
    
    // Generate QR code dengan ukuran yang lebih besar dan error correction yang lebih baik
    QRcode::png($qr_data, $qr_filename, QR_ECLEVEL_H, 10, 2);
    
    return $qr_filename;
}

// Hapus item individual
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);

    // --- Logging: Ambil data item sebelum dihapus ---
    $stmt_old_item = $conn->prepare("SELECT * FROM items WHERE id = ?");
    $stmt_old_item->bind_param("i", $delete_id);
    $stmt_old_item->execute();
    $old_item_data = $stmt_old_item->get_result()->fetch_assoc();
    $stmt_old_item->close();
    // --- Akhir Logging ---

    // --- Hapus catatan terkait di tabel maintenance terlebih dahulu ---
    $delete_maintenance_sql = "DELETE FROM maintenance WHERE item_id = $delete_id";
    $conn->query($delete_maintenance_sql);
    // --- Akhir penghapusan maintenance ---

    $stmt = $conn->prepare("DELETE FROM items WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        $message = "<p class='message success'>Item berhasil dihapus.</p>";
        // --- Logging: Catat aksi hapus item ---
        log_action($conn, $current_user_id, 'delete_item', 'items', $delete_id, $old_item_data, null);
        // --- Akhir Logging ---
    } else {
        $message = "<p class='message error'>Gagal menghapus item." . $conn->error . "</p>";
         // Log error jika hapus item gagal?
    }
    $stmt->close();
    // Redirect or refresh as needed
}

// Proses POST (Tambah Item Baru)
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['ajax_action']) && !isset($_POST['set_theme']) && !isset($_POST['get_data']) && !isset($_POST['add_repair']) && !isset($_POST['complete_repair']) && !isset($_POST['update_status'])) { // Filter out AJAX POSTs

    $name = $_POST["name"];
    $category_id = intval($_POST["category_id"]);
    $room_id = intval($_POST["room_id"]);
    $quantity = intval($_POST["quantity"]); // Jumlah total barang yang akan ditambahkan
    $item_condition = $_POST["item_condition"] ?? '';
    $purchase_date = $_POST["purchase_date"];
    $price = floatval($_POST["price"]);

    // Ambil nama kategori dan ruangan untuk QR Code dan log
    $category_name = '';
    $stmt_cat = $conn->prepare("SELECT name FROM categories WHERE id = ?");
    $stmt_cat->bind_param("i", $category_id);
    $stmt_cat->execute();
    $category_result = $stmt_cat->get_result();
    if($row_cat = $category_result->fetch_assoc()) $category_name = $row_cat['name'];
    $stmt_cat->close();

    $room_name = '';
    $stmt_room = $conn->prepare("SELECT name FROM rooms WHERE id = ?");
    $stmt_room->bind_param("i", $room_id);
    $stmt_room->execute();
    $room_result = $stmt_room->get_result();
    if($row_room = $room_result->fetch_assoc()) $room_name = $row_room['name'];
    $stmt_room->close();

    $image_name = null; // Default image name
    if (!empty($_FILES["image"]['name'])) {
        // ... existing image upload logic ...
        $ext = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        $files = glob("uploads/*.*");
        $max_num = 0;
        foreach ($files as $file) {
            $basename = basename($file);
            if (preg_match('/^(\\d+)\\.[a-zA-Z0-9]+$/', $basename, $m)) {
                $num = intval($m[1]);
                if ($num > $max_num) $max_num = $num;
            }
        }
        $new_num = $max_num + 1;
        $image_name = $new_num . '.' . $ext;
        $target_dir = "uploads/";
        $image_path = $target_dir . $image_name;

        if (!move_uploaded_file($_FILES["image"]["tmp_name"], $image_path)) {
             $message = "<p class='message error'>Gagal mengunggah gambar.</p>";
             // Mungkin log error upload gambar?
             $image_name = null; // Set to null if upload fails
        }
    }

    if ($image_name !== null) { // Hanya proses jika upload gambar berhasil atau tidak ada gambar yang diupload (jika field image tidak required)
         // Tambahkan barang sebanyak quantity
        $added_item_ids = []; // Untuk menyimpan ID barang yang berhasil ditambahkan
        for ($i = 1; $i <= $quantity; $i++) {
            $item_code = generateItemCode($name, $category_id); // Generate kode unik per item
            $stmt = $conn->prepare("INSERT INTO items (name, category_id, item_condition, purchase_date, price, image, room_id, quantity, total, status, item_code) VALUES (?, ?, ?, ?, ?, ?, ?, 1, 1, 'bisadipinjam', ?)"); // quantity dan total per baris adalah 1
            $stmt->bind_param("sissdsss", $name, $category_id, $item_condition, $purchase_date, $price, $image_name, $room_id, $item_code);

            if ($stmt->execute()) {
                $last_id = $conn->insert_id; // Ambil ID barang yang baru ditambahkan
                $added_item_ids[] = $last_id; // Simpan ID-nya

                // Generate QR code dengan informasi lengkap
                $qr_file = generateQRCode($last_id, $item_code, $name, $category_name, $room_name, $item_condition); // Gunakan nama kategori dan ruangan yang sudah diambil
                // Update path QR code di database
                $conn->query("UPDATE items SET barcode = '$qr_file' WHERE id = $last_id");

                 // --- Logging: Catat setiap item yang ditambahkan ---
                 // Ambil data lengkap item yang baru ditambahkan untuk new_data log
                 $stmt_new_item = $conn->prepare("SELECT * FROM items WHERE id = ?");
                 $stmt_new_item->bind_param("i", $last_id);
                 $stmt_new_item->execute();
                 $new_item_data = $stmt_new_item->get_result()->fetch_assoc();
                 $stmt_new_item->close();

                log_action($conn, $current_user_id, 'add_item', 'items', $last_id, null, $new_item_data);
                // --- Akhir Logging ---

            } else {
                 $message = "<p class='message error'>Gagal menambahkan item. " . $conn->error . "</p>";
                 // Log error jika insert item gagal?
                 break; // Hentikan loop jika ada yang gagal
            }
            $stmt->close();
        }

        if (!empty($added_item_ids)) {
             $message = "<p class='message success'>" . count($added_item_ids) . " Item berhasil ditambahkan dengan QR Code.</p>";
        } else if (empty($message)) { // Jika tidak ada item ditambahkan dan tidak ada pesan error upload gambar
             $message = "<p class='message error'>Gagal menambahkan item.</p>";
        }

    } // End if image_name !== null

}

// Ambil daftar barang termasuk status
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : "";
$per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
if ($per_page < 1) $per_page = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $per_page;
$sql_count = "
    SELECT COUNT(*) as total
    FROM items i
    JOIN categories c ON i.category_id = c.id 
    JOIN rooms r ON i.room_id = r.id
    WHERE i.id NOT IN (SELECT item_id FROM maintenance WHERE status = 'dalam_perbaikan')
";
if ($search !== "") {
    $sql_count .= " AND (i.name LIKE '%$search%' OR i.item_code LIKE '%$search%')";
}
$result_count = $conn->query($sql_count);
$total_items = $result_count->fetch_assoc()['total'];
$total_pages = ceil($total_items / $per_page);
$sql_items = "
    SELECT i.*, c.name AS category_name, r.name AS room_name,
           i.barcode AS qr_code_path
    FROM items i
    JOIN categories c ON i.category_id = c.id 
    JOIN rooms r ON i.room_id = r.id
    WHERE i.id NOT IN (SELECT item_id FROM maintenance WHERE status = 'dalam_perbaikan')
";
if ($search !== "") {
    $sql_items .= " AND (i.name LIKE '%$search%' OR i.item_code LIKE '%$search%')";
}
$sql_items .= " LIMIT $offset, $per_page";
$result_items = $conn->query($sql_items);
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Barang</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f6f8fa;
            color: #222;
            margin: 0;
            padding: 0;
        }
        body.dark {
            background-color: #121212;
            color: #e0e0e0;
        }
        .container, .card, .form-section, .iframe-section, .table-section, .table-container, .modal-content, .tab-content, .search-box {
            background: #fff;
            color: #222;
        }
        body.dark .container, body.dark .card, body.dark .form-section, body.dark .iframe-section, body.dark .table-section, body.dark .table-container, body.dark .modal-content, body.dark .tab-content, body.dark .search-box {
            background: #121212 !important;
            color: #e0e0e0 !important;
            box-shadow: 0 2px 8px rgba(52,152,219,0.08);
        }
        body.dark table {
            background: transparent;
            color: #e0e0e0;
        }
        body.dark table th, body.dark table td {
            background-color: transparent;
            border: 1px solid #fff;
            color: #fff;
        }
        body.dark table th {
            background-color: transparent;
            color: #fff;
        }
        body.dark .btn-repair, body.dark .btn-history, body.dark .btn, body.dark .edit-status-btn {
            background-color: #fff;
            color: #121212;
        }
        body.dark .btn-repair:hover, body.dark .btn-history:hover, body.dark .btn:hover, body.dark .edit-status-btn:hover {
            background-color: #e0e0e0;
            color: #121212;
        }
        body.dark .tab-button,
        body.dark .tab-button.active {
            color: #fff;
            background: transparent;
            border-bottom: 2px solid #fff;
        }
        body.dark .pagination a {
            background: transparent;
            color: #fff;
            border: 1px solid #fff;
        }
        body.dark .pagination a.active, body.dark .pagination a:hover {
            background: #fff;
            color: #121212;
        }
        h1, h2 {
            text-align: center;
            color: #3498db;
            margin-bottom: 20px;
        }
        .flex-row {
            display: flex;
            gap: 30px;
            margin-bottom: 30px;
        }
        .form-section, .iframe-section {
            flex: 1;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(52,152,219,0.08);
            padding: 24px 20px 20px 20px;
        }
        body.dark .form-section, body.dark .iframe-section {
            background: #23272b;
            color: #e0e0e0;
            box-shadow: 0 2px 8px rgba(52,152,219,0.08);
        }
        .form-section form input,
        .form-section form select,
        .form-section form button {
            width: 100%;
            margin-bottom: 14px;
            padding: 12px;
            border: 1px solid #3498db;
            border-radius: 5px;
            font-size: 16px;
        }


        
        .form-section form button {
            background-color: #3498db;
            color: #fff;
            font-weight: bold;
            border: none;
            transition: background 0.2s;
        }
        body.dark .form-section form button {
            background-color:rgb(255, 255, 255);
            color: #fff;
        }
        .form-section form button:hover {
            background-color: #217dbb;
        }
        .iframe-section {
            min-height: 420px;
            display: flex;
            flex-direction: column;
            align-items: stretch;
        }
        .iframe-section iframe {
            border: 1px solid #3498db;
            border-radius: 8px;
            width: 100%;
            height: 400px;
            background: #fff;
        }
        .message {
            margin: 10px 0;
            padding: 10px;
            border-radius: 4px;
        }
        .message.success {
            background-color: #4caf50;
            color: #fff;
        }
        .message.error {
            background-color: #f44336;
            color: #fff;
        }
        .search-form {
            margin-bottom: 18px;
        }
        .search-form input[type="text"] {
            width: 250px;
            display: inline-block;
            border: 1px solid #3498db;
            border-radius: 4px;
            padding: 8px 12px;
        }
        .search-form button {
            background: #3498db;
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 8px 18px;
            margin-left: 8px;
            font-size: 15px;
            cursor: pointer;
        }
        .search-form button:hover {
            background: #217dbb;
        }
        .table-section {
            margin-top: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            background: #fff;
        }
        body.dark table {
            background: #23272b;
            color: #e0e0e0;
        }
        table th, table td {
            border: 1px solid #3498db;
            padding: 10px;
            text-align: left;
        }
        body.dark table th, body.dark table td {
            background-color: transparent;
            border: 1px solidrgb(255, 255, 255);
        }
        table th {
            background-color: #3498db;
            color: #fff;
        }
        body.dark table th {
            background-color: #217dbb;
            color: #fff;
        }
        table tr:nth-child(even) {
            background-color: #f1f8fe;
        }
        body.dark table tr:nth-child(even) {
            background-color: #181c20;
        }
        .btn-repair, .btn-history, .btn, .edit-status-btn {
            background-color: #3498db;
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 7px 14px;
            margin: 2px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.2s;
        }
        body.dark .btn-repair, body.dark .btn-history, body.dark .btn, body.dark .edit-status-btn {
            background-color: #217dbb;
            color: #fff;
        }
        .btn-repair:hover, .btn-history:hover, .btn:hover, .edit-status-btn:hover {
            background-color: #217dbb;
        }
        .status-badge {
            padding: 7px 12px;
            border-radius: 4px;
            font-size: 0.95em;
            font-weight: 500;
            display: inline-block;
            margin-right: 10px;
        }
        .status-available {
            background-color: #4CAF50;
            color: #fff;
        }
        .status-unavailable {
            background-color: #ff9800;
            color: #fff;
        }
        body.dark .status-badge.status-available {
            background-color: #388e3c;
        }
        body.dark .status-badge.status-unavailable {
            background-color: #b26a00;
        }
        .pagination {
            margin-top: 20px;
            text-align: center;
        }
        .pagination a {
            margin: 0 5px;
            padding: 6px 12px;
            border: 1px solid #3498db;
            border-radius: 4px;
            text-decoration: none;
            color: #3498db;
            background: #fff;
            font-weight: 500;
        }
        body.dark .pagination a {
            background: #23272b;
            color: #3498db;
            border: 1px solid #217dbb;
        }
        .pagination a.active, .pagination a:hover {
            background: #3498db;
            color: #fff;
        }
        body.dark .pagination a.active, body.dark .pagination a:hover {
            background: #3498db;
            color: #fff;
        }
        @media (max-width: 900px) {
            .flex-row {
                flex-direction: column;
            }
            .form-section, .iframe-section {
                min-width: 0;
                width: 100%;
            }
        }
        .card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(52,152,219,0.08);
            padding: 20px;
            margin-bottom: 20px;
        }
        .tab-container {
            margin-bottom: 20px;
            text-align: center;
        }
        .tab-button {
            padding: 10px 24px;
            background-color: #fff;
            border: none;
            border-bottom: 2px solid transparent;
            cursor: pointer;
            font-size: 16px;
            color: #3498db;
            font-weight: bold;
            transition: all 0.3s;
            margin-right: 8px;
        }
        .tab-button.active {
            color: #217dbb;
            border-bottom: 2px solid #3498db;
            background: #f1f8fe;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .table-container {
            margin-bottom: 20px;
            overflow-x: auto;
        }
        .table-container table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
        }
        .table-container th, .table-container td {
            border: 1px solid #3498db;
            padding: 10px;
            text-align: left;
        }
        .table-container th {
            background-color: #3498db;
            color: #fff;
        }
        .table-container tr:nth-child(even) {
            background-color: #f1f8fe;
        }
        .search-box {
            margin-bottom: 10px;
        }
        .search-box input {
            width: 100%;
            padding: 8px;
            border: 1px solid #3498db;
            border-radius: 4px;
        }
        .btn-repair, .btn-complete {
            background-color: #3498db;
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 7px 14px;
            margin: 2px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.2s;
        }
        .btn-repair:hover, .btn-complete:hover {
            background-color: #217dbb;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.4);
        }
        .modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 24px 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 420px;
            color: #222;
            position: relative;
        }
        .close {
            color: #3498db;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s;
        }
        .close:hover {
            color: #217dbb;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #3498db;
            border-radius: 4px;
            transition: border-color 0.3s;
        }
        .form-group input:focus, .form-group textarea:focus {
            border-color: #217dbb;
            outline: none;
        }
        .message {
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            display: none;
        }
        .message.success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
            display: block;
        }
        .message.error {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
            display: block;
        }





        /* Mode Gelap */
body.dark {
  background-color: #121212;
  color: #e0e0e0;
}

/* Card dengan gradient dan bayangan */
body.dark .card {
  background: linear-gradient(
    135deg,
    rgba(255, 255, 255, 0.1),
    rgba(255, 255, 255, 0)
  );
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
}

/* Judul H1 dengan efek serupa */
body.dark h1 {
  background: linear-gradient(
    135deg,
    rgba(255, 255, 255, 0.1),
    rgba(255, 255, 255, 0)
  );
  box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

/* Semua tombol (btn, btn-repair, btn-history, dsb.) jadi biru cerah */
body.dark .btn,
body.dark .btn-repair,
body.dark .btn-history,
body.dark .btn-complete,
body.dark .edit-status-btn {
  background-color: #2196f3; /* biru cerah */
  color: #fff;
  border: none;
  transition: background 0.2s;
}

body.dark .btn:hover,
body.dark .btn-repair:hover,
body.dark .btn-history:hover,
body.dark .btn-complete:hover,
body.dark .edit-status-btn:hover {
  background-color: #1976d2; /* biru lebih gelap saat hover */
}

    </style>
</head>
<body class="<?php echo $theme; ?>">
<div class="container">
    <h1>Manajemen Inventaris Barang</h1>
    <?php echo $message; ?>
    <div class="flex-row">
        <div class="form-section">
            <h2>Tambah Barang</h2>
            <form method="post" enctype="multipart/form-data">
                <input type="text" name="name" placeholder="Nama Barang" required>
                <select name="category_id" required>
                    <option value="">Pilih Kategori</option>
                    <?php
                    $result_categories = $conn->query("SELECT * FROM categories");
                    while ($row_cat = $result_categories->fetch_assoc()) { ?>
                        <option value="<?php echo $row_cat['id']; ?>"><?php echo $row_cat['name']; ?></option>
                    <?php } ?>
                </select>
                <select name="room_id" required>
                    <option value="">Pilih Ruangan</option>
                    <?php
                    $result_rooms = $conn->query("SELECT * FROM rooms");
                    while ($row_room = $result_rooms->fetch_assoc()) { ?>
                        <option value="<?php echo $row_room['id']; ?>"><?php echo $row_room['name']; ?></option>
                    <?php } ?>
                </select>
                <select name="item_condition" required>
                    <option value="baik">Baik</option>
                    <option value="rusak ringan">Rusak Ringan</option>
                    <option value="rusak berat">Rusak Berat</option>
                </select>
                <input type="date" name="purchase_date" required>
                <input type="number" name="price" step="0.01" placeholder="Harga" required>
                <input type="number" name="quantity" placeholder="Jumlah Barang" required>
                <input type="file" name="image" required>
                <button type="submit">Submit</button>
            </form>
        </div>
        <div class="iframe-section">
            <h2>Perbaikan & Riwayat</h2>
            <div class="card">
                <div class="tab-container">
                    <button class="tab-button active" data-tab="broken">Barang Rusak</button>
                    <button class="tab-button" data-tab="repairing">Sedang Diperbaiki</button>
                    <button class="tab-button" data-tab="history">Riwayat Perbaikan</button>
                </div>
                <div id="message" class="message"></div>
                <!-- Tab Barang Rusak -->
                <div id="broken" class="tab-content active">
                    <div class="search-box">
                        <input type="text" id="search_broken" placeholder="Cari Barang Rusak...">
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nama Barang</th>
                                    <th>Kategori</th>
                                    <th>Ruangan</th>
                                    <th>Kondisi</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="broken-items"></tbody>
                        </table>
                    </div>
                </div>
                <!-- Tab Sedang Diperbaiki -->
                <div id="repairing" class="tab-content">
                    <div class="search-box">
                        <input type="text" id="search_repairing" placeholder="Cari Sedang Diperbaiki...">
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nama Barang</th>
                                    <th>Kategori</th>
                                    <th>Ruangan</th>
                                    <th>Tanggal Perbaikan</th>
                                    <th>Teknisi</th>
                                    <th>Biaya</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="repairing-items"></tbody>
                        </table>
                    </div>
                </div>
                <!-- Tab Riwayat Perbaikan -->
                <div id="history" class="tab-content">
                    <div class="search-box">
                        <input type="text" id="search_history" placeholder="Cari Riwayat Perbaikan...">
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nama Barang</th>
                                    <th>Kategori</th>
                                    <th>Ruangan</th>
                                    <th>Tanggal Perbaikan</th>
                                    <th>Deskripsi</th>
                                    <th>Teknisi</th>
                                    <th>Biaya</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="history-items"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- Modal Form Perbaikan -->
            <div id="repairModal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h2>Form Perbaikan Barang</h2>
                    <form id="repairForm">
                        <input type="hidden" id="item_id" name="item_id">
                        <div class="form-group">
                            <label>Nama Barang:</label>
                            <input type="text" id="item_name" readonly>
                        </div>
                        <div class="form-group">
                            <label>Tanggal Perbaikan:</label>
                            <input type="date" name="maintenance_date" required>
                        </div>
                        <div class="form-group">
                            <label>Deskripsi Perbaikan:</label>
                            <textarea name="description" required></textarea>
                        </div>
                        <div class="form-group">
                            <label>Biaya:</label>
                            <input type="number" name="cost" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label>Teknisi:</label>
                            <input type="text" name="technician" required>
                        </div>
                        <button type="submit" name="add_repair" class="btn btn-repair">Simpan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="table-section">
        <form class="search-form" method="get">
            <input type="text" name="search" placeholder="Cari barang..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit">Cari</button>
        </form>
        <form method="get" style="margin-bottom: 10px; display: inline-block;">
            <label for="per_page">Tampilkan</label>
            <select name="per_page" id="per_page" onchange="this.form.submit()">
                <option value="5" <?php if(isset($_GET['per_page']) && $_GET['per_page']==5) echo 'selected'; ?>>5</option>
                <option value="10" <?php if(!isset($_GET['per_page']) || $_GET['per_page']==10) echo 'selected'; ?>>10</option>
                <option value="25" <?php if(isset($_GET['per_page']) && $_GET['per_page']==25) echo 'selected'; ?>>25</option>
                <option value="100" <?php if(isset($_GET['per_page']) && $_GET['per_page']==100) echo 'selected'; ?>>100</option>
            </select>
            <span>data</span>
            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
        </form>
        <h2>Daftar Barang</h2>
        <table>
            <tr>
                <th>Kode Barang</th>
                <th>Nama</th>
                <th>Kategori</th>
                <th>Kondisi</th>
                <th>Tanggal Pembelian</th>
                <th>Harga</th>
                <th>Jumlah</th>
                <th>Ruangan</th>
                <th>Gambar</th>
                <th>QR Code</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
            <?php while ($row = $result_items->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo $row['item_code']; ?></td>
                    <td><?php echo $row['name']; ?></td>
                    <td><?php echo $row['category_name']; ?></td>
                    <td><?php echo $row['item_condition']; ?></td>
                    <td><?php echo $row['purchase_date']; ?></td>
                    <td><?php echo number_format($row['price'], 2); ?></td>
                    <td><?php echo $row['quantity']; ?></td>
                    <td><?php echo $row['room_name']; ?></td>
                    <td><img src="uploads/<?php echo $row['image']; ?>" width="100"></td>
                    <td>
                        <?php if ($row['barcode']) { ?>
                            <img src="<?php echo $row['barcode']; ?>" width="100">
                            <br>
                            <a href="<?php echo $row['barcode']; ?>" download class="btn btn-primary" style="font-size: 12px; padding: 5px 10px; margin-top: 5px;">
                                Download QR
                            </a>
                        <?php } ?>
                    </td>
                    <td>
                        <div class="status-container" data-item-id="<?php echo $row['id']; ?>">
                            <span class="status-badge <?php echo $row['status'] === 'bisadipinjam' ? 'status-available' : 'status-unavailable'; ?>">
                                <?php echo $row['status'] === 'bisadipinjam' ? 'Bisa Dipinjam' : 'Tidak Bisa Dipinjam'; ?>
                            </span>
                            <select class="status-select" style="display: none;">
                                <option value="bisadipinjam" <?php if ($row['status'] === 'bisadipinjam') echo 'selected'; ?>>Bisa Dipinjam</option>
                                <option value="tidakbisadipinjam" <?php if ($row['status'] === 'tidakbisadipinjam') echo 'selected'; ?>>Tidak Bisa Dipinjam</option>
                            </select>
                            <button class="edit-status-btn">Ubah</button>
                            <div class="loading-spinner"></div>
                        </div>
                    </td>
                    <td>
                        <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                            <?php if ($row['item_condition'] == 'rusak ringan' || $row['item_condition'] == 'rusak berat'): ?>
                                <?php if ($row['repair_status'] != 'selesai' && $row['repair_status'] != 'diperbaiki'): ?>
                                    <button class="btn btn-repair" onclick="showRepairForm(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['name'], ENT_QUOTES); ?>')">Perbaiki</button>
                                <?php endif; ?>
                            <?php endif; ?>
                            <a href="edit_item.php?id=<?php echo $row['id']; ?>" class="btn btn-history">Edit</a>
                            <a href="item_maintenance_history.php?item_id=<?php echo $row['id']; ?>" class="btn btn-history">Riwayat</a>
                            <a href="?delete_id=<?php echo $row['id']; ?>" class="btn btn-history" onclick="return confirm('Yakin ingin menghapus?');">Hapus</a>
                        </div>
                    </td>
                </tr>
            <?php } ?>
        </table>
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&per_page=<?php echo $per_page; ?>&search=<?php echo urlencode($search); ?>" class="<?php if($i==$page) echo 'active'; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    </div>
</div>

<script>
// Fungsi global agar bisa dipanggil dari HTML dan fungsi lain
function showRepairForm(itemId, itemName) {
    document.getElementById("item_id").value = itemId;
    document.getElementById("item_name").value = itemName;
    document.getElementById("repairModal").style.display = "block";
}

function showMessage(message, type) {
    const messageDiv = $('#message');
    messageDiv.removeClass('success error').addClass(type).text(message).fadeIn();
    setTimeout(() => messageDiv.fadeOut(), 3000);
}

function loadData() {
    $.post('item.php', { get_data: true }, function(data) {
        // Barang rusak
        let brokenHtml = '';
        data.broken.forEach(function(item) {
            brokenHtml += `
                <tr>
                    <td>${item.name}</td>
                    <td>${item.category_name}</td>
                    <td>${item.room_name}</td>
                    <td>${item.item_condition}</td>
                    <td>
                        <button class="btn-repair" onclick="showRepairForm(${item.id}, '${item.name.replace(/'/g, "\'")}')">
                            Perbaiki
                        </button>
                    </td>
                </tr>
            `;
        });
        $('#broken-items').html(brokenHtml);

        // Sedang diperbaiki
        let repairingHtml = '';
        data.repairing.forEach(function(item) {
            repairingHtml += `
                <tr>
                    <td>${item.name}</td>
                    <td>${item.category_name}</td>
                    <td>${item.room_name}</td>
                    <td>${item.maintenance_date}</td>
                    <td>${item.technician}</td>
                    <td>${new Intl.NumberFormat('id-ID').format(item.cost)}</td>
                    <td>
                        <button class="btn-complete" onclick="completeRepair(${item.id}, ${item.maintenance_id})">
                            Selesai
                        </button>
                    </td>
                </tr>
            `;
        });
        $('#repairing-items').html(repairingHtml);

        // History (Initial load handled below, but this function might be called elsewhere)
        // Letakkan logic history di dalam loadHistory function
    }, 'json');
}

function completeRepair(itemId, maintenanceId) {
    $.post('item.php', {
        complete_repair: true,
        item_id: itemId,
        maintenance_id: maintenanceId
    }, function(response) {
        if (response.status === 'success') {
            showMessage(response.message, 'success');
            loadData(); // Reload broken and repairing tabs
            // loadHistory(historyPage); // Mungkin perlu refresh history juga jika item pindah status
        } else {
            showMessage(response.message, 'error');
        }
    }, 'json');
}

// Fungsi untuk update status barang
function updateStatus(itemId, newStatus, container) {
    const spinner = container.querySelector('.loading-spinner');
    const statusBadge = container.querySelector('.status-badge');
    const select = container.querySelector('.status-select');
    const editButton = container.querySelector('.edit-status-btn');

    spinner.style.display = 'inline-block'; // Tampilkan spinner
    statusBadge.style.display = 'none'; // Sembunyikan badge saat proses
    select.style.display = 'none'; // Sembunyikan select saat proses
    editButton.textContent = 'Ubah'; // Kembalikan teks tombol ke "Ubah"

    fetch('item.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `update_status=1&item_id=${itemId}&new_status=${newStatus}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            statusBadge.textContent = newStatus === 'bisadipinjam' ? 'Bisa Dipinjam' : 'Tidak Bisa Dipinjam';
            statusBadge.className = `status-badge ${newStatus === 'bisadipinjam' ? 'status-available' : 'status-unavailable'}`;
            showMessage(data.message, 'success'); // Ganti showNotification
        } else {
            showMessage(data.message, 'error'); // Ganti showNotification
            // Jika gagal, kembalikan nilai select ke status sebelumnya
             select.value = statusBadge.textContent === 'Bisa Dipinjam' ? 'bisadipinjam' : 'tidakbisadipinjam';
        }
    })
    .catch(error => {
        console.error('Error updating status:', error);
        showMessage('Terjadi kesalahan saat mengubah status', 'error'); // Ganti showNotification
        // Jika error, kembalikan nilai select ke status sebelumnya
         select.value = statusBadge.textContent === 'Bisa Dipinjam' ? 'bisadipinjam' : 'tidakbisadipinjam';
    })
    .finally(() => {
        spinner.style.display = 'none'; // Sembunyikan spinner
        statusBadge.style.display = 'inline-block'; // Tampilkan kembali badge
        select.style.display = 'none'; // Pastikan select tersembunyi setelah selesai
    });
}

$(document).ready(function() {
    // Load awal data untuk tab perbaikan/riwayat
    loadData();
    // Load awal data untuk tab history secara terpisah dengan paginasi
    loadHistory(1);

    // Event tab switching
    $('.tab-button').click(function() {
        $('.tab-button').removeClass('active');
        $(this).addClass('active');
        const tabId = $(this).data('tab');
        $('.tab-content').removeClass('active');
        $(`#${tabId}`).addClass('active');

        // Muat ulang data sesuai tab yang aktif jika diperlukan
        if (tabId === 'history') {
            loadHistory(historyPage); // Muat history saat tab diklik
        } else {
            loadData(); // Muat data broken/repairing saat tab diklik
        }
    });

    // Form submission untuk perbaikan
    $('#repairForm').submit(function(e) {
        e.preventDefault();
        $.post('item.php', $(this).serialize() + '&add_repair=1', function(response) {
            if (response.status === 'success') {
                showMessage(response.message, 'success');
                $('#repairModal').css('display', 'none'); // Gunakan jQuery
                $('#repairForm')[0].reset();
                loadData(); // Muat ulang data broken dan repairing
            } else {
                showMessage(response.message, 'error');
            }
        }, 'json');
    });

    // Pencarian client-side untuk masing-masing menu
    $('#search_broken').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $("#broken-items tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });

    $('#search_repairing').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $("#repairing-items tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });

    $('#search_history').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $("#history-items tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });


    // Modal handling
    var modal = document.getElementById("repairModal");
    var span = document.getElementsByClassName("close")[0];

    span.onclick = function() {
        modal.style.display = "none";
    }
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

    // Event listener untuk tombol "Ubah" status
    // Menggunakan delegasi event karena tabel dimuat dinamis oleh PHP
    $(document).on('click', '.edit-status-btn', function() {
        const button = $(this);
        const container = button.closest('.status-container');
        const statusBadge = container.find('.status-badge');
        const select = container.find('.status-select');
        const itemId = container.data('itemId');

        if (button.text() === 'Ubah') {
            // Mode ubah: sembunyikan badge, tampilkan select, ubah teks tombol
            statusBadge.hide();
            select.show();
            button.text('Simpan');
            // Set nilai select sesuai status badge saat ini
            select.val(statusBadge.hasClass('status-available') ? 'bisadipinjam' : 'tidakbisadipinjam');

        } else {
            // Mode simpan: ambil nilai select, panggil updateStatus
            const newStatus = select.val();
            // Sembunyikan select, tampilkan badge kembali, ubah teks tombol kembali
            select.hide();
            statusBadge.show();
            button.text('Ubah'); // Kembali ke teks "Ubah" sebelum fetch
            updateStatus(itemId, newStatus, container[0]); // Panggil fungsi updateStatus, berikan elemen DOM container
        }
    });

    // Panggil updateStatus saat nilai select berubah (opsional, bisa juga hanya saat tombol simpan diklik)
    // $(document).on('change', '.status-select', function() {
    //     const select = $(this);
    //     const container = select.closest('.status-container');
    //     const itemId = container.data('itemId');
    //     const newStatus = select.val();
    //     // updateStatus(itemId, newStatus, container[0]);
    // });


    // PAGINASI RIWAYAT PERBAIKAN
    let historyPage = 1; // Keep track of current history page
    function loadHistory(page = 1) {
        $.post('item.php', { get_data: true, history_page: page }, function(data) {
            let historyHtml = '';
            data.history.data.forEach(function(item) {
                historyHtml += `
                    <tr>
                        <td>${item.name}</td>
                        <td>${item.category_name}</td>
                        <td>${item.room_name}</td>
                        <td>${item.maintenance_date}</td>
                        <td>${item.description}</td>
                        <td>${item.technician}</td>
                        <td>${new Intl.NumberFormat('id-ID').format(item.cost)}</td>
                        <td><span class="status-complete">Selesai</span></td>
                    </tr>
                `;
            });
            $('#history-items').html(historyHtml);

            // Tambahkan paginasi
            let paginHtml = '';
            // Pastikan elemen #history-pagination ada di HTML Anda
            const historyPaginationDiv = $('#history-pagination');
            if (historyPaginationDiv.length === 0) {
                 // Jika belum ada, tambahkan elemen div untuk paginasi riwayat
                $('<div id="history-pagination" class="pagination"></div>').insertAfter('#history .table-container');
            }

            for(let i=1; i<=data.history.total_pages; i++) {
                paginHtml += `<a href="#" class="history-page-link${i==data.history.page?' active':''}" data-page="${i}">${i}</a>`;
            }
            $('#history-pagination').html(paginHtml);

            historyPage = data.history.page; // Update current history page
        }, 'json');
    }
    // Event klik paginasi history
    $(document).on('click', '.history-page-link', function(e) {
        e.preventDefault();
        let page = $(this).data('page');
        loadHistory(page);
    });

    // Panggil loadHistory pertama kali saat dokumen siap
    // loadHistory(1); // Sudah dipanggil di atas

});
</script>
</body>
</html>
