<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php'; // Pastikan path sudah benar
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];

    $query = "SELECT id FROM users WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $token = bin2hex(random_bytes(50));
        $expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));

        // Update token di database
        $updateQuery = "UPDATE users SET reset_token = ?, token_expiry = ? WHERE email = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("sss", $token, $expiry, $email);
        $stmt->execute();

        // Link Reset Password
        $resetLink = "http://localhost/reset_password.php?token=" . $token;

        // Konfigurasi SMTP PHPMailer
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'kempligimang@gmail.com'; 
            $mail->Password = 'xahu lrdd wmnw sftr';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('your-email@gmail.com', 'Admin Inventaris');
            $mail->addAddress($email);
            $mail->Subject = "Reset Password";
            $mail->Body = "Klik link ini untuk reset password: $resetLink";

            if ($mail->send()) {
                echo "Email reset password telah dikirim.";
            } else {
                echo "Gagal mengirim email. Error: " . $mail->ErrorInfo;
            }
        } catch (Exception $e) {
            echo "Email gagal dikirim. Error: " . $mail->ErrorInfo;
        }
    } else {
        echo "Email tidak ditemukan.";
    }
}
?>
