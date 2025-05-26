<?php
session_start();
include "config.php";

$message = "";

// Proses form perbaikan
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_maintenance'])) {
        $item_id = intval($_POST['item_id']);
        $maintenance_date = $_POST['maintenance_date'];
        $description = $_POST['description'];
        $cost = floatval($_POST['cost']);
        $technician = $_POST['technician'];

        // Update status barang menjadi tidak bisa dipinjam
        $stmt = $conn->prepare("UPDATE items SET status = 'tidakbisadipinjam' WHERE id = ?");
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $stmt->close();

        // Tambah data maintenance
        $stmt = $conn->prepare("INSERT INTO maintenance (item_id, maintenance_date, description, cost, technician) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issds", $item_id, $maintenance_date, $description, $cost, $technician);
        
        if ($stmt->execute()) {
            $message = "<p class='message success'>Data perbaikan berhasil ditambahkan.</p>";
        } else {
            $message = "<p class='message error'>Gagal menambahkan data perbaikan.</p>";
        }
        $stmt->close();
    }

    // Proses selesai perbaikan
    if (isset($_POST['complete_maintenance'])) {
        $maintenance_id = intval($_POST['maintenance_id']);
        $item_id = intval($_POST['item_id']);

        // Update status maintenance
        $stmt = $conn->prepare("UPDATE maintenance SET status = 'selesai' WHERE id = ?");
        $stmt->bind_param("i", $maintenance_id);
        $stmt->execute();
        $stmt->close();

        // Update status perbaikan item
        $stmt = $conn->prepare("UPDATE items SET repair_status = 'diperbaiki' WHERE id = ?");
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $stmt->close();

        $message = "<p class='message success'>Perbaikan selesai.</p>";
    }
}

// Ambil daftar barang yang bisa diperbaiki (rusak ringan atau rusak berat)
$sql_items = "SELECT * FROM items WHERE (item_condition = 'rusak ringan' OR item_condition = 'rusak berat') AND repair_status = 'tidak_diperbaiki'";
$result_items = $conn->query($sql_items);

// Ambil daftar maintenance yang sedang berlangsung
$sql_maintenance = "
    SELECT m.*, i.name as item_name, i.item_condition 
    FROM maintenance m 
    JOIN items i ON m.item_id = i.id 
    WHERE m.status = 'dalam_perbaikan'
";
$result_maintenance = $conn->query($sql_maintenance);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Perbaikan Barang</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .container {
            margin: 20px auto;
            max-width: 1200px;
            padding: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .maintenance-list {
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Manajemen Perbaikan Barang</h1>
        <?php echo $message; ?>

        <h2>Tambah Perbaikan Baru</h2>
        <form method="post">
            <div class="form-group">
                <label>Pilih Barang:</label>
                <select name="item_id" required>
                    <option value="">Pilih Barang</option>
                    <?php while ($row = $result_items->fetch_assoc()) { ?>
                        <option value="<?php echo $row['id']; ?>">
                            <?php echo $row['name'] . " - " . $row['item_condition']; ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
            <div class="form-group">
                <label>Tanggal Perbaikan:</label>
                <input type="date" name="maintenance_date" required>
            </div>
            <div class="form-group">
                <label>Deskripsi Perbaikan:</label>
                <textarea name="description" required></textarea>
            </div>
            <div class="form-group">
                <label>Biaya:</label>
                <input type="number" name="cost" step="0.01" required>
            </div>
            <div class="form-group">
                <label>Teknisi:</label>
                <input type="text" name="technician" required>
            </div>
            <button type="submit" name="add_maintenance">Tambah Perbaikan</button>
        </form>

        <div class="maintenance-list">
            <h2>Daftar Perbaikan Berlangsung</h2>
            <table>
                <tr>
                    <th>Barang</th>
                    <th>Kondisi</th>
                    <th>Tanggal Perbaikan</th>
                    <th>Deskripsi</th>
                    <th>Biaya</th>
                    <th>Teknisi</th>
                    <th>Aksi</th>
                </tr>
                <?php while ($row = $result_maintenance->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo $row['item_name']; ?></td>
                        <td><?php echo $row['item_condition']; ?></td>
                        <td><?php echo $row['maintenance_date']; ?></td>
                        <td><?php echo $row['description']; ?></td>
                        <td><?php echo number_format($row['cost'], 2); ?></td>
                        <td><?php echo $row['technician']; ?></td>
                        <td>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="maintenance_id" value="<?php echo $row['id']; ?>">
                                <input type="hidden" name="item_id" value="<?php echo $row['item_id']; ?>">
                                <button type="submit" name="complete_maintenance">Selesai</button>
                            </form>
                        </td>
                    </tr>
                <?php } ?>
            </table>
        </div>
    </div>
</body>
</html> 