-- CRM database schema
-- MySQL / MariaDB, InnoDB, utf8mb4

CREATE DATABASE IF NOT EXISTS crm
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE crm;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS user_preferences;
DROP TABLE IF EXISTS api_logs;
DROP TABLE IF EXISTS api_keys;
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS export_batches;
DROP TABLE IF EXISTS import_errors;
DROP TABLE IF EXISTS import_rows;
DROP TABLE IF EXISTS import_batches;
DROP TABLE IF EXISTS custom_field_values;
DROP TABLE IF EXISTS custom_field_options;
DROP TABLE IF EXISTS custom_fields;
DROP TABLE IF EXISTS client_contacts;
DROP TABLE IF EXISTS client_tags;
DROP TABLE IF EXISTS contact_tags;
DROP TABLE IF EXISTS tags;
DROP TABLE IF EXISTS contacts;
DROP TABLE IF EXISTS clients;
DROP TABLE IF EXISTS sectors;
DROP TABLE IF EXISTS user_permissions;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    label VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_users_role_id (role_id),
    INDEX idx_users_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_permissions (
    user_id INT UNSIGNED NOT NULL,
    permission_key VARCHAR(80) NOT NULL,
    is_allowed TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, permission_key),
    CONSTRAINT fk_user_permissions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_user_permissions_key (permission_key),
    INDEX idx_user_permissions_allowed (is_allowed)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sectors (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE,
    slug VARCHAR(180) NULL UNIQUE,
    icon VARCHAR(80) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_sectors_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tags (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(130) NULL UNIQUE,
    color VARCHAR(20) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE clients (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sector_id INT UNSIGNED NULL,
    commercial_name VARCHAR(255) NOT NULL,
    legal_name VARCHAR(255) NULL,
    cif VARCHAR(50) NULL,
    address VARCHAR(255) NULL,
    postal_code VARCHAR(30) NULL,
    city VARCHAR(150) NULL,
    province VARCHAR(150) NULL,
    country VARCHAR(150) NULL,
    website VARCHAR(255) NULL,
    notes TEXT NULL,
    is_web_connected TINYINT(1) NOT NULL DEFAULT 0,
    is_web_connected_date TIMESTAMP NULL DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_active_date TIMESTAMP NULL DEFAULT NULL,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_clients_sector FOREIGN KEY (sector_id) REFERENCES sectors(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_clients_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_clients_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_clients_sector_id (sector_id),
    INDEX idx_clients_commercial_name (commercial_name),
    INDEX idx_clients_legal_name (legal_name),
    INDEX idx_clients_cif (cif),
    INDEX idx_clients_city (city),
    INDEX idx_clients_province (province),
    INDEX idx_clients_country (country)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE contacts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL DEFAULT '',
    email VARCHAR(190) NULL,
    is_corporate_email TINYINT(1) NULL DEFAULT NULL,
    email_status ENUM('valid','invalid','unknown') NULL DEFAULT NULL,
    phone VARCHAR(60) NULL,
    company VARCHAR(255) NOT NULL DEFAULT '',
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_contacts_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_contacts_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_contacts_full_name (full_name),
    INDEX idx_contacts_email (email),
    INDEX idx_contacts_is_corporate (is_corporate_email),
    INDEX idx_contacts_email_status (email_status),
    INDEX idx_contacts_phone (phone),
    INDEX idx_contacts_company (company),
    INDEX idx_contacts_created_at (created_at),
    INDEX idx_contacts_updated_at (updated_at),
    FULLTEXT INDEX ft_contacts_search (full_name, email, phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE contact_tags (
    contact_id INT UNSIGNED NOT NULL,
    tag_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (contact_id, tag_id),
    CONSTRAINT fk_contact_tags_contact FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_contact_tags_tag FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_contact_tags_tag_id (tag_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE client_tags (
    client_id INT UNSIGNED NOT NULL,
    tag_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (client_id, tag_id),
    CONSTRAINT fk_client_tags_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_client_tags_tag FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_client_tags_tag_id (tag_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE client_contacts (
    client_id INT UNSIGNED NOT NULL,
    contact_id INT UNSIGNED NOT NULL,
    relation_label VARCHAR(150) NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (client_id, contact_id),
    CONSTRAINT fk_client_contacts_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_client_contacts_contact FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_client_contacts_contact_id (contact_id),
    INDEX idx_client_contacts_is_primary (is_primary)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE custom_fields (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entity_type ENUM('contact', 'client') NOT NULL,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(180) NOT NULL,
    field_type ENUM('text', 'textarea', 'number', 'date', 'email', 'url', 'select', 'checkbox') NOT NULL,
    is_required TINYINT(1) NOT NULL DEFAULT 0,
    is_filterable TINYINT(1) NOT NULL DEFAULT 1,
    default_value TEXT NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_custom_fields_entity_slug (entity_type, slug),
    INDEX idx_custom_fields_entity_type (entity_type),
    INDEX idx_custom_fields_field_type (field_type),
    INDEX idx_custom_fields_sort_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE custom_field_options (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    field_id INT UNSIGNED NOT NULL,
    value VARCHAR(255) NOT NULL,
    label VARCHAR(255) NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_custom_field_options_field FOREIGN KEY (field_id) REFERENCES custom_fields(id) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY uq_custom_field_options_field_value (field_id, value),
    INDEX idx_custom_field_options_sort_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE custom_field_values (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    field_id INT UNSIGNED NOT NULL,
    entity_type ENUM('contact', 'client') NOT NULL,
    entity_id INT UNSIGNED NOT NULL,
    value_text TEXT NULL,
    value_number DECIMAL(18, 4) NULL,
    value_date DATE NULL,
    value_bool TINYINT(1) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_custom_field_values_field FOREIGN KEY (field_id) REFERENCES custom_fields(id) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY uq_custom_field_value (field_id, entity_type, entity_id),
    INDEX idx_custom_field_values_entity (entity_type, entity_id),
    INDEX idx_custom_field_values_field_id (field_id),
    INDEX idx_custom_field_values_number (field_id, value_number),
    INDEX idx_custom_field_values_date (field_id, value_date),
    INDEX idx_custom_field_values_bool (field_id, value_bool),
    FULLTEXT INDEX ft_custom_field_values_text (value_text)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE import_batches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    original_filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(255) NULL,
    file_type ENUM('xlsx', 'csv') NOT NULL,
    entity_type ENUM('contacts', 'clients') NOT NULL DEFAULT 'contacts',
    status ENUM('uploaded', 'previewed', 'processing', 'completed', 'partial', 'failed') NOT NULL DEFAULT 'uploaded',
    total_rows INT UNSIGNED NOT NULL DEFAULT 0,
    imported_rows INT UNSIGNED NOT NULL DEFAULT 0,
    skipped_rows INT UNSIGNED NOT NULL DEFAULT 0,
    error_rows INT UNSIGNED NOT NULL DEFAULT 0,
    field_mapping JSON NULL,
    started_at DATETIME NULL,
    finished_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_import_batches_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_import_batches_user_id (user_id),
    INDEX idx_import_batches_status (status),
    INDEX idx_import_batches_created_at (created_at),
    INDEX idx_import_batches_entity_status (entity_type, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE import_rows (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    import_batch_id BIGINT UNSIGNED NOT NULL,
    `row_number` INT UNSIGNED NOT NULL,
    raw_data JSON NULL,
    status ENUM('pending', 'imported', 'skipped', 'error') NOT NULL DEFAULT 'pending',
    related_contact_id INT UNSIGNED NULL,
    related_client_id INT UNSIGNED NULL,
    message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_import_rows_batch FOREIGN KEY (import_batch_id) REFERENCES import_batches(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_import_rows_contact FOREIGN KEY (related_contact_id) REFERENCES contacts(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_import_rows_client FOREIGN KEY (related_client_id) REFERENCES clients(id) ON DELETE SET NULL ON UPDATE CASCADE,
    UNIQUE KEY uq_import_rows_batch_row (import_batch_id, `row_number`),
    INDEX idx_import_rows_status (status),
    INDEX idx_import_rows_batch_status (import_batch_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE import_errors (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    import_batch_id BIGINT UNSIGNED NOT NULL,
    import_row_id BIGINT UNSIGNED NULL,
    `row_number` INT UNSIGNED NULL,
    error_message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_import_errors_batch FOREIGN KEY (import_batch_id) REFERENCES import_batches(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_import_errors_row FOREIGN KEY (import_row_id) REFERENCES import_rows(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_import_errors_batch_id (import_batch_id),
    INDEX idx_import_errors_row_number (`row_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE export_batches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    file_type ENUM('xlsx', 'csv') NOT NULL,
    entity_type ENUM('contacts', 'clients') NOT NULL DEFAULT 'contacts',
    stored_filename VARCHAR(255) NULL,
    filters JSON NULL,
    selected_fields JSON NULL,
    total_rows INT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('processing', 'completed', 'failed') NOT NULL DEFAULT 'processing',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    finished_at DATETIME NULL,
    CONSTRAINT fk_export_batches_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_export_batches_user_id (user_id),
    INDEX idx_export_batches_status (status),
    INDEX idx_export_batches_created_at (created_at),
    INDEX idx_export_batches_entity_created (entity_type, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(100) NULL,
    entity_id BIGINT UNSIGNED NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_audit_logs_user_id (user_id),
    INDEX idx_audit_logs_action (action),
    INDEX idx_audit_logs_entity (entity_type, entity_id),
    INDEX idx_audit_logs_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    request_id CHAR(24) NOT NULL,
    method VARCHAR(10) NOT NULL,
    path VARCHAR(255) NOT NULL,
    response_status SMALLINT UNSIGNED NOT NULL,
    error_code VARCHAR(64) NULL,
    items_count INT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    duration_ms INT UNSIGNED NULL,
    request_body TEXT NULL,
    response_body TEXT NULL,
    origin VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_api_logs_key FOREIGN KEY (api_key_id) REFERENCES api_keys(id) ON DELETE SET NULL ON UPDATE CASCADE,
    UNIQUE KEY uq_api_logs_request_id (request_id),
    INDEX idx_api_logs_key_id (api_key_id),
    INDEX idx_api_logs_created_at (created_at),
    INDEX idx_api_logs_status (response_status),
    INDEX idx_api_logs_key_created (api_key_id, created_at),
    INDEX idx_api_logs_status_created (response_status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_preferences (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    preference_key VARCHAR(80) NOT NULL,
    preference_value TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_pref (user_id, preference_key),
    CONSTRAINT fk_user_preferences_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_user_preferences_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO roles (name, label) VALUES
('admin', 'Administrator'),
('user', 'User');

INSERT INTO sectors (name, slug) VALUES
('Food', 'food'),
('Art', 'art'),
('Automotive', 'automotive'),
('Beauty', 'beauty'),
('Communications', 'communications'),
('Construction and Engineering', 'construction-and-engineering'),
('Consulting and Legal', 'consulting-and-legal'),
('Sports', 'sports'),
('Energy', 'energy'),
('Events', 'events'),
('Training', 'training'),
('Franchises', 'franchises'),
('Gaming', 'gaming'),
('Home', 'home'),
('Industry', 'industry'),
('Education', 'education'),
('Real Estate', 'real-estate'),
('Institution', 'institution'),
('Logistics', 'logistics'),
('Mother and Child', 'mother-and-child'),
('Fashion', 'fashion'),
('Nightlife', 'nightlife'),
('Hospitality', 'hospitality'),
('Health', 'health'),
('Insurance', 'insurance'),
('Start-up', 'start-up'),
('Technology', 'technology'),
('Tourism', 'tourism'),
('Wellness', 'wellness'),
('Pets', 'pets'),
('E-commerce', 'e-commerce'),
('Consulting and Human Resources', 'consulting-and-human-resources'),
('Personal Brand', 'personal-brand'),
('Finance', 'finance');

INSERT INTO tags (name, slug) VALUES
('Lead', 'lead'),
('Client', 'client'),
('Supplier', 'supplier'),
('Partner', 'partner'),
('Event', 'event'),
('Newsletter', 'newsletter'),
('Pending', 'pending'),
('Hot', 'hot'),
('Cold', 'cold');
