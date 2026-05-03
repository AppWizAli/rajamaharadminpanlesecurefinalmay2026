<?php
session_start();
include "config.php";
include "media_input_helper.php";

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $drama_id = intval($_POST['drama_id']);
    $season_number = intval($_POST['season_number']);
    $total_episodes = intval($_POST['total_episodes']);

    try {
        $thumbnail = resolve_media_value(
            $_FILES['thumbnail_file'] ?? [],
            $_POST['thumbnail'] ?? '',
            [
                'label' => 'thumbnail',
                'relativeDirectory' => 'uploads/thumbnails/season',
                'allowedExtensions' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
                'prefix' => 'season_thumb',
                'required' => true
            ]
        );
    } catch (RuntimeException $e) {
        echo $e->getMessage();
        exit;
    }

    // Insert season into the database
    $stmt = $conn->prepare("INSERT INTO season (drama_id, season_number, total_episodes, thumbnail) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iiis", $drama_id, $season_number, $total_episodes, $thumbnail);

        if ($stmt->execute()) {
            echo "Season added successfully.";
            header("Location: view_season.php?drama_id=$drama_id");
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Error: " . $conn->error;
    }
}

// Close database connection
$conn->close();
?>
