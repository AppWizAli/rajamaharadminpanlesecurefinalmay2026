<?php
session_start();
include "config.php";
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
$group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;

if ($user_id === 0 || $group_id === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user or group ID']);
    exit;
}

// Get current end_date
$stmt = $conn->prepare("SELECT end_date FROM group_members WHERE user_id = ? AND group_id = ?");
$stmt->bind_param("ii", $user_id, $group_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Record not found']);
    $stmt->close();
    $conn->close();
    exit;
}

$current_end_date = $row['end_date'];
$today = new DateTime('today');

if ($current_end_date) {
    $currentDate = new DateTime($current_end_date);
    $baseDate = $currentDate < $today ? $today : $currentDate;
} else {
    $baseDate = $today;
}

$baseDate->add(new DateInterval('P30D'));
$new_end_date = $baseDate->format('Y-m-d');

// Update end_date
$update_stmt = $conn->prepare("UPDATE group_members SET end_date = ?, updated_at = NOW() WHERE user_id = ? AND group_id = ?");
$update_stmt->bind_param("sii", $new_end_date, $user_id, $group_id);

if ($update_stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'End date increased by 30 days', 'end_date' => $new_end_date]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database update failed']);
}

$update_stmt->close();
$stmt->close();
$conn->close();
?>
