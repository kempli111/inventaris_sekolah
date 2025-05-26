<?php
session_start();
include "config.php";

// Ambil theme dari session, default light
$theme = isset($_SESSION['theme']) ? $_SESSION['theme'] : 'light';

// Ambil username dan foto profil berdasarkan user_id dari session
if (isset($_SESSION['user_id'])) {
  $user_id = $_SESSION['user_id'];
  $query = "SELECT username, profile_picture FROM users WHERE id = $user_id";
  $result = $conn->query($query);
  if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $username = $row['username'];
    $profile_picture = $row['profile_picture'];
  } else {
    $username = "User";
    $profile_picture = "profile/default.jpg";
  }

  // Proses perubahan foto profil
  if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['profile_picture'])) {
    $target_dir = "profile/";
    $target_file = $target_dir . basename($_FILES["profile_picture"]["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    $uploadOk = 1;

    // Cek apakah file gambar atau bukan
    $check = getimagesize($_FILES["profile_picture"]["tmp_name"]);
    if ($check === false) {
      echo "File yang diupload bukan gambar.";
      $uploadOk = 0;
    }

    // Cek ukuran file
    if ($_FILES["profile_picture"]["size"] > 500000) { // 500KB
      echo "Ukuran file terlalu besar.";
      $uploadOk = 0;
    }

    // Cek ekstensi file
    if (!in_array($imageFileType, ["jpg", "jpeg", "png", "gif"])) {
      echo "Hanya file JPG, JPEG, PNG, dan GIF yang diperbolehkan.";
      $uploadOk = 0;
    }

    if ($uploadOk) {
      if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
        $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
        $stmt->bind_param("si", $target_file, $user_id);
        if ($stmt->execute()) {
          header("Location: settings.php");
          exit();
        } else {
          echo "Gagal mengubah foto profil.";
        }
      } else {
        echo "Terjadi kesalahan saat mengupload file.";
      }
    }
  }
} else {
  $username = "Guest";
  $profile_picture = "profile/default.jpg";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pengaturan Akun</title>
  <style>
    /* Mode Terang */
    body.light { background: #f4f7f6; color: #333; }
    body.light .container { background: #fff; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
    body.light h2 { color: #469ced; }
    body.light .upload-btn { background: #469ced; color: #fff; }
    body.light .logout-btn { background: #ff4747; color: #fff; }
    body.light .settings-list li a, body.light .settings-list li button { color: #333; }

    /* Mode Gelap */
    body.dark { background-color: #121212; color: #e0e0e0; }
    body.dark .container { background: #1e1e1e; box-shadow: 0 2px 6px rgba(0,0,0,0.5); }
    body.dark h2 { color: #1f78d1; }
    body.dark .upload-btn { background: #1f78d1; color: #e0e0e0; }
    body.dark .logout-btn { background: #d32f2f; color: #e0e0e0; }
    body.dark .settings-list li a, body.dark .settings-list li button { color: #e0e0e0; }
    body.dark .settings-list li { border-bottom: 1px solid #333; }

    /* Umum */
    body, input, button, textarea, ul, li { font-family: Arial, sans-serif; }
    .container { max-width: 600px; margin: 20px auto; padding: 20px; border-radius: 8px; }
    h2 { text-align: center; margin-bottom: 20px; }
    .welcome { text-align: center; margin-bottom: 20px; font-size: 1.2em; }
    .profile-picture { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; display: block; margin: 0 auto 20px; }
    .upload-btn, .logout-btn { display: block; width: 100%; padding: 10px; border: none; border-radius: 5px; cursor: pointer; margin-bottom: 15px; transition: background 0.3s ease; }
    .settings-list { list-style: none; padding: 0; margin: 0; }
    .settings-list li { }
    .settings-list li a { display: block; padding: 15px; text-decoration: none; }
    .settings-list li button { width: 100%; text-align: left; padding: 15px; background: none; border: none; cursor: pointer; font-size: 1em; }
    .settings-list li a:hover, .settings-list li button:hover { background: rgba(0,0,0,0.05); }
  </style>
</head>
<body class="<?php echo $theme; ?>">
  <div class="container">
    <h2>Pengaturan Akun</h2>
    <p class="welcome">Selamat datang, <?php echo htmlspecialchars($username); ?>!</p>
    <div class="profile-section">
      <img src="<?php echo $profile_picture; ?>" alt="Foto Profil" class="profile-picture">
      <form action="settings.php" method="POST" enctype="multipart/form-data">
        <input type="file" name="profile_picture" accept="image/*" required>
        <button type="submit" class="upload-btn">Ubah Foto Profil</button>
      </form>
    </div>
    <ul class="settings-list">
      <li><a href="ubah.php">Ubah Username/Password</a></li>
      <!-- Logout button -->
      <li><button onclick="logout()" class="logout-btn">Logout</button></li>
    </ul>
  </div>

<script>
function logout() {
    if (confirm("Yakin ingin logout?")) {
        window.top.location.href = "logout.php";
    }
}
</script>
</body>
</html>
