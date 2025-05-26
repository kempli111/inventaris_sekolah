<?php
include "config.php";
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$current_user_id = $_SESSION['user_id'] ?? null; // Ambil user_id untuk logging

// Ambil preferensi tema dari session (atau database jika belum diset)
$theme = isset($_SESSION['theme']) ? $_SESSION['theme'] : 'light';

if (isset($_GET['id'])) {
    $room_id = intval($_GET['id']); // Gunakan intval untuk keamanan

    // Ambil data ruangan saat ini untuk ditampilkan di form
    $sql_room = "SELECT * FROM rooms WHERE id = '$room_id'";
    $result_room = $conn->query($sql_room);
    $room = $result_room->fetch_assoc();

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $name = $_POST["name"];
        $location = $_POST["location"];

        // --- Logging: Ambil data ruangan sebelum diupdate ---
        $stmt_old_room = $conn->prepare("SELECT * FROM rooms WHERE id = ?");
        $stmt_old_room->bind_param("i", $room_id);
        $stmt_old_room->execute();
        $old_room_data = $stmt_old_room->get_result()->fetch_assoc();
        $stmt_old_room->close();
        // --- Akhir Logging ---

        $sql_update = "UPDATE rooms SET name = '$name', location = '$location' WHERE id = '$room_id'";
        if ($conn->query($sql_update) === TRUE) {
            // --- Logging: Catat aksi edit ruangan ---
            $new_room_data = ['id' => $room_id, 'name' => $name, 'location' => $location];
            log_action($conn, $current_user_id, 'edit_room', 'rooms', $room_id, $old_room_data, $new_room_data);
            // --- Akhir Logging ---

            echo "<script>alert('Data berhasil diupdate'); window.location.href='ruangan.php';</script>";
            exit();
        } else {
            echo "ERROR: " . $sql_update . "<br>" . $conn->error;
             // Log error jika update gagal?
        }
    }
} else {
    echo "ID ruangan tidak ditemukan.";
}

// Tampilkan form edit hanya jika data ruangan ditemukan
if ($room) {
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Edit Ruangan</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"/>
  <style>
    * {
      box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
    }

    body {
      margin: 0;
      padding: 0;
      background: <?php echo ($theme === 'dark') ? '#121212' : '#f2f2f2'; ?>;
      color: <?php echo ($theme === 'dark') ? '#fff' : '#333'; ?>;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }

    .form-container {
      background: <?php echo ($theme === 'dark') ? '#1e1e1e' : '#fff'; ?>;
      padding: 30px 40px;
      border-radius: 15px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
      width: 100%;
      max-width: 500px;
    }

    h1 {
      text-align: center;
      margin-bottom: 30px;
    }

    input[type="text"] {
      width: 100%;
      padding: 12px 15px;
      margin-bottom: 20px;
      border: none;
      border-radius: 10px;
      background: <?php echo ($theme === 'dark') ? '#333' : '#eaeaea'; ?>;
      color: <?php echo ($theme === 'dark') ? '#fff' : '#000'; ?>;
    }

    input[type="text"]::placeholder {
      color: #999;
    }

    button {
      width: 100%;
      padding: 12px;
      border: none;
      border-radius: 10px;
      background-color: <?php echo ($theme === 'dark') ? '#4682B4' : '#469CED'; ?>;
      color: white;
      font-size: 16px;
      cursor: pointer;
      transition: background 0.3s ease;
    }

    button:hover {
      background-color: <?php echo ($theme === 'dark') ? '#5a9bd3' : '#2c82e0'; ?>;
    }
  </style>
</head>
<body class="<?php echo $theme; ?>">
  <div class="form-container">
    <h1>Edit Ruangan</h1>
    <form method="post">
      <input type="text" name="name" placeholder="Nama Ruangan" value="<?php echo htmlspecialchars($room['name']); ?>" required>
      <input type="text" name="location" placeholder="Lokasi" value="<?php echo htmlspecialchars($room['location']); ?>" required>
      <button type="submit">Update</button>
    </form>
  </div>
</body>
</html>
<?php
}
?>
