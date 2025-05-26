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
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIASET - Sistem Informasi Aset</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* General Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            display: flex;
            min-height: 100vh;
            background: #f8f9fa;
            color: #333;
            overflow-x: hidden;
        }

        /* Sidebar Navigation */
        .sidebar {
            width: 250px;
            background: #469CED;
            color: white;
            height: 100vh;
            position: fixed;
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }

        .sidebar.minimized {
            width: 60px;
        }

        .sidebar-header {
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header h1 {
            font-size: 20px;
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .minimize-btn {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            padding: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .minimize-btn:hover {
            opacity: 0.8;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
            overflow-y: auto;
        }

        .sidebar li {
            padding: 15px 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            transition: background 0.3s;
            white-space: nowrap;
        }

        .sidebar li:hover {
            background: #3a86ca;
        }

        .sidebar li i {
            margin-right: 15px;
            font-size: 20px;
            min-width: 20px;
        }

        .sidebar.minimized li span {
            display: none;
        }

        .sidebar.minimized li i {
            margin-right: 0;
        }

        /* Mobile Navigation */
        .mobile-nav {
            display: none;
            background: #469CED;
            color: white;
            padding: 15px;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .mobile-nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            justify-content: space-around;
            align-items: center;
        }

        .mobile-nav li {
            padding: 8px;
            cursor: pointer;
            font-size: 18px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .mobile-nav li i {
            margin-bottom: 4px;
        }

        .mobile-nav li:hover {
            opacity: 0.8;
        }

        /* Main Content */
        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 20px;
            transition: margin-left 0.3s ease;
            width: calc(100% - 250px);
        }

        .main-content.expanded {
            margin-left: 60px;
            width: calc(100% - 60px);
        }

        .content-wrapper {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            height: calc(100vh - 40px);
            overflow: hidden;
        }

        iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        /* Responsive Styles */
        @media screen and (max-width: 768px) {
            body {
                flex-direction: column;
            }

            .sidebar {
                display: none;
            }

            .mobile-nav {
                display: block;
            }

            .main-content, .main-content.expanded {
                margin-left: 0;
                margin-top: 60px;
                width: 100%;
                padding: 10px;
            }

            .content-wrapper {
                height: calc(100vh - 80px);
            }
        }

        /* Additional Mobile Optimizations */
        @media screen and (max-width: 480px) {
            .mobile-nav {
                padding: 10px;
            }

            .mobile-nav li {
                font-size: 16px;
                padding: 5px;
            }

            .main-content {
                padding: 5px;
            }

            .content-wrapper {
                border-radius: 5px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar Navigation (Desktop) -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h1>SIASET</h1>
            <button class="minimize-btn" onclick="toggleSidebar()">
                <i class="fas fa-chevron-left" id="toggleIcon"></i>
            </button>
        </div>
        <ul>
            <li onclick="loadPage('pinjam.php')">
                <i class="fas fa-box"></i>
                <span>Pinjam</span>
            </li>
            <li onclick="loadPage('kembali.php')">
                <i class="fas fa-undo"></i>
                <span>Kembalikan</span>
            </li>
            <li onclick="loadPage('daftarpinjam.php')">
                <i class="fas fa-history"></i>
                <span>Riwayat</span>
            </li>
            <li onclick="loadPage('user.php')">
                <i class="fas fa-user"></i>
                <span>Profile</span>
            </li>
        </ul>
    </div>

    <!-- Mobile Navigation -->
    <div class="mobile-nav">
        <ul>
            <li onclick="loadPage('pinjam.php')">
                <i class="fas fa-box"></i>
                <span>Pinjam</span>
            </li>
            <li onclick="loadPage('kembali.php')">
                <i class="fas fa-undo"></i>
                <span>Kembali</span>
            </li>
            <li onclick="loadPage('daftarpinjam.php')">
                <i class="fas fa-history"></i>
                <span>Riwayat</span>
            </li>
            <li onclick="loadPage('user.php')">
                <i class="fas fa-user"></i>
                <span>Profile</span>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <div class="content-wrapper">
            <iframe id="contentFrame" src="pinjam.php"></iframe>
        </div>
    </div>

    <script>
        // Load page dynamically in iframe
        function loadPage(page) {
            document.getElementById('contentFrame').src = page;
        }

        // Toggle Sidebar
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            const toggleIcon = document.getElementById('toggleIcon');

            sidebar.classList.toggle('minimized');
            mainContent.classList.toggle('expanded');
            
            // Rotate icon based on sidebar state
            if (sidebar.classList.contains('minimized')) {
                toggleIcon.classList.remove('fa-chevron-left');
                toggleIcon.classList.add('fa-chevron-right');
            } else {
                toggleIcon.classList.remove('fa-chevron-right');
                toggleIcon.classList.add('fa-chevron-left');
            }
        }

        // Handle window resize
        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            
            if (window.innerWidth <= 768) {
                sidebar.classList.remove('minimized');
                mainContent.classList.remove('expanded');
            }
        });
    </script>
</body>
</html>

