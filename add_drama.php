<?php

session_start();
include "config.php";
include "media_input_helper.php";

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    // Redirect to login page or any other page you prefer
    header("Location: login.php");
    exit;
}
// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name'] ?? '');
    $dramaNumber = intval($_POST['drama_number'] ?? 0);
    $totalSeasons = intval($_POST['total_seasons'] ?? 0);

    try {
        $thumbnail = resolve_media_value(
            $_FILES['thumbnail_file'] ?? [],
            $_POST['thumbnail'] ?? '',
            [
                'label' => 'thumbnail',
                'relativeDirectory' => 'uploads/thumbnails/drama',
                'allowedExtensions' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
                'prefix' => 'drama_thumb',
                'required' => true
            ]
        );
    } catch (RuntimeException $e) {
        echo $e->getMessage();
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO drama (name, drama_number, total_seasons, thumbnail) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        echo "Error: " . $conn->error;
        exit;
    }

    $stmt->bind_param("siis", $name, $dramaNumber, $totalSeasons, $thumbnail);

    if ($stmt->execute()) {
        $stmt->close();
        header("Location: product.php");
        exit;
    }

    echo "Error: " . $stmt->error;
    $stmt->close();
}

// Close database connection
$conn->close();
?>   
