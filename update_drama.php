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
// Process form submission
// Process form submission
// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $drama_number = intval($_POST['drama_number']);
    $total_seasons = intval($_POST['total_seasons']);

    $currentThumb = '';
    $currentStmt = $conn->prepare("SELECT thumbnail FROM drama WHERE id = ?");
    if ($currentStmt) {
        $currentStmt->bind_param("i", $id);
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
                'relativeDirectory' => 'uploads/thumbnails/drama',
                'allowedExtensions' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
                'prefix' => 'drama_thumb',
                'required' => true,
                'existingValue' => $currentThumb
            ]
        );
    } catch (RuntimeException $e) {
        echo $e->getMessage();
        exit;
    }

    $stmt = $conn->prepare("UPDATE drama SET name = ?, drama_number = ?, total_seasons = ?, thumbnail = ? WHERE id = ?");
    if (!$stmt) {
        echo "Error updating drama: " . $conn->error;
        exit;
    }

    $stmt->bind_param("siisi", $name, $drama_number, $total_seasons, $thumbnail, $id);

    if ($stmt->execute()) {
        $stmt->close();
        header("Location: product.php?drama_id=$id");
        exit;
    }

    echo "Error updating drama: " . $stmt->error;
    $stmt->close();
}

$conn->close();
?>
