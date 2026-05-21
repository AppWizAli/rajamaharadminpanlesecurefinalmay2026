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
$app_version_code = isset($data['app_version_code']) ? intval($data['app_version_code']) : null;
$package_name = trim($data['package_name'] ?? '');
$device_id = trim($data['device_id'] ?? '');
$device_brand = trim($data['device_brand'] ?? '');
$device_product = trim($data['device_product'] ?? '');
$device_hardware = trim($data['device_hardware'] ?? '');
$device_fingerprint = trim($data['device_fingerprint'] ?? '');
$latitude = isset($data['latitude']) && $data['latitude'] !== '' ? floatval($data['latitude']) : null;
$longitude = isset($data['longitude']) && $data['longitude'] !== '' ? floatval($data['longitude']) : null;
$location_accuracy = isset($data['location_accuracy']) && $data['location_accuracy'] !== '' ? floatval($data['location_accuracy']) : null;
$extra = trim($data['extra'] ?? '');
$ip_address = $_SERVER['REMOTE_ADDR'] ?? '';

if ($incident_type === '') {
    $incident_type = 'unknown';
}

$ignoredTypes = ['app_start', 'location_permission_denied'];
if (in_array(strtolower($incident_type), $ignoredTypes, true)) {
    echo json_encode(["status" => true, "message" => "Incident ignored"]);
    $conn->close();
    exit;
}

$criticalTypes = ['screen_recording', 'screen_capture', 'root_device', 'usb_connection_detected', 'debugger_detected', 'tamper_detected', 'uncaught_exception'];
$warningTypes = ['player_error', 'offline_player_error', 'download_denied', 'location_permission_denied', 'app_block_check_failed', 'caught_exception', 'network_error'];
$severity = 'info';
if (in_array(strtolower($incident_type), $criticalTypes, true)) {
    $severity = 'critical';
} elseif (in_array(strtolower($incident_type), $warningTypes, true)) {
    $severity = 'warning';
}

$stmt = $conn->prepare("
    INSERT INTO security_incidents
    (user_id, incident_type, incident_label, app_area, device_model, manufacturer, android_version, app_version, app_version_code, package_name, device_id, device_brand, device_product, device_hardware, device_fingerprint, latitude, longitude, location_accuracy, extra, ip_address, severity)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "isssssssissssssdddsss",
    $user_id,
    $incident_type,
    $incident_label,
    $app_area,
    $device_model,
    $manufacturer,
    $android_version,
    $app_version,
    $app_version_code,
    $package_name,
    $device_id,
    $device_brand,
    $device_product,
    $device_hardware,
    $device_fingerprint,
    $latitude,
    $longitude,
    $location_accuracy,
    $extra,
    $ip_address,
    $severity
);

if ($stmt->execute()) {
    echo json_encode(["status" => true, "message" => "Incident saved"]);
} else {
    echo json_encode(["status" => false, "message" => "Incident could not be saved"]);
}

$stmt->close();
$conn->close();
?>
