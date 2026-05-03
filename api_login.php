<?php
// Include your database connection file (config.php or similar)
include "config.php";
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set the content type to application/json
header("Content-Type: application/json");

// Retrieve the raw POST data
$input = file_get_contents("php://input");

// Decode the JSON input
$data = json_decode($input, true);

// Check if the request method is POST and data is not empty
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($data)) {
    $email = isset($data['email']) ? $data['email'] : '';
    $password = isset($data['password']) ? $data['password'] : '';
    $device_token = isset($data['device_token']) ? $data['device_token'] : '';

    // Validate the inputs
    if (!empty($email) && !empty($password)) {
        // Prepare SQL statement to fetch user details
        $sql = "SELECT id, username, email, password, logged_number, profile_image, created_at FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $username, $email, $hashed_password, $logged_number, $profile_image, $created_at);
            $stmt->fetch();

            // Verify the password
            if (password_verify($password, $hashed_password)) {
                // Increment logged_number by 1
                $logged_number = intval($logged_number) + 1;

                // Update the logged_number and device_token in the database
                $update_sql = "UPDATE users SET logged_number = ?, device_token = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("isi", $logged_number, $device_token, $id);

                if ($update_stmt->execute()) {
                    $user_data = [
                        "id" => $id,
                        "username" => $username,
                        "email" => $email,
                        "logged_number" => $logged_number,
                        "profile_image" => $profile_image,
                        "created_at" => $created_at,
                        "device_token" => $device_token
                    ];
                    $exp = time() + (30 * 24 * 60 * 60);
$token = base64_encode($id . '|' . $exp . '|' . hash_hmac('sha256', $id . '|' . $exp, $API_AUTH_SECRET));
echo json_encode([
    "message" => "Login successful",
    "user_data" => $user_data,
    "access_token" => $token,
    "accessToken" => $token
]);
                } else {
                    echo json_encode(["error" => "Failed to update logged number and device token"]);
                }
                $update_stmt->close();
            } else {
                echo json_encode(["error" => "Invalid email or password"]);
            }
        } else {
            echo json_encode(["error" => "User not found"]);
        }
        $stmt->close();
    } else {
        echo json_encode(["error" => "Email and password are required"]);
    }
} else {
    echo json_encode(["error" => "Invalid request method or empty input"]);
}

// Close database connection
$conn->close();
?>

