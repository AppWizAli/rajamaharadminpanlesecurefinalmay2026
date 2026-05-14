<?php
$servername = "localhost";
$username = "u223360224_May18db";
$password = "Officer1929@.";
$dbname = "u223360224_May18db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// =========================
// Secure Video Configuration
// =========================
// IMPORTANT: Change this to a long random string in production.
$VIDEO_SIGN_SECRET = "v1_9e9b9d61b75a4b74a0f2c8b0d7a3e6f8f07b9a2d7f4c1b3e8a6d0c2b5f9a1c7e";

// Signed URL TTLs (seconds)
$VIDEO_SIGN_TTL_PLAYBACK = 1200; // 20 minutes
$VIDEO_SIGN_TTL_DOWNLOAD = 3600; // 60 minutes (increase if downloads are large)

// Video storage base directory (local files)
// Ensure this points to the folder where videos are stored on your server.
$VIDEO_STORAGE_BASE = __DIR__ . "/uploads";

// If your admin UI still stores encrypted video_path, set the same key here.
// This matches the CryptoJS key currently used in add/edit episode pages.
$VIDEO_URL_ENCRYPTION_KEY = "MySecureKey32CharactersLongasdfg";

// =========================
// API Auth Configuration
// =========================
// Token signing secret for API auth.
$API_AUTH_SECRET = "v1_c7b2a4e9f6d1c8b3a5f0e7d9b2c6a1f4e8d0c3b5a9f7e2c4b6d1a8f3c7b9e0";

// =========================
// Admin Push Notifications
// =========================
// Optional: set your Firebase legacy server key to enable push notifications.
$FCM_SERVER_KEY = ""; // e.g. "AAAA...."
$ADMIN_FCM_TOPIC = "admin_urdu_bolo";
?>
