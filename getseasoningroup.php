<?php
include "config.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['darama_id'])) {
    $darama_id = $_POST['darama_id'];

    $query = "SELECT id, season_number FROM season WHERE drama_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $darama_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $options = '<option value="">Select Season</option>';
    while ($row = $result->fetch_assoc()) {
        $options .= '<option value="' . $row['id'] . '">Season ' . $row['season_number'] . '</option>';
    }
    echo $options;

    $stmt->close();
    $conn->close();
}
?>
