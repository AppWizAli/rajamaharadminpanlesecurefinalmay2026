<?php
function security_column_exists($conn, $table, $column) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
    ");
    $stmt->bind_param("ss", $table, $column);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return intval($row['total'] ?? 0) > 0;
}

function security_add_column_if_missing($conn, $table, $column, $definition) {
    if (!security_column_exists($conn, $table, $column)) {
        $conn->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    }
}

function security_index_exists($conn, $table, $index) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND INDEX_NAME = ?
    ");
    $stmt->bind_param("ss", $table, $index);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return intval($row['total'] ?? 0) > 0;
}

function ensure_security_tables($conn) {
    $conn->query("
        CREATE TABLE IF NOT EXISTS user_security_blocks (
            user_id INT NOT NULL PRIMARY KEY,
            is_blocked TINYINT(1) NOT NULL DEFAULT 0,
            message TEXT NULL,
            blocked_by INT NULL,
            blocked_at DATETIME NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $conn->query("
        CREATE TABLE IF NOT EXISTS security_incidents (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            incident_type VARCHAR(80) NOT NULL,
            incident_label VARCHAR(160) NULL,
            app_area VARCHAR(120) NULL,
            device_model VARCHAR(160) NULL,
            manufacturer VARCHAR(120) NULL,
            android_version VARCHAR(60) NULL,
            app_version VARCHAR(60) NULL,
            app_version_code INT NULL,
            package_name VARCHAR(160) NULL,
            device_id VARCHAR(160) NULL,
            device_brand VARCHAR(120) NULL,
            device_product VARCHAR(160) NULL,
            device_hardware VARCHAR(160) NULL,
            device_fingerprint VARCHAR(255) NULL,
            latitude DECIMAL(10,7) NULL,
            longitude DECIMAL(10,7) NULL,
            location_accuracy FLOAT NULL,
            extra TEXT NULL,
            ip_address VARCHAR(64) NULL,
            severity VARCHAR(30) NOT NULL DEFAULT 'info',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_security_incidents_created_at (created_at),
            INDEX idx_security_incidents_user_id (user_id),
            INDEX idx_security_incidents_type (incident_type),
            INDEX idx_security_incidents_device_id (device_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    security_add_column_if_missing($conn, "security_incidents", "app_version_code", "INT NULL");
    security_add_column_if_missing($conn, "security_incidents", "package_name", "VARCHAR(160) NULL");
    security_add_column_if_missing($conn, "security_incidents", "device_brand", "VARCHAR(120) NULL");
    security_add_column_if_missing($conn, "security_incidents", "device_product", "VARCHAR(160) NULL");
    security_add_column_if_missing($conn, "security_incidents", "device_hardware", "VARCHAR(160) NULL");
    security_add_column_if_missing($conn, "security_incidents", "device_fingerprint", "VARCHAR(255) NULL");
    security_add_column_if_missing($conn, "security_incidents", "location_accuracy", "FLOAT NULL");
    security_add_column_if_missing($conn, "security_incidents", "severity", "VARCHAR(30) NOT NULL DEFAULT 'info'");
    if (!security_index_exists($conn, "security_incidents", "idx_security_incidents_device_id")) {
        $conn->query("CREATE INDEX idx_security_incidents_device_id ON security_incidents (device_id)");
    }
}
?>
