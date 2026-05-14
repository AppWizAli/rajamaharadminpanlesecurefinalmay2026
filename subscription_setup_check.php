<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "config.php";
include "subscription_schema.php";

header("Content-Type: text/plain; charset=UTF-8");

echo "Subscription setup check\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

if (!isset($conn) || !$conn) {
    echo "Database connection is not available.\n";
    exit;
}

echo "Database connected successfully.\n";

$schemaStatus = ensure_subscription_tables($conn);

if (!empty($schemaStatus['message'])) {
    echo "Schema status: FAILED\n";
    echo "Error: " . $schemaStatus['message'] . "\n";
} else {
    echo "Schema status: OK\n";
}

$settingsExists = subscription_table_exists($conn, 'subscription_settings') ? 'yes' : 'no';
$requestsExists = subscription_table_exists($conn, 'subscription_requests') ? 'yes' : 'no';

echo "\nsubscription_settings table: " . $settingsExists . "\n";
echo "subscription_requests table: " . $requestsExists . "\n";

$conn->close();
?>
