<?php
include "config.php";

$message = '';

// Proses form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Validasi sederhana
    if (empty($username) || empty($email) || empty($password) || empty($role)) {
        $message = "Semua field harus diisi!";
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Format email tidak valid!";
    } else {
        // Cek apakah username atau email sudah ada
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt_check->bind_param("ss", $username, $email);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $message = "Username atau Email sudah terdaftar!";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert user baru ke database
            $stmt_insert = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt_insert->bind_param("ssss", $username, $email, $hashed_password, $role);

            if ($stmt_insert->execute()) {
                $message = "Pengguna baru berhasil ditambahkan!";
                // Opsional: Redirect ke halaman daftar pengguna setelah berhasil
                // header("Location: viewadmin.php");
                // exit();
            } else {
                $message = "Error: " . $stmt_insert->error;
            }
            $stmt_insert->close();
        }
        $stmt_check->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Pengguna Baru</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        form {
            max-width: 400px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        div {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"],
        select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background-color: #0056b3;
        }
        .message {
            margin-top: 15px;
            padding: 10px;
            border-radius: 4px;
            text-align: center;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
    </style>
</head>
<body>

<h2>Tambah Pengguna Baru</h2>

<form method="post" action="">
    <?php if (!empty($message)): ?>
        <div class="message <?php echo (strpos($message, 'berhasil') !== false) ? 'success' : 'error'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div>
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required>
    </div>

    <div>
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
    </div>

    <div>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
    </div>

    <div>
        <label for="role">Role:</label>
        <select id="role" name="role" required>
            <option value="user">User</option>
            <option value="admin">Admin</option>
        </select>
    </div>

    <button type="submit">Tambah Pengguna</button>
</form>

<p style="text-align: center; margin-top: 20px;"><a href="viewadmin.php">Kembali ke Daftar Pengguna</a></p>

</body>
</html> 