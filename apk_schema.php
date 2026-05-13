<?php
function apk_column_exists($conn, $table, $column) {
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

function apk_add_column_if_missing($conn, $table, $column, $definition) {
    if (!apk_column_exists($conn, $table, $column)) {
        $conn->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    }
}

function ensure_apk_table($conn) {
    $conn->query("
        CREATE TABLE IF NOT EXISTS apk_files (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            string VARCHAR(60) NOT NULL,
            version_name VARCHAR(60) NULL,
            version_code INT NULL,
            apk_url VARCHAR(255) NOT NULL,
            original_name VARCHAR(255) NULL,
            file_size BIGINT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            uploaded_by INT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_apk_active_created (is_active, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    apk_add_column_if_missing($conn, "apk_files", "version_name", "VARCHAR(60) NULL");
    apk_add_column_if_missing($conn, "apk_files", "version_code", "INT NULL");
    apk_add_column_if_missing($conn, "apk_files", "original_name", "VARCHAR(255) NULL");
    apk_add_column_if_missing($conn, "apk_files", "file_size", "BIGINT NULL");
    apk_add_column_if_missing($conn, "apk_files", "is_active", "TINYINT(1) NOT NULL DEFAULT 1");
    apk_add_column_if_missing($conn, "apk_files", "uploaded_by", "INT NULL");
    apk_add_column_if_missing($conn, "apk_files", "created_at", "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
}
?>
