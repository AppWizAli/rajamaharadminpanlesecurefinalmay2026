<?php
include('config.php');
$user_id = $_POST['user_id'];
$group_id = $_POST['group_id'];
$stmt = $conn->prepare("SELECT comment FROM group_members WHERE user_id = ? AND group_id = ?");
$stmt->bind_param("ii", $user_id, $group_id);
$stmt->execute();
$stmt->bind_result($comment);
$stmt->fetch();
$stmt->close();
echo htmlspecialchars($comment); 
$conn->close();
?>
