<?php
include "config.php";
session_start();

// Set theme based on session, default to light
$theme = isset($_SESSION['theme']) ? $_SESSION['theme'] : 'light';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST["name"];
    $location = $_POST["location"]; // Data sekarang datang dari select

    $sql = "INSERT INTO rooms(name, location) VALUES (?, ?)"; // Menggunakan prepared statement
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $name, $location);

    if ($stmt->execute()) {
        $last_id = $conn->insert_id; // Ambil ID ruangan yang baru ditambahkan
        echo "<p class='message'>Data berhasil ditambahkan</p>";

        // --- Tambahkan logika logging di sini ---
        $new_room_data = [
            'id' => $last_id,
            'name' => $name,
            'location' => $location
        ];
        log_action($conn, $_SESSION['user_id'], 'add_room', 'rooms', $last_id, null, $new_room_data);
        // --- Akhir logika logging ---

    } else {
        echo "<p class='message error'>ERROR: " . $conn->error . "</p>";
    }
     $stmt->close(); // Tutup statement
}

$sql_rooms = "SELECT * FROM rooms";
$result_rooms = $conn->query($sql_rooms);
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Form Ruangan</title>
  <style>
    /* Mode Terang */
    body.light {
      background: #f4f7f6;
      color: #333;
    }
    body.light .card {
      background: #fff;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    body.light h1 {
      background-color: #469ced;
      color: #fff;
    }
    body.light h2 {
      color: #469ced;
    }
    body.light form button, body.light table th {
      background-color: #469ced;
      color: #fff;
    }
      body.light a{
      background-color: #469ced;
      padding: 8px;
      color: white;
      border-radius: 3px;
    }

    /* Mode Gelap */
    body.dark {
      background-color: #121212;
      color: #e0e0e0;
    }
    body.dark .card {
      background: linear-gradient(135deg, rgba(255,255,255,0.1), rgba(255,255,255,0));
      box-shadow: 0 2px 10px rgba(0,0,0,0.5);
    }
    body.dark h1 {
      background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0));
    box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        

      color: #e0e0e0;
    }
    body.dark h2 {
      color:rgb(255, 255, 255);
    }
    body.dark form button, body.dark table th {
      background-color: transparent;
      color: #e0e0e0;
    }
    body.dark .table-container {
      background: #1e1e1e;
      border-radius: 8px;
      padding: 15px;
    }
    /* Chart card placeholder */
    body.dark .chart-card {
      background: linear-gradient(135deg, rgba(255,255,255,0.1), rgba(255,255,255,0));
      border-radius: 8px;
      padding: 15px;
      margin-top: 20px;
    }
    body.dark a {
      background-color:transparent;
      color: white;
    }
  

    /* Umum */
    body, input, button, table, select { /* Tambahkan select */
      font-family: Arial, sans-serif;
    }
    .container {
      width: 90%;
      max-width: 800px;
      margin: 30px auto;
    }
    h1 {
      text-align: center;
      padding: 15px;
      border-radius: 6px;
      margin-bottom: 20px;
    }
    h2 {
      text-align: center;
      margin-bottom: 15px;
    }
    .card {
      border-radius: 8px;
      padding: 20px;
      margin-bottom: 20px;
    }
    form {
      text-align: center;
    }
    form input[type="text"], form select { /* Tambahkan select */
      width: calc(50% - 20px); /* Sesuaikan lebar */
      padding: 10px;
      margin: 10px 5px;
      border: 1px solid #ddd;
      border-radius: 4px;
    }
    form button {
      border: none;
      padding: 10px 20px;
      margin-top: 10px;
      border-radius: 4px;
      cursor: pointer;
      transition: background-color 0.3s;
    }
    .table-container table {
      width: 100%;
      border-collapse: collapse;
    }
    .table-container th, .table-container td {
      padding: 12px;
      text-align: left;
      border-bottom: 1px solid #444; /* Sesuaikan border dark mode */
    }
    body.dark .table-container th, body.dark .table-container td { /* Override border dark mode */
         border-color: #444;
    }
    .table-container th {
      border-bottom: 2px solid #666; /* Sesuaikan border dark mode */
    }
    body.dark .table-container th { /* Override border dark mode */
         border-color: #666;
    }
    table tr:nth-child(even) { /* Gaya stripe */
      background-color: rgba(0,0,0,0.05); /* Gaya light mode */
    }
     body.dark table tr:nth-child(even) { /* Gaya stripe dark mode */
         background-color: rgba(255,255,255,0.05);
    }
    table a { /* Gaya link aksi */
      text-decoration: none;
      font-weight: bold;
      margin-right: 10px; /* Jarak antar link aksi */
    }
     body.dark table a { /* Gaya link aksi dark mode */
         color: #469ced; /* Warna biru */
     }
     table a:last-child { /* Hapus margin kanan link terakhir */
         margin-right: 0;
     }
    .message { /* Gaya pesan */
      text-align: center;
      margin-bottom: 20px;
      font-weight: bold;
    }
    .message.error { /* Gaya pesan error */
      color: #e57373; /* Warna merah */
    }
     body.dark .message.error { /* Gaya pesan error dark mode */
         color: #ff7961; /* Warna merah terang */
     }
  </style>
</head>
<body class="<?php echo $theme; ?>">
  <div class="container">
    <h1>Tambah Ruangan</h1>
    <div class="card form-card">
      <form method="post">
        <input type="text" name="name" placeholder="Nama Ruangan" required>
        <select name="location" required> <!-- Ganti input text dengan select -->
            <option value="">-- Pilih Lokasi --</option>
            <option value="lantai 1 depan">Lantai 1 Depan</option>
            <option value="lantai satu belakang">Lantai 1 Belakang</option>
            <option value="lantai 2 belakang">Lantai 2 Belakang</option>
            <option value="lantai 2 depan">Lantai 2 Depan</option>
        </select>
        <br>
        <button type="submit">Submit</button>
      </form>
    </div>

    <h2>Daftar Ruangan</h2>
    <div class="card table-container">
      <table>
        <tr>
          <th>Nama Ruangan</th>
          <th>Lokasi</th>
          <th>Aksi</th>
        </tr>
        <?php
        if ($result_rooms->num_rows > 0) {
            while ($row = $result_rooms->fetch_assoc()) {
        ?>
        <tr>
          <td><?php echo htmlspecialchars($row['name']); ?></td>
          <td><?php echo htmlspecialchars($row['location']); ?></td>
          <td>
            <a href="edit_room.php?id=<?php echo $row['id']; ?>">Edit</a> |
            <a href="delete_room.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus ruangan ini?');">Hapus</a>
          </td>
        </tr>
        <?php
            }
        } else {
            echo "<tr><td colspan='3' style='text-align: center;'>Tidak ada data ruangan.</td></tr>";
        }
        ?>
      </table>
    </div>

    <!-- Placeholder for future chart -->
    <div class="chart-card">
      <!-- Chart akan ditampilkan di sini -->
    </div>
  </div>
</body>
</html>
