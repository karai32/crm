<?php

return [
    'title' => 'Database',
    'description' => 'MySQL schema, relationships between entities, data integrity, and rules for changing the model.',
    'icon' => 'ph-arrow-elbow-down-right',
    'sections' => [
        [
            'id' => 'database-overview',
            'title' => 'Purpose and structure of the database',
            'paragraphs' => [
                'ContactCore uses a single relational MySQL or MariaDB database. The source schema is stored in database/schema.sql and creates 21 tables. Every table uses InnoDB, the utf8mb4 character set, and utf8mb4_unicode_ci collation, providing transactions, foreign keys, and correct storage of multilingual text.',
                'The database can be divided into five areas: users and access; clients, contacts, and classification; custom fields; import and export history; and the API and technical logs. These are not separate databases or independent modules: foreign keys and application-level relationships connect them.',
                'The application does not use Eloquent models. Repositories, authorization, reports, and exports build queries consistently through Illuminate Database Query Builder and return associative arrays. Raw expressions are limited to MySQL features such as GROUP_CONCAT, CASE, and special functions. The driver uses ATTR_STRINGIFY_FETCHES, so numeric SELECT values may arrive as strings; the code explicitly casts identifiers, counters, and flags to int where necessary.',
            ],
            'examples' => [[
                'title' => 'Table groups',
                'code' => <<<'CODE'
Access
  roles, users, user_permissions, user_preferences

Core data
  sectors, clients, contacts
  tags, client_tags, contact_tags, client_contacts

Custom fields
  custom_fields, custom_field_options, custom_field_values

Data exchange
  import_batches, import_rows, import_errors, export_batches

Integrations and history
  api_keys, api_logs, audit_logs
CODE,
            ]],
        ],
        [
            'id' => 'database-relations-map',
            'title' => 'Map of the main relationships',
            'paragraphs' => [
                'The center of the domain model is clients and contacts. A client represents an organization, while a contact represents a person who submitted a lead. A contact can be related to several clients, and a client can have several contacts, so the relationship is stored in the separate client_contacts table.',
                'A sector is assigned directly to a client through a one-to-many relationship. Tags are shared by clients and contacts but use two separate junction tables. Custom fields are defined separately, and their values are linked to a client or contact through the entity_type + entity_id pair.',
                'A user can create and modify core records and run imports and exports. Deleting a user does not delete business data or history: created_by, updated_by, and user_id foreign keys in log tables are changed to NULL.',
            ],
            'examples' => [[
                'title' => 'Simplified ER diagram',
                'code' => <<<'CODE'
roles 1 ─────── N users
                    ├── N user_permissions
                    └── N user_preferences

sectors 1 ───── N clients
                    │
                    N
              client_contacts
                    N
                    │
                contacts

clients  N ── client_tags  ── N tags
contacts N ── contact_tags ── N tags

custom_fields 1 ── N custom_field_options
custom_fields 1 ── N custom_field_values

users 1 ── N import_batches ── N import_rows ── N import_errors
users 1 ── N export_batches
api_keys 1 ── N api_logs
CODE,
            ]],
        ],
        [
            'id' => 'database-conventions',
            'title' => 'Types, identifiers, and timestamp fields',
            'paragraphs' => [
                'Ordinary entities use INT UNSIGNED AUTO_INCREMENT. Rapidly growing logs, import batches, and custom-field values use BIGINT UNSIGNED. A foreign key must have the same size and UNSIGNED attribute as its referenced primary key; mismatched types prevent the constraint from being created.',
                'Boolean values are stored as TINYINT(1), limited state sets as ENUM, and arbitrary parameter structures as JSON. ENUM is convenient for a fixed status, but adding a value requires a schema change. JSON is used only where the structure is genuinely variable: import mappings, export filters, scopes, and audit snapshots.',
                'created_at is normally populated with CURRENT_TIMESTAMP, while updated_at changes automatically through ON UPDATE CURRENT_TIMESTAMP. Domain timestamps such as last_login_at, started_at, and finished_at are DATETIME values set by the application. Server, PHP, and MySQL time must be aligned, or filters and reports will use shifted periods.',
            ],
            'examples' => [[
                'title' => 'Typical table skeleton',
                'code' => <<<'SQL'
CREATE TABLE projects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(190) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_projects_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_projects_name (name),
    INDEX idx_projects_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL,
            ]],
        ],
        [
            'id' => 'database-users',
            'title' => 'Users, roles, and preferences',
            'paragraphs' => [
                'roles contains the system roles admin and user. users stores the name, unique email, password_hash, active status, and last login date. A password is never stored as plain text: PHP creates it with password_hash(), and login verifies it with password_verify(). ON DELETE RESTRICT prevents removal of a role that is in use.',
                'user_permissions stores an individual decision for each permission_key. The composite PRIMARY KEY (user_id, permission_key) prevents two values for the same user permission. Permissions are deleted by cascade when the user is deleted.',
                'user_preferences is an extensible key-value store for interface settings. The application currently uses the per_page key. The unique user_id + preference_key pair enables INSERT ... ON DUPLICATE KEY UPDATE. Preferences must not be mixed with permissions: a preference affects interface convenience, while a permission controls access to an operation.',
            ],
            'examples' => [[
                'title' => 'A user and their explicit permissions',
                'code' => <<<'SQL'
SELECT
    u.id,
    u.name,
    u.email,
    r.name AS role,
    up.permission_key,
    up.is_allowed
FROM users u
INNER JOIN roles r ON r.id = u.role_id
LEFT JOIN user_permissions up ON up.user_id = u.id
WHERE u.id = :user_id
ORDER BY up.permission_key;
SQL,
            ]],
        ],
        [
            'id' => 'database-clients-contacts',
            'title' => 'Clients and contacts',
            'paragraphs' => [
                'clients stores an organization: its commercial and legal names, CIF, address, website, sector, notes, and two independent states—active cooperation and website connection through the API. is_active_date and is_web_connected_date record when the corresponding state changed.',
                'contacts stores a person and their available communication details. company is a textual company name entered manually or obtained through Gemini and does not replace a relationship with clients. is_corporate_email and email_status are address-classification results; NULL means no result, while unknown means classification without a live MX check.',
                'created_by and updated_by identify the user who performed an interface action when that context is available. ON DELETE SET NULL preserves the record if the user is deleted. A populated contacts.email and clients.commercial_name have UNIQUE indexes; preliminary application checks improve error messages, but MySQL enforces the final invariant.',
            ],
            'examples' => [[
                'title' => 'Contacts for a selected client',
                'code' => <<<'SQL'
SELECT
    c.id,
    c.full_name,
    c.email,
    c.phone,
    cc.relation_label,
    cc.is_primary
FROM client_contacts cc
INNER JOIN contacts c ON c.id = cc.contact_id
WHERE cc.client_id = :client_id
ORDER BY cc.is_primary DESC, c.full_name ASC;
SQL,
            ]],
        ],
        [
            'id' => 'database-classification',
            'title' => 'Sectors, tags, and junction tables',
            'paragraphs' => [
                'sectors is the industry catalog for clients. clients.sector_id allows NULL, and deleting a sector uses ON DELETE SET NULL, preserving the client without classification. In practice, the repository attempts to deactivate a sector that is in use rather than delete it, preserving the meaning of historical data.',
                'tags is the shared catalog of flexible labels. contact_tags and client_tags implement many-to-many relationships. Their composite primary keys also act as unique constraints: the same tag cannot be assigned to an entity twice. Reverse indexes on tag_id speed up retrieval of all clients or contacts with a tag.',
                'client_contacts is also a many-to-many relationship, but it contains properties of the relationship itself: relation_label and is_primary. PRIMARY KEY (client_id, contact_id) permits one relationship for a particular pair. If the same person needs two roles for one client, they currently have to be described in one relation_label or the model must be changed.',
            ],
            'examples' => [[
                'title' => 'Clients with tags without duplicate rows',
                'code' => <<<'SQL'
SELECT
    c.id,
    c.commercial_name,
    GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR ', ') AS tags
FROM clients c
LEFT JOIN client_tags ct ON ct.client_id = c.id
LEFT JOIN tags t ON t.id = ct.tag_id
GROUP BY c.id, c.commercial_name
ORDER BY c.commercial_name;
SQL,
            ]],
        ],
        [
            'id' => 'database-custom-fields',
            'title' => 'Custom-field model',
            'paragraphs' => [
                'Custom fields use a typed EAV model. custom_fields describes a field, its entity, slug, type, required and filterable flags, default value, and order. UNIQUE (entity_type, slug) permits the same slug for a client and contact, but not twice within one entity type.',
                'custom_field_options stores allowed select options. Deleting a field definition cascades to its options and values. custom_field_values contains one row for each field_id + entity_type + entity_id combination. Depending on field_type, only one column is populated: value_text, value_number, value_date, or value_bool. The repository saves through ON DUPLICATE KEY UPDATE.',
                'entity_type + entity_id is a polymorphic reference: one entity_id column can mean contacts.id or clients.id. MySQL cannot create one foreign key to two tables, so the entity itself has no constraint. The database cannot prevent a mismatch between field and value types or orphaned values after deleting a client or contact; service code and periodic checks are responsible for these cases.',
                'is_filterable does not automatically create a separate index. The flag only permits the field to appear in interface filters; composite field_id + typed-value indexes provide performance. A text FULLTEXT index exists, but current repository filters use LIKE.',
            ],
            'examples' => [
                [
                    'title' => 'How a contact language field is stored',
                    'code' => <<<'SQL'
-- Definition
INSERT INTO custom_fields
    (entity_type, name, slug, field_type, is_filterable)
VALUES
    ('contact', 'Language', 'language', 'text', 1);

-- Value for contacts.id = 125
INSERT INTO custom_field_values
    (field_id, entity_type, entity_id, value_text)
VALUES
    (:language_field_id, 'contact', 125, 'en')
ON DUPLICATE KEY UPDATE value_text = VALUES(value_text);
SQL,
                ],
                [
                    'title' => 'Finding orphaned values',
                    'code' => <<<'SQL'
SELECT cfv.*
FROM custom_field_values cfv
LEFT JOIN contacts c
    ON cfv.entity_type = 'contact' AND c.id = cfv.entity_id
LEFT JOIN clients cl
    ON cfv.entity_type = 'client' AND cl.id = cfv.entity_id
WHERE (cfv.entity_type = 'contact' AND c.id IS NULL)
   OR (cfv.entity_type = 'client' AND cl.id IS NULL);
SQL,
                ],
            ],
        ],
        [
            'id' => 'database-import-export',
            'title' => 'Import and export',
            'paragraphs' => [
                'import_batches is the header for one upload: user, original and stored filename, format, entity type, status, counters, and JSON column mapping. Statuses form the lifecycle uploaded → previewed → processing → completed or partial; failed indicates a general error. A conditional UPDATE in claimForProcessing prevents two requests from claiming the same batch simultaneously.',
                'import_rows and import_errors contain row-level diagnostics. The current process mainly records skipped and erroneous rows in import_rows together with raw_data, while import_errors provides a separate message list. Deleting a batch cascades to its rows and errors; deleting an imported contact or client only clears related_*_id.',
                'export_batches stores export-generation history: selected filters and fields as JSON, name, format, row count, status, and completion time. CSV/XLSX is currently sent directly to php://output; stored_filename is the download name and a history record, not a guarantee that a finished file exists on disk.',
            ],
            'examples' => [
                ['title' => 'Import states', 'code' => <<<'CODE'
uploaded
   │
   ├── previewed ──┐
   │               │
   └───────────────┴── processing
                           │
                  ┌────────┼────────┐
                  ▼        ▼        ▼
              completed  partial  failed
CODE],
                ['title' => 'Safely claiming a batch', 'code' => <<<'SQL'
UPDATE import_batches
SET status = 'processing', started_at = NOW()
WHERE id = :id
  AND status IN ('uploaded', 'previewed');

-- Processing starts only in the process where rowCount() === 1.
SQL],
            ],
        ],
        [
            'id' => 'database-api',
            'title' => 'API keys and request logs',
            'paragraphs' => [
                'api_keys stores the integration name, unique client_id, SHA-256 hash of the secret, JSON scope array, active status, and usage or revocation dates. The plain secret is shown only at creation and is never written to the table. Verification uses hash_equals, so a lost secret cannot be recovered from the database; a new key must be issued.',
                'api_logs receives a row for every API request, including failed authentication. request_id is unique and returned to the client in X-Request-Id. The log stores the method, logical path, status, error code, duration, IP, origin, and truncated request and response bodies. The code limits each body to approximately 64 KB.',
                'When an API key is deleted, api_key_id in the log becomes NULL, while request_id and the remaining details are preserved. There is currently no automatic api_logs retention policy, so production must define a retention period based on volume, diagnostics, and personal-data requirements.',
            ],
            'examples' => [[
                'title' => 'API errors in the last 24 hours',
                'code' => <<<'SQL'
SELECT request_id, method, path, response_status,
       error_code, duration_ms, created_at
FROM api_logs
WHERE response_status >= 400
  AND created_at >= NOW() - INTERVAL 1 DAY
ORDER BY id DESC;
SQL,
            ]],
        ],
        [
            'id' => 'database-audit',
            'title' => 'Change audit',
            'paragraphs' => [
                'audit_logs is provided for user-action history: action, entity type and ID, old and new JSON values, IP, user agent, and time. The user foreign key uses SET NULL so the history survives account deletion.',
                'Important: the current code has no AuditRepository or service that writes rows to audit_logs. The table’s existence does not mean that client and contact changes are already audited. These data cannot be relied upon for investigating user actions until recording is implemented.',
                'A correct implementation must write the audit in the same transaction as the entity change or through a guaranteed queue. Store only the required fields and mask passwords, API secrets, and other sensitive values. An audit failure must not silently create a false impression of complete history.',
            ],
            'examples' => [[
                'title' => 'Intended audit record',
                'code' => <<<'SQL'
INSERT INTO audit_logs (
    user_id, action, entity_type, entity_id,
    old_values, new_values, ip_address, user_agent
) VALUES (
    :user_id, 'contact.updated', 'contact', :contact_id,
    :old_values_json, :new_values_json, :ip, :user_agent
);
SQL,
            ]],
        ],
        [
            'id' => 'database-integrity',
            'title' => 'Foreign keys and deletion rules',
            'paragraphs' => [
                'CASCADE is used for dependent data that has no meaning without its owner: user_permissions, user_preferences, tag relationships, client_contacts, field options, import rows, and errors. SET NULL is used for historical references: record author, batch user, client sector, object created by import, and API key in a log.',
                'RESTRICT protects a system role while users refer to it. UNIQUE enforces business uniqueness for user and contact emails, client commercial_name, catalog names and slugs, API client_id, log request_id, and composite relationship pairs. CHECK rejects empty or untrimmed key strings, invalid Boolean flags, and simultaneous storage of several typed custom_field_values values.',
                'Not every domain rule is a constraint. A client is not limited to one primary contact, custom-field requiredness is checked by the application, and polymorphic custom_field_values has no entity foreign key. Direct SQL changes and new repositories must enforce these invariants explicitly.',
            ],
            'examples' => [[
                'title' => 'Key consequences of deletion',
                'code' => <<<'CODE'
DELETE users
  → CASCADE: user_permissions, user_preferences
  → SET NULL: created_by, updated_by, import/export user_id, audit user_id

DELETE clients or contacts
  → CASCADE: client_contacts and corresponding tag relationships
  → custom_field_values are NOT removed by a foreign key

DELETE custom_fields
  → CASCADE: custom_field_options, custom_field_values

DELETE api_keys
  → SET NULL: api_logs.api_key_id; the log is preserved
CODE,
            ]],
        ],
        [
            'id' => 'database-indexes',
            'title' => 'Indexes, search, and performance',
            'paragraphs' => [
                'Primary and unique keys are automatically indexes. Additional B-tree indexes cover foreign keys, statuses, dates, and frequently filtered fields. In junction tables, a composite PRIMARY KEY works well from its first column, while a separate index on the second column supports reverse lookups.',
                'contacts and custom_field_values define FULLTEXT indexes, but current repositories do not use MATCH ... AGAINST: text searches use LIKE with a %value% pattern. This pattern normally cannot use an ordinary B-tree index. It is acceptable for a small database, but as contacts grow, measure searches with EXPLAIN ANALYZE and move to FULLTEXT or a separate search service if required.',
                'Create an index for a specific query, not for every column. Excess indexes take space and slow INSERT/UPDATE. Column order matters in a composite index: idx_api_logs_key_created helps WHERE api_key_id = ? ORDER BY created_at, but does not replace an index beginning with created_at for a general time range.',
            ],
            'examples' => [
                ['title' => 'Inspecting a query plan', 'code' => <<<'SQL'
EXPLAIN ANALYZE
SELECT id, full_name, email
FROM contacts
WHERE created_at >= '2026-01-01 00:00:00'
  AND email_status = 'valid'
ORDER BY created_at DESC
LIMIT 50;
SQL],
                ['title' => 'FULLTEXT not yet used by the application', 'code' => <<<'SQL'
SELECT id, full_name, email, phone
FROM contacts
WHERE MATCH(full_name, email, phone)
      AGAINST(:query IN NATURAL LANGUAGE MODE)
ORDER BY created_at DESC
LIMIT 50;
SQL],
            ],
        ],
        [
            'id' => 'database-transactions',
            'title' => 'Transactions and concurrent changes',
            'paragraphs' => [
                'A transaction must cover the complete business invariant. If a contact is created and then receives client relationships, tags, and custom fields, commit is valid only after every step succeeds. Otherwise, an exception can leave a partially created object.',
                'Every query uses one Illuminate Database connection. Composite operations should use Database::transaction(): the helper opens and completes a transaction when it owns it or joins the callback to an existing API-batch or import-row transaction. An exception automatically rolls back the owner and is rethrown.',
                'A transaction alone does not prevent two concurrent decisions based on stale data. Claim work with a conditional UPDATE and rowCount(), as in import; use SELECT ... FOR UPDATE or optimistic locking with a version/updated_at for strict editing. Enforce uniqueness with a UNIQUE index and handle conflicts as expected errors.',
            ],
            'examples' => [[
                'title' => 'Transaction boundary in a service',
                'code' => <<<'PHP'
Database::transaction(function (): int {
    $contactId = $this->contacts->create($contact);
    $this->contacts->syncClients($contactId, $clientIds);
    $this->entityTags->sync('contact', $contactId, $tagIds);
    $this->customFields->saveValues('contact', $contactId, $fields, $values);

    return $contactId;
});
PHP,
            ]],
        ],
        [
            'id' => 'database-schema-changes',
            'title' => 'Schema changes and migrations',
            'paragraphs' => [
                'database/schema.sql is a complete snapshot for a clean installation. It begins by disabling foreign-key checks and running DROP TABLE, so executing it on a production database destroys data. Existing systems are updated with separate sequential SQL files in database/migrations; there is currently no automatic runner or applied-version table.',
                'Each change must be a separate SQL file with a unique date and name, applied first to a database copy and recorded in an external deployment log. The file should contain only the transition from one schema version to the next, while schema.sql is updated for new installations after verification. The 20260729 migrations first make permissions fail-closed and then add UNIQUE and CHECK constraints after diagnosing existing data.',
                'Before ALTER TABLE, create a backup, estimate table size and locking, and plan code compatibility for staged deployment. MySQL DDL may perform an implicit commit, so a normal START TRANSACTION cannot be assumed to roll back a schema change. Document rollback separately and test it on a test database.',
            ],
            'examples' => [
                ['title' => 'Recommended migration structure', 'code' => <<<'CODE'
database/
├── schema.sql
└── migrations/
    ├── 20260729_fail_closed_permissions.sql
    └── 20260729_enforce_database_constraints.sql
CODE],
                ['title' => 'Example forward migration', 'code' => <<<'SQL'
ALTER TABLE contacts
    ADD COLUMN source VARCHAR(100) NULL AFTER company,
    ADD INDEX idx_contacts_source (source);

-- After application verification, add the same field to the current schema.sql.
SQL],
            ],
        ],
        [
            'id' => 'database-development',
            'title' => 'Developer workflow with the database',
            'paragraphs' => [
                'A model change starts with the schema and data workflows, followed by Repository, Service, Controller, API, import, export, and view updates. A new column rarely affects only one SELECT: check creation, editing, filtering, bulk operations, the API format, and backup restoration.',
                'For diagnostics, use SHOW CREATE TABLE, SHOW INDEX, INFORMATION_SCHEMA, EXPLAIN ANALYZE, and precise SELECT queries. Do not repair production data manually without a saved query, a preliminary SELECT, and a backup. Run a bulk UPDATE first as a SELECT with the same WHERE inside a transaction or on a database copy.',
                'Test data must not contain real personal information. Anonymize any production snapshot used for development: emails, phone numbers, names, IPs, request_body, response_body, and custom-field values may all contain personal data.',
            ],
            'examples' => [
                ['title' => 'Checking the schema before a change', 'code' => <<<'SQL'
SHOW CREATE TABLE contacts;
SHOW INDEX FROM contacts;

SELECT TABLE_ROWS, DATA_LENGTH, INDEX_LENGTH
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'contacts';
SQL],
                ['title' => 'Model-change checklist', 'code' => <<<'CODE'
[ ] separate migration and updated schema.sql
[ ] compatible foreign-key types
[ ] required UNIQUE, FOREIGN KEY, and indexes
[ ] Repository and Service transaction boundary
[ ] forms, filters, API, import, and export
[ ] deletion and NULL handling
[ ] tests with existing and empty data
[ ] backup and a clear rollback procedure
CODE],
            ],
        ],
        [
            'id' => 'database-health',
            'title' => 'Integrity checks and maintenance',
            'paragraphs' => [
                'Periodically monitor growth in large tables: contacts, custom_field_values, import_rows, import_errors, and api_logs. Logs and imports need an agreed retention policy. Delete history in small batches and only after understanding cascades, avoiding long locks and a large binary-log spike.',
                'CHECK TABLE does not replace logical checks. Separately look for orphaned polymorphic values, unknown permission_key values, import_batches stuck in processing, and API logs without a retention policy. Evaluate tables and indexes after large deletions, but do not run OPTIMIZE TABLE automatically on large production tables without a maintenance window.',
                'A backup is usable only after a test restoration. Use mysqldump --single-transaction for a consistent InnoDB dump and store the copy separately from the application server. Test restoration together with a compatible code version and configuration.',
            ],
            'examples' => [[
                'title' => 'Several logical checks',
                'code' => <<<'SQL'
-- Imports stuck for more than two hours
SELECT id, original_filename, started_at
FROM import_batches
WHERE status = 'processing'
  AND started_at < NOW() - INTERVAL 2 HOUR;

-- Compare unknown permission keys with Auth::permissionDefinitions()
SELECT DISTINCT permission_key
FROM user_permissions
ORDER BY permission_key;

-- Size of the fastest-growing tables
SELECT TABLE_NAME, TABLE_ROWS, DATA_LENGTH, INDEX_LENGTH
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
ORDER BY DATA_LENGTH + INDEX_LENGTH DESC;
SQL,
            ]],
        ],
    ],
];
