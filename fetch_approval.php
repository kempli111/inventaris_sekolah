<?php
include "config.php";
session_start();

// Debug information
error_reporting(E_ALL);
ini_set('display_errors', 1);



// Query untuk mengambil data peminjaman yang perlu disetujui
$query = "SELECT p.id, u.username as nama_user, i.name as nama_barang, p.jumlah, p.tanggal_pinjam, p.status 
          FROM peminjaman p 
          JOIN users u ON p.user_id = u.id 
          JOIN items i ON p.item_id = i.id 
          WHERE p.status = 'diajukan'
          ORDER BY p.tanggal_pinjam DESC";

try {
    $result = $conn->query($query);

    if ($result === false) {
        throw new Exception("Error executing query: " . $conn->error);
    }

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['nama_user']) . "</td>";
            echo "<td>" . htmlspecialchars($row['nama_barang']) . "</td>";
            echo "<td>" . htmlspecialchars($row['jumlah']) . "</td>";
            echo "<td>" . htmlspecialchars($row['tanggal_pinjam']) . "</td>";
            echo "<td>
                    <button onclick='updateStatus(" . $row['id'] . ", \"dipinjam\")' class='btn btn-success btn-sm'>Setujui</button>
                    <button onclick='updateStatus(" . $row['id'] . ", \"ditolak\")' class='btn btn-danger btn-sm'>Tolak</button>
                  </td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='5' class='text-center'>Tidak ada peminjaman yang perlu disetujui</td></tr>";
    }
} catch (Exception $e) {
    echo "<tr><td colspan='5' class='text-center text-danger'>Error: " . $e->getMessage() . "</td></tr>";
}
?>
