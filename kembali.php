<?php
session_start();
include 'config.php'; // File koneksi database

if (!isset($_SESSION['user_id'])) {
    echo "Anda harus login untuk mengembalikan barang!";
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil daftar barang yang masih dipinjam oleh user
$query = "SELECT p.id, i.name AS nama_barang, p.jumlah, p.item_id, p.tanggal_kembali
          FROM peminjaman p 
          JOIN items i ON p.item_id = i.id 
          WHERE p.user_id = ? AND p.status = 'dipinjam'";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

// Proses pengembalian barang
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $peminjaman_id = $_POST['peminjaman_id'];
    $item_id = $_POST['item_id'];
    $jumlah_kembali = $_POST['jumlah'];
    $tanggal_kembali = date("Y-m-d H:i:s");

    // Perbarui status peminjaman menjadi "menunggu konfirmasi" dan catat tanggal kembali
    $updatePeminjaman = $conn->prepare("UPDATE peminjaman SET status = 'menunggu konfirmasi', tanggal_kembali = ? WHERE id = ? AND user_id = ? AND status = 'dipinjam'");
    $updatePeminjaman->bind_param("sii", $tanggal_kembali, $peminjaman_id, $user_id);
    
    if ($updatePeminjaman->execute()) {
        echo "<script>alert('Pengembalian berhasil, menunggu konfirmasi admin.'); window.location.href='kembali.php';</script>";
    } else {
        echo "<script>alert('Terjadi kesalahan, coba lagi.');</script>";
    }
    $updatePeminjaman->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengembalian Barang</title>
    <style>
                body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f9;
        }
        h2 {
            color: #469CED;
            text-align: center;
            margin: 20px 0;
            font-size: 24px;
        }

        /* Table Styles */
        table {
            width: 100vh;
            border-collapse: collapse;
            margin: 20px auto;
            background: white;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
        }

        table th, table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        table th {
            background: #469CED;
            color: white;
            text-transform: uppercase;
            font-size: 14px;
        }

        table tr:nth-child(even) {
            background: #f9f9f9;
        }

        table tr:hover {
            background: #f1f1f1;
        }

        /* Buttons */
        button {
            background-color: #469CED;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }

        button:hover {
            background-color: #3a86ca;
        }

        input[type="number"] {
            padding: 5px;
            width: 80px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 12px;
        }

        /* Mobile Specific Styles */
        @media screen and (max-width: 768px) {
            h2 {
                font-size: 20px;
                margin: 10px 0;
            }

            table {
                width: 100%;
                box-shadow: none;
                border-radius: 0;
            }

            table th, table td {
                font-size: 12px;
                padding: 10px;
            }

            table td {
                display: block;
                width: 100%;
                text-align: left;
                border: none;
                border-bottom: 1px solid #ddd;
            }

            table td:before {
                content: attr(data-label);
                display: block;
                font-weight: bold;
                color: #469CED;
                margin-bottom: 5px;
            }

            table tr {
                display: block;
                margin-bottom: 10px;
            }

            input[type="number"] {
                width: 100%;
            }

            button {
                width: 100%;
                font-size: 14px;
            }
        }

        /* Links */
        a {
            color: #469CED;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }








    </style>
</head>
<body>

<h2>Pengembalian Barang</h2>

<table>
    <tr>
        <th>Nama Barang</th>
        <th>Jumlah Dipinjam</th>
        <th>Tanggal Kembali</th>
        <th>Aksi</th>
    </tr>
    <?php while ($row = $result->fetch_assoc()) { ?>
        <tr>
            <td><?php echo htmlspecialchars($row['nama_barang']); ?></td>
            <td><?php echo htmlspecialchars($row['jumlah']); ?></td>
            <td><?php echo $row['tanggal_kembali'] ? htmlspecialchars($row['tanggal_kembali']) : '-'; ?></td>
            <td>
                <form method="POST">
                    <input type="hidden" name="peminjaman_id" value="<?php echo $row['id']; ?>">
                    <input type="hidden" name="item_id" value="<?php echo $row['item_id']; ?>">
                    <input type="hidden" name="jumlah" value="<?php echo $row['jumlah']; ?>">
                    <button type="submit" class="btn-kembali">Kembalikan</button>
                </form>
            </td>
        </tr>
    <?php } ?>
</table>

</body>
</html>
