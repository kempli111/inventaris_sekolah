<?php
session_start();
include "config.php";

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Ambil preferensi tema dari database berdasarkan username
$sql = "SELECT theme_mode FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$theme = ($row && isset($row['theme_mode'])) ? $row['theme_mode'] : 'light';

// Simpan tema ke session agar bisa diakses oleh iframe atau halaman lain
$_SESSION['theme'] = $theme;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <a href="aregister.php">tambahkan user</a>
    <a href="viewadmin.php">lihat user</a>
    
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Responsive Navigation</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background: #ffffff;
            color: #333;
        }

        .sidebar {
            width: 250px;
            background: #469CED;
            color: white;
            height: 100vh;
            position: fixed;
            display: flex;
            flex-direction: column;
            transition: width 0.3s ease;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.2);
        }
        .sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .sidebar li {
            padding: 15px;
            cursor: pointer;
            display: flex;
            align-items: center;
            font-size: 18px;
            transition: background 0.3s;
        }
        .sidebar li:hover {
            background: #3a86ca;
        }
        .sidebar li i {
            margin-right: 12px;
            font-size: 20px;
        }

        .toggle-btn {
            display: none;
            position: absolute;
            top: 15px;
            left: 15px;
            font-size: 24px;
            cursor: pointer;
            color: white;
        }

        .mobile-nav {
            display: none;
            background: #469CED;
            color: white;
            padding: 15px 20px;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            box-shadow: 0px 2px 10px rgba(0, 0, 0, 0.2);
        }
        .mobile-nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            justify-content: space-around;
        }
        .mobile-nav li {
            padding: 10px;
            cursor: pointer;
            font-size: 20px;
        }
        .mobile-nav li:hover {
            background: #3a86ca;
            border-radius: 5px;
        }

        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 20px;
            transition: margin-left 0.3s ease;
        }
        iframe {
            width: 100%;
            height: calc(100vh - 20px);
            border: none;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        }

        @media screen and (max-width: 768px) {
            .sidebar {
                width: 0;
                overflow: hidden;
            }
            .toggle-btn {
                display: block;
            }
            .mobile-nav {
                display: flex;
            }
            .main-content {
                margin-left: 0;
                margin-top: 70px;
            }
        }
    </style>
</head>
<body>
    <div class="toggle-btn" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </div>

    <div class="sidebar" id="sidebar">
        <ul>
            <li><h2>SIASET</h2></li>
            <li onclick="loadPage('aregister.php')">
                <i class="fas fa-box"></i> tambah user
            </li>
            <li onclick="loadPage('viewadmin.php')">
                <i class="fas fa-undo"></i> daftar user
            </li>
                        <li onclick="loadPage('logs.php')">




            
                <i class="fas fa-history"></i> Riwayat
            </li>
            <li onclick="loadPage('user.php')">
                <i class="fas fa-user"></i> Profile
            </li>
        </ul>
    </div>

    <div class="mobile-nav">
        <ul>
            <li>SIASET</li>
            <li onclick="loadPage('aregister.php')">
                <i class="fas fa-box"></i>
            </li>
            <li onclick="loadPage('viewadmin.php')">
                <i class="fas fa-undo"></i>
            </li>
            <li onclick="loadPage('logs.php')">
                <i class="fas fa-history"></i>
            </li>
            <li onclick="loadPage('user.php')">
                <i class="fas fa-user"></i>
            </li>
        </ul>
    </div>

    <div class="main-content">
        <iframe id="contentFrame" src="viewadmin.php"></iframe>
    </div>

    <script>
        
        function loadPage(page) {
            document.getElementById('contentFrame').src = page;
        }

        function toggleSidebar() {
            var sidebar = document.getElementById('sidebar');
            if (sidebar.style.width === '250px' || sidebar.style.width === '') {
                sidebar.style.width = '0';
            } else {
                sidebar.style.width = '250px';
            }
        }
    </script>
</body>
</html>
