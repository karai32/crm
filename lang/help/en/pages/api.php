<?php

return array (
  'id' => 'api',
  'title' => 'API',
  'description' => 'Connecting external systems to ContactCore data and operations.',
  'icon' => 'ph-plugs-connected',
  'sections' =>
  array (
    0 =>
    array (
      'id' => 'api-purpose',
      'title' => 'Purpose of the API',
      'paragraphs' =>
      array (
        0 => 'The API allows external websites and applications to work with ContactCore data without opening the CRM interface. It can create, retrieve, update, and delete contacts and clients. Sectors and tags are passed as fields of these records and do not have separate endpoints. Communication takes place over HTTPS, data is sent as JSON, and all routes in the current version begin with /api/v1.',
        1 => 'The main practical use case is connecting forms on client websites. After a form is submitted, the website sends the lead to ContactCore, where the person is created as a contact and linked to the appropriate client. Leads from different client websites are therefore collected in one database while retaining their relationship with the project from which they originated.',
        2 => 'The API is also suitable for synchronization with internal services, bulk record creation, and retrieving data for reports. It uses the same records, relationships, tags, and custom fields as the standard interface: integrations do not create a separate parallel database.',
      ),
    ),
    1 =>
    array (
      'id' => 'api-forms',
      'title' => 'Connecting website forms',
      'paragraphs' =>
      array (
        0 => 'Submit a lead with a POST request to /api/v1/contacts. Only full_name is required. Email can be omitted, but a supplied address must have a valid format and must not already belong to another contact. A phone number, the person’s own company, a client, tags, and values for previously created custom fields can also be sent.',
        1 => 'The company field identifies the contact’s own employer. To specify the website or project from which the lead came, use the clients field and pass the commercial name of the ContactCore client. If that client does not yet exist, the API creates a minimal record automatically. However, it is more reliable to create the client beforehand and use exactly the same spelling in every request.',
        2 => 'The “Connected to Web/API” setting in a client record is a status for staff and does not create an integration by itself. Enable it separately once the form has been configured. API credentials must not be placed in JavaScript or any other code available to website visitors: the request must be sent by the website server or the server-side component of the forms plugin.',
      ),
      'examples' =>
      array (
        0 =>
        array (
          'title' => 'Submitting one lead from a website',
          'code' => 'curl --request POST "https://crm.example.com/api/v1/contacts" \\
  --user "CLIENT_ID:SECRET" \\
  --header "Content-Type: application/json" \\
  --data \'{
    "full_name": "John Smith",
    "email": "john@example.org",
    "phone": "+34 600 123 456",
    "company": "Example Group",
    "clients": ["Acme Agency"],
    "tags": ["Website lead", "New lead"],
    "custom_fields": {
      "interested_service": "SEO",
      "consent": true
    }
  }\'',
        ),
        1 =>
        array (
          'title' => 'Successful response when creating a contact',
          'code' => '{
  "success": true,
  "data": {
    "processed": 1,
    "created": 1,
    "failed": 0,
    "results": [
      {
        "index": 0,
        "success": true,
        "data": {
          "contact_id": 125,
          "client_created": false,
          "tag_created": true
        }
      }
    ]
  }
}',
        ),
      ),
    ),
    2 =>
    array (
      'id' => 'api-credentials',
      'title' => 'Credentials and security',
      'paragraphs' =>
      array (
        0 => 'An administrator creates credentials in the API management section. The integration is given a clear name, after which the system issues a Client ID and secret. Requests use them as the username and password for HTTP Basic Auth. The Client ID identifies the integration and is not related to a client record in the CRM.',
        1 => 'The secret is shown only once, immediately after creation. ContactCore stores its hash and cannot display the original value again. Save the secret in the protected server-side configuration of the website; do not send it in the URL or commit it to a repository. If it is lost or may have been exposed, the safest option is to create new credentials and disable the old ones.',
        2 => 'In the commands below, https://crm.example.com and the values CLIENT_ID and SECRET are examples. Replace them with the address of the installed ContactCore instance and the credentials for the specific integration.',
        3 => 'API permissions are divided into read and write access for contacts and clients. A new integration receives all four current permissions; write permission also allows the corresponding resource to be read. An integration can be temporarily disabled, enabled again, or permanently deleted. For older keys, the synchronization button replaces the stored permissions with the current set and removes obsolete sector and tag scopes.',
      ),
    ),
    3 =>
    array (
      'id' => 'api-resources',
      'title' => 'Resources, routes, and methods',
      'paragraphs' =>
      array (
        0 => 'The API contains two resources: contacts and clients. Each supports the same core operations: GET /api/v1/{resource} retrieves a list, GET /api/v1/{resource}/{id} retrieves one record, POST /api/v1/{resource} creates records, PATCH /api/v1/{resource}/{id} updates a record, and DELETE /api/v1/{resource}/{id} deletes it. Sectors and tags are created and linked through the sector and tags fields in client and contact requests.',
        1 => 'POST accepts either a single JSON object or an array of up to 100 objects. This allows a form to submit one lead while an administrative integration creates records in bulk. A separate result is returned for each item: an error in one record does not roll back neighboring items that were processed successfully.',
        2 => 'Contact and client lists support pagination through page and per_page; no more than 100 records are returned per request. Contacts can be filtered, for example, by name, email, phone, company, client, tag, and creation date. Client filters include names, location, sector, tag, and dates. Dates are sent in YYYY-MM-DD format.',
        3 => 'PATCH performs a partial update: fields absent from the request remain unchanged. However, supplied tags and clients arrays replace the current set of relationships in full. DELETE permanently removes the selected contact or client. Tag and sector catalogs themselves are managed through the web interface, not through the public API.',
      ),
      'examples' =>
      array (
        0 =>
        array (
          'title' => 'Retrieving a client’s contacts for a period',
          'code' => 'curl --user "CLIENT_ID:SECRET" \\
  "https://crm.example.com/api/v1/contacts?client_id=42&created_from=2026-07-01&created_to=2026-07-31&per_page=50"',
        ),
        1 =>
        array (
          'title' => 'Creating a client',
          'code' => 'curl --request POST "https://crm.example.com/api/v1/clients" \\
  --user "CLIENT_ID:SECRET" \\
  --header "Content-Type: application/json" \\
  --data \'{
    "commercial_name": "Acme Agency",
    "legal_name": "Acme Agency SL",
    "website": "https://acme.example.com",
    "city": "Madrid",
    "country": "Spain",
    "sector": "Marketing",
    "tags": ["Active project"]
  }\'',
        ),
      ),
    ),
    4 =>
    array (
      'id' => 'api-fields',
      'title' => 'Fields, relationships, and automatic creation',
      'paragraphs' =>
      array (
        0 => 'A contact can contain full_name, email, phone, and company, together with clients, tags, and custom_fields. During creation, the API validates the email, determines its type, and prevents duplicate addresses. clients and tags can be submitted as one name, a comma-separated string of names, or a JSON array. These formats work for both creation and update; the tags field in client requests supports the same formats.',
        1 => 'In the string form, the separator is specifically a comma: “Lead,Newsletter.” Semicolons and vertical bars, which are supported during file import, do not separate names in the API. A JSON array is preferable because it separates items unambiguously and supports names that may themselves contain a comma.',
        2 => 'A client is created with the required commercial_name. It can also contain legal_name, cif, address fields, website, notes, sector, tags, and custom_fields. Unknown tag and sector names are created automatically. Integrations must therefore use agreed names consistently to prevent similar duplicates from appearing in the catalogs.',
        3 => 'A custom field must first be created in ContactCore for the required record type. In the API, it can be submitted as a nested object by slug—{"custom_fields":{"language":"en"}}—or as a separate key using dot notation: {"custom_fields.language":"en"}. The two formats are equivalent and supported when creating and updating both contacts and clients.',
        4 => 'For a numeric custom field, it is best to send a JSON number; for a checkbox, true or false; and for a date, a string in YYYY-MM-DD format. An unknown slug is ignored without creating a new field, so review the first record created after configuring an integration. When a record is created, default values are applied to any custom fields not included in the request.',
      ),
      'examples' =>
      array (
        0 =>
        array (
          'title' => 'Supported formats for tags and client relationships',
          'code' => 'One tag:
{"tags": "Lead"}

Several tags in a string:
{"tags": "Lead,Newsletter"}

Several tags as an array — recommended:
{"tags": ["Lead", "Newsletter"]}

The clients field supports the same formats:
{"clients": "Acme Agency"}
{"clients": "Acme Agency,Example Group"}
{"clients": ["Acme Agency", "Example Group"]}',
        ),
        1 =>
        array (
          'title' => 'Two equivalent custom-field formats',
          'code' => 'Nested object:
{
  "custom_fields": {
    "language": "en",
    "consent": true
  }
}

Dot notation:
{
  "custom_fields.language": "en",
  "custom_fields.consent": true
}',
        ),
        2 =>
        array (
          'title' => 'Partially updating a contact',
          'code' => 'curl --request PATCH "https://crm.example.com/api/v1/contacts/125" \\
  --user "CLIENT_ID:SECRET" \\
  --header "Content-Type: application/json" \\
  --data \'{
    "phone": "+34 611 987 654",
    "tags": ["Qualified"],
    "custom_fields": {
      "interested_service": "Paid search advertising"
    }
  }\'',
        ),
      ),
    ),
    5 =>
    array (
      'id' => 'api-responses',
      'title' => 'Responses, errors, and request logs',
      'paragraphs' =>
      array (
        0 => 'Standard reads, updates, and deletions return HTTP 200. Creation returns HTTP 207 Multi-Status even for a single object because the response uses a batch structure. An integration must check not only the overall HTTP status but also success for every item in data.results: one 207 response can contain both successful and failed records.',
        1 => 'The main errors are: 401—missing or invalid credentials; 403—the key lacks permission; 404—the record was not found; 409—a conflict or duplicate; 422—invalid JSON or a data validation error; and 500—an internal error. The response body contains a code, message, and additional details when available.',
        2 => 'Every response contains an X-Request-Id header. Save it in the external system’s log: the identifier makes it easier to find a specific request when investigating an error. In the API logs section, an administrator can filter calls by integration, method, path, status, and date, and can review execution time, IP address, request body, and response.',
        3 => 'Before enabling a form on a production website, submit several test leads and review the created contacts, client relationships, tags, and custom fields. The integration must handle repeat form submissions, temporary CRM outages, and partially successful batch responses correctly without creating endless retry loops.',
      ),
    ),
  ),
);
