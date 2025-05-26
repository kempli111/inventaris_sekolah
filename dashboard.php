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
  <title>Dashboard</title>
  <!-- Font Awesome untuk ikon -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    /* Reset dasar dan font */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
    }
    /* Background halaman untuk light mode */
    body {
      transition: background 0.3s ease, color 0.3s ease;
      /* Ganti dengan URL background light mode jika perlu */
      background-size: cover;
      background-position: center;
      overflow: hidden;
      position: relative;
    }
    /* Jika tema dark, background akan dikelola oleh canvas dan style dark */
    body.dark {
      background: #000;
      color: #fff;
    }
    /* Canvas untuk animasi luar angkasa (hanya tampil di dark mode) */
    #spaceCanvas {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: -2;
    }
    /* Sidebar / Navbar */
    .sidebar {
      width: 250px;
      height: 100vh;
      padding: 20px;
      position: fixed;
      transition: width 0.3s ease, background-color 0.3s ease;
      display: flex;
      flex-direction: column;
      overflow: hidden;
      /* Warna background default untuk light mode */
      background-color: rgba(70, 156, 237, 0.9);
    }
    /* Penyesuaian warna untuk dark mode */
    body.dark .sidebar {
      backdrop-filter: blur(5px);
      background-color: #1E1E1E;
    }
    /* Gaya untuk sidebar yang minimized */
    .sidebar.minimized {
      width: 60px;
      padding: 20px 5px; /* Mengurangi padding agar tampilan lebih proporsional */
    }
    .sidebar h2 {
      text-align: center;
      font-size: 1.5rem;
      color: white;
      margin-bottom: 20px;
      transition: opacity 0.3s ease;
    }
    /* Sembunyikan elemen tertentu saat minimized */
    .sidebar.minimized h2, .sidebar.minimized .link-text {
      opacity: 0;
      transition: opacity 0.3s ease;
    }
    .sidebar ul {
      list-style: none;
      padding: 0;
    }
    .sidebar ul li {
      margin: 15px 0;
    }
    .sidebar ul li a {
      color: inherit;
      text-decoration: none;
      font-size: 1.1rem;
      display: flex;
      align-items: center;
      padding: 12px;
      color: white;
      border-radius: 8px;
      transition: background 0.3s ease;
    }
    .sidebar ul li a i {
      margin-right: 12px;
      min-width: 20px;
      text-align: center;
    }
    .sidebar ul li a:hover {
      background: rgba(255, 255, 255, 0.2);
    }
    .toggle-btn {
      background: none;
      border: none;
      color: white;
      font-size: 1.8rem;
      cursor: pointer;
      align-self: flex-start;
      margin-bottom: 20px;
    }
    /* Penyesuaian konten ketika sidebar dalam keadaan minimized */
    .content {
      margin-left: 250px;
      flex-grow: 1;
      padding: 20px;
      transition: margin-left 0.3s ease;
    }
    .content.minimized {
      margin-left: 60px;
    }
    iframe {
      width: 100%;
      height: calc(100vh - 40px);
      border: none;
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    /* Custom Switch Theme */
    .theme-switch {
      display: flex;
      align-items: center;
      cursor: pointer;
      margin-top: auto;
      position: relative;
      transition: all 0.3s;
      height: 40px;
      justify-content: center;
    }
    .theme-switch .switch {
      position: relative;
      display: inline-block;
      width: 50px;
      height: 26px;
      background: #ddd;
      border-radius: 26px;
      transition: background 0.3s;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    .theme-switch .switch input {
      opacity: 0;
      width: 0;
      height: 0;
    }
    .theme-switch .slider {
      position: absolute;
      cursor: pointer;
      top: 2px;
      left: 2px;
      right: 2px;
      bottom: 2px;
      background: #fff;
      border-radius: 50%;
      width: 22px;
      height: 22px;
      transition: 0.3s;
      box-shadow: 0 1px 4px rgba(0,0,0,0.12);
      z-index: 2;
    }
    .theme-switch .switch input:checked + .slider {
      transform: translateX(24px);
      background: #222;
    }
    .theme-switch .switch input:checked ~ .switch-bg {
      background: #222;
    }
    .theme-switch .switch .icon {
      position: absolute;
      top: 3px;
      width: 20px;
      height: 20px;
      z-index: 1;
      transition: opacity 0.3s;
      pointer-events: none;
    }
    .theme-switch .switch .icon.sun {
      left: 5px;
      color: #f7c948;
      opacity: 1;
    }
    .theme-switch .switch input:checked ~ .icon.sun {
      opacity: 0;
    }
    .theme-switch .switch .icon.moon {
      right: 5px;
      color: #8ecae6;
      opacity: 0;
    }
    .theme-switch .switch input:checked ~ .icon.moon {
      opacity: 1;
    }
    /* Clickbox untuk mode minimized */
    .theme-switch .clickbox {
      display: none;
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: #fff;
      box-shadow: 0 2px 8px rgba(0,0,0,0.12);
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: background 0.3s;
      position: relative;
    }
    .theme-switch .clickbox .icon {
      font-size: 1.3rem;
      color: #222;
      transition: color 0.3s;
    }
    .sidebar.minimized .theme-switch .switch {
      display: none;
    }
    .sidebar.minimized .theme-switch .clickbox {
      display: flex;
    }
    .sidebar.minimized .theme-switch {
      justify-content: center;
    }
    .sidebar.minimized .theme-switch {
      margin-top: 0;
    }
  </style>
</head>
<body class="<?php echo $theme; ?>">
  <!-- Jika dark mode, tampilkan canvas untuk animasi -->
  <?php if($theme === 'dark'): ?>
    <canvas id="spaceCanvas"></canvas>
  <?php endif; ?>
  
  <div class="sidebar">
    <button class="toggle-btn"><i class="fas fa-bars"></i></button>
    <h2>Dashboard</h2>
    <ul>
      <li><a href="isi.php"><i class="fas fa-home"></i> <span class="link-text">View</span></a></li>
      <li><a href="ruangan.php"><i class="fas fa-building"></i> <span class="link-text">Ruangan</span></a></li>
      <li><a href="item.php"><i class="fas fa-box"></i> <span class="link-text">Item</span></a></li>
      <li><a href="pinjamadmin.php"><i class="	fas fa-backspace"></i> <span class="link-text">Peminjaman</span></a></li>
      <li><a href="user.php"><i class="fas fa-user"></i> <span class="link-text">User</span></a></li>
      <li></li>
    </ul>
    <!-- Custom Switch Theme -->
    <div class="theme-switch">
      <label class="switch">
        <input type="checkbox" id="themeToggle" <?php echo ($theme === 'dark') ? 'checked' : ''; ?>>
        <span class="slider"></span>
        <i class="fas fa-sun icon sun"></i>
        <i class="fas fa-moon icon moon"></i>
      </label>
      <div class="clickbox" id="themeClickbox" title="Ganti Mode">
        <i class="fas fa-sun icon sun" style="display: <?php echo ($theme === 'dark') ? 'none' : 'inline'; ?>;"></i>
        <i class="fas fa-moon icon moon" style="display: <?php echo ($theme === 'dark') ? 'inline' : 'none'; ?>;"></i>
      </div>
    </div>
  </div>
  <div class="content">
    <iframe id="content-frame" src="isi.php"></iframe>
  </div>
  <script>
    // Toggle sidebar dan sesuaikan margin konten
    document.querySelector('.toggle-btn').addEventListener('click', function() {
      let sidebar = document.querySelector('.sidebar');
      let content = document.querySelector('.content');
      sidebar.classList.toggle('minimized');
      content.classList.toggle('minimized');
    });
    
    // Ubah konten iframe jika link sidebar diklik
    document.querySelectorAll('.sidebar ul li a').forEach(link => {
      link.addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('content-frame').src = this.getAttribute('href');
      });
    });
    
    // Dark mode switch handler
    function setThemeSwitchUI(newTheme) {
      // Update icon pada clickbox
      const sun = document.querySelector('.clickbox .sun');
      const moon = document.querySelector('.clickbox .moon');
      if (newTheme === 'dark') {
        sun.style.display = 'none';
        moon.style.display = 'inline';
      } else {
        sun.style.display = 'inline';
        moon.style.display = 'none';
      }
    }
    document.getElementById('themeToggle').addEventListener('change', function() {
      let newTheme = this.checked ? 'dark' : 'light';
      document.body.className = newTheme;
      setThemeSwitchUI(newTheme);
      // Kirim data ke PHP dengan AJAX untuk update di database dan session
      let xhr = new XMLHttpRequest();
      xhr.open("POST", "save_theme.php", true);
      xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
      xhr.send("theme=" + newTheme);
      // Jika beralih ke dark mode, tambahkan canvas jika belum ada
      if(newTheme === 'dark' && !document.getElementById('spaceCanvas')){
          let canvas = document.createElement('canvas');
          canvas.id = 'spaceCanvas';
          document.body.appendChild(canvas);
          initSpaceAnimation();
      } else if(newTheme === 'light'){
          let canvas = document.getElementById('spaceCanvas');
          if(canvas) { canvas.remove(); }
      }
      // Reload iframe agar tampilan pada iframe ikut berubah
      document.getElementById('content-frame').contentWindow.location.reload();
    });
    // Clickbox handler untuk mode minimized
    document.getElementById('themeClickbox').addEventListener('click', function() {
      let themeToggle = document.getElementById('themeToggle');
      themeToggle.checked = !themeToggle.checked;
      themeToggle.dispatchEvent(new Event('change'));
    });
    
    // Jika tema dark, inisialisasi animasi luar angkasa
    <?php if($theme === 'dark'): ?>
      initSpaceAnimation();
    <?php endif; ?>
    
    // Fungsi untuk animasi bintang dan comet pada canvas
    function initSpaceAnimation() {
      const canvas = document.getElementById('spaceCanvas');
      if(!canvas) return;
      const ctx = canvas.getContext('2d');
      canvas.width = window.innerWidth;
      canvas.height = window.innerHeight;
      
      // Array untuk bintang
      let stars = [];
      for(let i = 0; i < 150; i++){
        stars.push({
          x: Math.random() * canvas.width,
          y: Math.random() * canvas.height,
          radius: Math.random() * 1.5,
          alpha: Math.random()
        });
      }
      
      // Fungsi untuk menggambar bintang
      function drawStars() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        for(let star of stars){
          ctx.save();
          ctx.globalAlpha = star.alpha;
          ctx.beginPath();
          ctx.arc(star.x, star.y, star.radius, 0, Math.PI * 2);
          ctx.fillStyle = '#fff';
          ctx.fill();
          ctx.restore();
        }
      }
      
      // Animasi comet
      let comet = {
        x: canvas.width,
        y: 0,
        length: 150,
        speed: 10,
        angle: Math.PI / 4
      };
      
      function drawComet(){
        ctx.save();
        ctx.beginPath();
        ctx.moveTo(comet.x, comet.y);
        let endX = comet.x - comet.length * Math.cos(comet.angle);
        let endY = comet.y + comet.length * Math.sin(comet.angle);
        ctx.lineTo(endX, endY);
        let grad = ctx.createLinearGradient(comet.x, comet.y, endX, endY);
        grad.addColorStop(0, 'rgba(255,255,255,1)');
        grad.addColorStop(1, 'rgba(255,255,255,0)');
        ctx.strokeStyle = grad;
        ctx.lineWidth = 2;
        ctx.stroke();
        ctx.restore();
        
        // Update posisi comet
        comet.x -= comet.speed;
        comet.y += comet.speed;
        if(comet.x < -comet.length || comet.y > canvas.height + comet.length){
          comet.x = canvas.width;
          comet.y = 0;
        }
      }
      
      // Loop animasi
      function animate(){
        drawStars();
        drawComet();
        requestAnimationFrame(animate);
      }
      
      animate();
      
      // Sesuaikan ukuran canvas ketika window diresize
      window.addEventListener('resize', function(){
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
      });
    }
  </script>
</body>
</html>
