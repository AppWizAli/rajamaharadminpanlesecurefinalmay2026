<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
include "auth_token_check.php"; 
include "config.php";
include "media_input_helper.php";

// Home screen should get newest 14, while "View All" can request the complete list.
$scope = isset($_GET['scope']) ? strtolower(trim($_GET['scope'])) : '';
$requestedLimit = isset($_GET['limit']) ? (int) $_GET['limit'] : 0;

$sql = "SELECT id, name, thumbnail, created_at FROM drama ORDER BY created_at DESC, id DESC";
if ($scope !== 'all') {
    $limit = $requestedLimit > 0 ? $requestedLimit : 14;
    $sql .= " LIMIT " . $limit;
}
$result = $conn->query($sql);

$dramas = array();
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        if (isset($row['thumbnail'])) {
            $row['thumbnail'] = media_to_client_url($row['thumbnail']);
        }
        $dramas[] = $row;
    }
} else {
    $dramas = array("message" => "No dramas found.");
}

echo json_encode($dramas);

$conn->close();
?>
