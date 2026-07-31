<?php

return [
    'title' => 'Domain model',
    'description' => 'Meaning of the core ContactCore entities, their relationships and states, and the business rules application code must preserve.',
    'icon' => 'ph-arrow-elbow-down-right',
    'sections' => [
        [
            'id' => 'domain-purpose',
            'title' => 'Purpose of the domain model',
            'paragraphs' => [
                'The domain model describes neither tables nor screens, but the meaning of the data ContactCore works with. It answers who the system calls a client or contact, which relationships are permitted, what record states mean, and which conditions must remain true after every operation. The Database section covers storage; here the same data is considered from the perspective of business and application code.',
                'The current project has no separate Entity classes or ORM models: entities pass between controllers, services, and repositories as associative arrays. Domain constraints are therefore not collected in one object. Some rules live in interface controllers, some in import and API handlers, and some in SQL constraints. A model change must review all of these entry points.',
                'The platform’s main purpose is to maintain a database of people who submitted leads on the websites of our organization’s clients. The central chain is therefore: our organization works with a client company, the client’s website receives a lead, and the person who submitted it becomes a contact linked to that client.',
            ],
            'examples' => [[
                'title' => 'The main domain chain',
                'code' => <<<'CODE'
Our organization
        │ works with
        ▼
Client — a company for which leads are collected
        │ receives a lead through a website or another channel
        ▼
Contact — a person who submitted their details
        │ is additionally classified with
        ├── tags
        └── custom fields
CODE,
            ]],
        ],
        [
            'id' => 'domain-map',
            'title' => 'Model map and boundaries',
            'paragraphs' => [
                'Client and Contact form the core of the model. Client represents a company that is a client of our organization. Contact represents a person in the shared contact database. There is no ownership relationship between them: a contact may relate to several clients, and a client may have many contacts.',
                'Sector, Tag, and CustomField extend the core. A sector gives a client one industry. Tags provide arbitrary classification for clients and contacts. Custom fields extend records without changing the main table. User, Role, and Permission control who may perform operations, but do not make a user the owner of records they created.',
                'Import, export, API keys, API logs, and audit are supporting models. They describe the origin, delivery, and history of data but do not replace client and contact entities. For example, an import row may refer to a created contact, but after import the Contact itself remains the source of truth.',
            ],
            'examples' => [[
                'title' => 'Simplified entity map',
                'code' => <<<'CODE'
                         Sector
                            │ 0..1
                            ▼
Tag N ─────────────── N Client N ─────────────── N Contact N ─────────────── N Tag
                            │                          │
                            └──── Custom fields ──────┘

User ── performs operations but does not own Client or Contact
Import / Export / API ── create and transport core entities
CODE,
            ]],
        ],
        [
            'id' => 'domain-client',
            'title' => 'Client',
            'paragraphs' => [
                'A client is a company our organization works with and for which it collects leads. It is not the end visitor of a website or the company where a contact works. The primary client identifier is the numeric id; the required commercial_name is intended for display and search but is not a reliable technical identifier.',
                'The record contains a legal name, CIF, address, website, notes, and one sector. A client may have any number of tags, contacts, and custom-field values. Most company details are optional: the API and contact import can create a minimal client record from commercial_name alone, and it can then be completed manually.',
                'is_active means that cooperation with the client is currently active. is_web_connected means that its website forms are configured to send leads to ContactCore. The flags are independent: an API-connected client may be inactive, while an active client may have no web integration. is_active_date and is_web_connected_date store the last change time of the corresponding flag, not the client creation date.',
                'is_web_connected describes the client’s state. It does not create an API key, define scopes, or block requests. Integration access is controlled independently through ApiKey and ApiAuthenticator.',
            ],
            'examples' => [[
                'title' => 'Minimal and complete records',
                'code' => <<<'CODE'
Minimum valid client
  id: 42
  commercial_name: Acme Studio

Extended record
  legal_name, cif, address, postal_code
  city, province, country, website, notes
  sector_id
  is_active, is_web_connected
  tags[], contacts[], custom_fields{}
CODE,
            ]],
        ],
        [
            'id' => 'domain-contact',
            'title' => 'Contact',
            'paragraphs' => [
                'A contact is a person whose data entered the platform, usually after a lead on a client website but also through manual creation, import, or the API. A contact exists in the shared database and is not duplicated for each client. full_name is required; email and phone may be absent.',
                'The numeric id remains the contact’s primary identity and must be used in foreign keys and URLs. A populated email is unique throughout contacts: the interface, API, and import perform a clear preliminary check, while a UNIQUE index protects the rule from concurrent requests. Multiple contacts without email are allowed because NULL does not conflict with another NULL.',
                'EmailInspector determines the address type and technical status independently. is_corporate_email indicates whether the domain is business-related, while email_status is valid, invalid, or unknown. valid means a correct format and a discovered MX record, not that a particular mailbox exists. DNS checking is disabled during bulk import, so a correctly classified address receives unknown.',
                'company is an ordinary text field containing the person’s likely employer. It may be entered manually or found by Gemini for a business address. It has no foreign key to Client and must not be used instead of client_contacts. company_change_date records an automatic change or manual confirmation of the AI result.',
            ],
            'examples' => [[
                'title' => 'Two different companies in contact data',
                'code' => <<<'CODE'
Contact #108
  full_name: Ana García
  company: Northwind Logistics     ← employer, free text
  clients:                         ← whose forms produced the contact
    - Acme Studio
    - Contoso Events
CODE,
            ]],
        ],
        [
            'id' => 'domain-client-contact',
            'title' => 'Client–contact relationship',
            'paragraphs' => [
                'Client and Contact have a many-to-many relationship through client_contacts. This is necessary because one person may submit leads on several client websites, while a client naturally has many contacts. Removing the relationship deletes neither the person nor the company.',
                'When a contact record is saved, syncClients replaces its complete relationship set: it first deletes current rows and then inserts the selected client_id values. The addClientsToContacts bulk action behaves differently and only adds missing relationships. Choose deliberately: sync means “the complete final set,” while add means “append to the existing set.”',
                'client_contacts includes relation_label and is_primary. Repositories read these values, but normal forms, import, and the API currently create relationships with their defaults: relation_label = NULL and is_primary = 0. These attributes should not yet be treated as a completed user feature.',
            ],
            'examples' => [[
                'title' => 'Semantics of relationship operations',
                'code' => <<<'CODE'
Before: Contact #108 → [Client #4, Client #7]

syncClients(108, [7, 9])
After: Contact #108 → [Client #7, Client #9]

addClientsToContacts([108], [12])
After: Contact #108 → [Client #7, Client #9, Client #12]
CODE,
            ]],
        ],
        [
            'id' => 'domain-classification',
            'title' => 'Sectors and tags',
            'paragraphs' => [
                'A sector is a managed industry catalog. A client may have at most one sector_id; a contact has no sector of its own. When the interface filters contacts by sector, it resolves the sector through related clients. Deleting a sector that is in use is implemented as deactivation, while an unused sector may be deleted; the client foreign key also permits NULL after deletion.',
                'A tag is a shared flexible label. Clients and contacts use the same tags catalog, but assignments are stored separately. A tag on a client is not automatically assigned to related contacts, and vice versa. Such propagation is allowed only as a separate, explicitly described business rule.',
                'The API and import may automatically create missing sectors and tags by name. A catalog name therefore participates in resolving input data. The comparison uses a database query, while id remains the stable reference after resolution; renaming must not break existing relationships.',
            ],
            'examples' => [[
                'title' => 'When to use a sector or a tag',
                'code' => <<<'CODE'
sector: Technology       ← one relatively stable client industry
tags: Hot, Newsletter     ← several changing working attributes

Client.sector_id → Sector.id
Client ↔ Tag
Contact ↔ Tag
Contact ↛ Sector directly
CODE,
            ]],
        ],
        [
            'id' => 'domain-custom-fields',
            'title' => 'Custom fields as model extensions',
            'paragraphs' => [
                'CustomField describes an additional attribute for either client or contact. The entity_type + slug pair is the field’s unique name within an entity type. The type selects the value column: text types and select use value_text, number uses value_number, date uses value_date, and checkbox uses value_bool.',
                'A field definition and its value have different lifecycles. Changing the field name does not automatically change its slug and must not lose values. Allowed select choices live in custom_field_options. default_value is applied on record creation when no value is provided; is_filterable controls whether the field appears in filters.',
                'is_required is an application rule, not a database constraint. Likewise, custom_field_values uses the polymorphic entity_type + entity_id pair without a foreign key to clients or contacts. Deleting the main entity does not make the database cascade these values. Deletion code and integrity checks must clean up orphaned values.',
            ],
            'examples' => [[
                'title' => 'One logical value in storage',
                'code' => <<<'CODE'
CustomField
  entity_type: contact
  slug: employees
  field_type: number

CustomFieldValue
  field_id: 15
  entity_type: contact
  entity_id: 108
  value_number: 240
  value_text/value_date/value_bool: NULL
CODE,
            ]],
        ],
        [
            'id' => 'domain-entry-points',
            'title' => 'Creation channels and shared rules',
            'paragraphs' => [
                'The same entities are created through four paths: the HTML interface, import, the public API, and internal tools such as AI. Input preparation and transport validation remain in their adapters, but creation and updating of the main record, tags, relationships, and custom values are centralized in ContactWriteService and ClientWriteService. Their repositories’ direct create/update methods should not be used outside these services.',
                'Every composite write is now atomic regardless of the channel. The interface receives a write-service transaction; an API item joins its batch-item transaction; import joins its row transaction. An error rolls back the main record, its relationships, tags, and custom fields. The full API batch or import is not atomic: previously successful items or rows remain.',
                'The API and import may create catalog entities by name. The contact API also creates a minimal client if the supplied name is not found. This convenience is part of the domain model, not merely request parsing: changing it affects client counts, deduplication, and reports.',
            ],
            'examples' => [[
                'title' => 'What to check when adding a new rule',
                'code' => <<<'CODE'
[ ] HTML: Controller and form
[ ] API: ApiService, error format, and transaction
[ ] Import: mapping, processor, and row error
[ ] Repository: read and write queries
[ ] Export: new field and filters
[ ] Database: constraint, index, or manual schema change
[ ] Help: user and technical description
CODE,
            ]],
        ],
        [
            'id' => 'domain-lifecycle',
            'title' => 'Lifecycle and deletion',
            'paragraphs' => [
                'A client supports soft business deactivation through is_active, but the delete command performs a physical DELETE. A contact has no separate active flag and is also physically deleted. Therefore, “not currently working together” should be expressed by deactivating a client; delete only when the record truly must not remain in the working database.',
                'Deleting a client or contact cascades to client_contacts rows and corresponding tag assignments. A contact is not deleted with a client, nor a client with a contact. Custom-field values, audit data, and some log references use polymorphic or optional references and need a separate cleanup policy.',
                'created_at and updated_at describe the row’s technical lifetime. The schema includes created_by and updated_by for authorship, but the main ClientRepository and ContactRepository currently do not write them. Do not interpret NULL as a system author without checking the specific write path.',
            ],
            'examples' => [[
                'title' => 'Result of deleting a client',
                'code' => <<<'CODE'
DELETE Client #7
  deleted: Client #7
  deleted: its client_tags and client_contacts
  preserved: every related Contact
  requires control: custom_field_values(entity_type='client', entity_id=7)

If cooperation has simply ended:
  Client #7.is_active = 0
CODE,
            ]],
        ],
        [
            'id' => 'domain-invariants',
            'title' => 'Development invariants',
            'paragraphs' => [
                'An invariant is a condition that must remain true regardless of how data changes. ContactCore’s basic invariants include commercial_name for a client and full_name for a contact, a valid custom-field type, existence of related ids, and no duplicate rows in relationship tables. Use unique and foreign keys wherever a rule is genuinely unconditional.',
                'The database enforces non-empty normalized names, uniqueness of populated contact email and client commercial_name, valid Boolean values, and at most one typed value in a custom_field_values row. Requiredness of a particular custom field remains an application rule, and the link from custom_field_values to the main entity is polymorphic. These remaining rules must not be silently assumed: enforce them in the shared write service and cover them with tests.',
                'New features must rely on ids, distinguish Client from contact.company explicitly, account for the many-to-many relationship, and keep a composite operation consistent with a transaction. If a rule is shared by the interface, API, and import, it belongs in a reusable domain service; controllers and processors should only adapt input.',
            ],
            'examples' => [[
                'title' => 'Recommended boundary for contact creation',
                'code' => <<<'CODE'
BEGIN
  validate Contact
  resolve or create referenced Client records
  resolve or create Tag records
  INSERT contacts
  SYNC client_contacts
  SYNC contact_tags
  SAVE custom_field_values
COMMIT

On any error → ROLLBACK the entire operation
CODE,
            ]],
        ],
    ],
];
