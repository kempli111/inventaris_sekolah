<?php
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = $_POST["token"];
    $new_password = $_POST["new_password"];

    if (strlen($new_password) < 6) {
        die("Password harus minimal 6 karakter.");
    }

    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

    $query = "SELECT * FROM users WHERE reset_token = ? AND token_expiry > NOW()";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $resetLink = "http://localhost/reset_password.php?token=" . $token;


    if ($result->num_rows > 0) {
        // Update password dan hapus token
        $updateQuery = "UPDATE users SET password = ?, reset_token = NULL, token_expiry = NULL WHERE reset_token = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("ss", $hashed_password, $token);
        if ($stmt->execute()) {
            echo "Password berhasil diubah. Silakan login.";
        } else {
            echo "Gagal mengupdate password.";
        }
    } else {
        echo "Token tidak valid atau sudah kedaluwarsa.";
    }
}
?>
