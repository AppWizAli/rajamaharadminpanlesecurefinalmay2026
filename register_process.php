<?php
// Include your database connection file (config.php or similar)
include "config.php";

// Retrieve form data using POST method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? $_POST['username'] : '';
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // Handle file upload for profile image
    $profile_image = '';
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $target_dir = "users/";
        $target_file = $target_dir . basename($_FILES["profile_image"]["name"]);
        if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
            $profile_image = $target_file;
        } else {
            echo "Error uploading file.";
            exit;
        }
    }

    // Check if the email already exists in the database
    $check_email_sql = "SELECT id FROM users WHERE email = ?";
    $check_stmt = $conn->prepare($check_email_sql);
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        // Email exists
        echo "User already exists.";
    } else {
        // Email does not exist, proceed to insert
        // Hash password for security
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Prepare SQL statement to insert into users table
        $sql = "INSERT INTO users (username, email, password, profile_image) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $username, $email, $hashed_password, $profile_image);

        // Execute the statement
        if ($stmt->execute()) {
            header("Location: usersrecords.php");
        } else {
            echo "Error adding user: " . $stmt->error;
        }

        // Close statement
        $stmt->close();
    }

    // Close check statement and database connection
    $check_stmt->close();
    $conn->close();
} else {
    echo "Invalid request method.";
}
?>
