<?php
session_start();
include "config.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Retrieve admin details from database
    $sql = "SELECT * FROM admin WHERE admin_name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        $hashed_password = $row['admin_password'];

        // Verify password
        if (password_verify($password, $hashed_password)) {
            // Set session variables
            $_SESSION['admin_id'] = $row['id'];
            $_SESSION['admin_name'] = $row['admin_name'];
            $_SESSION['admin_type'] = $row['admin_type'];

            // Redirect to dashboard or admin panel
            header("Location: index.php");
            exit;
        } else {
            echo "Invalid username or password.";
        }
    } else {
        echo "Invalid username or password.";
    }

    $stmt->close();
    $conn->close();
} else {
    echo "Invalid request method.";
}
?>
