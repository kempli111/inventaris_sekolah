<?php
include "config.php";
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "Anda harus login untuk melihat riwayat peminjaman!";
    exit();
}

$user_id = $_SESSION['user_id'];

$query = $conn->prepare("SELECT p.id, i.name, p.tanggal_pinjam, p.tanggal_kembali, p.status, p.jumlah 
                         FROM peminjaman p
                         JOIN items i ON p.item_id = i.id
                         WHERE p.user_id = ?
                         ORDER BY p.tanggal_pinjam DESC");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Peminjaman</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 15px;
            background-color: #f4f4f9;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .card-header {
            background: #469CED;
            color: white;
            padding: 15px 20px;
            border-radius: 10px 10px 0 0;
        }
        
        .card-body {
            padding: 20px;
        }
        
        table.dataTable {
            width: 100% !important;
            margin-bottom: 20px !important;
        }
        
        .dataTables_wrapper .dataTables_length select {
            padding: 5px 10px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        
        .dataTables_wrapper .dataTables_filter input {
            padding: 5px 10px;
            border-radius: 5px;
            border: 1px solid #ddd;
            margin-left: 10px;
        }
        
        .dataTables_wrapper .dataTables_info {
            padding-top: 15px;
        }
        
        .dataTables_wrapper .dataTables_paginate {
            padding-top: 15px;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 5px 10px;
            margin: 0 2px;
            border-radius: 5px;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #469CED !important;
            border-color: #469CED !important;
            color: white !important;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-diajukan { background: #ffd700; color: #000; }
        .status-dipinjam { background: #469CED; color: white; }
        .status-dikembalikan { background: #28a745; color: white; }
        .status-ditolak { background: #dc3545; color: white; }
        
        @media screen and (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .card-header {
                padding: 10px 15px;
            }
            
            .card-body {
                padding: 15px;
            }
            
            table.dataTable {
                font-size: 14px;
            }
            
            .dataTables_wrapper .dataTables_length,
            .dataTables_wrapper .dataTables_filter {
                text-align: left;
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Riwayat Peminjaman</h5>
        </div>
        <div class="card-body">
            <table id="riwayatTable" class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Barang</th>
                        <th>Jumlah</th>
                        <th>Tanggal Pinjam</th>
                        <th>Tanggal Kembali</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    while ($row = $result->fetch_assoc()): 
                    ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= htmlspecialchars($row['jumlah']) ?></td>
                        <td><?= date('d/m/Y', strtotime($row['tanggal_pinjam'])) ?></td>
                        <td><?= $row['tanggal_kembali'] ? date('d/m/Y', strtotime($row['tanggal_kembali'])) : '-' ?></td>
                        <td>
                            <span class="status-badge status-<?= strtolower($row['status']) ?>">
                                <?= ucfirst($row['status']) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#riwayatTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/id.json',
                    lengthMenu: "Tampilkan _MENU_ data per halaman",
                    zeroRecords: "Tidak ada data yang ditemukan",
                    info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                    infoEmpty: "Tidak ada data yang tersedia",
                    infoFiltered: "(difilter dari _MAX_ total data)",
                    search: "Cari:",
                    paginate: {
                        first: "Pertama",
                        last: "Terakhir",
                        next: "Selanjutnya",
                        previous: "Sebelumnya"
                    }
                },
                pageLength: 5,
                lengthMenu: [[5, 10, 25, -1], [5, 10, 25, "Semua"]],
                order: [[0, 'asc']],
                responsive: true
            });
        });
    </script>
</body>
</html>
