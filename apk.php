<?php
session_start();
include "config.php";
include "apk_schema.php";
include "chunk_upload_helper.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

ensure_apk_table($conn);

function redirect_with_message($type, $message) {
    header("Location: upload_apk.php?" . http_build_query([$type => $message]));
    exit;
}

function safe_apk_name($name) {
    $base = preg_replace('/[^A-Za-z0-9._-]/', '_', pathinfo($name, PATHINFO_FILENAME));
    return $base ?: 'urdu_bolo';
}

function read_apk_badging($apkPath) {
    if (!function_exists('exec')) {
        return [];
    }
    $aapt = trim(shell_exec('where aapt 2>NUL') ?: '');
    if ($aapt === '') {
        $aapt = trim(shell_exec('which aapt 2>/dev/null') ?: '');
    }
    if ($aapt === '') {
        return [];
    }
    $cmd = escapeshellarg($aapt) . ' dump badging ' . escapeshellarg($apkPath) . ' 2>&1';
    $output = [];
    @exec($cmd, $output);
    $firstLine = $output[0] ?? '';
    $meta = [];
    if (preg_match("/versionName='([^']+)'/", $firstLine, $m)) {
        $meta['version_name'] = $m[1];
    }
    if (preg_match("/versionCode='([0-9]+)'/", $firstLine, $m)) {
        $meta['version_code'] = intval($m[1]);
    }
    return $meta;
}

if (
    $_SERVER["REQUEST_METHOD"] !== "POST" ||
    (
        !isset($_POST['upload_apk']) &&
        trim((string) ($_POST['apk_upload_token'] ?? '')) === '' &&
        !isset($_FILES['apk_file'])
    )
) {
    redirect_with_message('error', 'Invalid APK upload request.');
}

if (
    (!isset($_FILES['apk_file']) || ($_FILES['apk_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) &&
    trim((string) ($_POST['apk_upload_token'] ?? '')) === ''
) {
    redirect_with_message('error', 'No APK file was uploaded.');
}

$uploadDirRelative = 'uploads/apk/';
$uploadDirFs = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'apk' . DIRECTORY_SEPARATOR;
if (!is_dir($uploadDirFs)) {
    mkdir($uploadDirFs, 0755, true);
}

$apkUploadToken = trim((string) ($_POST['apk_upload_token'] ?? ''));
$isChunkUpload = $apkUploadToken !== '';
$originalName = '';
$tempPath = '';

if ($isChunkUpload) {
    try {
        $chunkMeta = chunk_upload_materialize($apkUploadToken, 'apk');
        $originalName = basename($chunkMeta['original_name']);
        $tempPath = $chunkMeta['tmp_path'];
    } catch (RuntimeException $e) {
        redirect_with_message('error', $e->getMessage());
    }
} else {
    $originalName = basename($_FILES["apk_file"]["name"]);
    $tempPath = $_FILES["apk_file"]["tmp_name"];
}

$apkFileType = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
if ($apkFileType !== 'apk') {
    if ($isChunkUpload) {
        @unlink($tempPath);
        chunk_upload_cleanup($apkUploadToken);
    }
    redirect_with_message('error', 'Only APK files are allowed.');
}

$versionName = trim($_POST['version_name'] ?? $_POST['apk_str'] ?? '');
$versionCode = isset($_POST['version_code']) && $_POST['version_code'] !== '' ? intval($_POST['version_code']) : null;
$badging = read_apk_badging($tempPath);
if ($versionName === '' && !empty($badging['version_name'])) {
    $versionName = $badging['version_name'];
}
if ($versionCode === null && !empty($badging['version_code'])) {
    $versionCode = intval($badging['version_code']);
}
if ($versionName === '') {
    if ($isChunkUpload) {
        @unlink($tempPath);
        chunk_upload_cleanup($apkUploadToken);
    }
    redirect_with_message('error', 'Please enter the APK version number.');
}

$latest = $conn->query("SELECT version_name, version_code FROM apk_files WHERE is_active = 1 ORDER BY created_at DESC LIMIT 1");
if ($latest && $latest->num_rows > 0) {
    $row = $latest->fetch_assoc();
    $oldCode = $row['version_code'] !== null ? intval($row['version_code']) : null;
    $oldName = $row['version_name'] ?: $row['string'];
    if ($versionCode !== null && $oldCode !== null && $versionCode <= $oldCode) {
        if ($isChunkUpload) {
            @unlink($tempPath);
            chunk_upload_cleanup($apkUploadToken);
        }
        redirect_with_message('error', 'New APK version code must be greater than the current APK.');
    }
    if ($versionCode === null && $oldCode === null && version_compare($versionName, $oldName, '<=')) {
        if ($isChunkUpload) {
            @unlink($tempPath);
            chunk_upload_cleanup($apkUploadToken);
        }
        redirect_with_message('error', 'New APK version must be greater than the current APK.');
    }
}

$safeName = safe_apk_name($originalName);
$fileName = $safeName . '_v' . preg_replace('/[^A-Za-z0-9._-]/', '_', $versionName) . '_' . date('Ymd_His') . '.apk';
$apkPath = $uploadDirRelative . $fileName;
$apkPathFs = $uploadDirFs . $fileName;

if ($isChunkUpload) {
    $moved = @rename($tempPath, $apkPathFs);
    if (!$moved) {
        $moved = @copy($tempPath, $apkPathFs);
        if ($moved) {
            @unlink($tempPath);
        }
    }
    chunk_upload_cleanup($apkUploadToken);
} else {
    $moved = move_uploaded_file($tempPath, $apkPathFs);
}

if (!$moved) {
    redirect_with_message('error', 'APK upload failed while saving the file.');
}

$conn->query("UPDATE apk_files SET is_active = 0");
$adminId = intval($_SESSION['admin_id']);
$fileSize = filesize($apkPathFs);
$stmt = $conn->prepare("
    INSERT INTO apk_files (`string`, version_name, version_code, apk_url, original_name, file_size, is_active, uploaded_by)
    VALUES (?, ?, ?, ?, ?, ?, 1, ?)
");
$legacyString = $versionName;
$stmt->bind_param("ssissii", $legacyString, $versionName, $versionCode, $apkPath, $originalName, $fileSize, $adminId);

if (!$stmt->execute()) {
    @unlink($apkPathFs);
    redirect_with_message('error', 'APK was saved but database record failed.');
}

$stmt->close();
$conn->close();
redirect_with_message('success', 'APK uploaded. This version is now the latest update for the app.');
?>
