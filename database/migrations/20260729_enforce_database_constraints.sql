-- Requires a database version that enforces CHECK constraints (MySQL 8.0.16+).
-- Adds database-level invariants used by every write channel.
-- Run on a backup/staging copy first. Detail diagnostics must return no rows
-- and every counter in the summary must be zero.

-- Duplicate values that would violate the new case-insensitive UNIQUE keys.
SELECT LOWER(TRIM(email)) AS normalized_email,
       COUNT(*) AS duplicate_count,
       GROUP_CONCAT(id ORDER BY id) AS contact_ids
FROM contacts
WHERE email IS NOT NULL
GROUP BY LOWER(TRIM(email))
HAVING COUNT(*) > 1;

SELECT LOWER(TRIM(commercial_name)) AS normalized_commercial_name,
       COUNT(*) AS duplicate_count,
       GROUP_CONCAT(id ORDER BY id) AS client_ids
FROM clients
GROUP BY LOWER(TRIM(commercial_name))
HAVING COUNT(*) > 1;

-- Required strings that are empty or contain outer whitespace.
SELECT 'contacts.full_name' AS field_name, id, full_name AS invalid_value
FROM contacts
WHERE OCTET_LENGTH(full_name) <> OCTET_LENGTH(TRIM(full_name)) OR CHAR_LENGTH(full_name) = 0
UNION ALL
SELECT 'contacts.email', id, email
FROM contacts
WHERE email IS NOT NULL AND (OCTET_LENGTH(email) <> OCTET_LENGTH(TRIM(email)) OR CHAR_LENGTH(email) = 0)
UNION ALL
SELECT 'clients.commercial_name', id, commercial_name
FROM clients
WHERE OCTET_LENGTH(commercial_name) <> OCTET_LENGTH(TRIM(commercial_name)) OR CHAR_LENGTH(commercial_name) = 0
UNION ALL
SELECT 'sectors.name', id, name
FROM sectors
WHERE OCTET_LENGTH(name) <> OCTET_LENGTH(TRIM(name)) OR CHAR_LENGTH(name) = 0
UNION ALL
SELECT 'tags.name', id, name
FROM tags
WHERE OCTET_LENGTH(name) <> OCTET_LENGTH(TRIM(name)) OR CHAR_LENGTH(name) = 0
UNION ALL
SELECT 'roles.name', id, name
FROM roles
WHERE OCTET_LENGTH(name) <> OCTET_LENGTH(TRIM(name)) OR CHAR_LENGTH(name) = 0
UNION ALL
SELECT 'roles.label', id, label
FROM roles
WHERE OCTET_LENGTH(label) <> OCTET_LENGTH(TRIM(label)) OR CHAR_LENGTH(label) = 0
UNION ALL
SELECT 'users.name', id, name
FROM users
WHERE OCTET_LENGTH(name) <> OCTET_LENGTH(TRIM(name)) OR CHAR_LENGTH(name) = 0
UNION ALL
SELECT 'users.email', id, email
FROM users
WHERE OCTET_LENGTH(email) <> OCTET_LENGTH(TRIM(email)) OR CHAR_LENGTH(email) = 0
UNION ALL
SELECT 'user_permissions.permission_key', user_id, permission_key
FROM user_permissions
WHERE OCTET_LENGTH(permission_key) <> OCTET_LENGTH(TRIM(permission_key)) OR CHAR_LENGTH(permission_key) = 0
UNION ALL
SELECT 'custom_fields.name', id, name
FROM custom_fields
WHERE OCTET_LENGTH(name) <> OCTET_LENGTH(TRIM(name)) OR CHAR_LENGTH(name) = 0
UNION ALL
SELECT 'custom_fields.slug', id, slug
FROM custom_fields
WHERE OCTET_LENGTH(slug) <> OCTET_LENGTH(TRIM(slug)) OR CHAR_LENGTH(slug) = 0
UNION ALL
SELECT 'api_keys.name', id, name
FROM api_keys
WHERE OCTET_LENGTH(name) <> OCTET_LENGTH(TRIM(name)) OR CHAR_LENGTH(name) = 0
UNION ALL
SELECT 'api_keys.client_id', id, client_id
FROM api_keys
WHERE OCTET_LENGTH(client_id) <> OCTET_LENGTH(TRIM(client_id)) OR CHAR_LENGTH(client_id) = 0;

SELECT
    (SELECT COUNT(*) FROM users WHERE is_active NOT IN (0, 1)) AS invalid_users_flags,
    (SELECT COUNT(*) FROM user_permissions WHERE is_allowed NOT IN (0, 1)) AS invalid_permission_flags,
    (SELECT COUNT(*) FROM sectors WHERE is_active NOT IN (0, 1)) AS invalid_sector_flags,
    (SELECT COUNT(*) FROM clients
        WHERE is_web_connected NOT IN (0, 1) OR is_active NOT IN (0, 1)) AS invalid_client_flags,
    (SELECT COUNT(*) FROM contacts
        WHERE is_corporate_email IS NOT NULL AND is_corporate_email NOT IN (0, 1)) AS invalid_contact_flags,
    (SELECT COUNT(*) FROM client_contacts WHERE is_primary NOT IN (0, 1)) AS invalid_relation_flags,
    (SELECT COUNT(*) FROM custom_fields
        WHERE is_required NOT IN (0, 1) OR is_filterable NOT IN (0, 1)) AS invalid_custom_field_flags,
    (SELECT COUNT(*) FROM custom_field_values
        WHERE (value_bool IS NOT NULL AND value_bool NOT IN (0, 1))
           OR ((value_text IS NOT NULL)
               + (value_number IS NOT NULL)
               + (value_date IS NOT NULL)
               + (value_bool IS NOT NULL) > 1)) AS invalid_custom_field_values,
    (SELECT COUNT(*) FROM api_keys WHERE is_active NOT IN (0, 1)) AS invalid_api_key_flags;

-- Stop before any persistent DDL if the critical preflight checks failed.
CREATE TEMPORARY TABLE constraint_migration_guard (
    is_clean TINYINT NOT NULL,
    CONSTRAINT chk_constraint_migration_guard CHECK (is_clean = 1)
);

INSERT INTO constraint_migration_guard (is_clean)
SELECT IF(
    (SELECT COUNT(*) FROM (
        SELECT LOWER(TRIM(email))
        FROM contacts
        WHERE email IS NOT NULL
        GROUP BY LOWER(TRIM(email))
        HAVING COUNT(*) > 1
    ) duplicate_contact_emails) = 0
    AND (SELECT COUNT(*) FROM (
        SELECT LOWER(TRIM(commercial_name))
        FROM clients
        GROUP BY LOWER(TRIM(commercial_name))
        HAVING COUNT(*) > 1
    ) duplicate_client_names) = 0
    AND NOT EXISTS (
        SELECT 1 FROM contacts
        WHERE OCTET_LENGTH(full_name) <> OCTET_LENGTH(TRIM(full_name)) OR CHAR_LENGTH(full_name) = 0
           OR (email IS NOT NULL AND (OCTET_LENGTH(email) <> OCTET_LENGTH(TRIM(email)) OR CHAR_LENGTH(email) = 0))
    )
    AND NOT EXISTS (
        SELECT 1 FROM clients
        WHERE OCTET_LENGTH(commercial_name) <> OCTET_LENGTH(TRIM(commercial_name)) OR CHAR_LENGTH(commercial_name) = 0
           OR is_web_connected NOT IN (0, 1) OR is_active NOT IN (0, 1)
    )
    AND NOT EXISTS (
        SELECT 1 FROM roles
        WHERE OCTET_LENGTH(name) <> OCTET_LENGTH(TRIM(name)) OR CHAR_LENGTH(name) = 0
           OR OCTET_LENGTH(label) <> OCTET_LENGTH(TRIM(label)) OR CHAR_LENGTH(label) = 0
    )
    AND NOT EXISTS (
        SELECT 1 FROM users
        WHERE OCTET_LENGTH(name) <> OCTET_LENGTH(TRIM(name)) OR CHAR_LENGTH(name) = 0
           OR OCTET_LENGTH(email) <> OCTET_LENGTH(TRIM(email)) OR CHAR_LENGTH(email) = 0
           OR is_active NOT IN (0, 1)
    )
    AND NOT EXISTS (
        SELECT 1 FROM user_permissions
        WHERE OCTET_LENGTH(permission_key) <> OCTET_LENGTH(TRIM(permission_key)) OR CHAR_LENGTH(permission_key) = 0
           OR is_allowed NOT IN (0, 1)
    )
    AND NOT EXISTS (
        SELECT 1 FROM sectors
        WHERE OCTET_LENGTH(name) <> OCTET_LENGTH(TRIM(name)) OR CHAR_LENGTH(name) = 0
           OR is_active NOT IN (0, 1)
    )
    AND NOT EXISTS (
        SELECT 1 FROM tags
        WHERE OCTET_LENGTH(name) <> OCTET_LENGTH(TRIM(name)) OR CHAR_LENGTH(name) = 0
    )
    AND NOT EXISTS (
        SELECT 1 FROM contacts
        WHERE is_corporate_email IS NOT NULL AND is_corporate_email NOT IN (0, 1)
    )
    AND NOT EXISTS (
        SELECT 1 FROM client_contacts
        WHERE is_primary NOT IN (0, 1)
    )
    AND NOT EXISTS (
        SELECT 1 FROM custom_fields
        WHERE OCTET_LENGTH(name) <> OCTET_LENGTH(TRIM(name)) OR CHAR_LENGTH(name) = 0
           OR OCTET_LENGTH(slug) <> OCTET_LENGTH(TRIM(slug)) OR CHAR_LENGTH(slug) = 0
           OR is_required NOT IN (0, 1) OR is_filterable NOT IN (0, 1)
    )
    AND NOT EXISTS (
        SELECT 1 FROM custom_field_values
        WHERE (value_bool IS NOT NULL AND value_bool NOT IN (0, 1))
           OR ((value_text IS NOT NULL)
               + (value_number IS NOT NULL)
               + (value_date IS NOT NULL)
               + (value_bool IS NOT NULL) > 1)
    )
    AND NOT EXISTS (
        SELECT 1 FROM api_keys
        WHERE OCTET_LENGTH(name) <> OCTET_LENGTH(TRIM(name)) OR CHAR_LENGTH(name) = 0
           OR OCTET_LENGTH(client_id) <> OCTET_LENGTH(TRIM(client_id)) OR CHAR_LENGTH(client_id) = 0
           OR is_active NOT IN (0, 1)
    ),
    1,
    0
);

DROP TEMPORARY TABLE constraint_migration_guard;

ALTER TABLE roles
    ADD CONSTRAINT chk_roles_name_normalized
        CHECK (OCTET_LENGTH(name) = OCTET_LENGTH(TRIM(name)) AND CHAR_LENGTH(name) > 0),
    ADD CONSTRAINT chk_roles_label_normalized
        CHECK (OCTET_LENGTH(label) = OCTET_LENGTH(TRIM(label)) AND CHAR_LENGTH(label) > 0);

ALTER TABLE users
    ADD CONSTRAINT chk_users_name_normalized
        CHECK (OCTET_LENGTH(name) = OCTET_LENGTH(TRIM(name)) AND CHAR_LENGTH(name) > 0),
    ADD CONSTRAINT chk_users_email_normalized
        CHECK (OCTET_LENGTH(email) = OCTET_LENGTH(TRIM(email)) AND CHAR_LENGTH(email) > 0),
    ADD CONSTRAINT chk_users_is_active
        CHECK (is_active IN (0, 1));

ALTER TABLE user_permissions
    ADD CONSTRAINT chk_user_permissions_key_normalized
        CHECK (OCTET_LENGTH(permission_key) = OCTET_LENGTH(TRIM(permission_key)) AND CHAR_LENGTH(permission_key) > 0),
    ADD CONSTRAINT chk_user_permissions_is_allowed
        CHECK (is_allowed IN (0, 1));

ALTER TABLE sectors
    ADD CONSTRAINT chk_sectors_name_normalized
        CHECK (OCTET_LENGTH(name) = OCTET_LENGTH(TRIM(name)) AND CHAR_LENGTH(name) > 0),
    ADD CONSTRAINT chk_sectors_is_active
        CHECK (is_active IN (0, 1));

ALTER TABLE tags
    ADD CONSTRAINT chk_tags_name_normalized
        CHECK (OCTET_LENGTH(name) = OCTET_LENGTH(TRIM(name)) AND CHAR_LENGTH(name) > 0);

ALTER TABLE clients
    DROP INDEX idx_clients_commercial_name,
    ADD UNIQUE KEY uq_clients_commercial_name (commercial_name),
    ADD CONSTRAINT chk_clients_commercial_name_normalized
        CHECK (OCTET_LENGTH(commercial_name) = OCTET_LENGTH(TRIM(commercial_name)) AND CHAR_LENGTH(commercial_name) > 0),
    ADD CONSTRAINT chk_clients_is_web_connected
        CHECK (is_web_connected IN (0, 1)),
    ADD CONSTRAINT chk_clients_is_active
        CHECK (is_active IN (0, 1));

ALTER TABLE contacts
    DROP INDEX idx_contacts_email,
    ADD UNIQUE KEY uq_contacts_email (email),
    ADD CONSTRAINT chk_contacts_full_name_normalized
        CHECK (OCTET_LENGTH(full_name) = OCTET_LENGTH(TRIM(full_name)) AND CHAR_LENGTH(full_name) > 0),
    ADD CONSTRAINT chk_contacts_email_normalized
        CHECK (email IS NULL OR (OCTET_LENGTH(email) = OCTET_LENGTH(TRIM(email)) AND CHAR_LENGTH(email) > 0)),
    ADD CONSTRAINT chk_contacts_is_corporate_email
        CHECK (is_corporate_email IS NULL OR is_corporate_email IN (0, 1));

ALTER TABLE client_contacts
    ADD CONSTRAINT chk_client_contacts_is_primary
        CHECK (is_primary IN (0, 1));

ALTER TABLE custom_fields
    ADD CONSTRAINT chk_custom_fields_name_normalized
        CHECK (OCTET_LENGTH(name) = OCTET_LENGTH(TRIM(name)) AND CHAR_LENGTH(name) > 0),
    ADD CONSTRAINT chk_custom_fields_slug_normalized
        CHECK (OCTET_LENGTH(slug) = OCTET_LENGTH(TRIM(slug)) AND CHAR_LENGTH(slug) > 0),
    ADD CONSTRAINT chk_custom_fields_is_required
        CHECK (is_required IN (0, 1)),
    ADD CONSTRAINT chk_custom_fields_is_filterable
        CHECK (is_filterable IN (0, 1));

ALTER TABLE custom_field_values
    ADD CONSTRAINT chk_custom_field_values_bool
        CHECK (value_bool IS NULL OR value_bool IN (0, 1)),
    ADD CONSTRAINT chk_custom_field_values_single_type
        CHECK (
            (value_text IS NOT NULL)
            + (value_number IS NOT NULL)
            + (value_date IS NOT NULL)
            + (value_bool IS NOT NULL) <= 1
        );

ALTER TABLE api_keys
    ADD CONSTRAINT chk_api_keys_name_normalized
        CHECK (OCTET_LENGTH(name) = OCTET_LENGTH(TRIM(name)) AND CHAR_LENGTH(name) > 0),
    ADD CONSTRAINT chk_api_keys_client_id_normalized
        CHECK (OCTET_LENGTH(client_id) = OCTET_LENGTH(TRIM(client_id)) AND CHAR_LENGTH(client_id) > 0),
    ADD CONSTRAINT chk_api_keys_is_active
        CHECK (is_active IN (0, 1));
