<?php
include "config.php";
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = array();
    
    switch($_POST['action']) {
        case 'add':
            if (isset($_FILES['image'])) {
                $name = $_POST["name"];
                $category_id = intval($_POST["category_id"]);
                $room_id = intval($_POST["room_id"]);
                $quantity = intval($_POST["quantity"]);
                $item_condition = $_POST["item_condition"] ?? '';
                $purchase_date = $_POST["purchase_date"];
                $price = floatval($_POST["price"]);
                
                $image_name = basename($_FILES["image"]["name"]);
                $target_dir = "uploads/";
                $image_path = $target_dir . $image_name;
                
                if (move_uploaded_file($_FILES["image"]["tmp_name"], $image_path)) {
                    $success_count = 0;
                    for ($i = 1; $i <= $quantity; $i++) {
                        $item_code = generateItemCode($conn, $name, $category_id);
                        $stmt = $conn->prepare("INSERT INTO items (name, category_id, item_condition, purchase_date, price, image, room_id, quantity, total, status, item_code) VALUES (?, ?, ?, ?, ?, ?, ?, 1, 1, 'bisadipinjam', ?)");
                        $stmt->bind_param("sissdsss", $name, $category_id, $item_condition, $purchase_date, $price, $image_name, $room_id, $item_code);
                        if ($stmt->execute()) {
                            $last_id = $stmt->insert_id;
                            $qr_file = "barcodes/qr_$last_id.png";
                            QRcode::png("ID: $last_id\nKode: $item_code\nNama: $name\nKategori: $category_id", $qr_file, QR_ECLEVEL_L, 4, 2);
                            $conn->query("UPDATE items SET barcode = '$qr_file' WHERE id = $last_id");
                            $success_count++;
                        }
                        $stmt->close();
                    }
                    $response['success'] = true;
                    $response['message'] = "Berhasil menambahkan $success_count barang";
                } else {
                    $response['success'] = false;
                    $response['message'] = "Gagal mengunggah gambar";
                }
            }
            break;
            
        case 'delete':
            if (isset($_POST['item_id'])) {
                $delete_id = intval($_POST['item_id']);
                $stmt = $conn->prepare("DELETE FROM items WHERE id = ?");
                $stmt->bind_param("i", $delete_id);
                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = "Barang berhasil dihapus";
                } else {
                    $response['success'] = false;
                    $response['message'] = "Gagal menghapus barang";
                }
                $stmt->close();
            }
            break;
            
        case 'get_items':
            $search = isset($_POST['search']) ? $conn->real_escape_string($_POST['search']) : "";
            $sql_items = "
                SELECT items.*, categories.name AS category_name, rooms.name AS room_name 
                FROM items 
                JOIN categories ON items.category_id = categories.id 
                JOIN rooms ON items.room_id = rooms.id
                WHERE items.id NOT IN (SELECT item_id FROM maintenance WHERE status = 'dalam_perbaikan')
            ";
            if ($search !== "") {
                $sql_items .= " AND (items.name LIKE '%$search%' OR items.item_code LIKE '%$search%')";
            }
            $result = $conn->query($sql_items);
            $items = array();
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
            }
            $response['success'] = true;
            $response['data'] = $items;
            break;
    }
    
    echo json_encode($response);
} else {
    http_response_code(405);
    echo json_encode(array('success' => false, 'message' => 'Method not allowed'));
}

function generateItemCode($conn, $name, $category_id) {
    $nameCode = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $name), 0, 3));
    $date = date('ymd');
    $random = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'), 0, 5);
    $itemCode = $nameCode . '-' . sprintf('%02d', $category_id) . '-' . $date . '-' . $random;
    
    $stmt = $conn->prepare("SELECT id FROM items WHERE item_code = ?");
    $stmt->bind_param("s", $itemCode);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        return generateItemCode($conn, $name, $category_id);
    }
    $stmt->close();
    
    return $itemCode;
}
?> 