<?php
$host = "localhost";
$user = "root";  
$pass = "";     
$dbname = "inventaris_sekolah"; 

$conn = new mysqli($host, $user, $pass, $dbname);


if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

function log_action($conn, $user_id, $action, $table_name, $record_id = null, $old_data = null, $new_data = null) {
    // Ensure user_id is valid
    $userId = ($user_id !== null) ? $user_id : null;

    $stmt = $conn->prepare("INSERT INTO logs (user_id, action, table_name, record_id, old_data, new_data) VALUES (?, ?, ?, ?, ?, ?)");
    // Encode data ke JSON jika tidak null
    $old_data_json = $old_data ? json_encode($old_data) : null;
    $new_data_json = $new_data ? json_encode($new_data) : null;

    // Change binding types for JSON data from 'b' (blob) to 's' (string)
    $stmt->bind_param("isssss", $userId, $action, $table_name, $record_id, $old_data_json, $new_data_json);
    $stmt->execute();
    $stmt->close();
}
?>
