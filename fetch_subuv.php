<?php
include "config.php";

header('Content-Type: application/json');

if (!isset($_GET['user_id']) || !isset($_GET['video_id'])) {
    echo json_encode([
        "status" => false,
        "message" => "Missing user_id or video_id"
    ]);
    exit;
}

$user_id = intval($_GET['user_id']);
$video_id = intval($_GET['video_id']);
$today = date('Y-m-d');

$stmt = $conn->prepare("
    SELECT group_id, end_date FROM group_members 
    WHERE user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$status = false;
$message = "No active subscription found or video not available in the group.";

while ($row = $result->fetch_assoc()) {
    $group_id = $row['group_id'];
    $end_date = $row['end_date'];

    if ($end_date) {
        $days_left = (strtotime($end_date) - strtotime($today)) / (60 * 60 * 24);
        $days_left = (int)$days_left;

        if ($days_left >= 0) {
            // Now check if the video is part of this group
            $vstmt = $conn->prepare("
                SELECT id FROM group_videos 
                WHERE group_id = ? AND video_id = ?
            ");
            $vstmt->bind_param("ii", $group_id, $video_id);
            $vstmt->execute();
            $vresult = $vstmt->get_result();

            if ($vresult->num_rows > 0) {
                // Subscription is active AND video is valid
                if ($days_left > 2) {
                    $status = false;
                    $message = "Subscription is active.";
                    break;
                } elseif ($days_left === 2) {
                    $status = true;
                    $message = "Your subscription ends in two days. Please contact us to resubscribe.";
                    break;
                } elseif ($days_left === 1) {
                    $status = true;
                    $message = "Your subscription ends in one day. Please contact us to resubscribe.";
                    break;
                } elseif ($days_left === 0) {
                    $status = true;
                    $message = "Your subscription ends today. Please contact us to resubscribe.";
                    break;
                }
            }

            $vstmt->close();
        }
    }
}
$stmt->close();

echo json_encode([
    "status" => $status,
    "message" => $message
]);
