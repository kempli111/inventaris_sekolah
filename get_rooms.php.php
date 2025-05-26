<?php

include "config.php";


$sql_rooms = "SELECT rooms.id, rooms.name AS room_name, rooms.location, 
                     COALESCE(SUM(items.total), 0) AS total_quantity 
              FROM rooms 
              LEFT JOIN items ON rooms.id = items.room_id 
              GROUP BY rooms.id";

$result_rooms = $conn->query($sql_rooms);

$rooms = array();
if ($result_rooms->num_rows > 0) {
    while ($row = $result_rooms->fetch_assoc()) {
        $rooms[] = $row;
    }
} else {
    echo json_encode(["error" => "No rooms found"]);
    exit;
}

echo json_encode($rooms);
?>
