<?php
// Start session and include your database connection file
session_start();
include "config.php";

// Unset all session variables
session_unset();

// Destroy the session
session_destroy();

// Redirect to login page after logout
header("Location: login.php");
exit;
?>
