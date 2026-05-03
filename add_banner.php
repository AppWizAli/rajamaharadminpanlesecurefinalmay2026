<?php
session_start();
include "config.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

if (isset($_POST['submit_image']) && !empty($_POST['banner_image'])) {
    $image_url = $_POST['banner_image'];
    $check_sql = "SELECT * FROM banners LIMIT 1";
    $result = $conn->query($check_sql);
    if ($result->num_rows > 0) {
        $sql = "UPDATE banners SET image_url='$image_url'";
    } else {
        $sql = "INSERT INTO banners (image_url) VALUES ('$image_url')";
    }
    if ($conn->query($sql) === TRUE) {
        header("Location: view_banner.php");
        exit;
    } else {
        echo "Database error: " . $conn->error;
    }
}

if (isset($_POST['submit_video']) && !empty($_POST['banner_video'])) {
    $video_url = $_POST['banner_video'];
    $check_sql = "SELECT * FROM banners LIMIT 1";
    $result = $conn->query($check_sql);
    if ($result->num_rows > 0) {
        $sql = "UPDATE banners SET video_url='$video_url'";
    } else {
        $sql = "INSERT INTO banners (video_url) VALUES ('$video_url')";
    }
    if ($conn->query($sql) === TRUE) {
        header("Location: view_banner.php");
        exit;
    } else {
        echo "Database error: " . $conn->error;
    }
}

$conn->close();

?>
