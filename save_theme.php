<?php
session_start();
include "config.php";

// Pastikan user sudah login dan data theme dikirim
if (!isset($_SESSION['user_id']) || !isset($_POST['theme'])) {
    exit();
}

$username = $_SESSION['username'];
$theme = $_POST['theme'];

// Update database (pastikan kolom di tabel users sesuai, misal menggunakan 'theme_mode' dan 'username')
$sql = "UPDATE users SET theme_mode = ? WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $theme, $username);
$stmt->execute();

// Update session agar halaman dan iframe mendapatkan tema terbaru
$_SESSION['theme'] = $theme;
?>
