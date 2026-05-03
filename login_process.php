<?php
session_start();
include "config.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if ($username === '' || $password === '') {
        echo "Username and password are required.";
        exit;
    }

    $sql = "SELECT * FROM admin WHERE admin_name = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("login_process.php prepare failed: " . $conn->error);
        echo "Login is temporarily unavailable.";
        exit;
    }

    $stmt->bind_param("s", $username);
    if (!$stmt->execute()) {
        error_log("login_process.php execute failed: " . $stmt->error);
        echo "Login is temporarily unavailable.";
        $stmt->close();
        $conn->close();
        exit;
    }

    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $meta = $stmt->result_metadata();
        $row = [];
        $bindRefs = [];
        while ($field = $meta->fetch_field()) {
            $row[$field->name] = null;
            $bindRefs[] = &$row[$field->name];
        }
        call_user_func_array([$stmt, 'bind_result'], $bindRefs);
        $stmt->fetch();

        $hashed_password = $row['admin_password'] ?? '';
        if ($hashed_password !== '' && password_verify($password, $hashed_password)) {
            $_SESSION['admin_id'] = $row['id'] ?? null;
            $_SESSION['admin_name'] = $row['admin_name'] ?? '';
            $_SESSION['admin_type'] = $row['admin_type'] ?? '';
            header("Location: index.php");
            $stmt->close();
            $conn->close();
            exit;
        }

        echo "Invalid username or password.";
    } else {
        echo "Invalid username or password.";
    }

    $stmt->close();
    $conn->close();
} else {
    echo "Invalid request method.";
}
?>
