<?php
function subscription_column_exists($conn, $table, $column) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
    ");
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param("ss", $table, $column);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return intval($row['total'] ?? 0) > 0;
}

function subscription_table_exists($conn, $table) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
    ");
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param("s", $table);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return intval($row['total'] ?? 0) > 0;
}

function subscription_index_exists($conn, $table, $index) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND INDEX_NAME = ?
    ");
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param("ss", $table, $index);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return intval($row['total'] ?? 0) > 0;
}

function subscription_add_column_if_missing($conn, $table, $column, $definition) {
    if (!subscription_column_exists($conn, $table, $column)) {
        return $conn->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    }
    return true;
}

function subscription_create_index_if_missing($conn, $table, $index, $expression) {
    if (!subscription_index_exists($conn, $table, $index)) {
        return $conn->query("CREATE INDEX `$index` ON `$table` $expression");
    }
    return true;
}

function ensure_subscription_tables($conn) {
    $errors = [];

    if (!$conn->query("
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
            payment_instructions TEXT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ")) {
        $errors[] = $conn->error;
    }

    if (!$conn->query("
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
            rejected_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ")) {
        $errors[] = $conn->error;
    }

    if (subscription_table_exists($conn, 'subscription_settings')) {
        $settingsColumns = [
            'updated_by' => "INT NULL",
            'updated_at' => "DATETIME NULL"
        ];
        foreach ($settingsColumns as $column => $definition) {
            if (!subscription_add_column_if_missing($conn, 'subscription_settings', $column, $definition)) {
                $errors[] = $conn->error;
            }
        }
        $conn->query("UPDATE subscription_settings SET updated_at = NOW() WHERE updated_at IS NULL");
    }

    if (subscription_table_exists($conn, 'subscription_requests')) {
        $requestColumns = [
            'details_snapshot' => "TEXT NULL",
            'status' => "VARCHAR(20) NOT NULL DEFAULT 'pending'",
            'admin_note' => "TEXT NULL",
            'invoice_no' => "VARCHAR(80) NULL",
            'months_added' => "INT NOT NULL DEFAULT 1",
            'subscription_start_date' => "DATE NULL",
            'subscription_end_date' => "DATE NULL",
            'approved_by' => "INT NULL",
            'approved_at' => "DATETIME NULL",
            'rejected_at' => "DATETIME NULL",
            'created_at' => "DATETIME NULL",
            'updated_at' => "DATETIME NULL"
        ];
        foreach ($requestColumns as $column => $definition) {
            if (!subscription_add_column_if_missing($conn, 'subscription_requests', $column, $definition)) {
                $errors[] = $conn->error;
            }
        }
        if (!subscription_create_index_if_missing($conn, 'subscription_requests', 'idx_subscription_requests_user_id', '(user_id)')) {
            $errors[] = $conn->error;
        }
        if (!subscription_create_index_if_missing($conn, 'subscription_requests', 'idx_subscription_requests_status', '(status)')) {
            $errors[] = $conn->error;
        }
        if (!subscription_create_index_if_missing($conn, 'subscription_requests', 'idx_subscription_requests_created_at', '(created_at)')) {
            $errors[] = $conn->error;
        }
        $conn->query("UPDATE subscription_requests SET created_at = NOW() WHERE created_at IS NULL");
        $conn->query("UPDATE subscription_requests SET updated_at = NOW() WHERE updated_at IS NULL");
    }

    if (subscription_table_exists($conn, 'subscription_settings')) {
        $conn->query("
            INSERT INTO subscription_settings (id, updated_at)
            SELECT 1, NOW()
            WHERE NOT EXISTS (
                SELECT 1 FROM subscription_settings WHERE id = 1
            )
        ");
        if ($conn->error) {
            $errors[] = $conn->error;
        }
    }

    return [
        'ok' => empty($errors),
        'message' => empty($errors) ? '' : implode(' | ', array_unique(array_filter($errors)))
    ];
}

function get_subscription_settings($conn) {
    $stmt = $conn->prepare("
        SELECT ss.*, g.group_name AS default_group_name
        FROM subscription_settings ss
        LEFT JOIN `groups` g ON g.id = ss.default_group_id
        WHERE ss.id = 1
        LIMIT 1
    ");
    if (!$stmt) {
        return [
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
            'payment_instructions' => '',
            'updated_by' => null,
            'updated_at' => null
        ];
    }
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
        'payment_instructions' => '',
        'updated_by' => null,
        'updated_at' => null
    ];
}

function subscription_build_public_url($relativePath) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    return $scheme . '://' . $host . $dir . '/' . ltrim($relativePath, '/');
}
?>
