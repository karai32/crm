ALTER TABLE `clients`
    ADD COLUMN `is_web_connected_date` TIMESTAMP NULL DEFAULT NULL AFTER `is_web_connected`,
    ADD COLUMN `is_active_date`        TIMESTAMP NULL DEFAULT NULL AFTER `is_active`;
