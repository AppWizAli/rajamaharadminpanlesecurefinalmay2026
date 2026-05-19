<?php
// Include your database connection file
include "config.php";
include "api_request_security.php";

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
        $username = api_security_normalize_username($_POST['username']);
        $email = api_security_normalize_email($_POST['email']);
        $password = (string) $_POST['password'];
        $deviceId = trim((string) ($_POST['device_id'] ?? ''));
        $deviceModel = trim((string) ($_POST['device_model'] ?? ''));
        $platform = trim((string) ($_POST['platform'] ?? 'android'));

        $validationMessage = '';
        if (!api_security_validate_username($username, $validationMessage)) {
            api_security_json_error($validationMessage, 400);
        }

        if (!api_security_validate_email($email, $validationMessage)) {
            api_security_json_error($validationMessage, 400);
        }

        if (!api_security_validate_password($password, $validationMessage)) {
            api_security_json_error($validationMessage, 400);
        }

        api_security_throttle_or_fail(
            'signup-ip',
            api_security_client_ip(),
            4,
            24 * 60 * 60,
            "Too many account creations from this network. Please try again later."
        );

        if ($deviceId !== '') {
            api_security_throttle_or_fail(
                'signup-device',
                $deviceId,
                2,
                3 * 24 * 60 * 60,
                "This device has reached the account creation limit for now."
            );
        }

        // Handle file upload for profile image (optional)
        $profile_image = NULL; // Default value is NULL if no image is uploaded
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $target_dir = __DIR__ . DIRECTORY_SEPARATOR . "users";
            if (!is_dir($target_dir) && !mkdir($target_dir, 0755, true)) {
                api_security_json_error("Unable to prepare profile image upload.", 500);
            }

            $tmpName = $_FILES["profile_image"]["tmp_name"];
            $fileSize = intval($_FILES["profile_image"]["size"] ?? 0);
            if ($fileSize <= 0 || $fileSize > 5 * 1024 * 1024) {
                api_security_json_error("Profile image must be smaller than 5MB.", 400);
            }

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($tmpName);
            $extensions = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp'
            ];

            if (!isset($extensions[$mimeType])) {
                api_security_json_error("Only JPG, PNG, or WEBP profile images are allowed.", 400);
            }

            $target_file = $target_dir . DIRECTORY_SEPARATOR . 'user_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $extensions[$mimeType];
            if (move_uploaded_file($tmpName, $target_file)) {
                $profile_image = 'users/' . basename($target_file);
            } else {
                api_security_json_error("Error uploading file.", 500);
            }
        }

        $check_user_sql = "SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1";
        $check_stmt = $conn->prepare($check_user_sql);
        $check_stmt->bind_param("ss", $email, $username);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            http_response_code(409);
            echo json_encode(array(
                "message" => "",
                "error" => "An account already exists with this email or username."
            ));
        } else {
            // Email does not exist, proceed to insert
            // Hash password for security
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            if ($hashed_password === false) {
                api_security_json_error("Unable to secure this password right now.", 500);
            }

            // Prepare SQL statement to insert into users table
            $sql = "INSERT INTO users (username, email, password, profile_image) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $username, $email, $hashed_password, $profile_image);

            // Execute the statement
            if ($stmt->execute()) {
                http_response_code(201);
                echo json_encode(array(
                    "message" => "User added successfully.",
                    "error" => null,
                    "meta" => array(
                        "device_model" => $deviceModel,
                        "platform" => $platform
                    )
                ));
            } else {
                api_security_json_error("Error adding user: " . $stmt->error, 500);
            }

            // Close statement
            $stmt->close();
        }

        // Close check statement and database connection
        $check_stmt->close();
        $conn->close();
    } else {
        api_security_json_error("Missing required form data.", 400);
    }
} else {
    api_security_json_error("Invalid request method.", 405);
}
?>
