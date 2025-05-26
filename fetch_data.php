<?php
include "config.php";

session_start();

// Ambil statistik peminjaman berdasarkan status
$query_stats = "SELECT 
                COUNT(CASE WHEN status = 'diajukan' THEN 1 END) AS diajukan,
                COUNT(CASE WHEN status = 'dipinjam' THEN 1 END) AS dipinjam,
                COUNT(CASE WHEN status = 'dikembalikan' THEN 1 END) AS dikembalikan,
                COUNT(CASE WHEN status = 'ditolak' THEN 1 END) AS ditolak
                FROM peminjaman";
$result_stats = $conn->query($query_stats);
$stats = $result_stats->fetch_assoc();

// Ambil data persetujuan peminjaman
$query_persetujuan = "SELECT p.id, u.username, i.name AS nama_barang, p.jumlah, p.tanggal_pinjam
                      FROM peminjaman p
                      JOIN users u ON p.user_id = u.id
                      JOIN items i ON p.item_id = i.id
                      WHERE p.status = 'diajukan'";
$result_persetujuan = $conn->query($query_persetujuan);
$persetujuan = [];
while ($row = $result_persetujuan->fetch_assoc()) {
    $persetujuan[] = $row;
}

// Ambil data konfirmasi pengembalian
$query_kembali = "SELECT p.id, u.username, i.name AS nama_barang, p.jumlah
                  FROM peminjaman p
                  JOIN users u ON p.user_id = u.id
                  JOIN items i ON p.item_id = i.id
                  WHERE p.status = 'menunggu konfirmasi'";
$result_kembali = $conn->query($query_kembali);
$pengembalian = [];
while ($row = $result_kembali->fetch_assoc()) {
    $pengembalian[] = $row;
}

// Kirim data dalam format JSON
echo json_encode([
    "stats" => $stats,
    "persetujuan" => $persetujuan,
    "pengembalian" => $pengembalian
]);

?>
