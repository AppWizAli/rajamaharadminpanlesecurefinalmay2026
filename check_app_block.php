<?php
include "config.php";
include "security_schema.php";

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

ensure_security_tables($conn);

$data = json_decode(file_get_contents("php://input"), true);
if (!is_array($data)) {
    $data = $_POST;
}

$user_id = isset($data['user_id']) ? intval($data['user_id']) : 0;
if ($user_id <= 0) {
    echo json_encode(["status" => false, "is_blocked" => false, "message" => "Invalid user"]);
    exit;
}

$stmt = $conn->prepare("SELECT is_blocked, message FROM user_security_blocks WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $message = trim($row['message'] ?? '');
    if ($message === '') {
        $message = "Your app access is currently paused. Please contact Urdu Bolo support.";
    }
    echo json_encode([
        "status" => true,
        "is_blocked" => intval($row['is_blocked']) === 1,
        "message" => $message
    ]);
} else {
    echo json_encode(["status" => true, "is_blocked" => false, "message" => ""]);
}

$stmt->close();
$conn->close();
?>
