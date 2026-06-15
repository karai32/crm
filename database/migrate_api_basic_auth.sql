-- Replace the legacy X-Api-Key system with HTTP Basic credentials.
-- This intentionally deletes all existing API keys and API request logs.

USE crm;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS api_logs;
DROP TABLE IF EXISTS api_keys;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE api_keys (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    client_id VARCHAR(64) NOT NULL UNIQUE,
    secret_hash CHAR(64) NOT NULL,
    scopes JSON NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_used_at DATETIME NULL,
    revoked_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_api_keys_is_active (is_active),
    INDEX idx_api_keys_last_used_at (last_used_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE api_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    api_key_id INT UNSIGNED NULL,
    method VARCHAR(10) NOT NULL,
    path VARCHAR(255) NOT NULL,
    request_body JSON NULL,
    response_status SMALLINT UNSIGNED NOT NULL,
    response_body JSON NULL,
    ip_address VARCHAR(45) NULL,
    duration_ms INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_api_logs_key FOREIGN KEY (api_key_id) REFERENCES api_keys(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_api_logs_key_id (api_key_id),
    INDEX idx_api_logs_created_at (created_at),
    INDEX idx_api_logs_status (response_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
