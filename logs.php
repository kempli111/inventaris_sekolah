<?php
include "config.php";
session_start();

// Pastikan user sudah login dan punya izin melihat log (misal: admin)
// Sesuaikan dengan logika otorisasi aplikasi Anda
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// --- Logika Hapus Log ---
if (isset($_POST['delete_log_id'])) {
    $delete_id = $_POST['delete_log_id'];
    $stmt_delete = $conn->prepare("DELETE FROM logs WHERE id = ?");
    $stmt_delete->bind_param("i", $delete_id);
    if ($stmt_delete->execute()) {
        // Redirect kembali ke halaman log setelah berhasil hapus
        header("Location: logs.php");
        exit();
    } else {
        echo "Error deleting log: " . $conn->error; // Tampilkan error jika gagal
    }
    $stmt_delete->close();
}

$theme = isset($_SESSION['theme']) ? $_SESSION['theme'] : 'light';

// Ambil data log dari database, join dengan tabel users untuk nama user
// Data diambil SEMUA karena DataTables akan handle pagination dan search di client-side
$sql_logs = "SELECT l.*, u.username FROM logs l LEFT JOIN users u ON l.user_id = u.id ORDER BY l.timestamp DESC";
$result_logs = $conn->query($sql_logs);

// Fungsi helper untuk format data JSON agar lebih mudah dibaca
function formatJsonData($json_data) {
    if ($json_data === null) return '- ';
    $data = json_decode($json_data, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return htmlspecialchars($json_data); // Return raw data if JSON is invalid
    }
    $output = '';
    foreach ($data as $key => $value) {
        $output .= "<strong>" . htmlspecialchars($key) . ":</strong> " . htmlspecialchars(print_r($value, true)) . "<br>";
    }
    return $output;
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Log Aktivitas Sistem</title>
  <link rel="stylesheet" href="style.css"> <!-- Sesuaikan dengan style Anda -->
  <!-- DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">

  <style>
     body {
      margin: 0; padding: 20px;
      font-family: Arial, sans-serif;
      transition: background 0.3s, color 0.3s;
    }
    .content {
      max-width: 1400px; /* Ubah max-width agar tabel lebih lebar */
      margin: auto;
    }
    h1 {
      text-align: center;
      margin-bottom: 20px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    th, td {
        border: 1px solid #ddd;
        padding: 10px;
        text-align: left;
        vertical-align: top; /* Align content to top */
    }
    th {
        background-color: #f2f2f2;
    }
    .action {
        font-weight: bold;
    }
     .old-data, .new-data {
        font-size: 0.9em;
        word-break: break-word; /* Wrap long words */
        white-space: pre-wrap; /* Preserve formatting and wrap text */
        max-height: 200px; /* Limit height */
        overflow-y: auto; /* Add scroll if content is too large */
    }
    .undo-btn,
    .delete-btn {
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 4px;
        cursor: pointer;
        margin-right: 5px; /* Jarak antar tombol */
    }
    .undo-btn { background-color: #ff9800; }
    .delete-btn { background-color: #dc3545; }

    .undo-btn:hover { background-color: #f57c00; }
    .delete-btn:hover { background-color: #c82333; }

    /* Dark Mode Styles (sesuaikan dengan style.css Anda) */
    body.dark { background-color: #121212; color: #e0e0e0; }
    body.dark .content { background: #23272b; box-shadow: 0 2px 8px rgba(0,0,0,0.5); } /* Sesuaikan jika menggunakan card */
    body.dark table.dataTable { background: #1e1e1e; color: #e0e0e0; } /* Gaya DataTables di dark mode */
    body.dark table.dataTable thead th { background-color: #333; color: #e0e0e0; } /* Header tabel */
    body.dark table.dataTable tbody td { border-color: #444; } /* Border sel tabel */
    body.dark table.dataTable.stripe tbody tr.odd, body.dark table.dataTable.display tbody tr.odd { background-color: #2a2a2a; } /* Warna stripe baris ganjil */
    body.dark table.dataTable.hover tbody tr:hover, body.dark table.dataTable.display tbody tr:hover { background-color: #3a3a3a; } /* Warna hover baris */
    body.dark .dataTables_wrapper .dataTables_length label, /* Label Show entries */
    body.dark .dataTables_wrapper .dataTables_filter label, /* Label Search */
    body.dark .dataTables_wrapper .dataTables_info, /* Info Showing x to y of z */
    body.dark .dataTables_wrapper .dataTables_paginate .paginate_button { /* Pagination buttons */
        color: #e0e0e0 !important;
    }
     body.dark .dataTables_wrapper .dataTables_paginate .paginate_button.disabled { opacity: 0.5; }
     body.dark .dataTables_wrapper .dataTables_paginate .paginate_button.current { 
        background: #469CED !important; /* Warna aktif pagination */
        border-color: #469CED !important;
        color: white !important;
    }
     body.dark input[type="search"] { /* Input search DataTables */
         background-color: #333; color: #e0e0e0; border: 1px solid #555;
     }
     body.dark select { /* Select Length Menu DataTables */
          background-color: #333; color: #e0e0e0; border: 1px solid #555;
     }
  </style>
</head>
<body class="<?php echo $theme; ?>">
  <div class="content">
    <h1>Log Aktivitas Sistem</h1>

    <table id="logTable" class="table table-striped table-bordered"> <!-- Tambahkan ID dan kelas DataTables -->
        <thead>
            <tr>
                <th>Waktu</th>
                <th>User</th>
                <th>Aksi</th>
                <th>Tabel</th>
                <th>ID Record</th>
                <th>Data Lama</th>
                <th>Data Baru</th>
                <th>Aksi</th> <!-- Kolom diganti jadi 'Aksi' umum -->
                <th>Hapus</th> <!-- Kolom baru untuk hapus -->
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result_logs->num_rows > 0) {
                while ($row = $result_logs->fetch_assoc()) {
            ?>
            <tr>
                <td><?php echo htmlspecialchars($row['timestamp']); ?></td>
                <td><?php echo htmlspecialchars($row['username'] ?? 'N/A'); ?></td>
                <td class="action"><?php echo htmlspecialchars($row['action']); ?></td>
                <td><?php echo htmlspecialchars($row['table_name'] ?? '- '); ?></td>
                <td><?php echo htmlspecialchars($row['record_id'] ?? '- '); ?></td>
                <td><pre class="old-data"><?php echo formatJsonData($row['old_data']); ?></pre></td>
                <td><pre class="new-data"><?php echo formatJsonData($row['new_data']); ?></pre></td>
                <td>
                    <?php
                     // Tentukan apakah tombol undo perlu ditampilkan untuk aksi ini
                     $undoable_actions = ['add_item', 'edit_item', 'delete_item', 'add_room', 'edit_room', 'delete_room', 'approve_loan', 'reject_loan', 'confirm_return', 'update_item_status', 'bulk_move_item', 'bulk_delete_item', 'start_item_repair', 'complete_maintenance_record', 'item_repair_completed', 'add_maintenance_record', 'update_item_quantity_after_loan']; // Daftar aksi yang bisa di-undo
                     if (in_array($row['action'], $undoable_actions)) {
                        // Anda mungkin juga ingin mengecek apakah aksi ini sudah di-undo sebelumnya
                        // Perlu kolom tambahan di tabel logs jika ingin melacak ini.
                    ?>
                    <button class="undo-btn" data-log-id="<?php echo $row['id']; ?>">Undo</button>
                    <?php
                     } else {
                         echo '- '; // Tidak bisa di-undo
                     }
                    ?>
                </td>
                <td>
                     <!-- Form untuk Hapus Log -->
                    <form method="post" action="logs.php" style="display:inline;">
                        <input type="hidden" name="delete_log_id" value="<?php echo $row['id']; ?>">
                        <button type="submit" class="delete-btn" onclick="return confirm('Apakah Anda yakin ingin menghapus log ini?\nID Log: <?php echo $row['id']; ?>')">Hapus</button>
                    </form>
                </td>
            </tr>
            <?php
                }
            } else {
                echo "<tr><td colspan='9' style='text-align: center;'>Tidak ada log aktivitas.</td></tr>";
            }
            ?>
        </tbody>
    </table>

  </div>

  <!-- jQuery (diperlukan oleh DataTables) -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- DataTables JS -->
  <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
  
  <script>
  $(document).ready(function() {
      // Inisialisasi DataTables
      $('#logTable').DataTable({
          language: {
              url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/id.json', // Bahasa Indonesia
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
          pageLength: 5, // Batasi 5 data per halaman
          lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Semua"]], // Opsi jumlah data per halaman
          order: [[0, 'desc']] // Urutkan berdasarkan kolom pertama (Waktu) secara descending
      });

      // Handler untuk tombol Undo (jika masih digunakan)
      $(document).on('click', '.undo-btn', function() { 
          const logId = $(this).data('log-id');
          const button = $(this); 
          if (confirm('Apakah Anda yakin ingin membatalkan aksi ini?\n\nPerhatian: Membatalkan aksi peminjaman/pengembalian akan mengubah status peminjaman sesuai aturan sistem (setuju -> tolak, kembali -> dipinjam kembali).')) {
              button.prop('disabled', true).text('Processing...'); 
              $.ajax({
                  url: 'undo.php', // Skrip untuk proses undo
                  type: 'POST',
                  data: { log_id: logId },
                  dataType: 'json',
                  success: function(response) {
                      if (response.status === 'success') {
                          alert('Aksi berhasil dibatalkan.\n' + response.message);
                          // Reload halaman setelah berhasil
                          location.reload();
                      } else {
                          alert('Gagal membatalkan aksi: ' + response.message);
                          button.prop('disabled', false).text('Undo'); 
                      }
                  },
                  error: function(xhr, status, error) {
                      alert('Terjadi kesalahan saat menghubungi server undo: ' + error);
                       button.prop('disabled', false).text('Undo'); 
                      console.error("Error details:", xhr.responseText);
                  }
              });
          }
      });

       // Handler untuk tombol Hapus Log
       // Event handler untuk form delete, bukan hanya tombol
       $(document).on('submit', 'form', function(e) {
           // Cek apakah form yang disubmit memiliki input hidden delete_log_id
           if ($(this).find('input[name="delete_log_id"]').length > 0) {
               // Konfirmasi sudah diatur di atribut onclick tombol submit
               // Tidak perlu konfirmasi ganda di sini jika sudah ada onclick
               // Jika ingin konfirmasi JS di sini:
               // return confirm('Apakah Anda yakin ingin menghapus log ini?');
           }
            // Jika bukan form delete_log_id, biarkan submit berjalan normal
       });


  });
  </script>
</body>
</html> 