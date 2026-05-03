<?php
include "config.php"; // Database connection

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user_to_group'])) {
    // Retrieve and sanitize inputs
    $group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : null;
    $start_date = isset($_POST['start_date']) ? $_POST['start_date'] : null;
    $end_date = isset($_POST['end_date']) ? $_POST['end_date'] : null;


    // Debugging output to check form data
    echo "<strong>Debug Output:</strong><br>";
    echo "Group ID: " . htmlspecialchars($group_id) . "<br>";
    echo "User ID: " . htmlspecialchars($user_id) . "<br>";
    echo "Comment: " . htmlspecialchars($comment) . "<br>";

    if ($group_id > 0 && $user_id > 0) {
        // Check if the user is already in the group
        $check_stmt = $conn->prepare("SELECT * FROM group_members WHERE group_id = ? AND user_id = ?");
        $check_stmt->bind_param("ii", $group_id, $user_id);

        if ($check_stmt->execute()) {
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows == 0) {
                // User is not in the group, proceed with insertion
                // If no comment provided, fetch the existing comment
                if (empty($comment)) {
                    $comment_stmt = $conn->prepare("SELECT comment FROM group_members WHERE user_id = ? LIMIT 1");
                    $comment_stmt->bind_param("i", $user_id);
                    $comment_stmt->execute();
                    $comment_result = $comment_stmt->get_result();
                    
                    if ($comment_result->num_rows > 0) {
                        // Get the existing comment for the user
                        $existing_comment = $comment_result->fetch_assoc();
                        $comment = $existing_comment['comment'];
                    } else {
                        // Default comment if no existing comment is found
                        $comment = 'No comment provided.';
                    }
                    $comment_stmt->close();
                }

                // Insert the user into the group with the comment (either user input or existing comment)
                // $stmt = $conn->prepare("INSERT INTO group_members (group_id, user_id, comment) VALUES (?, ?, ?)");
                // $stmt->bind_param("iis", $group_id, $user_id, $comment);

                $stmt = $conn->prepare("INSERT INTO group_members (group_id, user_id, comment, start_date, end_date) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("iisss", $group_id, $user_id, $comment, $start_date, $end_date);

                // Check for successful insertion
                if ($stmt->execute()) {
                    echo "User successfully added to the group.<br>";
                    // You can enable the redirection after debugging
                    header("Location: view_dgroups_users.php?group_id=$group_id");
                    exit;
                } else {
                    // Display SQL error
                    echo "Error adding user to the group: " . $stmt->error . "<br>";
                }
                $stmt->close();
            } else {
                // User already exists in the group
                echo "User is already in the group.<br>";
            }
        } else {
            // SQL execution error in check query
            echo "Error checking if user is in the group: " . $check_stmt->error . "<br>";
        }

        $check_stmt->close();
    } else {
        // Invalid group ID or user ID
        echo "Invalid group ID or user ID.<br>";
    }
}

$conn->close();
?>
