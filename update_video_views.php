<?php
// Set headers for JSON response
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *'); // Adjust based on your security needs
header('Access-Control-Allow-Methods: POST');
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
// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse('error', 'Invalid request method. Use POST.', null);
}
// Get and validate input
$input = json_decode(file_get_contents('php://input'), true);
$video_id = isset($input['id']) ? intval($input['id']) : null;
$user_id = isset($input['user_id']) ? intval($input['user_id']) : null;
if (!$video_id || $video_id <= 0 || !$user_id || $user_id <= 0) {
    sendResponse('error', 'Invalid or missing video ID or user ID', null);
}
try {
    // Prepare query to insert a new view log entry
    // Assumes table 'video_views' with columns: id (INT AUTO_INCREMENT PRIMARY KEY), video_id (INT), user_id (INT), view_time (TIMESTAMP DEFAULT CURRENT_TIMESTAMP)
    $stmt = $conn->prepare(
        "INSERT INTO video_views (video_id, user_id) VALUES (?, ?)"
    );
   
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . $conn->error);
    }
    $stmt->bind_param("ii", $video_id, $user_id);
   
    if ($stmt->execute()) {
        // Get total views for the video
        $total_stmt = $conn->prepare("SELECT COUNT(*) as total_views FROM video_views WHERE video_id = ?");
        $total_stmt->bind_param("i", $video_id);
        $total_stmt->execute();
        $result = $total_stmt->get_result();
        $total_views = $result->fetch_assoc()['total_views'];
        $total_stmt->close();
        
        sendResponse('success', 'View logged successfully', [
            'video_id' => $video_id,
            'user_id' => $user_id,
            'total_views' => $total_views
        ]);
    } else {
        throw new Exception('Failed to execute statement: ' . $stmt->error);
    }
    $stmt->close();
} catch (Exception $e) {
    http_response_code(500);
    sendResponse('error', 'Server error: ' . $e->getMessage(), null);
}
// Close database connection
$conn->close();
?>