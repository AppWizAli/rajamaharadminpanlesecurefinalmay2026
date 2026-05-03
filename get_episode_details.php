<?php
// Start session and include database connection
session_start();
include "config.php";

// Set response header to JSON
header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

// Get the period parameter (daily or monthly)
$period = isset($_GET['period']) ? $_GET['period'] : 'daily';

try {
    // Calculate time range based on period
    if ($period === 'daily') {
        $timeCondition = date('Y-m-d H:i:s', strtotime('-24 hours'));
    } else {
        $timeCondition = date('Y-m-01 00:00:00');
    }
    
    // Query to get episode details with view counts
    // Joining video_views with episode table to get all episode information
    $query = "SELECT 
                e.id,
                e.season_id,
                e.episode_number,
                e.video_path,
                e.description,
                e.thumbnail,
                e.privacy,
                e.download_access,
                e.created_at,
                COUNT(vv.id) as view_count
              FROM episode e
              INNER JOIN video_views vv ON e.id = vv.video_id
              WHERE vv.view_time >= ?
              GROUP BY e.id, e.season_id, e.episode_number, e.video_path, 
                       e.description, e.thumbnail, e.privacy, e.download_access, e.created_at
              ORDER BY view_count DESC, e.episode_number DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $timeCondition);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $episodes = [];
    while ($row = $result->fetch_assoc()) {
        $episodes[] = [
            'id' => $row['id'],
            'season_id' => $row['season_id'],
            'episode_number' => $row['episode_number'],
            'video_path' => $row['video_path'],
            'description' => $row['description'] ? $row['description'] : 'No description available',
            'thumbnail' => $row['thumbnail'],
            'privacy' => $row['privacy'],
            'download_access' => $row['download_access'],
            'created_at' => $row['created_at'],
            'view_count' => (int)$row['view_count']
        ];
    }
    
    $stmt->close();
    
    // Return success response with episode data
    echo json_encode([
        'success' => true,
        'episodes' => $episodes,
        'period' => $period,
        'total_episodes' => count($episodes),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'episodes' => []
    ]);
}

// Close database connection
if (isset($conn)) {
    $conn->close();
}
?>