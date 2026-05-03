<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
include "auth_token_check.php"; 
include "config.php";

$sql = "
    SELECT td.id, td.drama_id, d.name, d.thumbnail, td.position
    FROM trending_dramas td
    JOIN drama d ON td.drama_id = d.id
    ORDER BY td.position ASC
";

$result = $conn->query($sql);

$data = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
} else {
    $data = ["message" => "No trending dramas found."];
}

echo json_encode($data);
$conn->close();
?>
