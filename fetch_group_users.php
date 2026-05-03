<?php
include "config.php"; // Include database connection

if (isset($_GET['group_id'])) {
    $group_id = intval($_GET['group_id']);

    // Fetch users for the selected group
    $query = "SELECT u.id, u.username, u.email, gm.comment FROM users u
              JOIN group_members gm ON u.id = gm.user_id
              WHERE gm.group_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $group_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row["id"]) . '</td>';
            echo '<td>' . htmlspecialchars($row["username"]) . '</td>';
            echo '<td>' . htmlspecialchars($row["email"]) . '</td>';
            echo '<td>' . htmlspecialchars($row["comment"]) . '</td>';
            echo '<td>';
            echo '<button class="btn btn-sm btn-primary edit-btn" data-user-id="' . $row["id"] . '" data-group-id="' . $group_id . '">Edit Comment</button> ';
                echo '<button class="btn btn-sm btn-danger remove-btn" data-user-id="' . $row["id"] . '" data-group-id="' . $group_id . '">Remove</button>';
                echo '</td>';
            echo '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="5" class="text-center">No users found in this group.</td></tr>';
    }
}
?>
