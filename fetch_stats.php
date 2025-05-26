<?php
include "config.php";

$query_stats = "SELECT 
                COUNT(CASE WHEN status = 'diajukan' THEN 1 END) AS diajukan,
                COUNT(CASE WHEN status = 'dipinjam' THEN 1 END) AS dipinjam,
                COUNT(CASE WHEN status = 'dikembalikan' THEN 1 END) AS dikembalikan,
                COUNT(CASE WHEN status = 'ditolak' THEN 1 END) AS ditolak
                FROM peminjaman";
$result_stats = $conn->query($query_stats);
$stats = $result_stats->fetch_assoc() ?? ['diajukan' => 0, 'dipinjam' => 0, 'dikembalikan' => 0, 'ditolak' => 0];

echo json_encode($stats);
?>
