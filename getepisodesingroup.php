<?php
include "config.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['season_id'])) {
    $season_id = $_POST['season_id'];

    $query = "SELECT * FROM episode WHERE season_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $season_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $options = '';

    while ($row = $result->fetch_assoc()) {
        $options .= '<option value="' . $row['id'] . '">Episode ' . $row['episode_number'] . ' - ' . $row['video_path'] . '</option>';
    }
    echo $options;

    $stmt->close();
    $conn->close();
}
?>
