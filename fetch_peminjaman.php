<?php
include "config.php";
session_start();

$limit = 5; // Jumlah data per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Ambil total data untuk menghitung jumlah halaman
$totalQuery = $conn->query("SELECT COUNT(*) AS total FROM peminjaman");
$totalRow = $totalQuery->fetch_assoc();
$totalPages = ceil($totalRow['total'] / $limit);

// Ambil data dengan pagination
$query = $conn->prepare("SELECT p.id, i.name, p.tanggal_pinjam, p.tanggal_kembali, p.status 
                         FROM peminjaman p
                         JOIN items i ON p.item_id = i.id
                         ORDER BY p.tanggal_pinjam DESC
                         LIMIT ? OFFSET ?");
$query->bind_param("ii", $limit, $offset);
$query->execute();
$result = $query->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

$response = [
    "data" => $data,
    "totalPages" => $totalPages
];

header('Content-Type: application/json');
echo json_encode($response);
?>
