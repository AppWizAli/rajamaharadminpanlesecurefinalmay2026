<?php
header("Content-Type: application/json");
include "auth_token_check.php";
// Database configuration
include "config.php";



// Read the JSON data from POST request
$data = json_decode(file_get_contents("php://input"), true);

// Ensure drama_id is provided and is a valid integer
$drama_id = isset($data['drama_id']) ? intval($data['drama_id']) : 0;

if ($drama_id > 0) {
    // Fetch seasons for the specific drama_id
    $sql = "SELECT * FROM season WHERE drama_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $drama_id);
} else {
    // If no valid drama_id is provided, return an error message
    echo json_encode(array("error" => "Invalid or missing drama ID."));
    exit;
}

// Execute the query
$stmt->execute();
$result = $stmt->get_result();

$seasons = array();
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $seasons[] = $row;
    }
} else {
    $seasons = array("message" => "No seasons found for the provided drama ID.");
}

echo json_encode($seasons);

$conn->close();
?>
