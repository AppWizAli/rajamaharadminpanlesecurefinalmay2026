<?php
// Include your database connection file (config.php or similar)
include "config.php";

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set the content type to application/json
header("Content-Type: application/json");

// Retrieve the raw POST data
$input = file_get_contents("php://input");

// Debugging: Print the raw input to the error log
file_put_contents('php://stderr', "Raw input: $input\n");

// Decode the JSON input
$data = json_decode($input, true);

// Debugging: Print the decoded data to the error log
file_put_contents('php://stderr', "Decoded data: " . print_r($data, true) . "\n");

// Check if the request method is POST and data is not empty
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($data)) {
    $user_id = isset($data['id']) ? $data['id'] : '';

    // Validate the input
    if (!empty($user_id)) {
        // Prepare SQL statement to update logged_number
        $update_sql = "UPDATE users SET logged_number = 0 WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("i", $user_id);

        if ($stmt->execute()) {
            echo json_encode(["message" => "Successfully logged out"]);
        } else {
            echo json_encode(["error" => "Failed to update logged number"]);
        }

        $stmt->close();
    } else {
        echo json_encode(["error" => "User ID is required"]);
    }
} else {
    echo json_encode(["error" => "Invalid request method or empty input"]);
}

// Close database connection
$conn->close();
?>
