<?php
include "config.php";
include "auth_token_check.php"; 
include "media_input_helper.php";
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$drama_id = isset($_GET['drama_id']) ? intval($_GET['drama_id']) : 0;

try {
    if ($drama_id > 0) {
        $sql = "SELECT * FROM season WHERE drama_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $drama_id);
    } else {
        $sql = "SELECT * FROM season";
        $stmt = $conn->prepare($sql);
    }
    
    // Execute the query
    $stmt->execute();
    $result = $stmt->get_result();

    $seasons = array();
    while ($row = $result->fetch_assoc()) {
        if (isset($row['thumbnail'])) {
            $row['thumbnail'] = media_to_client_url($row['thumbnail']);
        }
        $seasons[] = $row;
    }
    
    // Return the results as JSON
    echo json_encode([
        "success" => true,
        "seasons" => $seasons
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}

// Close statement and connection
$stmt->close();
$conn->close();
?>
