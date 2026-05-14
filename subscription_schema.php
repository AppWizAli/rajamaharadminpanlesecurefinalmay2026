<?php
function ensure_subscription_tables($conn) {
    $conn->query("
        CREATE TABLE IF NOT EXISTS subscription_settings (
            id TINYINT NOT NULL PRIMARY KEY,
            monthly_amount DECIMAL(10,2) NOT NULL DEFAULT 1000.00,
            currency VARCHAR(10) NOT NULL DEFAULT 'PKR',
            default_group_id INT NULL,
            jazzcash_number VARCHAR(50) NULL,
            jazzcash_title VARCHAR(160) NULL,
            easypaisa_number VARCHAR(50) NULL,
            easypaisa_title VARCHAR(160) NULL,
            bank_name VARCHAR(160) NULL,
            bank_account_title VARCHAR(160) NULL,
            bank_account_number VARCHAR(80) NULL,
            bank_iban VARCHAR(80) NULL,
            payment_instructions TEXT NULL,
            updated_by INT NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $conn->query("
        CREATE TABLE IF NOT EXISTS subscription_requests (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            group_id INT NULL,
            amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            currency VARCHAR(10) NOT NULL DEFAULT 'PKR',
            payment_method VARCHAR(40) NULL,
            screenshot_url VARCHAR(255) NULL,
            note TEXT NULL,
            details_snapshot TEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            admin_note TEXT NULL,
            invoice_no VARCHAR(80) NULL,
            months_added INT NOT NULL DEFAULT 1,
            subscription_start_date DATE NULL,
            subscription_end_date DATE NULL,
            approved_by INT NULL,
            approved_at DATETIME NULL,
            rejected_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_subscription_requests_user_id (user_id),
            INDEX idx_subscription_requests_status (status),
            INDEX idx_subscription_requests_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $conn->query("
        INSERT INTO subscription_settings (id)
        SELECT 1
        WHERE NOT EXISTS (
            SELECT 1 FROM subscription_settings WHERE id = 1
        )
    ");
}

function get_subscription_settings($conn) {
    $stmt = $conn->prepare("
        SELECT ss.*, g.group_name AS default_group_name
        FROM subscription_settings ss
        LEFT JOIN `groups` g ON g.id = ss.default_group_id
        WHERE ss.id = 1
        LIMIT 1
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    return $row ?: [
        'id' => 1,
        'monthly_amount' => '1000.00',
        'currency' => 'PKR',
        'default_group_id' => null,
        'default_group_name' => null,
        'jazzcash_number' => '',
        'jazzcash_title' => '',
        'easypaisa_number' => '',
        'easypaisa_title' => '',
        'bank_name' => '',
        'bank_account_title' => '',
        'bank_account_number' => '',
        'bank_iban' => '',
        'payment_instructions' => ''
    ];
}

function subscription_build_public_url($relativePath) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    return $scheme . '://' . $host . $dir . '/' . ltrim($relativePath, '/');
}
?>
