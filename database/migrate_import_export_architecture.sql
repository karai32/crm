-- Run once on the existing CRM database.
-- Select the CRM database in phpMyAdmin before executing.

ALTER TABLE import_batches
    ADD COLUMN entity_type ENUM('contacts', 'clients')
        NOT NULL DEFAULT 'contacts' AFTER file_type,
    ADD INDEX idx_import_batches_entity_status (entity_type, status);

ALTER TABLE export_batches
    ADD COLUMN entity_type ENUM('contacts', 'clients')
        NOT NULL DEFAULT 'contacts' AFTER file_type,
    ADD INDEX idx_export_batches_entity_created (entity_type, created_at);

UPDATE export_batches
SET entity_type = 'clients'
WHERE stored_filename LIKE 'clients-%';

ALTER TABLE import_rows
    ADD INDEX idx_import_rows_batch_status (import_batch_id, status);
