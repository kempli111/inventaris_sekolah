<?php
include "config.php"; // Koneksi database

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validasi input
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Format email tidak valid!");
    }
    if (strlen($password) < 6) {
        die("Password minimal 6 karakter!");
    }

    $hashed_password = password_hash($password, PASSWORD_BCRYPT); // Hash password

    // Cek apakah username atau email sudah ada
    $check_user = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $check_user->bind_param("ss", $username, $email);
    $check_user->execute();
    $check_user->store_result();

    if ($check_user->num_rows > 0) {
        echo "Username atau Email sudah digunakan!";
    } else {
        // Simpan user ke database
        $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $email, $hashed_password);

        if ($stmt->execute()) {
            echo "Registrasi berhasil! <a href='login.php'>Login</a>";
        } else {
            echo "Terjadi kesalahan saat registrasi!";
        }
        
        $stmt->close();
    }

    $check_user->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="css/Register.css">
    <style>
       /* CSS untuk Pesan */
       .message-box {
        display: none; /* Pesan tidak muncul saat pertama kali */
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background-color: rgba(0, 0, 0, 0.8);
        color: white;
        padding: 20px;
        border-radius: 5px;
        font-size: 16px;
        text-align: center;
        z-index: 1000;
      }

      /* CSS untuk Background */
      body {
        font-family: Arial, sans-serif;
      }

    </style>
</head>
<body>
<div class="container">
  <div class="register-left">
    <div class="card">
      <h1 class="title">Register</h1>
      <p class="desc">
        Silahkan Mengisi Form 
      </p>
    </div>
  </div>
  <div class="register-right">
    <div class="card">
      <div class="card--desc">
        <h2>Register</h2>
        <p>
         Sudah Mempunyai Akun?
          <a href="login.php" class="policy">Login</a>
        </p>
      </div>
      <div class="card--register">
        <form id="registerForm" action="" method="POST">
          <input type="text" name="username" placeholder="Username" required />
          <input type="email" name="email" placeholder="Email" required />
          <input type="password" name="password" placeholder="Password" required />
          <div class="agree">
            <input type="checkbox" id="agree" name="agree" /><label for="agree">I accept the Terms of Service</label>
          </div>
          <input type="button" value="Submit" onclick="validateForm()" />
        </form>
      </div>
    </div>
  </div>
</div>


<div id="messageBox" class="message-box">
  You must accept the Terms of Service to continue.
</div>

<script>
  function validateForm() {
    var agreeCheckbox = document.getElementById("agree");
    var messageBox = document.getElementById("messageBox");

    if (!agreeCheckbox.checked) {
      // Tampilkan pesan
      messageBox.style.display = "block";

      // Sembunyikan pesan setelah 3 detik
      setTimeout(function() {
        messageBox.style.display = "none";
      }, 3000);
    } else {
      document.getElementById("registerForm").submit();  // Submit form jika checkbox dicentang
    }
  }
</script>

</body>
</html>
