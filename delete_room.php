<?php
include "config.php";
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'] ?? null; // Ambil user_id untuk logging

if (isset($_GET['id'])) {
    $room_id = intval($_GET['id']); // Gunakan intval untuk keamanan

    // --- Logging: Ambil data ruangan dan item terkait sebelum dihapus ---
    $old_room_data = null;
    $old_items_data = [];

    $stmt_old_room = $conn->prepare("SELECT * FROM rooms WHERE id = ?");
    $stmt_old_room->bind_param("i", $room_id);
    $stmt_old_room->execute();
    $old_room_data = $stmt_old_room->get_result()->fetch_assoc();
    $stmt_old_room->close();

    if ($old_room_data) { // Pastikan ruangan ada sebelum mencoba mengambil item
        $stmt_old_items = $conn->prepare("SELECT * FROM items WHERE room_id = ?");
        $stmt_old_items->bind_param("i", $room_id);
        $stmt_old_items->execute();
        $result_old_items = $stmt_old_items->get_result();
        while($row = $result_old_items->fetch_assoc()){
            $old_items_data[] = $row; // Simpan semua item terkait
        }
        $stmt_old_items->close();
    }
    // --- Akhir Logging ---

    $conn->begin_transaction();

    try {
        // --- Hapus catatan maintenance terkait item di ruangan ini terlebih dahulu ---
        if (!empty($old_items_data)) {
            $item_ids_in_room = implode(',', array_column($old_items_data, 'id'));
            $sql_delete_maintenance = "DELETE FROM maintenance WHERE item_id IN ($item_ids_in_room)";
             if ($conn->query($sql_delete_maintenance) === FALSE) {
                // Ini mungkin tidak critical error jika maintenance memang tidak ada
                // Tapi baiknya dilog atau dihandle
                // throw new Exception("Gagal menghapus catatan maintenance terkait: " . $conn->error);
             }
        }
        // --- Akhir penghapusan maintenance ---

        // Hapus item terkait di ruangan ini
        $sql_delete_items = "DELETE FROM items WHERE room_id = '$room_id'";
        if ($conn->query($sql_delete_items) === FALSE) {
            throw new Exception("Gagal menghapus item terkait: " . $conn->error);
        }

        // Hapus ruangan
        $sql_delete_room = "DELETE FROM rooms WHERE id = '$room_id'";
        if ($conn->query($sql_delete_room) === FALSE) {
            throw new Exception("Gagal menghapus ruangan: " . $conn->error);
        }

        $conn->commit();
        $message = "Ruangan beserta item terkait berhasil dihapus.";

        // --- Logging: Catat aksi hapus ruangan (termasuk item terkait di old_data) ---
        // Gabungkan data ruangan dan item terkait di old_data log
        $log_old_data = [
            'room' => $old_room_data,
            'items' => $old_items_data
        ];
        log_action($conn, $current_user_id, 'delete_room', 'rooms', $room_id, $log_old_data, null);
        // --- Akhir Logging ---

        echo $message;

    } catch (Exception $e) {
        $conn->rollback();
        $message = "Terjadi kesalahan: " . $e->getMessage();
        echo $message;
         // Log error exception?
    }
} else {
    $message = "ID ruangan tidak ditemukan.";
    echo $message;
}

echo '<br><a href="ruangan.php">Kembali ke Daftar Ruangan</a>'; // Link kembali ke halaman ruangan
?>
