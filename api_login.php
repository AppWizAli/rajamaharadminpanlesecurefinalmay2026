<?php
// Include your database connection file (config.php or similar)
include "config.php";
include "api_request_security.php";
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
    $email = api_security_normalize_email($data['email'] ?? '');
    $password = isset($data['password']) ? $data['password'] : '';
    $device_token = isset($data['device_token']) ? $data['device_token'] : '';

    $validationMessage = '';
    if (!api_security_validate_email($email, $validationMessage)) {
        api_security_json_error($validationMessage, 400);
    }

    api_security_throttle_or_fail(
        'login-ip-email',
        api_security_client_ip() . '|' . $email,
        12,
        15 * 60,
        "Too many login attempts. Please wait a little and try again."
    );

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
$tokenSecret = $API_TOKEN_SIGNING_SECRET ?? $API_AUTH_SECRET;
$token = base64_encode($id . '|' . $exp . '|' . hash_hmac('sha256', $id . '|' . $exp, $tokenSecret));
echo json_encode([
    "message" => "Login successful",
    "user_data" => $user_data,
    "access_token" => $token,
    "accessToken" => $token
]);
                } else {
                    api_security_json_error("Failed to update logged number and device token", 500);
                }
                $update_stmt->close();
            } else {
                api_security_json_error("Invalid email or password", 401);
            }
        } else {
            api_security_json_error("Invalid email or password", 401);
        }
        $stmt->close();
    } else {
        api_security_json_error("Email and password are required", 400);
    }
} else {
    api_security_json_error("Invalid request method or empty input", 405);
}

// Close database connection
$conn->close();
?>

