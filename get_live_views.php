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

try {
    // Calculate timestamp for 24 hours ago
    $twentyFourHoursAgo = date('Y-m-d H:i:s', strtotime('-24 hours'));
    
    // Get current month start date
    $currentMonthStart = date('Y-m-01 00:00:00');
    
    // Query for last 24 hours views
    $dailyQuery = "SELECT COUNT(*) as daily_count 
                   FROM video_views 
                   WHERE view_time >= ?";
    
    $stmt = $conn->prepare($dailyQuery);
    $stmt->bind_param("s", $twentyFourHoursAgo);
    $stmt->execute();
    $dailyResult = $stmt->get_result();
    $dailyRow = $dailyResult->fetch_assoc();
    $dailyViews = $dailyRow['daily_count'] ?? 0;
    $stmt->close();
    
    // Query for current month views
    $monthlyQuery = "SELECT COUNT(*) as monthly_count 
                     FROM video_views 
                     WHERE view_time >= ?";
    
    $stmt = $conn->prepare($monthlyQuery);
    $stmt->bind_param("s", $currentMonthStart);
    $stmt->execute();
    $monthlyResult = $stmt->get_result();
    $monthlyRow = $monthlyResult->fetch_assoc();
    $monthlyViews = $monthlyRow['monthly_count'] ?? 0;
    $stmt->close();
    
    // Return success response with counts
    echo json_encode([
        'success' => true,
        'daily_views' => (int)$dailyViews,
        'monthly_views' => (int)$monthlyViews,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'daily_views' => 0,
        'monthly_views' => 0
    ]);
}

// Close database connection
if (isset($conn)) {
    $conn->close();
}
?>