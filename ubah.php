<?php
include "config.php";
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $new_username = $_POST['new_username'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    
    // Ambil data pengguna
    $stmt = $conn->prepare("SELECT username, password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($current_username, $hashed_password);
    $stmt->fetch();
    
    if (password_verify($current_password, $hashed_password)) {
        
        if (!empty($new_username) && $new_username != $current_username) {
            $stmt = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
            $stmt->bind_param("si", $new_username, $user_id);
            $stmt->execute();
            $_SESSION['username'] = $new_username;
            echo "Username berhasil diubah!";
        }
        
        if (!empty($new_password)) {
            $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_new_password, $user_id);
            $stmt->execute();
            echo "Password berhasil diubah!";
        }
    } else {
        echo "Password lama salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ganti Password dan Username</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="wrapper">
        <h1>Ganti Password dan Username</h1>
        <form method="post">
            <input type="text" name="new_username" placeholder="Username Baru">
            <input type="password" name="current_password" placeholder="Password Lama" required>
            <input type="password" name="new_password" placeholder="Password Baru">
            <button type="submit">Simpan Perubahan</button>
        </form>
        <a href="user.php">Kembali ke Dashboard</a>
    </div>
</body>
</html>
