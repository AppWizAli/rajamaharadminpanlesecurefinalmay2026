<?php
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
            device_id VARCHAR(160) NULL,
            latitude DECIMAL(10,7) NULL,
            longitude DECIMAL(10,7) NULL,
            extra TEXT NULL,
            ip_address VARCHAR(64) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_security_incidents_created_at (created_at),
            INDEX idx_security_incidents_user_id (user_id),
            INDEX idx_security_incidents_type (incident_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}
?>
