<?php
include "config.php";

// Data untuk diagram garis (peminjaman per hari)
$query_line = "SELECT DATE(tanggal_pinjam) as tanggal, COUNT(*) as total 
               FROM peminjaman 
               GROUP BY DATE(tanggal_pinjam)
               ORDER BY tanggal ASC";
$result_line = $conn->query($query_line);

$tanggal = [];
$total_peminjaman = [];

while ($row = $result_line->fetch_assoc()) {
    $tanggal[] = $row['tanggal'];
    $total_peminjaman[] = $row['total'];
}

// Data untuk diagram batang dan pie (statistik peminjaman)
$query_bar_pie = "SELECT 
                    COUNT(CASE WHEN status = 'diajukan' THEN 1 END) AS diajukan,
                    COUNT(CASE WHEN status = 'dipinjam' THEN 1 END) AS dipinjam,
                    COUNT(CASE WHEN status = 'dikembalikan' THEN 1 END) AS dikembalikan,
                    COUNT(CASE WHEN status = 'ditolak' THEN 1 END) AS ditolak
                  FROM peminjaman";
$result_bar_pie = $conn->query($query_bar_pie);
$stats = $result_bar_pie->fetch_assoc();

// Konversi ke JSON
echo json_encode([
    "labels" => $tanggal,
    "data" => $total_peminjaman,
    "bar_labels" => ["Diajukan", "Dipinjam", "Dikembalikan", "Ditolak"],
    "bar_data" => [$stats['diajukan'], $stats['dipinjam'], $stats['dikembalikan'], $stats['ditolak']]
]);
?>
