<?php
include "config.php";
include "subscription_schema.php";

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

$schemaStatus = ensure_subscription_tables($conn);
if (!empty($schemaStatus['message'])) {
    echo json_encode(["status" => false, "message" => "Subscription setup issue: " . $schemaStatus['message']]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => false, "message" => "Invalid request method."]);
    exit;
}

$userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
$paymentMethod = trim($_POST['payment_method'] ?? '');
$note = trim($_POST['note'] ?? '');

if ($userId <= 0) {
    echo json_encode(["status" => false, "message" => "Invalid user."]);
    exit;
}

$userStmt = $conn->prepare("
    SELECT id
    FROM users
    WHERE id = ?
    LIMIT 1
");
$userStmt->bind_param("i", $userId);
$userStmt->execute();
$userExists = $userStmt->get_result()->num_rows > 0;
$userStmt->close();

if (!$userExists) {
    echo json_encode(["status" => false, "message" => "User not found."]);
    exit;
}

if ($paymentMethod === '') {
    echo json_encode(["status" => false, "message" => "Please select a payment method."]);
    exit;
}

if (!isset($_FILES['screenshot']) || $_FILES['screenshot']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(["status" => false, "message" => "Payment screenshot is required."]);
    exit;
}

$pendingStmt = $conn->prepare("
    SELECT id
    FROM subscription_requests
    WHERE user_id = ? AND status = 'pending'
    LIMIT 1
");
$pendingStmt->bind_param("i", $userId);
$pendingStmt->execute();
$pendingExists = $pendingStmt->get_result()->num_rows > 0;
$pendingStmt->close();

if ($pendingExists) {
    echo json_encode(["status" => false, "message" => "You already have a pending subscription request."]);
    exit;
}

$settings = get_subscription_settings($conn);

$tmpFile = $_FILES['screenshot']['tmp_name'];
$fileSize = intval($_FILES['screenshot']['size'] ?? 0);
$originalName = basename($_FILES['screenshot']['name'] ?? 'payment.png');
$extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
$allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

if (!in_array($extension, $allowedExtensions, true)) {
    echo json_encode(["status" => false, "message" => "Only JPG, PNG, or WEBP screenshots are allowed."]);
    exit;
}

if ($fileSize <= 0 || $fileSize > 10 * 1024 * 1024) {
    echo json_encode(["status" => false, "message" => "Screenshot must be smaller than 10MB."]);
    exit;
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($tmpFile);
$allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
if (!in_array($mimeType, $allowedMimeTypes, true)) {
    echo json_encode(["status" => false, "message" => "Invalid screenshot format."]);
    exit;
}

$relativeDir = 'uploads/subscription_payments';
$absoluteDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'subscription_payments';
if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0755, true)) {
    echo json_encode(["status" => false, "message" => "Upload folder could not be created."]);
    exit;
}

$fileName = 'subscription_' . $userId . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
$absolutePath = $absoluteDir . DIRECTORY_SEPARATOR . $fileName;
if (!move_uploaded_file($tmpFile, $absolutePath)) {
    echo json_encode(["status" => false, "message" => "Unable to save screenshot."]);
    exit;
}

$relativePath = $relativeDir . '/' . $fileName;
$screenshotUrl = subscription_build_public_url($relativePath);
$detailsSnapshot = json_encode([
    'monthly_amount' => $settings['monthly_amount'],
    'currency' => $settings['currency'],
    'default_group_id' => $settings['default_group_id'],
    'default_group_name' => $settings['default_group_name'],
    'jazzcash_number' => $settings['jazzcash_number'],
    'jazzcash_title' => $settings['jazzcash_title'],
    'easypaisa_number' => $settings['easypaisa_number'],
    'easypaisa_title' => $settings['easypaisa_title'],
    'bank_name' => $settings['bank_name'],
    'bank_account_title' => $settings['bank_account_title'],
    'bank_account_number' => $settings['bank_account_number'],
    'bank_iban' => $settings['bank_iban'],
    'payment_instructions' => $settings['payment_instructions']
], JSON_UNESCAPED_UNICODE);

$stmt = $conn->prepare("
    INSERT INTO subscription_requests
    (user_id, group_id, amount, currency, payment_method, screenshot_url, note, details_snapshot, status, months_added, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', 1, ?, ?)
");
$prepareError = $stmt ? '' : $conn->error;
$requestGroupId = null;
$amount = floatval($settings['monthly_amount'] ?? 0);
$currency = trim($settings['currency'] ?? 'PKR');
$createdAt = date('Y-m-d H:i:s');
if ($stmt) {
    $stmt->bind_param(
        "iidsssssss",
        $userId,
        $requestGroupId,
        $amount,
        $currency,
        $paymentMethod,
        $screenshotUrl,
        $note,
        $detailsSnapshot,
        $createdAt,
        $createdAt
    );
}

if ($stmt && $stmt->execute()) {
    echo json_encode([
        "status" => true,
        "message" => "Subscription request sent successfully. Please wait for admin approval."
    ]);
} else {
    @unlink($absolutePath);
    echo json_encode([
        "status" => false,
        "message" => "Unable to submit subscription request." . ($prepareError !== '' ? ' ' . $prepareError : '')
    ]);
}

if ($stmt) {
    $stmt->close();
}
$conn->close();
?>
