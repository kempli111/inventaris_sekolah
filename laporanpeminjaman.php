<?php
// laporan_csv.php
include "config.php";
session_start();

// Ambil parameter filter
$filter = isset($_GET['filter_type']) ? $_GET['filter_type'] : 'date';
$date   = isset($_GET['date']) ? $_GET['date'] : '';
$month  = isset($_GET['month']) ? $_GET['month'] : '';
$format = isset($_GET['format']) ? $_GET['format'] : 'csv';

// Fungsi untuk mengambil data riwayat peminjaman
function getData($conn, $filter, $date, $month) {
    $where = array();
    if ($filter === 'date' && $date !== '') {
        $d = $conn->real_escape_string($date);
        $where[] = "DATE(p.tanggal_pinjam) = '$d'";
    } elseif ($filter === 'month' && $month !== '') {
        list($y, $m) = explode('-', $month);
        $y = intval($y);
        $m = intval($m);
        $where[] = "YEAR(p.tanggal_pinjam) = $y AND MONTH(p.tanggal_pinjam) = $m";
    }
    $sql = "SELECT u.username AS peminjam,
                   i.item_code,
                   i.name AS nama_item,
                   c.name AS kategori,
                   r.name AS ruangan,
                   p.jumlah,
                   p.tanggal_pinjam,
                   p.tanggal_kembali
            FROM peminjaman p
            JOIN users u ON p.user_id = u.id
            JOIN items i ON p.item_id = i.id
            LEFT JOIN categories c ON i.category_id = c.id
            LEFT JOIN rooms r ON i.room_id = r.id";
    if (count($where) > 0) {
        $sql .= " WHERE " . implode(' AND ', $where);
    }
    $sql .= " ORDER BY p.tanggal_pinjam ASC";
    return $conn->query($sql);
}

// Jika download ditrigger
if (isset($_GET['download']) && $_GET['download'] === '1') {
    $result   = getData($conn, $filter, $date, $month);
    $filename = 'riwayat_peminjaman_' . date('Ymd_His');

    if ($format === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename='.$filename.'.csv');
        $out = fopen('php://output', 'w');

        // Header CSV
        fputcsv($out, array('No','Peminjam','Kode Item','Nama Item','Kategori','Ruangan','Jumlah','Tanggal Pinjam','Tanggal Kembali'));

        // Isi data
        $no = 1;
        while ($row = $result->fetch_assoc()) {
            fputcsv($out, array(
                $no++,
                $row['peminjam'],
                $row['item_code'],
                $row['nama_item'],
                $row['kategori'],
                $row['ruangan'],
                $row['jumlah'],
                $row['tanggal_pinjam'],
                $row['tanggal_kembali'] ?: '-'
            ));
        }
        fclose($out);
    } else {
        header("Content-Type: application/vnd.ms-word");
        header("Content-Disposition: attachment; filename=$filename.doc");
        echo "<html><meta charset='UTF-8'><body><h3>Laporan Riwayat Peminjaman</h3><table border='1' cellpadding='5'><tr><th>No</th><th>Peminjam</th><th>Kode Item</th><th>Nama Item</th><th>Kategori</th><th>Ruangan</th><th>Jumlah</th><th>Tanggal Pinjam</th><th>Tanggal Kembali</th></tr>";
        $no = 1;
        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>{$no}</td>
                    <td>{$row['peminjam']}</td>
                    <td>{$row['item_code']}</td>
                    <td>{$row['nama_item']}</td>
                    <td>{$row['kategori']}</td>
                    <td>{$row['ruangan']}</td>
                    <td>{$row['jumlah']}</td>
                    <td>{$row['tanggal_pinjam']}</td>
                    <td>" . ($row['tanggal_kembali'] ?: '-') . "</td>
                  </tr>";
            $no++;
        }
        echo "</table></body></html>";
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download Riwayat Peminjaman</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body class="p-4">
<div class="container">
    <h3 class="mb-4">Download Riwayat Peminjaman</h3>
    <form method="get" action="">
        <div class="form-row align-items-end">
            <div class="form-group col-md-4">
                <label for="filter_type">Filter Berdasarkan</label>
                <select id="filter_type" name="filter_type" class="form-control">
                    <option value="date"<?php if ($filter==='date') echo ' selected'; ?>>Per Tanggal</option>
                    <option value="month"<?php if ($filter==='month') echo ' selected'; ?>>Per Bulan</option>
                </select>
            </div>
            <div class="form-group col-md-4" id="date_group">
                <label for="date">Pilih Tanggal</label>
                <input type="date" id="date" name="date" class="form-control" value="<?php echo htmlspecialchars($date); ?>">
            </div>
            <div class="form-group col-md-4" id="month_group" style="display:none;">
                <label for="month">Pilih Bulan</label>
                <input type="month" id="month" name="month" class="form-control" value="<?php echo htmlspecialchars($month); ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-4">
                <label for="format">Format Dokumen</label>
                <select name="format" id="format" class="form-control">
                    <option value="csv"<?php if ($format==='csv') echo ' selected'; ?>>CSV</option>
                    <option value="doc"<?php if ($format==='doc') echo ' selected'; ?>>Dokumen Word</option>
                </select>
            </div>
        </div>
        <button type="submit" name="download" value="1" class="btn btn-primary">Download</button>
    </form>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var filterType = document.getElementById('filter_type');
        var dateGroup  = document.getElementById('date_group');
        var monthGroup = document.getElementById('month_group');
        function toggle() {
            if (filterType.value === 'date') {
                dateGroup.style.display = 'block';
                monthGroup.style.display = 'none';
            } else {
                dateGroup.style.display = 'none';
                monthGroup.style.display = 'block';
            }
        }
        filterType.addEventListener('change', toggle);
        toggle();
    });
</script>
</body>
</html>