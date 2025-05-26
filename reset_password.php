<?php
include 'config.php';

$token = $_GET['token'] ?? '';

if ($token) {
    $query = "SELECT * FROM users WHERE reset_token = ? AND token_expiry > NOW()";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        die("Token tidak valid atau sudah kedaluwarsa.");
    }
} else {
    die("Token tidak ditemukan.");
}
?>

<form method="POST" action="reset_password_process.php">
    <input type="hidden" name="token" value="<?= htmlspecialchars($token); ?>">
    <input type="password" name="new_password" placeholder="Masukkan password baru" required>
    <button type="submit">Reset Password</button>
</form>
