<?php
include "config.php";
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "Anda harus login untuk mengembalikan barang!";
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['peminjaman_id'])) {
    $peminjaman_id = $_POST['peminjaman_id'];
    $tanggal_kembali = date("Y-m-d");

    // Ambil informasi peminjaman
    $stmt = $conn->prepare("SELECT item_id, jumlah FROM peminjaman WHERE id = ? AND status = 'dipinjam'");
    $stmt->bind_param("i", $peminjaman_id);
    $stmt->execute();
    $stmt->bind_result($item_id, $jumlah);
    $stmt->fetch();
    $stmt->close();

    if (!$item_id) {
        echo "Peminjaman tidak ditemukan atau sudah dikembalikan!";
        exit();
    }

    // Update status peminjaman ke "dikembalikan"
    $conn->begin_transaction();
    try {
        $update = $conn->prepare("UPDATE peminjaman SET status = 'dikembalikan', tanggal_kembali = ? WHERE id = ?");
        $update->bind_param("si", $tanggal_kembali, $peminjaman_id);
        if (!$update->execute()) {
            throw new Exception("Gagal memperbarui status peminjaman.");
        }
        $update->close();

        // Tambah jumlah barang di tabel `items`
        $restore = $conn->prepare("UPDATE items SET quantity = quantity + ? WHERE id = ?");
        $restore->bind_param("ii", $jumlah, $item_id);
        if (!$restore->execute()) {
            throw new Exception("Gagal memperbarui jumlah barang.");
        }
        $restore->close();

        $conn->commit();
        echo "success"; // Respon sukses ke AJAX
    } catch (Exception $e) {
        $conn->rollback();
        echo "Error: " . $e->getMessage();
    }
}
?>
