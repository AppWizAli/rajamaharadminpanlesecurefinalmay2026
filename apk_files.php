<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

include "config.php";
include "apk_schema.php";

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

ensure_apk_table($conn);

function public_base_url() {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    return $host ? $scheme . '://' . $host . $dir . '/' : '';
}

if ($conn->connect_error) {
    echo json_encode([]);
    exit;
}

$sql = "
    SELECT `string`, version_name, version_code, apk_url, original_name, file_size, created_at
    FROM apk_files
    WHERE is_active = 1
    ORDER BY created_at DESC
    LIMIT 1
";
$result = $conn->query($sql);

$response = [];
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $row['string'] = $row['version_name'] ?: $row['string'];
    $row['apk_url'] = ltrim(str_replace('\\', '/', $row['apk_url']), '/');
    $row['download_url'] = public_base_url() . $row['apk_url'];
    $row['is_latest'] = true;
    $response[] = $row;
}

echo json_encode($response);
$conn->close();
?>
