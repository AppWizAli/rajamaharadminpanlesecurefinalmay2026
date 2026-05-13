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

$user_id = isset($data['user_id']) ? intval($data['user_id']) : null;
$incident_type = trim($data['incident_type'] ?? 'unknown');
$incident_label = trim($data['incident_label'] ?? '');
$app_area = trim($data['app_area'] ?? '');
$device_model = trim($data['device_model'] ?? '');
$manufacturer = trim($data['manufacturer'] ?? '');
$android_version = trim($data['android_version'] ?? '');
$app_version = trim($data['app_version'] ?? '');
$device_id = trim($data['device_id'] ?? '');
$latitude = isset($data['latitude']) && $data['latitude'] !== '' ? floatval($data['latitude']) : null;
$longitude = isset($data['longitude']) && $data['longitude'] !== '' ? floatval($data['longitude']) : null;
$extra = trim($data['extra'] ?? '');
$ip_address = $_SERVER['REMOTE_ADDR'] ?? '';

if ($incident_type === '') {
    $incident_type = 'unknown';
}

$stmt = $conn->prepare("
    INSERT INTO security_incidents
    (user_id, incident_type, incident_label, app_area, device_model, manufacturer, android_version, app_version, device_id, latitude, longitude, extra, ip_address)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "issssssssddss",
    $user_id,
    $incident_type,
    $incident_label,
    $app_area,
    $device_model,
    $manufacturer,
    $android_version,
    $app_version,
    $device_id,
    $latitude,
    $longitude,
    $extra,
    $ip_address
);

if ($stmt->execute()) {
    echo json_encode(["status" => true, "message" => "Incident saved"]);
} else {
    echo json_encode(["status" => false, "message" => "Incident could not be saved"]);
}

$stmt->close();
$conn->close();
?>
