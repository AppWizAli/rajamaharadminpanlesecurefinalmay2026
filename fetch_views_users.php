<?php
// Set headers for JSON response
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *'); // Adjust based on security needs
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');
// Include database configuration
include "config.php";
// Consistent response model
function sendResponse($status, $message, $data = null) {
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}
// Ensure $conn is defined in config.php as a mysqli connection
if (!$conn) {
    sendResponse('error', 'Database connection failed', null);
}
// Get and validate input from either GET or POST
$input = json_decode(file_get_contents('php://input'), true);
$video_id = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($input['video_id'])) {
    $video_id = intval($input['video_id']);
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['video_id'])) {
    $video_id = intval($_GET['video_id']);
}
if (!$video_id || $video_id <= 0) {
    sendResponse('error', 'Invalid or missing video ID', null);
}
try {
    // Query to get unique users and their view times for the video
    $stmt = $conn->prepare(
        "SELECT u.id, u.username, u.email, GROUP_CONCAT(v.view_time ORDER BY v.view_time) as view_times
         FROM video_views v
         INNER JOIN users u ON v.user_id = u.id
         WHERE v.video_id = ?
         GROUP BY u.id, u.username, u.email"
    );
   
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . $conn->error);
    }
   
    $stmt->bind_param("i", $video_id);
    $stmt->execute();
    $result = $stmt->get_result();
   
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $row['view_times'] = explode(',', $row['view_times']);
        $users[] = $row;
    }
   
    $stmt->close();
   
    sendResponse('success', 'Users fetched successfully', $users);
} catch (Exception $e) {
    http_response_code(500);
    sendResponse('error', 'Server error: ' . $e->getMessage(), null);
}
// Close database connection
$conn->close();
?>