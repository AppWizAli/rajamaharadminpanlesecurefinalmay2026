<?php
include "config.php";

$user_id = intval($_POST['user_id'] ?? 0);
$group_id = intval($_POST['group_id'] ?? 0);

if (!$user_id || !$group_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

$stmt = $conn->prepare("SELECT start_date, end_date FROM group_members WHERE user_id = ? AND group_id = ?");
$stmt->bind_param("ii", $user_id, $group_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $row = $result->fetch_assoc()) {
    echo json_encode([
        'status' => 'success',
        'start_date' => $row['start_date'],
        'end_date' => $row['end_date']
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Subscription not found']);
}

$stmt->close();
$conn->close();
?>
