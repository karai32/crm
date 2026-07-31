<?php

return array (
  'id' => 'clients',
  'title' => 'Clients',
  'description' => 'Working with organizations, their company details, and related data.',
  'icon' => 'ph-buildings',
  'sections' =>
  array (
    0 =>
    array (
      'id' => 'client-meaning',
      'title' => 'What the system considers a client',
      'paragraphs' =>
      array (
        0 => 'In ContactCore, clients are companies and organizations that are clients of our organization. We provide services to them, maintain their websites, or work with them through another active business relationship. The Clients section therefore represents the companies for which leads are collected, not the people who submitted those leads.',
        1 => 'People who submit forms on our clients’ websites are stored in a different section: Contacts. Such a contact represents a prospective or existing customer of the company we serve. When a lead is received, the contact is linked to the relevant client company, making it possible to see which project or website the person came from.',
        2 => 'This distinction must be maintained during manual entry, import, and integration setup: an organization is created as a client, while a person submitting a lead is created as a contact linked to that client. Mixing these entities prevents reports, filters, and related-contact lists from reflecting the database’s real structure.',
      ),
    ),
    1 =>
    array (
      'id' => 'default-fields',
      'title' => 'Fields in a client record',
      'paragraphs' =>
      array (
        0 => 'The only required field in the standard record is the commercial name. This is the familiar company name that is convenient to search for in the CRM. The legal name is entered separately and may differ from the commercial name. The “Tax ID / CIF” field is intended for the organization’s tax or registration number.',
        1 => 'The sector shows the client’s main line of business. Tags provide more flexible classification and can indicate the service type, internal status, project, or any other characteristic. Unlike a sector, a tag does not have to describe an industry, and a company can have several tags.',
        2 => 'The address area of the record includes the street address, postal code, city, province or region, and country. The website field should contain the address of the client’s primary website. Notes can store important working information that has no dedicated structured field. If the standard set is insufficient, the record can be extended with custom fields created specifically for clients.',
        3 => 'Before creating a record, check whether the company is already in the database. The commercial name is required, but the system does not prevent several clients from having the same name, so duplicate control must be handled through the team’s working practices.',
      ),
    ),
    2 =>
    array (
      'id' => 'active-status',
      'title' => 'What “Active client” means',
      'paragraphs' =>
      array (
        0 => 'An active client is a company with which our organization is currently working. This setting is enabled by default for a new client. As long as the arrangement remains in effect and the client receives our services, the status should remain active.',
        1 => 'If the business relationship ends or is temporarily suspended, the setting can be turned off. The record is not deleted: the company information and its relationships with previously received contacts are preserved. This separates current clients from former ones without losing history or accumulated data.',
        2 => 'The status is used in the client list and filters. When it changes, the system also records the date of the change. Do not use the active setting to assess the quality of a client or an individual lead—it describes the current state of cooperation between our organization and the company.',
      ),
    ),
    3 =>
    array (
      'id' => 'api-status',
      'title' => 'What Web / API connection means',
      'paragraphs' =>
      array (
        0 => 'The “Connected to Web / API” setting means that the client’s website is integrated with ContactCore. Once connected, leads submitted through forms on the website are sent to the platform through the API and created here as contacts. Each created contact should be linked to the client whose website received the lead.',
        1 => 'This setting is an indicator of the integration state. Turning on the switch in the record does not connect the website, create an API key, or configure form submission. Enable it only after the integration has actually been configured and the receipt of leads has been verified. The system records the date when this status changes.',
        2 => 'To link incoming contacts correctly, the integration must use the client name or identifier consistently. The API can create missing entities, so an error or alternative spelling of a name may create a separate record instead of linking to an existing client. After launching the integration, review the first incoming leads manually.',
      ),
    ),
    4 =>
    array (
      'id' => 'related-contacts',
      'title' => 'Related contacts',
      'paragraphs' =>
      array (
        0 => 'Related contacts are shown at the bottom of a client record. These are primarily people who submitted leads on the company’s website and were added manually, through import, or through the API. From the list, you can open an individual contact record and review its details.',
        1 => 'A client can have any number of contacts. The same person can be linked to several clients when necessary—for example, if they submitted leads for different projects. This relationship does not create a duplicate contact: a single person record remains in the database and is accessible from every related company.',
        2 => 'Removing a relationship and deleting the contact itself are different actions. If a person is no longer related to a company, the relationship can be changed in the contact record. Deleting a client does not automatically delete its contacts, but their relationship with the deleted company disappears.',
      ),
    ),
    5 =>
    array (
      'id' => 'client-list',
      'title' => 'Working with the client list',
      'paragraphs' =>
      array (
        0 => 'The main client list lets you search for companies by commercial and legal name, sort records, and apply filters. Filters are available for sector, tags, active status, Web/API connection, website, location, creation date, and custom fields. Active filters are preserved in the page URL, so a prepared selection can be reopened or shared with another user.',
        1 => 'Bulk actions can add or remove tags for several clients at once. Deleting a client permanently removes the record, so first make sure the company is genuinely no longer needed in the database. If the business relationship has simply ended, it is usually better to turn off “Active client” than to delete the record.',
        2 => 'Client records should be kept up to date: use consistent spelling for names, update active and connection statuses promptly, specify the correct website, and check relationships with incoming contacts. The quality of this data determines how accurately ContactCore can separate leads among client projects.',
      ),
    ),
  ),
);
