<?php

return array (
  'id' => 'contacts',
  'title' => 'Contacts',
  'description' => 'Working with people, contact details, and relationships with clients.',
  'icon' => 'ph-address-book',
  'sections' =>
  array (
    0 =>
    array (
      'id' => 'contact-meaning',
      'title' => 'What the system considers a contact',
      'paragraphs' =>
      array (
        0 => 'A contact in ContactCore is a specific person. In the platform’s main data model, contacts are created when people submit forms on our clients’ websites. The company whose website received the lead is stored as a client, while the person who submitted the form is stored as a contact linked to that client.',
        1 => 'A contact is not necessarily a client of our own organization. Most often, the person is a prospective or existing customer of the company we serve. This distinction makes it possible to collect leads from different client websites in one database without mixing the companies themselves with the people who contacted them.',
        2 => 'One contact can be linked to several clients. This is useful when the same person has submitted leads on different websites or is associated with several projects. In that case, use one contact record with multiple relationships rather than creating a separate copy of the person for each client.',
      ),
    ),
    1 =>
    array (
      'id' => 'contact-fields',
      'title' => 'Fields in a contact record',
      'paragraphs' =>
      array (
        0 => 'The full name is the required standard field. Email and phone are filled in when that information is available in the lead. Email is especially important: it is used to find the contact, validate the address, and detect duplicate records during import or when data is submitted through the API.',
        1 => 'The “Company” field identifies the organization where the person works. It is not the same as a related client. For example, a lead may arrive through our client “Acme Agency,” while the person who submitted it works for “Example Group.” In this case, “Acme Agency” is specified in the client relationships and “Example Group” in the “Company” field. If the employer is unknown, the field can be left empty.',
        2 => 'Tags can identify a contact’s state or type, while custom fields store additional details from the form: the source, service of interest, budget, marketing consent, or other data. Contact custom fields are configured separately from client fields.',
      ),
    ),
    2 =>
    array (
      'id' => 'contact-sources',
      'title' => 'How contacts enter the system',
      'paragraphs' =>
      array (
        0 => 'A contact can be created manually, imported from CSV or XLSX, or received through the API. The main automated workflow begins when a form is submitted on a client’s website. The integration sends the lead data to ContactCore, creates the contact, and links it to the client company that owns the website.',
        1 => 'When submitting leads, it is important to identify the same client consistently. If the integration uses a different spelling of the name, the API may create a new company record instead of linking to the existing one. After connecting a website, review the first leads: check that the name, email, and phone are correct, that the intended client has been assigned, and that additional form fields are being submitted.',
        2 => 'Email is often used as a practical identifier for a person. During import, rows with an email that already exists are skipped, while the API returns a duplicate error. If the person is already in the database but is now associated with another client, add a new relationship to the existing record. Manual creation does not block identical addresses, so use search before saving.',
      ),
    ),
    3 =>
    array (
      'id' => 'email-validation',
      'title' => 'How email is validated',
      'paragraphs' =>
      array (
        0 => 'When a contact is created manually or through the API, the system analyzes the supplied email address. It first checks the address format: the presence of a local part, an @ sign, and a valid domain part. The domain is then compared with a list of common personal email services and, during a normal check, its MX record is looked up—a technical record indicating that the domain can receive email.',
        1 => 'The address receives two independent attributes based on the result. The first determines the email type: business or personal. Addresses from Gmail, Outlook, Yahoo, Yandex, Mail.ru, and other public services are considered personal; an address on an organization’s own domain is considered business. The second attribute indicates whether the technical check found a problem with the address.',
        2 => 'If the format is incorrect or the domain has no MX record, the email is marked as invalid. This mark is visible in the contact list and record. If there is no warning, it only means that no obvious technical problem was found. The system does not confirm that the specific mailbox exists, identify its owner, or guarantee actual delivery of a message.',
        3 => 'Classifying an email as business is not legal proof of where the person works. It is based on the domain: any address whose domain is not on the list of known personal services is treated as business. Consider the result a useful automatic classification that can be corrected manually when necessary.',
      ),
    ),
    4 =>
    array (
      'id' => 'email-correction',
      'title' => 'Correcting email status manually',
      'paragraphs' =>
      array (
        0 => 'When editing a contact, the email status can be set manually to “Business,” “Personal,” or “Invalid.” A manual selection takes precedence over automatic detection. Use it when the system misidentifies a lesser-known email service, a business domain is used for personal email, or the DNS check does not reflect the actual situation.',
        1 => 'If the address itself changes, review the selected status again. A previously saved manual classification may not suit the new domain. No status is set for a contact without an email address.',
        2 => 'The settings include a batch check for addresses whose email type has not yet been determined. It runs in small batches to avoid overloading the server, classifies each address, and checks the domain’s MX record. A questionable or clearly incorrect result can then be corrected in the contact record.',
      ),
    ),
    5 =>
    array (
      'id' => 'contact-list',
      'title' => 'Relationships, search, and list operations',
      'paragraphs' =>
      array (
        0 => 'A contact record displays related clients, tags, and custom fields. A client relationship shows the client project where the contact originated or is used. The “Company” field remains a separate attribute of the person.',
        1 => 'The contact list can be filtered by name, email, phone, related client, client sector, tags, dates, email type and status, and custom fields. The global search in the top bar quickly finds a person by name, address, or phone number from any section of the CRM.',
        2 => 'Bulk actions add and remove tags, link selected contacts to a client, and delete records. Deleting a contact is permanent and removes its relationships with clients. If you only need to remove a contact from a project, change the relationships in the record instead of deleting the person.',
      ),
    ),
  ),
);
