<?php
include "config.php";

$query_persetujuan = "SELECT p.id, u.username, i.name AS nama_barang, p.jumlah, p.tanggal_pinjam
                      FROM peminjaman p
                      JOIN users u ON p.user_id = u.id
                      JOIN items i ON p.item_id = i.id
                      WHERE p.status = 'diajukan'";
$result_persetujuan = $conn->query($query_persetujuan);

while ($row = $result_persetujuan->fetch_assoc()) {
    echo "<tr id='row-".$row['id']."'>
            <td>".htmlspecialchars($row['username'])."</td>
            <td>".htmlspecialchars($row['nama_barang'])."</td>
            <td>".htmlspecialchars($row['jumlah'])."</td>
            <td>".htmlspecialchars($row['tanggal_pinjam'])."</td>
            <td>
                <select id='statusSelect-".$row['id']."'>
                    <option value='dipinjam'>Setujui</option>
                    <option value='ditolak'>Tolak</option>
                </select>
                <button onclick='updateStatus(".$row['id'].")'>Update</button>
            </td>
          </tr>";
}
?>
