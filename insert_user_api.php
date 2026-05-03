<?php
// Include your database connection file
include "config.php";

// Set headers to allow CORS and specify the content type
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if all necessary form data is set
    if (isset($_POST['username']) && isset($_POST['email']) && isset($_POST['password'])) {
        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = $_POST['password'];

        // Handle file upload for profile image (optional)
        $profile_image = NULL; // Default value is NULL if no image is uploaded
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $target_dir = "users/";
            $target_file = $target_dir . basename($_FILES["profile_image"]["name"]);
            if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
                $profile_image = $target_file;
            } else {
                echo json_encode(array("error" => "Error uploading file."));
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
            echo json_encode(array("error" => "User already exists with this email."));
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
                echo json_encode(array("message" => "User added successfully."));
            } else {
                echo json_encode(array("error" => "Error adding user: " . $stmt->error));
            }

            // Close statement
            $stmt->close();
        }

        // Close check statement and database connection
        $check_stmt->close();
        $conn->close();
    } else {
        echo json_encode(array("error" => "Missing required form data."));
    }
} else {
    echo json_encode(array("error" => "Invalid request method."));
}
?>
