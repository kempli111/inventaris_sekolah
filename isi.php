<?php
include "config.php";
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$theme = isset($_SESSION['theme']) ? $_SESSION['theme'] : 'light';


$query_stats = "SELECT 
                COUNT(CASE WHEN status = 'diajukan' THEN 1 END) AS diajukan,
                COUNT(CASE WHEN status = 'dipinjam' THEN 1 END) AS dipinjam,
                COUNT(CASE WHEN status = 'dikembalikan' THEN 1 END) AS dikembalikan,
                COUNT(CASE WHEN status = 'ditolak' THEN 1 END) AS ditolak
                FROM peminjaman";
$result_stats = $conn->query($query_stats);
$stats = $result_stats->fetch_assoc() ?? ['diajukan' => 0, 'dipinjam' => 0, 'dikembalikan' => 0, 'ditolak' => 0];

$sql_rooms = "SELECT rooms.id, rooms.name AS room_name, rooms.location, SUM(items.total) AS total_quantity
              FROM rooms
              LEFT JOIN items ON rooms.id = items.room_id
              GROUP BY rooms.id";
$result_rooms = $conn->query($sql_rooms);

$sql_chart1 = "SELECT rooms.name AS room_name, SUM(items.total) AS total_quantity 
               FROM rooms 
               LEFT JOIN items ON rooms.id = items.room_id 
               GROUP BY rooms.id";
$result_chart1 = $conn->query($sql_chart1);
$chart1_labels = array();
$chart1_data = array();
if ($result_chart1->num_rows > 0) {
  while ($row = $result_chart1->fetch_assoc()) {
    $chart1_labels[] = $row['room_name'];
    $chart1_data[] = $row['total_quantity'] ? $row['total_quantity'] : 0;
  }
}

$sql_chart2 = "SELECT categories.name AS category_name, SUM(items.total) AS total_quantity 
               FROM items 
               LEFT JOIN categories ON items.category_id = categories.id 
               GROUP BY categories.id";
$result_chart2 = $conn->query($sql_chart2);
$chart2_labels = array();
$chart2_data = array();
if ($result_chart2->num_rows > 0) {
  while ($row = $result_chart2->fetch_assoc()) {
    $chart2_labels[] = $row['category_name'];
    $chart2_data[] = $row['total_quantity'] ? $row['total_quantity'] : 0;
  }
}

$sql_chart3 = "SELECT rooms.name AS room_name, SUM(items.price * items.total) AS total_value 
               FROM rooms 
               LEFT JOIN items ON rooms.id = items.room_id 
               GROUP BY rooms.id";
$result_chart3 = $conn->query($sql_chart3);
$chart3_labels = array();
$chart3_data = array();
if ($result_chart3->num_rows > 0) {
  while ($row = $result_chart3->fetch_assoc()) {
    $chart3_labels[] = $row['room_name'];
    $chart3_data[] = $row['total_value'] ? $row['total_value'] : 0;
  }
}

$sql_chart4 = "SELECT item_condition, SUM(total) AS total_quantity 
               FROM items 
               GROUP BY item_condition";
$result_chart4 = $conn->query($sql_chart4);
$chart4_labels = array();
$chart4_data = array();
if ($result_chart4->num_rows > 0) {
  while ($row = $result_chart4->fetch_assoc()) {
    $chart4_labels[] = ucfirst($row['item_condition']);
    $chart4_data[] = $row['total_quantity'] ? $row['total_quantity'] : 0;
  }
}

$sql_chart5 = "SELECT categories.name AS category_name, SUM(items.price * items.total) AS total_value 
               FROM items 
               LEFT JOIN categories ON items.category_id = categories.id 
               GROUP BY categories.id";
$result_chart5 = $conn->query($sql_chart5);
$chart5_labels = array();
$chart5_data = array();
if ($result_chart5->num_rows > 0) {
  while ($row = $result_chart5->fetch_assoc()) {
    $chart5_labels[] = $row['category_name'];
    $chart5_data[] = $row['total_value'] ? $row['total_value'] : 0;
  }
}



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

$tanggal_json = json_encode($tanggal);
$total_peminjaman_json = json_encode($total_peminjaman);

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Inventaris</title>
  <link rel="stylesheet" href="style.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


</head>
<style>
    * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Roboto', sans-serif;
    background: #f4f7f6;
    color: #333;
    padding: 20px;
    overflow: visible;
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
      .blue { background-color: #3498db; }
      .green { background-color: #2ecc71; }
      .yellow { background-color: #f1c40f; }
      .red { background-color: #e74c3c; }
      .card {
          flex: 1;
          padding: 15px;
          border-radius: 10px;
          text-align: center;
          color: white;
          min-width: 150px;
      }

.header {
    text-align: center;
    padding: 20px;
    background: #469ced;
    color: #fff;
    border-radius: 8px;
    margin-bottom: 20px;
}

.charts-section {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-around;
    margin-bottom: 20px;
}

.chart-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    margin: 10px;
    padding: 20px;
    flex: 1 1 300px;
    max-width: 400px;
    text-align: center;
}

.chart-card canvas {
    max-width: 100%;
}

.chart-title {
    margin-top: 10px;
    font-weight: bold;
}

.table-section {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    padding: 20px;
}

.table-section h2 {
    margin-bottom: 10px;
    text-align: center;
}

table {
    width: 100%;
    border-collapse: collapse;
}

table th,
table td {
    padding: 12px;
    border: 1px solid #ddd;
    text-align: left;
}

table th {
    background: #f2f2f2;
}

table tr:nth-child(even) {
    background: #f9f9f9;
}

a {
    color: #469ced;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}

body.dark.chart-title {
    margin-top: 10px;
    font-weight: bold;
    color: white;
}


/* Mode Gelap */
body.dark {
  background-color: #1E1E1E ;}
   


/* Header dark mode */
body.dark .header {
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0));
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border-radius: 20px;
    border:1px solid rgba(255, 255, 255, 0.18);
    box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
    color: #fff;
}

/* Card, Chart, dan Table dark mode */
body.dark .card,
body.dark .chart-card,
body.dark .table-section {
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0));
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border-radius: 20px;
    border:1px solid rgba(255, 255, 255, 0.18);
    box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
}

/* Tabel dark mode */
body.dark table th {
    background: rgba(255, 255, 255, 0.15);
    color: white;

}
body.dark table tr:nth-child(even) {
    background: rgba(255, 255, 255, 0.05);
    color: white;
}
body.dark h2 {
    color: white;
}
/* Link dark mode */
body.dark a {
    color: #9ecfff;
}
body.dark a:hover {
    text-decoration: underline;
}





</style>
<body class="<?php echo $theme; ?>">

  <header class="header">
    <h1>Dashboard Inventaris</h1>
  </header>
  
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
  <section class="charts-section">
    <div class="chart-card">
      <canvas id="chart1"></canvas>
      <div class="chart-title"><h2>Inventaris per Ruangan</h2></div>
    </div>
    <div class="chart-card">
      <canvas id="chart2"></canvas>
      <div class="chart-title"><h2>Inventaris per Kategori</h2></div>
    </div>
    <div class="chart-card">
      <canvas id="chart3"></canvas>
      <div class="chart-title"><h2>Total Harga Inventaris per Ruangan</h2></div>
    </div>
    <div class="chart-card">
      <canvas id="chart4"></canvas>
      <div class="chart-title"><h2>Distribusi Kondisi Barang</h2></div>
    </div>
    <div class="chart-card">
      <canvas id="chart5"></canvas>
      <div class="chart-title"><h2>Total Harga Inventaris per Kategori</h2></div>
    </div><div class="chart-container">
      <div class="chart-card">
          <canvas id="lineChart"></canvas>
          <div class="chart-title"> <h2>Diagram Garis Peminjaman Per hari  </h2> </div>

      </div>
  </section>
  <section class="table-section">
    <h2>Daftar Ruangan</h2>
    <table>
      <tr>
        <th>Nama Ruangan</th>
        <th>Lokasi</th>
        <th>Total Quantity</th>
        <th>Aksi</th>
      </tr>
      <?php
      if ($result_rooms->num_rows > 0) {
        while ($row = $result_rooms->fetch_assoc()) {
          echo "<tr>";
          echo "<td>".$row['room_name']."</td>";
          echo "<td>".$row['location']."</td>";
          echo "<td>".$row['total_quantity']."</td>";
          echo "<td><a href='detail_ruangan.php?id=".$row['id']."'>Lihat Lebih Detail</a></td>";
          echo "</tr>";
        }
      } else {
        echo "<tr><td colspan='4'>Tidak ada data ruangan.</td></tr>";
      }
      ?>
    </table>
  </section>


  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    var chart1Labels = <?php echo json_encode($chart1_labels); ?>;
    
    var chart1Data = <?php echo json_encode($chart1_data); ?>;
    var ctx1 = document.getElementById('chart1').getContext('2d');
    var chart1 = new Chart(ctx1, {
      type: 'bar',

      data: {
        labels: chart1Labels,
        datasets: [{
          label: 'Total Quantity',
         data: chart1Data,
           backgroundColor: 'rgba(75, 192, 192, 0.7)',
          borderColor: 'rgba(75, 192, 192, 1)',
           borderWidth: 1
        }]



      },
      options: {
        responsive: true,
        scales: { y: { beginAtZero: true } }
      }
    });

    var chart2Labels = <?php echo json_encode($chart2_labels); ?>;
    var chart2Data = <?php echo json_encode($chart2_data); ?>;
    var ctx2 = document.getElementById('chart2').getContext('2d');
    var chart2 = new Chart(ctx2, {
      type: 'pie',
      data: {
        labels: chart2Labels,
        datasets: [{
          label: 'Total Quantity',
          data: chart2Data,
          backgroundColor: [
            'rgba(255, 99, 132, 0.7)',
            'rgba(54, 162, 235, 0.7)',
            'rgba(255, 206, 86, 0.7)',
            'rgba(75, 192, 192, 0.7)',
            'rgba(153, 102, 255, 0.7)',
            'rgba(255, 159, 64, 0.7)'
          ],
          borderColor: [
            'rgba(255, 99, 132, 1)',
            'rgba(54, 162, 235, 1)',
            'rgba(255, 206, 86, 1)',
            'rgba(75, 192, 192, 1)',
            'rgba(153, 102, 255, 1)',
            'rgba(255, 159, 64, 1)'
          ],
          borderWidth: 1
        }]
      },
      options: { responsive: true }
    });

    var chart3Labels = <?php echo json_encode($chart3_labels); ?>;
    var chart3Data = <?php echo json_encode($chart3_data); ?>;
    var ctx3 = document.getElementById('chart3').getContext('2d');
    var chart3 = new Chart(ctx3, {
      type: 'bar',
      data: {
        labels: chart3Labels,
        datasets: [{
          label: 'Total Harga',
          data: chart3Data,
          backgroundColor: 'rgba(153, 102, 255, 0.7)',
          borderColor: 'rgba(153, 102, 255, 1)',
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        scales: { y: { beginAtZero: true } }
      }
    });

    var chart4Labels = <?php echo json_encode($chart4_labels); ?>;
    var chart4Data = <?php echo json_encode($chart4_data); ?>;
    var ctx4 = document.getElementById('chart4').getContext('2d');
    var chart4 = new Chart(ctx4, {
      type: 'doughnut',
      data: {
        labels: chart4Labels,
        datasets: [{
          label: 'Total Quantity',
          data: chart4Data,
          backgroundColor: [
            'rgba(255, 159, 64, 0.7)',
            'rgba(255, 99, 132, 0.7)',
            'rgba(54, 162, 235, 0.7)'
          ],
          borderColor: [
            'rgba(255, 159, 64, 1)',
            'rgba(255, 99, 132, 1)',
            'rgba(54, 162, 235, 1)'
          ],
          borderWidth: 1
        }]
      },
      options: { responsive: true }
    });

    var chart5Labels = <?php echo json_encode($chart5_labels); ?>;
    var chart5Data = <?php echo json_encode($chart5_data); ?>;
    var ctx5 = document.getElementById('chart5').getContext('2d');
    var chart5 = new Chart(ctx5, {
      type: 'bar',
      data: {
        labels: chart5Labels,
        datasets: [{
          label: 'Total Harga',
          data: chart5Data,
          backgroundColor: 'rgba(255, 206, 86, 0.7)',
          borderColor: 'rgba(255, 206, 86, 1)',
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        scales: { y: { beginAtZero: true } }
      }
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





const ctxLine = document.getElementById('lineChart').getContext('2d');


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

function updateCharts() {
    $.ajax({
        url: "fetch_chart_data.php",
        type: "GET",
        dataType: "json",
        success: function(response) {
            lineChart.data.labels = response.labels;
            lineChart.data.datasets[0].data = response.data;
            lineChart.update();

         
        },
        error: function() {
            console.error("Gagal mengambil data grafik.");
        }
    });
}
setInterval(updateCharts, 1000);







$(document).ready(function() {
    fetchStats();
    setInterval(fetchStats, 5000);
});
function updateLineChart() {
    $.ajax({
        url: "fetch_chart_data.php", 
        type: "GET",
        dataType: "json",
        success: function(response) {
            lineChart.data.labels = response.labels;
            lineChart.data.datasets[0].data = response.data;
            lineChart.update(); 
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error("Error fetching chart data: ", textStatus, errorThrown);
        }
    });
}

$(document).ready(function() {
    setInterval(updateLineChart, 1000); 
});















  </script>
</body>
</html>
