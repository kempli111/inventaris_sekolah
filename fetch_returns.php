<?php
include "config.php";

$query = "SELECT p.id, u.username, i.name AS nama_barang, p.jumlah, p.tanggal_kembali
          FROM peminjaman p
          JOIN users u ON p.user_id = u.id
          JOIN items i ON p.item_id = i.id
          WHERE p.status = 'menunggu konfirmasi'
          ORDER BY p.tanggal_pinjam DESC";
$result = $conn->query($query);

$output = "";
while ($row = $result->fetch_assoc()) {
    $output .= "<tr id='row-kembali-".$row['id']."'>";
    $output .= "<td>" . htmlspecialchars($row['username']) . "</td>";
    $output .= "<td>" . htmlspecialchars($row['nama_barang']) . "</td>";
    $output .= "<td>" . htmlspecialchars($row['jumlah']) . "</td>";
    $output .= "<td>" . ($row['tanggal_kembali'] ? htmlspecialchars($row['tanggal_kembali']) : '-') . "</td>";
    $output .= "<td>
                  <button onclick='updateStatus(".$row['id'].", \"dikembalikan\")'>Setujui</button>
                  <button onclick='updateStatus(".$row['id'].", \"dipinjam\")'>Tolak</button>
                </td>";
    $output .= "</tr>";
}
echo $output;
?>
