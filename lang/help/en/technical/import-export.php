<?php

return [
    'title' => 'Import and export',
    'description' => 'Internal batch data exchange: CSV/XLSX upload and reading, column mapping, transactions, result logging, and streamed file delivery.',
    'icon' => 'ph-arrow-elbow-down-right',
    'sections' => [
        [
            'id' => 'import-export-boundary',
            'title' => 'The subsystem’s place in the application',
            'paragraphs' => [
                'Import and export are separate server-side workflows in the web interface. They do not belong to public /api/v1 and do not use background queues. ImportController handles upload, preview, import execution, and error review; ExportController renders the field-selection page and immediately streams the completed file in the HTTP response. Both controllers use the normal user session and common CSRF protection for POST requests.',
                'Access is divided between two permissions. Every /imports route requires imports.manage, while /exports requires exports.use. A user with the relevant permission sees the shared operation history, not only their own jobs. Tables store the initiating user_id; if that user is deleted, the foreign key changes it to NULL without deleting history.',
                'The core logic is outside the controllers. ImportManager coordinates the file, batch, and entity processor; ImportFileReader reads formats; ImportMapping defines permitted column targets. For export, ExportManager assembles the plan, ExportService builds Query Builder, ExportWriter writes the result, and ImportRepository and ExportRepository persist operation state.',
            ],
            'examples' => [[
                'title' => 'Subsystem routes',
                'code' => <<<'CODE'
GET  /imports                 ImportController::index
POST /imports/upload          ImportController::storeUpload
POST /imports/process         ImportController::process
GET  /imports/errors?id={id}  ImportController::errors

GET  /exports                 ExportController::index
POST /exports/download        ExportController::download
CODE,
            ]],
        ],
        [
            'id' => 'import-upload',
            'title' => 'Uploading an import file',
            'paragraphs' => [
                'Import supports only CSV and XLSX files up to 20 MB. ImportManager checks the PHP error code, size, extension, and MIME type. XLSX additionally requires OpenSpout. A file is rejected when its extension and actual type disagree. If the fileinfo extension is unavailable, the MIME check is skipped, leaving the filename extension as the only format check.',
                'An accepted file is moved to storage/imports, outside the public_html directory. The directory is created with 0770 permissions. The original name is retained only for the interface; the physical name is built from the entity type, time, and random bytes. basename() is used when accessing the file, preventing stored_filename from escaping the import directory.',
                'The application limit must be coordinated with PHP and the web server. upload_max_filesize, post_max_size, and client_max_body_size must allow more than 20 MB to account for multipart overhead. Prepared contacts-import-template and clients-import-template files are in public_html/assets/templates as CSV and XLSX static files. When importable fields change, update the templates together with the mapping code.',
            ],
            'examples' => [[
                'title' => 'Accepted filename and location',
                'code' => <<<'CODE'
Original: contacts-july.xlsx
Stored:   storage/imports/contacts-2026-07-29-14-35-08-a93f10c42e77.xlsx

Application limit: 20 MB
Formats:           csv | xlsx
CODE,
            ]],
        ],
        [
            'id' => 'import-reading',
            'title' => 'Reading CSV and XLSX',
            'paragraphs' => [
                'ImportFileReader provides one rows() generator for both formats. The first row is always treated as the header, empty data rows are skipped, and each row number is preserved as the user sees it in the table. Leading and trailing whitespace is removed from headers and values; a UTF-8 BOM is removed from the first header. An empty header is ignored when combining a row with its columns.',
                'CSV is read with standard fgetcsv() and PHP defaults: comma delimiter, standard escaping, and no automatic encoding or delimiter detection. Semicolon-delimited files or encodings other than UTF-8 must be converted beforehand or require a dedicated reader extension.',
                'XLSX is read through OpenSpout in true streaming mode. The active sheet is used, dates are formatted for import, formula values are read from their saved result, and empty rows remain in the internal iterator so row numbers match the source sheet. The complete workbook is not loaded into memory.',
            ],
            'examples' => [[
                'title' => 'Row-generator contract',
                'code' => <<<'PHP'
foreach ($reader->rows($path, $fileType) as $item) {
    $item['row_number']; // 2, 3, 4... — number in the source file
    $item['headers'];    // normalized first-row headers
    $item['row'];        // ['Full name' => 'Ana Ruiz', ...]
}
PHP,
            ]],
        ],
        [
            'id' => 'import-preview-mapping',
            'title' => 'Preview and column mapping',
            'paragraphs' => [
                'After upload, import_batches is created with status uploaded and the browser redirects to /imports?id={batch}. preview() displays no more than ten rows but reads every row to calculate total_rows. For a large CSV this is an extra complete pass; for XLSX it is an extra complete workbook read before the actual import.',
                'ImportMapping contains an allowlist of system fields for contacts and clients and a set of English header aliases. suggest() only proposes matches; the user may change each target, exclude a column, or select __custom. Before processing, clean() discards unknown targets again, so form manipulation cannot pass an arbitrary column name to a repository.',
                'A contact mapping must include full_name; a client mapping must include commercial_name. The mapped column’s presence is checked, while its value is validated per row. Several source columns may target one system field, but mapRow() retains the last value; duplicate file headers also collapse in the associative array. Such files are ambiguous and should be rejected by future strict validation.',
            ],
            'examples' => [[
                'title' => 'Mapping form data',
                'code' => <<<'CODE'
mapping[Name]                = full_name
mapping[Email address]       = email
mapping[Labels]              = tags
mapping[Preferred language]  = __custom

custom_fields[Preferred language][field_type] = select
CODE,
            ]],
        ],
        [
            'id' => 'import-processors',
            'title' => 'Processors and the row-processing pattern',
            'paragraphs' => [
                'ImportManager selects ContactImportProcessor or ClientImportProcessor from entity_type. Both inherit AbstractImportProcessor, which resolves tags, sectors, and contacts and prepares custom fields. The processor adapts an import row; final persistence goes through the shared ContactWriteService or ClientWriteService also used by HTML and the API.',
                'Processing calls set_time_limit(0), reads the file a second time, and runs every non-empty row through the same Database::transaction() cycle: map values, perform domain processing, commit or roll back, and record a problem. The write service joins the open row transaction. After an error, the processor is recreated so its caches contain no ids for rolled-back entities.',
                'Removing the PHP time limit does not cancel timeouts in Nginx, FastCGI, the load balancer, or the browser. With no queue or separate worker, a long import remains tied to one HTTP request. For genuinely large datasets, the next architectural step is a background job with chunks, heartbeat, and a separate progress screen.',
            ],
            'examples' => [[
                'title' => 'Lifecycle of one row',
                'code' => <<<'CODE'
raw row
  → ImportMapping::mapRow()
  → beginTransaction()
  → ContactImportProcessor | ClientImportProcessor
      → main entity
      → relations
      → custom fields
  → commit()

ImportRowException | Throwable
  → rollback()
  → import_rows + import_errors
CODE,
            ]],
        ],
        [
            'id' => 'contact-import-rules',
            'title' => 'Contact import rules',
            'paragraphs' => [
                'A contact requires a non-empty full_name. Email is optional but, when provided, must pass FILTER_VALIDATE_EMAIL. An email matching an existing contact or an earlier row in the same run is skipped rather than treated as an error. The UNIQUE index on contacts.email finally enforces the rule for concurrent imports; a constraint conflict is also recorded as skipped.',
                'The contact is created with full_name, email, phone, and company. EmailInspector runs with DNS checking disabled: the address is classified as business or personal, but email_status receives unknown. This deliberately avoids thousands of blocking MX requests during a bulk operation.',
                'If the client column contains a name, commercial_name is searched. A missing client is created automatically as active and not connected to the API; its supplied sector may also be created. The contact is then linked to the client. Contact tags are synchronized and also added to the related client, without removing the client’s existing tags.',
            ],
            'examples' => [[
                'title' => 'Contact-row validation results',
                'code' => <<<'CODE'
empty full_name       → error
invalid email format  → error
duplicate email       → skipped
empty email           → allowed
unknown client name   → create active client, then link contact
CODE,
            ]],
        ],
        [
            'id' => 'client-import-rules',
            'title' => 'Client import rules',
            'paragraphs' => [
                'A client requires a non-empty commercial_name. A commercial name matching the database or an earlier row in the current run is marked skipped. A new client receives mapped company and address fields, is_active = 1, and is_web_connected = 0.',
                'The sector is found by name and created automatically when absent. Tags are likewise created as needed and then synchronized with the new client. tags values may use comma, semicolon, or vertical-bar separators; repeated names in one row are reduced to unique ids.',
                'The contact column is stricter: every name must already exist in the database. Multiple names are separated by commas, semicolons, or vertical bars. If any contact is missing, the entire client row rolls back. Contacts are not created automatically from a name alone because a complete record may require additional data and unambiguous identification.',
            ],
        ],
        [
            'id' => 'import-custom-fields',
            'title' => 'Custom fields during import',
            'paragraphs' => [
                '__custom means using the source-column header as the custom-field name. The slug is generated through Illuminate\Support\Str::slug(); if a slug cannot be produced, a stable SHA-256 suffix is used. An existing field is found by entity_type + slug; otherwise a new optional, filterable field is created with sort_order = 0.',
                'Supported types are text, textarea, number, date, email, url, select, and checkbox. For checkbox, 1, yes, true, and si are true case-insensitively; every other value becomes 0. Number is cast to float, date is written as a string in value_date, and the other types use value_text. Email, URL, date, and select-option values receive no special validation during import.',
                'If a field with the slug exists, the selected import type does not change its schema: the existing field and its real field_type are used for saving. A new select does not automatically create custom_field_options, so a value may be stored without appearing among interface options. These cases require explicit checks when import is extended.',
            ],
            'examples' => [[
                'title' => 'Created field definition',
                'code' => <<<'PHP'
[
    'entity_type'  => 'contact',
    'name'         => 'Preferred language',
    'slug'         => 'preferred-language',
    'field_type'   => 'select',
    'is_required'  => 0,
    'is_filterable'=> 1,
    'sort_order'   => 0,
    'default_value'=> null,
]
PHP,
            ]],
        ],
        [
            'id' => 'import-state-errors',
            'title' => 'Import states, transactions, and errors',
            'paragraphs' => [
                'import_batches is the job log. The normal status path is uploaded → previewed → processing → completed or partial. An atomic UPDATE in claimForProcessing() moves only uploaded or previewed to processing and prevents a double launch. partial is used when at least one row is skipped or erroneous; an unhandled pipeline exception produces failed.',
                'A transaction covers one row, not the entire file. A row error therefore rolls back that row’s created entity, relationships, tags, sector, and custom values, while earlier successful rows remain in the database. After a global failure, a batch may have status failed and still contain already imported records.',
                'import_rows is currently created only for skipped and error; successful rows and their related_contact_id/related_client_id are not recorded. import_errors duplicates the issue message and links to import_rows. The error screen returns at most 500 records with raw_data. Details would be cleared on a new run, but completed and stuck processing batches cannot be rerun: there is no automatic recovery, heartbeat, or resume command.',
            ],
            'examples' => [[
                'title' => 'State machine',
                'code' => <<<'CODE'
uploaded ──preview──> previewed
   │                    │
   └──────process───────┴──> processing
                                  ├──> completed  (all rows imported)
                                  ├──> partial    (skipped or errors)
                                  └──> failed     (pipeline failure)

Terminal states are not retryable by the current UI or manager.
CODE,
            ]],
        ],
        [
            'id' => 'export-pipeline',
            'title' => 'Export pipeline',
            'paragraphs' => [
                'Export begins at /exports with selection of an entity, fields, and format. ExportController normalizes entity and format, collects form parameters, and passes them to ExportManager. The manager gets system and custom-field definitions, cleans the selection against an allowlist, builds a Query Builder plan, and creates an export_batches row with status processing.',
                'ExportService returns a plan containing Builder and headers rather than the data itself. ExportWriter calls cursor() and passes rows sequentially to CSV or OpenSpout. After a successful write, ExportRepository marks the batch completed and stores its row count; an exception marks it failed and is rethrown.',
                'If no valid fields are selected, sanitizeFields() supplies id. Column order follows fields[] order in POST. Header labels come from fieldDefinitions(); when localizing the interface, note that current export group and column names are defined in English in ExportService rather than language files.',
            ],
            'examples' => [[
                'title' => 'Responsibility split',
                'code' => <<<'CODE'
ExportController  → HTTP input and download headers
ExportManager     → normalize, create batch, coordinate result
ExportService     → whitelist fields, compose Query Builder
ExportWriter      → iterate cursor, write CSV or XLSX
ExportRepository  → processing/completed/failed history
CODE,
            ]],
        ],
        [
            'id' => 'export-query',
            'title' => 'Export fields, relationships, and filters',
            'paragraphs' => [
                'Contacts can export base fields, tags, client_names, and all custom fields for contact. Clients can export base and address fields, sector_name, tags, contact_count, and custom fields for client. Related names and tags use separate GROUP_CONCAT aggregation subqueries; selected custom fields use conditional MAX(CASE...) expressions keyed by numeric ids.',
                'Subqueries and JOINs are added only for selected columns. Filter values use Query Builder bindings, standard field names come from fixed arrays, and custom ids first pass through the definition allowlist. Raw expressions are limited to GROUP_CONCAT and MAX(CASE...) aggregates, keeping dynamic SELECT construction controlled.',
                'The internal contract supports contact filters by name, email, and phone through LIKE, presence of company, client, and tags. Client filters include commercial_name, legal_name, city, country, province, sector_id, and tags. Multiple selected tags mean matching at least one. The current /exports page does not render these controls, so the standard form sends empty values and exports every record of the selected entity.',
            ],
            'examples' => [[
                'title' => 'Example contact export plan',
                'code' => <<<'SQL'
SELECT contacts.full_name,
       contacts.email,
       COALESCE(_tags_agg.tag_names, '') AS tags,
       COALESCE(_cfv_agg.cf_12, '') AS cf_12
FROM contacts
LEFT JOIN (...) _tags_agg ON _tags_agg.contact_id = contacts.id
LEFT JOIN (...) _cfv_agg  ON _cfv_agg.entity_id = contacts.id
WHERE contacts.full_name LIKE ?
ORDER BY contacts.id DESC
SQL,
            ]],
        ],
        [
            'id' => 'export-writers',
            'title' => 'Generating CSV and XLSX',
            'paragraphs' => [
                'CSV is written directly to php://output through fputcsv(): headers first, then rows from Query Builder cursor(). This is the most memory-efficient path. XLSX uses OpenSpout to write headers and the same rows sequentially to the output stream without accumulating the entire workbook in memory. Export size is still limited by HTTP request duration and temporary disk capacity.',
                'Before writing, every string cell passes through safeCell(). Values beginning with =, +, @, or a minus sign followed by a non-numeric expression receive a leading apostrophe. This protects against formula injection: exported user data must not become a formula when opened in Excel or LibreOffice.',
                'Content-Type and Content-Disposition are sent before generation. If an error occurs after CSV streaming or XLSX writing begins, the server can no longer return a normal HTML error page; the user may receive a partial or corrupt file, while export_batches is marked failed. Large or critical exports are safer when first written to a private temporary file, atomically marked ready, and only then downloaded.',
            ],
            'examples' => [[
                'title' => 'Protecting cell content',
                'code' => <<<'CODE'
=HYPERLINK("https://example.test")  → '=HYPERLINK("https://example.test")
@SUM(A1:A2)                          → '@SUM(A1:A2)
-125.50                              → -125.50
-cmd|' /C calc'!A0                   → '-cmd|' /C calc'!A0
CODE,
            ]],
        ],
        [
            'id' => 'export-history-storage',
            'title' => 'History and result storage',
            'paragraphs' => [
                'export_batches stores the initiator, entity, format, name, filter JSON, selected-field JSON, row count, status, and completion time. stored_filename currently contains only the name suggested to the browser. The CSV or XLSX is not stored on disk, so history is an operation audit, not an archive that can be downloaded again.',
                'Import behaves differently: source files physically remain in storage/imports. The project has no automatic deletion policy for files, import_batches, import_rows, import_errors, or export_batches. Production must define retention, personal-data cleanup, and acceptable directory size and implement them through a cron command or administrative procedure.',
                'Deleting import_batches cascades to its rows and errors, but the database cannot delete the disk file. Cleanup must first select expired batches precisely, safely delete only basename(stored_filename) inside storage/imports, and then remove the row, or perform the operations in another coordinated and logged order. Never construct a deletion path directly from an unchecked database value.',
            ],
        ],
        [
            'id' => 'import-export-extension',
            'title' => 'Extension and required checks',
            'paragraphs' => [
                'Adding a system import field requires coordinated changes to the template, ImportMapping::fields(), suggest() aliases, and the corresponding processor. A new entity requires its own processor, permitted entity_type in every controller and manager, an ENUM value or a manual history-table change, interface tabs, and domain tests. A file format requires upload validation, a MIME map, reader, and writer; changing only HTML accept has no server effect.',
                'For export, add a field to fieldDefinitions() and the relevant SQL-builder branch. Never accept an SQL name directly from POST. A new relationship must aggregate to one row per entity or its JOIN multiplies results. A custom field must remain tied to an id from the actually loaded allowlist.',
                'Minimum automated checks cover both formats, BOM and empty CSV rows, a header-only file, duplicate headers, invalid MIME, and size limits; required fields, duplicates, rollback of relationships and custom values; every batch status transition and global failure; formula escaping, empty field selection, filters, aggregates, large CSV, and XLSX memory exhaustion. Also test concurrent execution of one import batch and two imports with the same email.',
            ],
            'examples' => [[
                'title' => 'Import-change checklist',
                'code' => <<<'CODE'
[ ] public_html/assets/templates/*.csv and *.xlsx
[ ] ImportMapping::fields() and suggest()
[ ] ContactImportProcessor or ClientImportProcessor
[ ] validation and duplicate semantics
[ ] transaction includes entity, relations and custom values
[ ] preview and result screens
[ ] import_batches status/counts and issue details
[ ] CSV + XLSX integration tests
[ ] retention and recovery behavior
CODE,
            ]],
        ],
        [
            'id' => 'import-export-known-gaps',
            'title' => 'Current limitations and technical debt',
            'paragraphs' => [
                'Import is synchronous, reads the file twice, and has no progress, cancellation, or recovery for stuck processing. XLSX is loaded fully into memory. CSV does not detect delimiter or encoding. Successful rows are not logged individually, and the 500-item error-screen limit has no pagination or full-report export.',
                'Contact email and client commercial_name uniqueness are enforced by UNIQUE indexes, while preliminary SELECT checks provide clear messages and early skipping. Creating a custom select does not create its options, and number and date values have no strict domain validation. There is no automatic cleanup for source files or history.',
                'Export has no row limit or background job. CSV is relatively memory-efficient, but XLSX can exhaust memory_limit. Filters exist in the server layer but not in the current form. History cannot download a result again because the file is not stored. These are boundaries of the current implementation, not a promised contract; fixing them must update the schema, interface, operating procedures, and this documentation together.',
            ],
        ],
    ],
];
