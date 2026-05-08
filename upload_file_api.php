<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// === CONFIGURATION ===
define('MAX_FILE_SIZE', 100 * 1024 * 1024); // 100 MB
$upload_folder = "x9Nq4GkL2v7TzR1s/uploads/";
$base_url = "https://ub.urdubolo.pk/MAyPN23gE19/uploads/";

$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'mp3', 'wav', 'pdf', 'docx', 'txt', 'xlsx', 'pptx', 'zip', 'rar','m4a'];
$forbidden_extensions = ['php', 'exe', 'sh', 'bat', 'js', 'py', 'cgi'];

$response = [
    "status" => false,
    "file_url" => null,
    "file_size_exceeded" => false,
    "file_type_valid" => false,
    "error_message" => ""
];

// === 1. Check if File is Uploaded ===
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $response["error_message"] = "No file uploaded or upload failed.";
    echo json_encode($response);
    exit;
}

// === 2. Extract Details ===
$file_tmp = $_FILES['file']['tmp_name'];
$original_name = basename($_FILES['file']['name']);
$file_size = $_FILES['file']['size'];
$file_ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

// === 3. Block Dangerous Extensions ===
if (in_array($file_ext, $forbidden_extensions)) {
    $response["error_message"] = "File type .$file_ext is not allowed.";
    echo json_encode($response);
    exit;
}

// === 4. Only Allow Safe File Types ===
if (!in_array($file_ext, $allowed_extensions)) {
    $response["file_type_valid"] = false;
    $response["error_message"] = "Unsupported file type: .$file_ext";
    echo json_encode($response);
    exit;
}
$response["file_type_valid"] = true;

// === 5. Limit File Size ===
if ($file_size > MAX_FILE_SIZE) {
    $response["file_size_exceeded"] = true;
    $response["error_message"] = "File exceeds 100MB size limit.";
    echo json_encode($response);
    exit;
}

// === 6. Validate MIME Type ===
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime_type = $finfo->file($file_tmp);

$valid_mime_types = [
    'image/jpeg', 'image/png', 'image/gif',
    'video/mp4', 'video/quicktime', 'video/x-matroska',
    'audio/mpeg', 'audio/wav', 'audio/x-wav', 'audio/aac', 'audio/mp4', 'audio/x-m4a',
    'application/pdf', 'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel', 'application/zip',
    'text/plain'
];

if (!in_array($mime_type, $valid_mime_types)) {
    $response["error_message"] = "Blocked MIME type: $mime_type";
    echo json_encode($response);
    exit;
}

// === 7. Ensure Folder Exists ===
$server_path = __DIR__ . '/uploads/';
if (!file_exists($server_path)) {
    if (!mkdir($server_path, 0755, true)) {
        $response["error_message"] = "Upload folder could not be created.";
        echo json_encode($response);
        exit;
    }
}

// === 8. Final Destination Path ===
$destination_path = $server_path . $original_name;
$public_url = $base_url . rawurlencode($original_name);

// === 9. If Image, Compress ===
if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif']) && function_exists('imagecreatefromjpeg')) {
    $success = compressImage($file_tmp, $destination_path, 70);
    if (!$success) {
        $response["error_message"] = "Image compression failed.";
        echo json_encode($response);
        exit;
    }
} else {
    if (!move_uploaded_file($file_tmp, $destination_path)) {
        $response["error_message"] = "Failed to save uploaded file.";
        echo json_encode($response);
        exit;
    }
}

// === 10. Final Response ===
$response["status"] = true;
$response["file_url"] = $public_url;
$response["file_size_exceeded"] = false;
$response["error_message"] = "";

echo json_encode($response);

// === Compress Images ===
function compressImage($source, $destination, $quality = 70)
{
    $info = getimagesize($source);
    if (!$info) return false;

    switch ($info['mime']) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($source); break;
        case 'image/png':
            $image = imagecreatefrompng($source); break;
        case 'image/gif':
            $image = imagecreatefromgif($source); break;
        default: return false;
    }

    imagejpeg($image, $destination, $quality);
    imagedestroy($image);
    return true;
}
?>
