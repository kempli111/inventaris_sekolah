<?php
include "config.php";
session_start();

// Pastikan user sudah login dan ambil user_id untuk logging
if (!isset($_SESSION['user_id'])) {
    // Handle unauthorized access, maybe return an error JSON
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'User not logged in.']);
    exit();
}
$current_user_id = $_SESSION['user_id'] ?? null;


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = intval($_POST['id']); // Gunakan intval untuk keamanan
    $status = $_POST['status']; // 'dipinjam', 'ditolak', 'dikembalikan'
    $tanggal_kembali = isset($_POST['tanggal_kembali']) ? $_POST['tanggal_kembali'] : null;
    $success = true;

    // Mulai transaksi
    $conn->begin_transaction();

    try {
        // --- Logging: Ambil data peminjaman sebelum diupdate ---
        $stmt_old_peminjaman = $conn->prepare("SELECT * FROM peminjaman WHERE id = ?");
        $stmt_old_peminjaman->bind_param("i", $id);
        $stmt_old_peminjaman->execute();
        $old_peminjaman_data = $stmt_old_peminjaman->get_result()->fetch_assoc();
        $stmt_old_peminjaman->close();
        // --- Akhir Logging ---

        // Update status peminjaman
        $stmt = $conn->prepare("UPDATE peminjaman SET status = ?, tanggal_kembali = ? WHERE id = ?");
        // Bind parameter, handle null tanggal_kembali jika perlu
        $stmt->bind_param("ssi", $status, $tanggal_kembali, $id);

        if (!$stmt->execute()) {
            throw new Exception("Gagal mengupdate status peminjaman: " . $conn->error);
        }
        $stmt->close();

        // --- Logging: Ambil data peminjaman setelah diupdate ---
        $stmt_new_peminjaman = $conn->prepare("SELECT * FROM peminjaman WHERE id = ?");
        $stmt_new_peminjaman->bind_param("i", $id);
        $stmt_new_peminjaman->execute();
        $new_peminjaman_data = $stmt_new_peminjaman->get_result()->fetch_assoc();
        $stmt_new_peminjaman->close();
        // --- Akhir Logging ---

        // --- Logging: Catat aksi update status peminjaman ---
        $action_name = 'update_loan_status';
        if ($status === 'dipinjam') $action_name = 'approve_loan';
        else if ($status === 'ditolak') $action_name = 'reject_loan';
        else if ($status === 'dikembalikan') $action_name = 'confirm_return';

        log_action($conn, $current_user_id, $action_name, 'peminjaman', $id, $old_peminjaman_data, $new_peminjaman_data);
        // --- Akhir Logging ---


        // Jika status adalah 'ditolak' atau 'dikembalikan', kembalikan stok barang
        if ($status == 'ditolak' || $status == 'dikembalikan') {
            // Ambil informasi peminjaman (item_id dan jumlah)
             // Gunakan old_peminjaman_data yang sudah diambil
            $peminjaman = $old_peminjaman_data;

            if ($peminjaman && isset($peminjaman['item_id']) && isset($peminjaman['jumlah'])) {
                $item_id_to_update = $peminjaman['item_id'];
                $jumlah_dikembalikan = $peminjaman['jumlah'];

                // --- Logging: Ambil data item sebelum update quantity ---
                $stmt_old_item = $conn->prepare("SELECT id, quantity FROM items WHERE id = ?");
                $stmt_old_item->bind_param("i", $item_id_to_update);
                $stmt_old_item->execute();
                $old_item_data = $stmt_old_item->get_result()->fetch_assoc();
                $stmt_old_item->close();
                // --- Akhir Logging ---

                // Update stok barang
                $stmt_item = $conn->prepare("UPDATE items SET quantity = quantity + ? WHERE id = ?");
                $stmt_item->bind_param("ii", $jumlah_dikembalikan, $item_id_to_update);

                if (!$stmt_item->execute()) {
                    throw new Exception("Gagal mengupdate stok barang: " . $conn->error);
                }
                $stmt_item->close();

                 // --- Logging: Ambil data item setelah update quantity ---
                $stmt_new_item = $conn->prepare("SELECT id, quantity FROM items WHERE id = ?");
                $stmt_new_item->bind_param("i", $item_id_to_update);
                $stmt_new_item->execute();
                $new_item_data = $stmt_new_item->get_result()->fetch_assoc();
                $stmt_new_item->close();
                // --- Akhir Logging ---

                // --- Logging: Catat aksi update quantity item ---
                log_action($conn, $current_user_id, 'update_item_quantity_after_loan', 'items', $item_id_to_update, $old_item_data, $new_item_data);
                // --- Akhir Logging ---

            } else {
                // Log warning jika data peminjaman tidak lengkap?
            }
        }

        // Commit transaksi jika semua berhasil
        $conn->commit();
        echo "success";
    } catch (Exception $e) {
        // Rollback jika terjadi error
        $conn->rollback();
        // Log error exception?
        echo $e->getMessage();
    }
} else {
    echo "Invalid request method";
}

$conn->close();
?>
