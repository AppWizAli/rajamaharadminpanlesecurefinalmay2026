<?php
include "config.php";
include "auth_token_check.php";

header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => false, "message" => "Invalid request method."]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$user_id = isset($input['user_id']) ? intval($input['user_id']) : 0;
$username = isset($input['username']) ? trim($input['username']) : '';
$message = isset($input['message']) ? trim($input['message']) : '';

if ($user_id <= 0 || $message === '') {
    echo json_encode(["status" => false, "message" => "Invalid data."]);
    exit;
}

if (empty($FCM_SERVER_KEY)) {
    echo json_encode(["status" => false, "message" => "FCM server key not configured."]);
    exit;
}

$payload = [
    "to" => "/topics/" . $ADMIN_FCM_TOPIC,
    "notification" => [
        "title" => "New Message",
        "body" => $username !== '' ? ($username . ": " . $message) : $message
    ],
    "data" => [
        "user_id" => $user_id,
        "username" => $username,
        "message" => $message
    ]
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://fcm.googleapis.com/fcm/send");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: key=" . $FCM_SERVER_KEY,
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo json_encode(["status" => true, "message" => "Notification sent"]);
} else {
    echo json_encode(["status" => false, "message" => "FCM send failed", "debug" => $result]);
}
?>
