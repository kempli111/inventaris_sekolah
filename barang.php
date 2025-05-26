<?php
include "config.php";
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "Anda harus login untuk mengajukan peminjaman!";
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil daftar barang yang tersedia untuk dipinjam (dengan status bisadipinjam)
$query = "SELECT id, name, quantity FROM items WHERE quantity > 0 AND status = 'bisadipinjam'";
$result = $conn->query($query);

// Proses pengajuan peminjaman
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $item_id = $_POST['item_id'];
    $jumlah = (int) $_POST['jumlah']; // Jumlah yang dipinjam
    $tanggal_pinjam = date("Y-m-d");
    $status = "diajukan"; // Status awal peminjaman

    // Cek stok barang
    $check = $conn->prepare("SELECT quantity FROM items WHERE id = ?");
    $check->bind_param("i", $item_id);
    $check->execute();
    $check->bind_result($quantity);
    $check->fetch();
    $check->close();

    if ($jumlah <= 0 || $jumlah > $quantity) {
        echo "Jumlah barang yang diminta tidak tersedia!";
        exit();
    }

    // Kurangi stok barang setelah peminjaman berhasil
    $update = $conn->prepare("UPDATE items SET quantity = quantity - ? WHERE id = ?");
    $update->bind_param("ii", $jumlah, $item_id);
    $update->execute();
    $update->close();

    // Simpan peminjaman ke database
    $stmt = $conn->prepare("INSERT INTO peminjaman (user_id, item_id, jumlah, tanggal_pinjam, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iiiss", $user_id, $item_id, $jumlah, $tanggal_pinjam, $status);

    if ($stmt->execute()) {
        echo "Pengajuan peminjaman berhasil!";
        header("refresh:2; url=pinjam.php"); // Redirect setelah 2 detik
    } else {
        echo "Terjadi kesalahan saat mengajukan peminjaman.";
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulir Peminjaman</title>
    <style>
        /* Global Styles */
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
    <h2>Formulir Peminjaman Barang</h2>
    <table>
        <tr>
            <th>Nama Barang</th>
            <th>Stok Tersedia</th>
            <th>Pilih Jumlah</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()) { ?>
            <tr>
                <td data-label="Nama Barang"><?= htmlspecialchars($row['name']); ?></td>
                <td data-label="Stok Tersedia"><?= htmlspecialchars($row['quantity']); ?></td>
                <td data-label="Pilih Jumlah">
                    <form action="pinjam.php" method="POST">
                        <input type="hidden" name="item_id" value="<?= $row['id']; ?>">
                        <input type="number" name="jumlah" min="1" max="<?= $row['quantity']; ?>" required>
                        <button type="submit">Ajukan Peminjaman</button>
                    </form>
                </td>
            </tr>
        <?php } ?>
    </table>
</body>
</html>
