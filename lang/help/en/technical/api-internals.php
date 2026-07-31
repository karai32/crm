<?php

return [
    'title' => 'Internal API architecture',
    'description' => 'Architecture of the public ContactCore API: routing, Basic Auth, scopes, resource services, transactions, responses, and logging.',
    'icon' => 'ph-arrow-elbow-down-right',
    'sections' => [
        [
            'id' => 'api-internal-boundary',
            'title' => 'The API boundary in the application',
            'paragraphs' => [
                'The public API is a separate HTTP boundary of the same ContactCore modular monolith. It uses the shared database, repositories, and domain entities but does not use browser sessions, HTML views, or AJAX contracts. Every route in the current version is under /api/v1 and returns JSON.',
                'The API is intended primarily for server-to-server integrations: forms on client websites, synchronization with external applications, and batch exchange. Internal /ajax/* routes use the session and CSRF, while /api/v1/* uses HTTP Basic Auth and scopes. These interfaces are not interchangeable even when they invoke the same repository.',
                'Architecturally, a request passes through Router, the shared ApiController, ApiAuthenticator, a resource ApiService, and Repository. The controller handles the common HTTP protocol, the service implements contact or client application behavior, and the repository handles SQL. ApiController then forms the response and request log.',
            ],
            'examples' => [[
                'title' => 'Complete API request path',
                'code' => <<<'CODE'
External system
      │ HTTPS + Basic Auth + JSON
      ▼
public_html/index.php
      ▼
Router
      ▼
ApiController::handle()
      ├── ApiAuthenticator
      ├── scope check
      ├── JSON decoding
      ▼
ContactApiService / ClientApiService
      ▼
Repository → MySQL
      ▼
ApiResult → JSON response → api_logs
CODE,
            ]],
        ],
        [
            'id' => 'api-internal-routes',
            'title' => 'Resources and routing',
            'paragraphs' => [
                'Two resources are currently published: contacts and clients. Each has the same CRUD surface. A collection supports GET and POST; an individual record supports GET, PATCH, and DELETE. Sectors and tags are passed inside these resources and have no independent routes.',
                'Router stores exact and parameterized routes separately. When a pattern matches, the {id} value is written to $_GET and ApiController::routeId() casts it to int. Zero, negative, and non-numeric values become a 404 error. Router does not pass parameters as controller method arguments.',
                'The version is part of the URL rather than a header. An incompatible contract change must receive a new prefix such as /api/v2 while v1 remains available for an agreed transition period. Adding an optional field or filter is usually compatible within v1.',
            ],
            'examples' => [[
                'title' => 'Current CRUD contract',
                'code' => <<<'CODE'
GET     /api/v1/{resource}
GET     /api/v1/{resource}/{id}
POST    /api/v1/{resource}
PATCH   /api/v1/{resource}/{id}
DELETE  /api/v1/{resource}/{id}

resource = contacts | clients
CODE,
            ]],
        ],
        [
            'id' => 'api-internal-controller',
            'title' => 'Shared resource controller',
            'paragraphs' => [
                'ApiController handles both resources and receives the resource name and a service instance in its constructor. This removes empty classes that differed only by the contacts or clients string. Resources are checked against an allowlist, and public_html/index.php creates two configured controller instances.',
                'index and show require {resource}:read; create, update, and destroy require {resource}:write. The path supplied to handle() is used for logging, so individual record routes are stored as the /{id} template rather than an actual URL containing a number.',
                'At the start of handle(), php://input is read, authentication runs, a 24-character request ID is created, and X-Request-Id is sent. The scope is then checked, last_used_at updated, and the service invoked. ApiException, PDOException, and other Throwable instances become stable JSON responses; an unhandled internal error never exposes the exception text to the client.',
            ],
            'examples' => [[
                'title' => 'Configuring resource controllers',
                'code' => <<<'PHP'
$apiControllers = [
    'contacts' => new ApiController('contacts', new ContactApiService()),
    'clients' => new ApiController('clients', new ClientApiService()),
];

// A shared loop registers five CRUD routes for every resource.
PHP,
            ]],
        ],
        [
            'id' => 'api-internal-auth',
            'title' => 'API keys and Basic Auth',
            'paragraphs' => [
                'An integration account consists of client_id and a random secret. ApiKeyController generates a client_id with the crm_ prefix and 16 random bytes, and a secret from 32 random bytes. Client ID identifies an API client and is unrelated to the CRM Client entity.',
                'The secret is shown to the administrator once and stored only as a SHA-256 hash. ApiAuthenticator reads Basic credentials from PHP_AUTH_USER/PHP_AUTH_PW or parses Authorization from HTTP_AUTHORIZATION, REDIRECT_HTTP_AUTHORIZATION, or getallheaders(). This accounts for differences between PHP-FPM and web-server configurations.',
                'After finding an active key by client_id, the supplied secret is hashed and compared with hash_equals(). Basic Auth does not encrypt credentials, so the API is permitted only over HTTPS. A revoked key has is_active = 0 and is no longer found by the authenticator; enabling it again restores access with the same secret.',
                'last_used_at is updated no more often than once every five minutes to avoid an extra write on every API request. Key deletion is physical; related api_logs remain through ON DELETE SET NULL but lose their link to the integration name.',
            ],
            'examples' => [[
                'title' => 'Credential verification',
                'code' => <<<'CODE'
Authorization: Basic base64(CLIENT_ID:SECRET)

client_id → SELECT active api_keys row
SECRET    → sha256(SECRET)
stored    → api_keys.secret_hash

hash_equals(stored, provided) → authenticated API key
CODE,
            ]],
        ],
        [
            'id' => 'api-internal-scopes',
            'title' => 'Scopes and operation authorization',
            'paragraphs' => [
                'Scopes are stored as a JSON array in api_keys. Contacts and clients each define read and write, producing four values in ApiController::SCOPES. New keys receive this list; syncScopes fully replaces an old key’s set, including removing obsolete sectors:* and tags:* values.',
                'hasScope() first looks for an exact match. A resource write scope also satisfies its read check: contacts:write implicitly grants contacts:read. The reverse is not true. Invalid JSON in scopes is treated as no permissions.',
                'Scopes are checked before JSON decoding or calling the resource service. Authentication failure returns 401 and WWW-Authenticate; an insufficient scope returns 403. Every route is checked independently: possession of a key does not imply access to all resources.',
            ],
            'examples' => [[
                'title' => 'Access matrix',
                'code' => <<<'CODE'
contacts:read
  ✓ GET /api/v1/contacts
  ✓ GET /api/v1/contacts/{id}
  ✗ POST / PATCH / DELETE

contacts:write
  ✓ GET /api/v1/contacts
  ✓ GET /api/v1/contacts/{id}
  ✓ POST / PATCH / DELETE
CODE,
            ]],
        ],
        [
            'id' => 'api-internal-input',
            'title' => 'Reading and parsing input',
            'paragraphs' => [
                'ApiController reads and trims php://input once per request. PATCH calls jsonObject() and accepts only a non-empty JSON object. POST calls jsonBatch(): a single object becomes a one-item array, while a JSON array is used as a batch. An empty body, scalar, malformed JSON, or batch over 100 items returns 422.',
                'Content-Type is not currently checked: any body that can be parsed as JSON is accepted. A client should still send application/json for a predictable public contract; if the server is tightened later, an unsuitable Content-Type should receive 415.',
                'GET filters are read from $_GET inside the resource service. Contacts and clients normalize page to at least 1 and per_page to 1–100 with a default of 25.',
                'PATCH is a partial update: an absent key preserves the current value, while explicit null or an empty string clears a supported optional field. For tags and clients, absence differs from an empty supplied set: an empty set removes every corresponding relationship. An empty custom_fields object changes nothing; to clear one custom field, send its slug with null or an empty string.',
            ],
            'examples' => [[
                'title' => 'POST normalization',
                'code' => <<<'CODE'
Single object:
{"full_name":"Ana"}
→ items[0] = {"full_name":"Ana"}

Batch:
[
  {"full_name":"Ana"},
  {"full_name":"Luis"}
]
→ items[0], items[1]

Maximum: 100 items
CODE,
            ]],
        ],
        [
            'id' => 'api-internal-services',
            'title' => 'Resource service layer',
            'paragraphs' => [
                'AbstractApiService defines a common CRUD surface: index(), show(), createBatch(), update(), and destroy(). It also contains shared operations for nullable strings, record lookup, tags, custom-field preparation, batch processing, and input lists. A resource service handles API validation, resolves external names to ids, and defines the response data shape.',
                'ContactApiService and ClientApiService do not write main entities and relationships directly: the prepared contract is passed to ContactWriteService or ClientWriteService. HTML and import use the same shared services. There are no separate SectorApiService and TagApiService classes; catalogs are resolved as nested values through AbstractApiService and their repositories.',
                'Services do not return database rows directly. detail() and format() cast ids to int and flags to bool and explicitly select response fields. This prevents password_hash, internal flags, or a newly added SELECT * column from appearing accidentally in the contract.',
                'Contact and client domain rules are shared with the HTML interface and import through ContactWriteService and ClientWriteService. A resource API service validates only JSON structure and resolves external names to ids; the write service checks required fields, email format, internal email classification, and duplicates. A new rule shared by every channel belongs at this common boundary.',
            ],
            'examples' => [[
                'title' => 'Layer responsibilities',
                'code' => <<<'CODE'
ApiController
  HTTP method, auth, scope, JSON, status, headers, log

ApiService
  validation, business operation, transaction, response DTO

Repository
  prepared SQL, persistence, queries
CODE,
            ]],
        ],
        [
            'id' => 'api-internal-batch',
            'title' => 'Batches and transaction boundaries',
            'paragraphs' => [
                'Every creation POST is processed through AbstractApiService::batch(). For each item, Database::transaction() opens a transaction on the shared Illuminate Database connection, and the resource service calls the shared write service. The nested write service sees the open transaction and joins it. ApiException, PDOException, or another error rolls back only the current item; processing continues with later items.',
                'A batch is therefore not atomic as a whole. If the first nine records are created and the tenth fails, the nine remain. However, one item’s complete composition—the main record, automatically created catalogs, relationships, tags, and custom fields—must either be saved entirely or rolled back entirely.',
                'A creation POST always returns HTTP 207 Multi-Status, even for one object and even when every item succeeds or fails. Top-level success means the batch was parsed and processed, not that every record was created. The integration must check data.results[*].success and preserve index to match results with the source array.',
                'Contact and client PATCH opens a transaction around resolving new catalog values and invoking the write service. Null for a relationship set means “do not change,” while a supplied empty array means “clear.” ClientWriteService merges partial changes with the current record, preserving is_active and is_web_connected when the API does not change them. POST has no built-in idempotency: retrying after a network timeout may create a duplicate.',
            ],
            'examples' => [[
                'title' => 'Partially successful batch',
                'code' => <<<'JSON'
HTTP/1.1 207 Multi-Status

{
  "success": true,
  "data": {
    "processed": 2,
    "created": 1,
    "failed": 1,
    "results": [
      {"index": 0, "success": true, "data": {"contact_id": 125}},
      {
        "index": 1,
        "success": false,
        "error": {
          "code": "duplicate_contact",
          "details": ["Contact with this email already exists"]
        }
      }
    ]
  }
}
JSON,
            ]],
        ],
        [
            'id' => 'api-internal-relations',
            'title' => 'Relationships, catalogs, and custom fields',
            'paragraphs' => [
                'tags and clients accept one name, a comma-separated string, or a JSON array. splitNames() removes empty values and ignores complex array items. Names are resolved to ids; missing tags are created automatically, and ContactApiService also creates a minimal client by commercial_name. ClientApiService similarly creates a missing sector.',
                'In PATCH, supplied tags or clients represents the complete final set and replaces existing relationships through sync(). It is not an append operation. If the key is absent, relationships remain unchanged. An integration must first retrieve the current state if it wants to preserve old items and add one new item.',
                'custom_fields supports a nested object and custom_fields.{slug} keys. expandCustomFieldKeys() converts dotted keys into a nested array. saveCustomFields() finds only predefined fields for the correct entity_type; an unknown slug is silently skipped. During creation, default_value is applied to fields not supplied by the integration.',
                'Response types are normalized: number becomes float, checkbox becomes bool, date and text remain strings, and an absent value becomes null. Requiredness and valid select options are not enforced by a single API validator, so future contract work requires explicit type and is_required checks.',
            ],
            'examples' => [[
                'title' => 'Equivalent input forms',
                'code' => <<<'JSON'
{"tags":"Lead,Newsletter"}
{"tags":["Lead","Newsletter"]}

{"custom_fields":{"language":"en","consent":true}}
{"custom_fields.language":"en","custom_fields.consent":true}
JSON,
            ]],
        ],
        [
            'id' => 'api-internal-results',
            'title' => 'ApiResult, errors, and HTTP statuses',
            'paragraphs' => [
                'ApiResult is a simple result object with status, data, and an optional itemsCount for logging. Successful service methods return it explicitly. If an action returns another type, the shared controller treats it as a programming error and generates 500.',
                'ApiException carries an expected application error: HTTP status, stable errorCode, message, and details. ApiController converts it to {success:false,error:{code,message,details}}. A database constraint violation with SQLSTATE 23000 becomes 409 conflict; other PDOException and Throwable instances are logged server-side and return a safe 500 server_error.',
                'Reads, PATCH, and DELETE normally return 200. Creation POST returns 207. The API also uses 401, 403, 404, 409, 422, and 500; external-service failure is not currently a separate API workflow. A 401 response also includes WWW-Authenticate, and every response includes JSON Content-Type and X-Request-Id.',
                'A client must make decisions from the HTTP status and body together. In particular, 207 is a successful 2xx status for HTTP libraries but may contain item errors. error.code is intended for program logic, while message and details are diagnostic; integration logic must not compare the full English message text.',
            ],
            'examples' => [[
                'title' => 'Normal controller error',
                'code' => <<<'JSON'
HTTP/1.1 422 Unprocessable Entity
X-Request-Id: a8d94b7b912ac2aeaa15cc11

{
  "success": false,
  "error": {
    "code": "validation_error",
    "message": "Full name is required."
  }
}
JSON,
            ]],
        ],
        [
            'id' => 'api-internal-logging',
            'title' => 'Logging and observability',
            'paragraphs' => [
                'finish() sends JSON first and then attempts to write api_logs. The log contains api_key_id, request_id, method, route template, status, error_code, items_count, IP, duration, origin, request body, and response body. An unauthorized attempt is also logged, but its api_key_id is NULL.',
                'Bodies are limited to approximately 64 KB each. Origin comes from Origin or, when absent, Referer and is truncated to 255 characters. A logging failure does not change the completed API response; it is written to PHP error_log with the request ID.',
                'Administrators view logs at /api-logs and filter by key, method, status group, path, and date. X-Request-Id should be passed to support and saved by the external system. Routes with ids currently log /api/v1/resource/{id}, so path alone cannot identify the specific record.',
                'request_body and response_body may contain names, emails, phone numbers, addresses, and custom-field values. Redaction and automatic retention are not implemented. Before production, define a retention period, limit access, and redact or avoid logging secrets and sensitive fields.',
            ],
            'examples' => [[
                'title' => 'Correlating logs from two systems',
                'code' => <<<'CODE'
External log:
  crm_request_id=a8d94b7b912ac2aeaa15cc11
  local_form_submission=98731

ContactCore api_logs:
  request_id=a8d94b7b912ac2aeaa15cc11
  response_status=422
  error_code=validation_error
  duration_ms=18
CODE,
            ]],
        ],
        [
            'id' => 'api-internal-security',
            'title' => 'Security and operational limitations',
            'paragraphs' => [
                'The public API is excluded from session CSRF checks because it does not use cookie authentication. Its security boundary consists of HTTPS, a random secret, Basic Auth, key activity, and scopes. The secret must not be exposed to browser JavaScript, URLs, repositories, or application logs in the integrating system.',
                'The API does not emit CORS headers, so the browser normally blocks direct calls from another origin. This matches the server-to-server model. If a browser client is needed, simply allowing Access-Control-Allow-Origin is insufficient: it requires a separate model of short-lived credentials, restricted origins, and threats.',
                'The API has no built-in rate limiter, quotas, idempotency key, replay protection beyond Basic Auth, or total HTTP-body byte limit before json_decode. The limit of 100 applies to item count, not bytes. Implement these controls at the reverse proxy and/or in the application before connecting untrusted or high-volume sources.',
                'EmailInspector performs a live DNS request when creating a contact or patching its email. A batch containing many different domains may increase response time. An external system needs a reasonable timeout and retry with backoff, but cannot blindly repeat a non-idempotent POST.',
            ],
            'examples' => [[
                'title' => 'Minimum production boundary',
                'code' => <<<'CODE'
Internet
   ▼
HTTPS reverse proxy
  - body size limit
  - request rate limit
  - access/error logs
  - Authorization forwarding
   ▼
PHP-FPM / ContactCore
  - Basic Auth
  - scopes
  - validation and transactions
  - api_logs with retention policy
CODE,
            ]],
        ],
        [
            'id' => 'api-internal-known-gaps',
            'title' => 'Current inconsistencies and technical debt',
            'paragraphs' => [
                'The client-state contract is aligned. ClientWriteService forms the complete column set: during creation without explicit values it sets is_web_connected = 0 and is_active = 1, and during PATCH it preserves current states. ClientRepository also applies the same safe defaults at its boundary. Client creation from ClientApiService, ContactApiService, and import passes through the shared service.',
                'The API checks contact email and client commercial_name uniqueness in advance to return a clear domain error before writing. UNIQUE indexes remain the final protection against concurrent races; a late conflict returns HTTP 409 with code conflict. Unknown custom-field slugs are still skipped silently, so strict enforcement must be centralized and tested.',
                'The project has no complete automated API test suite or published machine-readable OpenAPI specification. A route or response change can therefore break an external integration without affecting the CRM interface. Priorities are integration tests, OpenAPI, log redaction, rate limiting, and idempotency.',
            ],
            'examples' => [[
                'title' => 'Expected client values at the repository boundary',
                'code' => <<<'PHP'
$data = [
    'commercial_name' => $commercialName,
    // Other fields and states may be omitted during creation.
];

$clientId = $this->clientWriter->create($data);

// The normalized repository contract contains:
// is_web_connected = 0, is_active = 1
PHP,
            ]],
        ],
        [
            'id' => 'api-internal-extension',
            'title' => 'Adding or changing a resource',
            'paragraphs' => [
                'A new independent resource requires a repository, an ApiService class, read/write scopes in ApiController::SCOPES, service loading, and an item in $apiControllers. The shared loop in public_html/index.php registers five CRUD routes. If the resource does not support an operation, the universal loop cannot be used blindly: the absence must be expressed through an intentional 405 or explicit contract.',
                'Before publication, define request and response fields, filters, pagination limits, PATCH rules, the transaction boundary, stable error.code values, and DELETE consequences. Response fields must be assembled explicitly. Review every new value that may contain personal data for logging impact.',
                'Minimum verification covers missing Authorization, invalid and revoked secrets, insufficient scope, reads for existing and absent ids, malformed JSON, empty PATCH, single and batch POST, partial success, a database conflict, relationship rollback, filters, page/per_page boundaries, and api_logs writes. Tests must check the JSON schema and X-Request-Id as well as status.',
            ],
            'examples' => [[
                'title' => 'New-resource checklist',
                'code' => <<<'CODE'
[ ] Repository and migration/indexes
[ ] ResourceApiService implements 5 methods
[ ] resource name is permitted through ApiController::SCOPES
[ ] require_once for the service and an item in $apiControllers
[ ] resource:read and resource:write in ApiController::SCOPES
[ ] validation, response DTO, and stable error codes
[ ] transaction boundary and delete semantics
[ ] api_logs redaction/retention impact
[ ] integration tests and API help
[ ] backward compatibility or a new /api/v2
CODE,
            ]],
        ],
    ],
];
