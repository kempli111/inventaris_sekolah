<?php
session_start();
include "config.php";

$theme = isset($_SESSION['theme']) ? $_SESSION['theme'] : 'light';

if (!isset($_GET['item_id'])) {
    header('Location: item.php');
    exit;
}

$item_id = intval($_GET['item_id']);

// Ambil informasi item
$stmt = $conn->prepare("SELECT name FROM items WHERE id = ?");
$stmt->bind_param("i", $item_id);
$stmt->execute();
$result = $stmt->get_result();
$item = $result->fetch_assoc();

if (!$item) {
    header('Location: item.php');
    exit;
}

// Ambil riwayat pemeliharaan untuk item tertentu
$sql = "SELECT * FROM maintenance WHERE item_id = ? ORDER BY maintenance_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $item_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pemeliharaan - <?php echo htmlspecialchars($item['name']); ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f0f7ff;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(70, 156, 237, 0.1);
        }
        h1 {
            color: #469ced;
            text-align: center;
            margin-bottom: 30px;
            font-size: 24px;
        }
        .back-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #469ced;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-bottom: 20px;
            transition: background-color 0.3s;
        }
        .back-button:hover {
            background-color: #3b8cdb;
        }
        .maintenance-history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background-color: #fff;
            border-radius: 8px;
            overflow: hidden;
        }
        .maintenance-history-table th,
        .maintenance-history-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e1eeff;
        }
        .maintenance-history-table th {
            background-color: #469ced;
            color: white;
            font-weight: 500;
        }
        .maintenance-history-table tr:hover {
            background-color: #f8fbff;
        }
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
            display: inline-block;
        }
        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-error {
            background-color: #f8d7da;
            color: #721c24;
        }
        .cost-column {
            text-align: right;
            color: #469ced;
            font-weight: 500;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #469ced;
            font-size: 1.1em;
            background-color: #f8fbff;
            border-radius: 8px;
            margin: 20px 0;
            border: 2px dashed #469ced;
        }
        .date-column {
            color: #666;
            white-space: nowrap;
        }
        .technician-column {
            color: #469ced;
            font-weight: 500;
        }
        .description-column {
            max-width: 300px;
            color: #333;
        }
    </style>
</head>
<body class="<?php echo $theme; ?>">
    <div class="container">
        <a href="item.php" class="back-button">← Kembali</a>
        <h1>Riwayat Pemeliharaan: <?php echo htmlspecialchars($item['name']); ?></h1>

        <?php if ($result->num_rows > 0): ?>
            <table class="maintenance-history-table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Deskripsi</th>
                        <th>Teknisi</th>
                        <th>Biaya</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): 
                        $date = date_create($row['maintenance_date'])->format('d F Y');
                        
                        if (!empty($row['maintenance_error'])) {
                            $row['status'] = 'error';
                            $row['description'] .= " (Error: " . $row['maintenance_error'] . ")";
                        }

                        $statusClass = '';
                        $statusText = '';
                        switch(strtolower($row['status'])) {
                            case 'completed':
                                $statusClass = 'status-completed';
                                $statusText = 'Selesai';
                                break;
                            case 'pending':
                                $statusClass = 'status-pending';
                                $statusText = 'Menunggu';
                                break;
                            case 'error':
                                $statusClass = 'status-error';
                                $statusText = 'Error';
                                break;
                        }
                    ?>
                    <tr>
                        <td class="date-column"><?php echo $date; ?></td>
                        <td class="description-column"><?php echo htmlspecialchars($row['description']); ?></td>
                        <td class="technician-column"><?php echo htmlspecialchars($row['technician']); ?></td>
                        <td class="cost-column"><?php echo 'Rp ' . number_format($row['cost'], 0, ',', '.'); ?></td>
                        <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-data">
                Belum ada riwayat pemeliharaan untuk barang ini
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 