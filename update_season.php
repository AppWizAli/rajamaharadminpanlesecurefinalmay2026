<?php
// Include your database connection file (config.php or similar)
session_start();
include "config.php";
include "media_input_helper.php";

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    // Redirect to login page or any other page you prefer
    header("Location: login.php");
    exit;
}

// Ensure the form data is submitted via POST method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize inputs
    $season_id = isset($_POST['season_id']) ? intval($_POST['season_id']) : 0;
    $drama_id = isset($_POST['drama_id']) ? intval($_POST['drama_id']) : 0;
    $season_number = isset($_POST['season_number']) ? intval($_POST['season_number']) : 0;
    $total_episodes = isset($_POST['total_episodes']) ? intval($_POST['total_episodes']) : 0;
    $currentThumb = '';
    
    // Check if season_id and drama_id are valid
    if ($season_id <= 0 || $drama_id <= 0) {
        echo "Invalid season or drama ID.";
        exit;
    }

    $currentStmt = $conn->prepare("SELECT thumbnail FROM season WHERE id = ?");
    if ($currentStmt) {
        $currentStmt->bind_param("i", $season_id);
        $currentStmt->execute();
        $currentStmt->bind_result($currentThumb);
        $currentStmt->fetch();
        $currentStmt->close();
    }

    try {
        $thumbnail = resolve_media_value(
            $_FILES['thumbnail_file'] ?? [],
            $_POST['thumbnail'] ?? '',
            [
                'label' => 'thumbnail',
                'relativeDirectory' => 'uploads/thumbnails/season',
                'allowedExtensions' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
                'prefix' => 'season_thumb',
                'required' => true,
                'existingValue' => $currentThumb
            ]
        );
    } catch (RuntimeException $e) {
        echo $e->getMessage();
        exit;
    }

    $sql = "UPDATE season SET season_number = ?, total_episodes = ?, thumbnail = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iisi", $season_number, $total_episodes, $thumbnail, $season_id);

    // Execute the update query
    if ($stmt->execute()) {
        // Redirect to view_season.php with drama_id
        header("Location: view_season.php?drama_id=" . $drama_id);
        exit;
    } else {
        echo "Error updating season: " . $stmt->error;
    }

    // Close statement and database connection
    $stmt->close();
    $conn->close();
} else {
    echo "Invalid request method.";
}
?>
