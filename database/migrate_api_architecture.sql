-- Run this migration once on an existing CRM database.
-- Select the CRM database in phpMyAdmin before executing it.

ALTER TABLE api_logs
    ADD COLUMN request_id CHAR(24) NULL AFTER api_key_id,
    ADD COLUMN error_code VARCHAR(64) NULL AFTER response_status,
    ADD COLUMN items_count INT UNSIGNED NULL AFTER error_code;

UPDATE api_logs
SET request_id = LOWER(SUBSTRING(SHA2(CONCAT('api-log-', id, '-', created_at), 256), 1, 24))
WHERE request_id IS NULL;

ALTER TABLE api_logs
    MODIFY request_id CHAR(24) NOT NULL,
    ADD UNIQUE KEY uq_api_logs_request_id (request_id),
    ADD INDEX idx_api_logs_key_created (api_key_id, created_at),
    ADD INDEX idx_api_logs_status_created (response_status, created_at),
    DROP COLUMN request_body,
    DROP COLUMN response_body;
