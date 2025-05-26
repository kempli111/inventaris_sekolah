<?php
// pinjam.php
include "config.php";
session_start();

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Query data barang
$stmt = $conn->prepare("
    SELECT id, name, quantity, status, image AS image_url, item_condition, item_code
    FROM items
    WHERE status != 'tidakbisadipinjam'
      AND quantity > 0
");
$stmt->execute();
$result = $stmt->get_result();
$items = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $selected = $_POST['selected_items'] ?? [];

    if (empty($selected)) {
        $message = "Pilih minimal satu barang untuk dipinjam!";
    } else {
        $tgl = date("Y-m-d");
        $ok = true;
        foreach ($selected as $item_id) {
            // cek stok
            $c = $conn->prepare("SELECT quantity FROM items WHERE id = ?");
            $c->bind_param("i", $item_id);
            $c->execute();
            $c->bind_result($qty);
            $c->fetch();
            $c->close();
            if ($qty < 1) { $ok = false; break; }

            // insert peminjaman
            $ins = $conn->prepare("
                INSERT INTO peminjaman (user_id, item_id, jumlah, tanggal_pinjam, status)
                VALUES (?, ?, 1, ?, 'diajukan')
            ");
            $ins->bind_param("iis", $user_id, $item_id, $tgl);
            if ($ins->execute()) {
                // update stok
                $up = $conn->prepare("UPDATE items SET quantity = quantity - 1 WHERE id = ?");
                $up->bind_param("i", $item_id);
                $up->execute();
                $up->close();
            } else {
                $ok = false;
            }
            $ins->close();
        }
        $message = $ok
            ? "Pengajuan peminjaman berhasil dikirim!"
            : "Terjadi kesalahan saat mengajukan peminjaman!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulir Peminjaman Barang</title>

    <!-- Bootstrap & DataTables CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap4.min.css">

    <style>
        /* ===== GLOBAL ===== */
        body {
            background: #f8f9fa;
            padding: 20px;
            font-family: 'Poppins', sans-serif;
        }
        .container { max-width: 100%; padding: 0; }

        /* ===== CARD ===== */
        .card {
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .card-header {
            background: #469ced;
            color: #fff;
            font-weight: 600;
            border-radius: 10px 10px 0 0;
        }
        .card-body { padding: 15px; }

        /* ===== TABLE ===== */
        .table td, .table th {
            vertical-align: middle;
            padding: 12px 8px;
        }
        img.item-img {
            width: 60px;
            height: auto;
            border-radius: 5px;
            object-fit: cover;
        }

        /* ===== BUTTON ===== */
        .btn-primary {
            background: #469ced;
            border: none;
            font-weight: 600;
        }
        .btn-primary:hover { background: #3182ce; }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 767px) {
            body { padding: 10px; }
            .table td, .table th { padding: 8px 4px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h3 class="text-center mb-4">Formulir Peminjaman Barang</h3>

        <?php if ($message): ?>
            <div class="alert alert-info"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header"><strong>Daftar Barang</strong></div>
            <div class="card-body">
                <form method="POST">
                    <div class="table-responsive">
                        <table id="itemsTable" class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th class="text-center">Pilih</th>
                                    <th class="text-center">Gambar</th>
                                    <th>Nama Barang</th>
                                    <th class="text-center">Kode</th>
                                    <th class="text-center">Kondisi</th>
                                    <th class="text-center">Stok</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- DataTables akan isi otomatis -->
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3 text-right">
                        <button type="submit" class="btn btn-primary">Ajukan Peminjaman</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- jQuery, Bootstrap, DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/responsive.bootstrap4.min.js"></script>

    <script>
    $(document).ready(function(){
        const data = <?php echo json_encode($items, JSON_HEX_TAG); ?>;

        $('#itemsTable').DataTable({
            data: data,
            rowId: 'id',
            deferRender: true,
            responsive: { details: false },
            columns: [
                {
                    data: null,
                    render: function(row) {
                        return `
                          <div class="form-check">
                            <input class="form-check-input" type="checkbox"
                                   id="item_${row.id}"
                                   name="selected_items[]"
                                   value="${row.id}">
                          </div>`;
                    },
                    orderable: false,
                    searchable: false,
                    className: 'text-center'
                },
                {
                    data: 'image_url',
                    render: function(src) {
                        if (src) {
                            return `<img src="uploads/${src}"
                                         onerror="this.src='images/placeholder.jpg'"
                                         class="item-img"/>`;
                        }
                        return '<span class="text-muted">Tidak ada</span>';
                    },
                    className: 'text-center'
                },
                { data: 'name' },
                { data: 'item_code', className: 'text-center' },
                {
                    data: 'item_condition',
                    render: function(cond) {
                        let cls = (cond === 'baik') ? 'success'
                                : (cond === 'rusak') ? 'danger'
                                : 'warning';
                        return `<span class="badge badge-${cls}">${cond}</span>`;
                    },
                    className: 'text-center'
                },
                {
                    data: 'quantity',
                    render: q => `<span class="badge badge-info">${q} unit</span>`,
                    className: 'text-center'
                },
                {
                    data: 'status',
                    render: () => `<span class="badge badge-success">Tersedia</span>`,
                    className: 'text-center'
                }
            ],
            language: {
                lengthMenu: "Tampilkan _MENU_ per halaman",
                zeroRecords: "Tidak ada data",
                info: "Halaman _PAGE_ dari _PAGES_",
                search: "Cari:",
                paginate: {
                  first: "Awal", last: "Akhir", next: ">", previous: "<"
                }
            },
            dom: '<"row"<"col-md-6"l><"col-md-6"f>>rtip'
        });
    });
    </script>
</body>
</html>
