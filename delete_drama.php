<?php
session_start();
include "config.php";

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo "Invalid drama ID.";
    exit;
}

// Step 1: Delete group videos related to episodes of this drama
$deleteGroupVideosSql = "
    DELETE gv FROM group_videos gv
    INNER JOIN episode e ON gv.video_id = e.id
    INNER JOIN season s ON e.season_id = s.id
    WHERE s.drama_id = ?
";
$stmt1 = $conn->prepare($deleteGroupVideosSql);
$stmt1->bind_param("i", $id);
$stmt1->execute();
$stmt1->close();

// Step 2: Delete episodes of this drama
$deleteEpisodesSql = "
    DELETE e FROM episode e
    INNER JOIN season s ON e.season_id = s.id
    WHERE s.drama_id = ?
";
$stmt2 = $conn->prepare($deleteEpisodesSql);
$stmt2->bind_param("i", $id);
$stmt2->execute();
$stmt2->close();

// Step 3: Delete seasons of this drama
$deleteSeasonsSql = "DELETE FROM season WHERE drama_id = ?";
$stmt3 = $conn->prepare($deleteSeasonsSql);
$stmt3->bind_param("i", $id);
$stmt3->execute();
$stmt3->close();

// Step 4: Delete the drama itself
$deleteDramaSql = "DELETE FROM drama WHERE id = ?";
$stmt4 = $conn->prepare($deleteDramaSql);
$stmt4->bind_param("i", $id);

if ($stmt4->execute()) {
    header("Location: product.php?drama_id=$id");
    exit;
} else {
    echo "Error deleting drama: " . $stmt4->error;
}

$stmt4->close();
$conn->close();
?>
