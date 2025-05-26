<?php
include "config.php";
session_start();

//
$theme = isset($_SESSION['theme']) ? $_SESSION['theme'] : 'light';

// Ambil data statistik awal untuk card
$query_stats = "SELECT 
                COUNT(CASE WHEN status = 'diajukan' THEN 1 END) AS diajukan,
                COUNT(CASE WHEN status = 'dipinjam' THEN 1 END) AS dipinjam,
                COUNT(CASE WHEN status = 'dikembalikan' THEN 1 END) AS dikembalikan,
                COUNT(CASE WHEN status = 'ditolak' THEN 1 END) AS ditolak
                FROM peminjaman";
$result_stats = $conn->query($query_stats);
$stats = $result_stats->fetch_assoc() ?? ['diajukan' => 0, 'dipinjam' => 0, 'dikembalikan' => 0, 'ditolak' => 0];

// Ambil data peminjaman per bulan untuk diagram garis (data statis)
$query = "SELECT DATE(tanggal_pinjam) as tanggal, COUNT(*) as total 
          FROM peminjaman 
          GROUP BY DATE(tanggal_pinjam)
          ORDER BY tanggal ASC";
$result = $conn->query($query);

$tanggal = [];
$total_peminjaman = [];

while ($row = $result->fetch_assoc()) {
    $tanggal[] = $row['tanggal'];
    $total_peminjaman[] = $row['total'];
}

// Konversi ke JSON untuk digunakan di JavaScript
$tanggal_json = json_encode($tanggal);
$total_peminjaman_json = json_encode($total_peminjaman);

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Admin</title>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- Sertakan Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
      body {
          font-family: Arial, sans-serif;
          padding: 20px;
          background-color: #f9f9f9;
      }
      .container {
          max-width: 1200px;
          margin: auto;
      }
      .card-container {
          display: flex;
          gap: 10px;
          margin-bottom: 20px;
          justify-content: space-between;
          flex-wrap: wrap;
      }
      .card {
          flex: 1;
          padding: 15px;
          border-radius: 10px;
          text-align: center;
          color: white;
          min-width: 150px;
      }
      .blue { background-color: #3498db; }
      .green { background-color: #2ecc71; }
      .yellow { background-color: #f1c40f; }
      .red { background-color: #e74c3c; }
      .table-container {
          overflow-x: auto;
          background: white;
          padding: 15px;
          border-radius: 10px;
          box-shadow: 0px 0px 10px rgba(0,0,0,0.1);
          margin-top: 20px;
      }
      table {
          width: 100%;
          border-collapse: collapse;
          margin-top: 10px;
      }
      th, td {
          border: 1px solid #ddd;
          padding: 10px;
          text-align: center;
      }
     
      .chart-container {
          display: flex;
          flex-wrap: wrap;
          gap: 20px;
          margin-bottom: 20px;
      }
      .chart-card {
          background: white;
          padding: 10px;
          border-radius: 10px;
          box-shadow: 0px 0px 10px rgba(0,0,0,0.1);
          flex: 1;
          min-width: 300px;
      }
      /* Atur ukuran canvas agar konsisten */
      .chart-card canvas {
          width: 100% !important;
          height: 300px !important;
      }




      /* Mode Light (default) */
body {
    font-family: Arial, sans-serif;
    padding: 20px;
    background-color: #f9f9f9;
}
.container {
    max-width: 1200px;
    margin: auto;
}
.card-container {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    justify-content: space-between;
    flex-wrap: wrap;
}
.card {
    flex: 1;
    padding: 15px;
    border-radius: 10px;
    text-align: center;
    color: white;
    min-width: 150px;
}
.blue { background-color: #3498db; }
.green { background-color: #2ecc71; }
.yellow { background-color: #f1c40f; }
.red { background-color: #e74c3c; }
.table-container {
    overflow-x: auto;
    background: white;
    padding: 15px;
    border-radius: 10px;
    box-shadow: 0px 0px 10px rgba(0,0,0,0.1);
    margin-top: 20px;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}
th, td {
    border: 1px solid #ddd;
    padding: 10px;
    text-align: center;
}

.chart-container {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 20px;
}
.chart-card {
    background: white;
    padding: 10px;
    border-radius: 10px;
    box-shadow: 0px 0px 10px rgba(0,0,0,0.1);
    flex: 1;
    min-width: 300px;
    text-align: center;
}
/* Atur ukuran canvas agar konsisten */
.chart-card canvas {
    width: 100% !important;
    height: 300px !important;
}

/* Mode Gelap */
body.dark {
    background-color: #121212;
    color: #e0e0e0;
    /* Jika ingin menambahkan background khusus untuk dark mode, bisa gunakan gradient atau gambar di sini */
}
body.dark .card {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0));
}
body.dark .table-container {
    background: #1e1e1e;
}
body.dark .chart-card {
    background: linear-gradient(135deg, rgba(255,255,255,0.1), rgba(255,255,255,0));
}

.btn-sm {
    padding: 5px 10px;
    font-size: 12px;
    margin: 0 2px;
    border-radius: 3px;
    cursor: pointer;
}
.btn-success {
    background-color: #2ecc71;
    color: white;
    border: none;
}
.btn-danger {
    background-color: #e74c3c;
    color: white;
    border: none;
}
.btn-success:hover {
    background-color: #27ae60;
}
.btn-danger:hover {
    background-color: #c0392b;
}
#laporan{
    text-decoration:none ;
    color: white;
    padding: 5px;
    background-color: #3498db;
    border-radius: 5px;
}

  </style>
</head>
<body class="<?php echo $theme; ?>">

<div class="container">
  <h2>Dashboard Admin</h2>
  <div class="card-container">
    <div class="card blue">
        <h3 id="diajukanCard"><?= $stats['diajukan']; ?></h3>
        <p>Peminjaman Diajukan</p>
    </div>
    <div class="card green">
        <h3 id="dipinjamCard"><?= $stats['dipinjam']; ?></h3>
        <p>Peminjaman Dipinjam</p>
    </div>
    <div class="card yellow">
        <h3 id="dikembalikanCard"><?= $stats['dikembalikan']; ?></h3>
        <p>Peminjaman Dikembalikan</p>
    </div>
    <div class="card red">
        <h3 id="ditolakCard"><?= $stats['ditolak']; ?></h3>
        <p>Peminjaman Ditolak</p>
    </div>
</div>
  
  <!-- Chart Container -->
  <div class="chart-container">
      <!-- Diagram Garis Peminjaman Per Bulan (Statis) -->
      <div class="chart-card">
          <h4>Diagram Garis Peminjaman Per Bulan</h4>
          <canvas id="lineChart"></canvas>
      </div>
      <!-- Diagram Batang Statistik Peminjaman (Statis) -->
      <div class="chart-card">
          <h4>Diagram Batang Statistik Peminjaman</h4>
          <canvas id="barChart"></canvas>
      </div>
      <!-- Diagram Pie Komposisi Peminjaman (Statis) -->
      <div class="chart-card">
          <h4>Diagram Pie Komposisi Peminjaman</h4>
          <canvas id="pieChart"></canvas>
      </div>
  </div>
  
  <!-- Persetujuan Peminjaman (menggunakan AJAX tetap) -->
  <div class="table-container">
      <h3>Persetujuan Peminjaman</h3>
      <table>
          <thead>
              <tr>
                  <th>Nama User</th>
                  <th>Nama Barang</th>
                  <th>Jumlah</th>
                  <th>Tanggal Peminjaman</th>
                  <th>Aksi</th>
              </tr>
          </thead>
          <tbody id="approvalData">
              <!-- Data persetujuan akan dimuat lewat AJAX -->
          </tbody>
      </table>
  </div>
  
  <!-- Konfirmasi Pengembalian (menggunakan AJAX tetap) -->
  <div class="table-container">
      <h3>Konfirmasi Pengembalian</h3>
      <table>
          <thead>
              <tr>
                  <th>Nama User</th>
                  <th>Nama Barang</th>
                  <th>Jumlah</th>
                  <th>Tanggal Kembali</th>
                  <th>Aksi</th>
              </tr>
          </thead>
          <tbody id="returnData">
              <!-- Data pengembalian akan dimuat lewat AJAX -->
          </tbody>
      </table>
  </div>
  <div class="table-container">
    <h2>Riwayat Peminjaman</h2>
    <div class="search-container mb-3">
        <input type="text" id="searchInput" class="form-control" placeholder="Cari riwayat peminjaman..." style="width: 300px; margin-bottom: 15px; padding: 8px; border: 1px solid #ddd; border-radius: 5px;">
    </div>
    <table id="peminjaman-table">
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Barang</th>
                <th>Tanggal Pinjam</th>
                <th>Tanggal Kembali</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <!-- Data akan dimuat melalui AJAX -->
        </tbody>
    </table>

    <div class="pagination" style="margin-top: 20px; text-align: center;">
        <button id="prevPage" class="btn btn-primary" style="margin-right: 10px;" disabled>Previous</button>
        <span id="pageInfo" style="margin: 0 10px;">Page 1</span>
        <button id="nextPage" class="btn btn-primary" style="margin-left: 10px;">Next</button>
    </div>
    <a id="laporan" href="laporanpeminjaman.php">laporan peminjaman </a>
  </div>

</div>

<script>
let currentPage = 1;
let totalPages = 1;
let searchQuery = '';

function fetchPeminjaman(page, search = '') {
    fetch(`fetch_peminjaman.php?page=${page}&search=${encodeURIComponent(search)}`)
        .then(response => response.json())
        .then(responseData => {
            const tbody = document.querySelector('#peminjaman-table tbody');
            tbody.innerHTML = '';

            if (responseData.data.length === 0) {
                const tr = document.createElement('tr');
                tr.innerHTML = '<td colspan="5" style="text-align: center;">Tidak ada data yang ditemukan</td>';
                tbody.appendChild(tr);
            } else {
                responseData.data.forEach((row, index) => {
                    const tr = document.createElement('tr');
                    const rowNumber = (currentPage - 1) * 10 + index + 1; // Asumsi 10 data per halaman
                    tr.innerHTML = `
                        <td data-label="No">${rowNumber}</td>
                        <td data-label="Nama Barang">${row.name}</td>
                        <td data-label="Tanggal Pinjam">${row.tanggal_pinjam}</td>
                        <td data-label="Tanggal Kembali">${row.tanggal_kembali ? row.tanggal_kembali : 'Belum dikembalikan'}</td>
                        <td data-label="Status">
                            <span class="badge ${getStatusBadgeClass(row.status)}">${row.status}</span>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            }

            totalPages = responseData.totalPages;
            document.getElementById('pageInfo').innerText = `Page ${currentPage} of ${totalPages}`;
            document.getElementById('prevPage').disabled = currentPage <= 1;
            document.getElementById('nextPage').disabled = currentPage >= totalPages;
        })
        .catch(error => console.error('Error:', error));
}

function getStatusBadgeClass(status) {
    switch(status.toLowerCase()) {
        case 'diajukan':
            return 'badge-warning';
        case 'dipinjam':
            return 'badge-primary';
        case 'dikembalikan':
            return 'badge-success';
        case 'ditolak':
            return 'badge-danger';
        default:
            return 'badge-secondary';
    }
}

// Event listener untuk pencarian
let searchTimeout;
document.getElementById('searchInput').addEventListener('input', function(e) {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        searchQuery = e.target.value;
        currentPage = 1;
        fetchPeminjaman(currentPage, searchQuery);
    }, 500);
});

document.getElementById('prevPage').addEventListener('click', () => {
    if (currentPage > 1) {
        currentPage--;
        fetchPeminjaman(currentPage, searchQuery);
    }
});

document.getElementById('nextPage').addEventListener('click', () => {
    if (currentPage < totalPages) {
        currentPage++;
        fetchPeminjaman(currentPage, searchQuery);
    }
});

// Inisialisasi dan update realtime
document.addEventListener('DOMContentLoaded', () => {
    fetchPeminjaman(currentPage, searchQuery);
    // Update data setiap 5 detik
    setInterval(() => {
        fetchPeminjaman(currentPage, searchQuery);
    }, 5000);
});

// Tambahkan style untuk badge
const style = document.createElement('style');
style.textContent = `
    .badge {
        padding: 5px 10px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: bold;
        color: white;
    }
    .badge-warning {
        background-color: #f1c40f;
    }
    .badge-primary {
        background-color: #3498db;
    }
    .badge-success {
        background-color: #2ecc71;
    }
    .badge-danger {
        background-color: #e74c3c;
    }
    .badge-secondary {
        background-color: #95a5a6;
    }
    .search-container {
        margin-bottom: 20px;
    }
    #searchInput:focus {
        border-color: #469ced;
        box-shadow: 0 0 0 0.2rem rgba(70, 156, 237, 0.25);
    }
    .table-container table td {
        vertical-align: middle;
    }
`;
document.head.appendChild(style);

// Inisialisasi Chart.js dengan data statis dari PHP

const ctxLine = document.getElementById('lineChart').getContext('2d');
const ctxBar = document.getElementById('barChart').getContext('2d');
const ctxPie = document.getElementById('pieChart').getContext('2d');

let lineChart = new Chart(ctxLine, {
    type: 'line',
    data: {
        labels: [],
        datasets: [{
            label: 'Peminjaman per Hari',
            data: [],
            borderColor: 'blue',
            backgroundColor: 'rgba(52,152,219,0.2)',
            fill: true,
            tension: 0.3
        }]
    },
    options: {
        responsive: true,
        scales: {
            x: { title: { display: true, text: 'Tanggal' } },
            y: { title: { display: true, text: 'Jumlah Peminjaman' }, beginAtZero: true }
        }
    }
});

let barChart = new Chart(ctxBar, {
    type: 'bar',
    data: {
        labels: [],
        datasets: [{
            label: 'Jumlah Peminjaman',
            data: [],
            backgroundColor: ['blue', 'green', 'yellow', 'red']
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true }
        }
    }
});

let pieChart = new Chart(ctxPie, {
    type: 'pie',
    data: {
        labels: [],
        datasets: [{
            data: [],
            backgroundColor: ['blue', 'green', 'yellow', 'red']
        }]
    },
    options: {
        responsive: true
    }
});

function updateCharts() {
    $.ajax({
        url: "fetch_chart_data.php",
        type: "GET",
        dataType: "json",
        success: function(response) {
            lineChart.data.labels = response.labels;
            lineChart.data.datasets[0].data = response.data;
            lineChart.update();

            barChart.data.labels = response.bar_labels;
            barChart.data.datasets[0].data = response.bar_data;
            barChart.update();

            pieChart.data.labels = response.bar_labels;
            pieChart.data.datasets[0].data = response.bar_data;
            pieChart.update();
        },
        error: function() {
            console.error("Gagal mengambil data grafik.");
        }
    });
}
setInterval(updateCharts, 1000);









// Fungsi AJAX untuk persetujuan
function updateStatus(id, status) {
    if (confirm('Apakah Anda yakin ingin ' + (status === 'dipinjam' ? 'menyetujui' : 'menolak') + ' peminjaman ini?')) {
        $.ajax({
            url: "update_status.php",
            type: "POST",
            data: { 
                id: id, 
                status: status,
                tanggal_kembali: (status === 'ditolak' || status === 'dikembalikan') ? new Date().toISOString().split('T')[0] : null

            },
            success: function(response) {
                if(response === "success") {
                    let message = "";
                    switch(status) {
                        case 'dipinjam':
                            message = "Peminjaman berhasil disetujui!";
                            break;
                        case 'ditolak':
                            message = "Peminjaman ditolak dan tercatat tanggal pengembalian hari ini!";
                            break;
                        case 'dikembalikan':
                            message = "Barang berhasil dikembalikan!";
                            break;
                        default:
                            message = "Status berhasil diperbarui!";
                    }
                    alert(message);
                    
                    // Refresh semua data
                    fetchApprovals();
                    fetchReturns();
                    fetchStats();
                    fetchPeminjaman(currentPage, searchQuery);
                    updateCharts();
                } else {
                    alert("Error: " + response);
                }
            },
            error: function(xhr, status, error) {
                alert("Terjadi kesalahan: " + error);
                console.error("Error details:", xhr.responseText);
            }
        });
    }
}

function fetchApprovals() {
    $.ajax({
        url: "fetch_approval.php",
        type: "GET",
        success: function(data) {
            $("#approvalData").html(data);
        },
        error: function(xhr, status, error) {
            console.error("Error fetching approvals:", error);
            $("#approvalData").html("<tr><td colspan='5' class='text-center'>Error loading data</td></tr>");
        }
    });
}

function fetchReturns() {
  $.ajax({
      url: "fetch_returns.php",
      type: "GET",
      success: function(data) {
          $("#returnData").html(data);
      }
  });
}

function fetchStats() {
  $.ajax({
      url: "fetch_stats.php",
      type: "GET",
      dataType: "json",
      success: function(data) {
          $("#diajukanCard").text(data.diajukan);
          $("#dipinjamCard").text(data.dipinjam);
          $("#dikembalikanCard").text(data.dikembalikan);
          $("#ditolakCard").text(data.ditolak);
          // Grafik tidak diupdate secara AJAX karena menggunakan data statis
      }
  });
}

// Panggil fungsi untuk mengambil data persetujuan, pengembalian, dan statistik
$(document).ready(function() {
  fetchApprovals();
  fetchReturns();
  fetchStats();
  // Update tabel data setiap 5 detik (tetap menggunakan AJAX)
  setInterval(function() {
      fetchApprovals();
      fetchReturns();
      fetchStats();
  }, 5000);
});
function fetchStats() {
    $.ajax({
        url: "fetch_stats.php",
        type: "GET",
        dataType: "json",
        success: function(data) {
            $("#diajukanCard").text(data.diajukan);
            $("#dipinjamCard").text(data.dipinjam);
            $("#dikembalikanCard").text(data.dikembalikan);
            $("#ditolakCard").text(data.ditolak);
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error("Error mengambil data statistik: ", textStatus, errorThrown);
        }
    });
}

// Panggil fungsi ketika halaman telah siap dan update setiap 5 detik
$(document).ready(function() {
    fetchStats();
    setInterval(fetchStats, 5000);
});
// Fungsi untuk melakukan update data diagram garis secara realtime
function updateLineChart() {
    $.ajax({
        url: "fetch_chart_data.php", // Endpoint yang menyediakan data chart terbaru
        type: "GET",
        dataType: "json",
        success: function(response) {
            // Perbarui label dan data untuk lineChart
            lineChart.data.labels = response.labels;
            lineChart.data.datasets[0].data = response.data;
            lineChart.update(); // Panggil update untuk me-render ulang diagram
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error("Error fetching chart data: ", textStatus, errorThrown);
        }
    });
}

// Panggil fungsi updateLineChart setiap 5 detik
$(document).ready(function() {
    setInterval(updateLineChart, 5000); // Update setiap 5000 ms (5 detik)
});

</script>

</body>
</html>
