<?php
header("Content-Type: application/json");
include "config.php";


// SQL query to fetch all groups
$sql = "SELECT * FROM groups";
$result = $conn->query($sql);

$groups = array();
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $groups[] = $row;
    }
} else {
    $groups = array("message" => "No groups found.");
}

echo json_encode($groups);

$conn->close();
?>
