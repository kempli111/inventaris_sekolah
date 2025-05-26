<?php
include "config.php";
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Jika request AJAX untuk fetch table data, kembalikan HTML baris tabel dan exit
if (isset($_GET['ajax_action']) && $_GET['ajax_action'] == 'fetch_items') {
    $room_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $q = "";
    if (isset($_GET['q'])) {
        $q = $conn->real_escape_string($_GET['q']);
    }
    $sql_items = "SELECT items.id, items.name AS item_name, items.item_code, items.item_condition, items.purchase_date, items.price,
                         items.quantity, categories.name AS category_name, items.image, items.status
                  FROM items
                  LEFT JOIN categories ON items.category_id = categories.id
                  WHERE items.room_id = '$room_id'";
    if ($q != "") {
        $sql_items .= " AND items.name LIKE '%$q%'";
    }
    $result_items = $conn->query($sql_items);
    $data = [];
    if ($result_items->num_rows > 0) {
        while ($row = $result_items->fetch_assoc()) {
            $data[] = [
                'id' => $row['id'],
                'item_name' => $row['item_name'],
                'item_code' => $row['item_code'],
                'category_name' => $row['category_name'],
                'item_condition' => $row['item_condition'],
                'purchase_date' => $row['purchase_date'],
                'price' => number_format($row['price'],2),
                'quantity' => $row['quantity'],
                'room_name' => htmlspecialchars($_GET['room_name'] ?? ''),
                'image' => $row['image'],
                'status' => $row['status']
            ];
        }
    }
    echo json_encode(['data' => $data]);
    exit;
}

// Proses update status via AJAX
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ajax_action']) && $_POST['ajax_action'] == 'update_status') {
    $item_id = intval($_POST['item_id']);
    $new_status = ($_POST['new_status'] === 'bisadipinjam') ? 'bisadipinjam' : 'tidakbisadipinjam';
    $update_sql = "UPDATE items SET status = '$new_status' WHERE id = $item_id";
    if ($conn->query($update_sql)) {
        echo json_encode(["status" => "success", "message" => "Status berhasil diperbarui."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Gagal mengubah status."]);
    }
    exit;
}

// Proses bulk pindah dan bulk hapus tetap menggunakan form tradisional
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['move_selected'])) {
        $room_id = $_POST['room_id'];
        $target_room = $_POST['target_room'];
        if (isset($_POST['selected_items']) && count($_POST['selected_items']) > 0 && !empty($target_room)) {
            foreach ($_POST['selected_items'] as $item_id) {
                $update_sql = "UPDATE items SET room_id = '$target_room' WHERE id = $item_id";
                $conn->query($update_sql);
            }
            echo "Item terpilih berhasil dipindahkan.";
        } else {
            echo "Tidak ada item yang dipilih atau ruangan tujuan belum dipilih.";
        }
    }
    if (isset($_POST['delete_selected'])) {
        $room_id = $_POST['room_id'];
        if (isset($_POST['selected_items']) && count($_POST['selected_items']) > 0) {
            foreach ($_POST['selected_items'] as $item_id) {
                $delete_sql = "DELETE FROM items WHERE id = $item_id";
                $conn->query($delete_sql);
            }
            echo "Item terpilih berhasil dihapus.";
        } else {
            echo "Tidak ada item yang dipilih untuk dihapus.";
        }
    }
} else {
    $room_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
}

$q = "";
if (isset($_GET['q'])) {
    $q = $conn->real_escape_string($_GET['q']);
}

// Ambil data ruangan
$sql_room = "SELECT name AS room_name, location FROM rooms WHERE id = '$room_id'";
$result_room = $conn->query($sql_room);
$room = $result_room->fetch_assoc();

// Ambil theme dari session
$theme = isset($_SESSION['theme']) ? $_SESSION['theme'] : 'light';

// Ambil data ruangan tujuan untuk bulk move
$sql_target_rooms = "SELECT id, name FROM rooms WHERE id != '$room_id'";
$result_target_rooms = $conn->query($sql_target_rooms);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Detail Ruangan</title>
  <link rel="stylesheet" href="style.css">
  <!-- Sertakan jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.10.24/css/jquery.dataTables.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.dataTables.min.css">
  <style>
     body {
      margin: 0; padding: 20px;
      font-family: Arial, sans-serif;
      transition: background 0.3s, color 0.3s;
    }
    .content {
      max-width: 1200px;
      margin: auto;
    }
    h1, h2 {
      margin-bottom: 10px;
    }
    a { text-decoration: none; }
    a:hover { text-decoration: underline; }

    /* ===== Theme Switch ===== */
    .theme-switch {
      display: flex; align-items: center;
      margin-bottom: 20px;
    }
    .theme-switch input {
      margin-right: 8px;
      width: 20px; height: 20px;
      cursor: pointer;
    }
    .theme-switch label {
      font-weight: bold;
    }

    /* ===== Mode Terang ===== */
    body.light {
      background: #f4f7f6;
      color: #333;
    }
    body.light .content {
      background: #fff;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }
    body.light h1, body.light h2 {
      color: #469ced;
    }
    body.light .ajax-message.success { background: #4caf50; color: #fff; }
    body.light .ajax-message.error   { background: #f44336; color: #fff; }

    /* DataTable terang */
    body.light table.dataTable {
      background: #fff; color: #333;
    }
    body.light table.dataTable thead th {
      background: #469ced; color: #fff;
      border-bottom: none;
    }
    body.light .download-btn, body.light button, body.light select {
      background: #469ced;
      color: #fff;
      border: none;
    }
    body.light .download-btn:hover, body.light button:hover {
      background: #357ab8;
    }
    body.light .search-form button {
      background: #469ced;
      color: #fff;
      border: none;
    }

    /* ===== Mode Gelap ===== */
    body.dark {
      background: #181818;
      color: #f1f1f1;
    }
    body.dark .content {
      background: #23272f;
      box-shadow: 0 2px 8px rgba(0,0,0,0.5);
    }
    body.dark .ajax-message.success { background: #388e3c; color: #fff; }
    body.dark .ajax-message.error   { background: #d32f2f; color: #fff; }

    /* DataTable gelap */
    body.dark table.dataTable {
      background: #2c2f33; color: #f1f1f1;
    }
    body.dark table.dataTable thead th {
      background: #2c2f33; color: #fff;
      border-bottom: 1px solid rgba(255,255,255,0.2);
    }
    body.dark table.dataTable tbody td {
      border-top: 1px solid rgba(255,255,255,0.1);
    }
    body.dark .dataTables_wrapper .dataTables_paginate .paginate_button {
      background: #2c2f33 !important; color: #fff !important;
      border: 1px solid #444 !important;
    }
    body.dark .dataTables_wrapper .dataTables_filter input,
    body.dark .dataTables_wrapper .dataTables_length select {
      background: #2c2f33; color: #fff;
      border: 1px solid #444;
    }

    /* ===== Table & Forms ===== */
    .search-form { margin-bottom: 20px; }
    .search-form input[type="text"] { padding: 6px; width: 200px; }
    .search-form button { padding: 6px 12px; }

    #itemsTable { width: 100%; margin-bottom: 20px; }
    #itemsTable img { border-radius: 4px; }

    form#bulkForm > div, form#bulkForm button {
      margin-right: 10px; margin-bottom: 10px;
    }
    select, button {
      padding: 6px 12px; border-radius: 4px;
      border: 1px solid #ccc;
      cursor: pointer;
      transition: background 0.3s, color 0.3s;
    }
    body.dark select, body.dark button {
      border: 1px solid #444;
    }
    button:hover {
      opacity: 0.9;
    }

    .download-btn {
      display: inline-block; margin: 5px;
      padding: 8px 16px;
      background: #469ced; color: #fff;
      border-radius: 4px;
    }
    .download-btn:hover { background: #357ab8; }

    .back-link { margin-top: 20px; }
    
  </style>
</head>
<body class="<?php echo $theme; ?>">
  <div class="content">
    <h1>Detail Ruangan: <?php echo $room['room_name']; ?></h1>
    <p>Lokasi: <?php echo $room['location']; ?></p>
    <form method="GET" action="detail_ruangan.php" class="search-form" id="searchForm">
      <input type="hidden" name="id" value="<?php echo $room_id; ?>">
      <input type="hidden" name="room_name" value="<?php echo $room['room_name']; ?>">
      <input type="text" name="q" placeholder="Cari item..." value="<?php echo htmlspecialchars($q); ?>">
      <button type="submit">Cari</button>
    </form>
    <h2>Daftar Item di Ruangan Ini</h2>
    <div id="ajaxMessage" class="ajax-message"></div>
    <form method="post" id="bulkForm">
      <input type="hidden" name="room_id" value="<?php echo $room_id; ?>">
      <table id="itemsTable" border="1" class="display" style="width:100%">
        <thead>
          <tr>
            <th><input type="checkbox" id="select_all"></th>
            <th>Nama Item</th>
            <th>Kode Barang</th>
            <th>Kategori</th>
            <th>Kondisi</th>
            <th>Tanggal Pembelian</th>
            <th>Harga</th>
            <th>Jumlah</th>
            <th>Ruangan</th>
            <th>Gambar</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody id="tableData">
          <!-- Data akan dimuat via AJAX/DataTables -->
        </tbody>
      </table>
      <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <label for="target_room">Pindahkan ke Ruangan:</label>
        <select name="target_room" id="target_room">
          <option value="">Pilih Ruangan</option>
          <?php while ($row = $result_target_rooms->fetch_assoc()): ?>
            <option value="<?php echo $row['id']; ?>"><?php echo $row['name']; ?></option>
          <?php endwhile; ?>
        </select>
        <button type="submit" name="move_selected">Pindahkan Item Terpilih</button>
        <button type="submit" name="delete_selected" onclick="return confirm('Apakah Anda yakin ingin menghapus item terpilih?');">Hapus Item Terpilih</button>
      </div>
    </form>
    <div style="text-align: center; margin-top:20px;">
      <a href="download_report.php?id=<?php echo $room_id; ?>&q=<?php echo urlencode($q); ?>" class="download-btn">Download Laporan</a>
      <a href="dqr.php?id=<?php echo $room_id; ?>&q=<?php echo urlencode($q); ?>" class="download-btn">Download QRcode</a>
    </div>
    <div class="back-link" style="text-align:center;">
      <a href="isi.php">Kembali ke View</a>
    </div>
  </div>
  <!-- DataTables JS -->
  <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
  <script>
    // Fungsi untuk load data tabel via AJAX
    function loadTableData() {
      var roomId = <?php echo $room_id; ?>;
      var query = $("input[name='q']").val();
      var roomName = $("input[name='room_name']").val();
      $('#itemsTable').DataTable({
        destroy: true,
        responsive: true,
        processing: true,
        ajax: {
          url: "detail_ruangan.php",
          type: "GET",
          data: {
            ajax_action: "fetch_items",
            id: roomId,
            q: query,
            room_name: roomName
          },
          dataSrc: 'data'
        },
        pageLength: 5,
        lengthMenu: [[5, 10, 25, -1], [5, 10, 25, 'Semua']],
        columns: [
          {
            data: 'id',
            render: function(data, type, row) {
              return `<input type='checkbox' name='selected_items[]' value='${data}'>`;
            },
            orderable: false,
            className: 'text-center'
          },
          { data: 'item_name' },
          { data: 'item_code', className: 'text-center' },
          { data: 'category_name' },
          { data: 'item_condition', className: 'text-center' },
          { data: 'purchase_date', className: 'text-center' },
          { data: 'price', className: 'text-right' },
          { data: 'quantity', className: 'text-center' },
          { data: 'room_name' },
          {
            data: 'image',
            render: function(src) {
              if (src) {
                return `<img src='uploads/${src}' width='60' height='60' style='object-fit:cover;border-radius:5px;'>`;
              }
              return '<span class="text-muted">Tidak ada</span>';
            },
            className: 'text-center'
          },
          {
            data: null,
            render: function(row) {
              return `<a href='edit_item.php?id=${row.id}'>Edit</a> | <a href='?delete_id=${row.id}' onclick=\"return confirm('Apakah Anda yakin ingin menghapus item ini?');\">Hapus</a>`;
            },
            orderable: false,
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
    }

    $(document).ready(function() {
      // Load tabel saat halaman dimuat
      loadTableData();

      // Jika search form disubmit, cegah reload halaman dan load data via AJAX
      $("#searchForm").submit(function(e) {
        e.preventDefault();
        loadTableData();
      });

      // Bulk select checkbox
      $("#select_all").on("change", function() {
        $("input[name='selected_items[]']").prop("checked", $(this).prop("checked"));
      });

      // AJAX untuk update status individual
      $(document).on("submit", ".update-status-form", function(e) {
        e.preventDefault();
        var form = $(this);
        var itemId = form.data("item-id");
        var newStatus = form.find("select[name='new_status']").val();
        $.ajax({
          url: "", // file saat ini
          type: "POST",
          dataType: "json",
          data: {
            ajax_action: "update_status",
            item_id: itemId,
            new_status: newStatus
          },
          success: function(response) {
            var messageDiv = $("#ajaxMessage");
            if (response.status === "success") {
              messageDiv.removeClass("error").addClass("success").text(response.message).fadeIn().delay(2000).fadeOut();
              // Reload data tabel setelah update
              loadTableData();
            } else {
              messageDiv.removeClass("success").addClass("error").text(response.message).fadeIn().delay(2000).fadeOut();
            }
          },
          error: function() {
            alert("Terjadi kesalahan saat mengupdate status.");
          }
        });
      });
    });
  </script>
</body>
</html>
