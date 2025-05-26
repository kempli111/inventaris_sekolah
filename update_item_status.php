<?php
include "config.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = array();
    
    if (isset($_POST['item_id']) && isset($_POST['new_status'])) {
        $item_id = intval($_POST['item_id']);
        $new_status = ($_POST['new_status'] === 'bisadipinjam') ? 'bisadipinjam' : 'tidakbisadipinjam';
        
        $stmt = $conn->prepare("UPDATE items SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $item_id);
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = "Status berhasil diubah";
            $response['new_status'] = $new_status;
        } else {
            $response['success'] = false;
            $response['message'] = "Gagal mengubah status";
        }
        $stmt->close();
    } else {
        $response['success'] = false;
        $response['message'] = "Data tidak lengkap";
    }
    
    echo json_encode($response);
} else {
    http_response_code(405);
    echo json_encode(array('success' => false, 'message' => 'Method not allowed'));
}
?> 