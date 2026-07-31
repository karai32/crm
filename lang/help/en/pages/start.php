<?php

return array (
  'id' => 'start',
  'title' => 'Getting started',
  'description' => 'An introduction to the system, navigation and core workflow.',
  'icon' => 'ph-house-line',
  'sections' =>
  array (
    0 =>
    array (
      'id' => 'purpose',
      'title' => 'What ContactCore is for',
      'paragraphs' =>
      array (
        0 => 'ContactCore is a system for managing clients and contacts centrally. It helps keep information about companies and people in one place, link them together, and maintain the data in a clear and organized form. Instead of several spreadsheets, scattered files, and records held by different employees, the team gets a shared database where it can quickly find the information it needs and understand the context of a client relationship.',
        1 => 'The system is designed for more than storing addresses and phone numbers. Its main purpose is to show the structure of the data: which organization a person is linked to, which sector a company belongs to, which tags have been assigned to a record, what additional details have been collected, and when the information changed. This makes a client or contact record a working hub rather than just a row in a directory.',
        2 => 'ContactCore is suitable for everyday database work: adding new records, refining existing data, finding the right people and companies, preparing selections, exchanging information with other systems, and gradually improving data quality.',
      ),
    ),
    1 =>
    array (
      'id' => 'contents',
      'title' => 'What the system includes',
      'paragraphs' =>
      array (
        0 => 'ContactCore is built around two connected sections: clients and contacts. A client is an organization or company. Its record stores the name, legal and address details, website, business sector, notes, and other information. A contact is a specific person with a name, email address, phone number, and company information. One client can be linked to several contacts, and one contact can be linked to several clients at the same time.',
        1 => 'Sectors and tags are used to classify data. A sector describes a client’s main line of business, such as technology, education, or tourism. Tags are more flexible: they can identify a status, relationship type, project membership, or any other characteristic important to the team. Custom fields extend the standard records without requiring code changes and store the exact data a particular business needs.',
        2 => 'The system also includes import and export tools. They can load existing databases from CSV or XLSX, map columns to CRM fields, monitor errors, and export the required data. AI tools help with specific data-enrichment tasks, while the REST API connects external websites, forms, and internal services.',
        3 => 'Separate sections are provided for managing users, settings, and integrations. They configure accounts, interface preferences, API keys, and other administrative capabilities. The technical documentation describes the platform’s internal architecture, configuration, and deployment.',
      ),
    ),
    2 =>
    array (
      'id' => 'capabilities',
      'title' => 'What ContactCore lets you do',
      'paragraphs' =>
      array (
        0 => 'In day-to-day work, ContactCore lets you create a company record, add the people associated with it, and gradually complete the records with all available information. Data can be edited as it is clarified, linked to new clients, assigned to sectors, and marked with tags. Contacts and clients have separate custom fields, so their record structure can evolve with the team’s needs.',
        1 => 'The contact and client lists support search, sorting, and filtering. Records can be found by their main data, related entities, dates, and custom fields. Bulk actions help process several records at once—for example, assigning a common tag, adding a relationship, or deleting selected items. The global search in the top bar is useful when you need to jump quickly to a specific person or organization from anywhere in the CRM.',
        2 => 'Large volumes of information do not have to be entered one record at a time. Import loads data in batches and records the result of processing each row, while export creates a dataset with the required fields. External applications can use the same core entities through the API. This allows ContactCore to work both as a standalone CRM and as a central data source for other tools.',
        3 => 'The system delivers the greatest value when data is maintained consistently. Common naming rules, accurate relationships, clear tags, and complete records make information easier to find, help prevent duplicates, and preserve important context when working as a team.',
      ),
    ),
    3 =>
    array (
      'id' => 'data-model',
      'title' => 'How the main data is connected',
      'paragraphs' =>
      array (
        0 => 'When working with the system, it is important to distinguish between a client and a contact. A client answers “which organization are we working with?”, while a contact answers “which person are we communicating with?”. These records can exist independently, but their relationships provide the most complete picture. A client record shows the people associated with it, while a contact record provides links to the related organizations.',
        1 => 'A sector is assigned to a client and describes its industry. Tags can be assigned to both clients and contacts and provide more flexible classification. Custom fields are also created separately for clients and contacts: a field added for organizations does not automatically appear in people’s records. This separation preserves the database structure and prevents data with different purposes from being mixed.',
        2 => 'When information arrives from an external file or integration, it ultimately enters the same entities and relationships. Before importing data or connecting the API, it is therefore useful to decide which records are clients, which are contacts, which values should become tags, and which are better stored in custom fields.',
      ),
    ),
    4 =>
    array (
      'id' => 'using-help',
      'title' => 'How to use the help center',
      'paragraphs' =>
      array (
        0 => 'The help center follows the same sections as the CRM itself. The navigation on the left opens the relevant topic: clients, contacts, classification, custom fields, data exchange, AI tools, settings, or the API. On smaller screens, the section list opens from a separate button above the article.',
        1 => 'The search field at the top of the page helps find a section by its title or short description. It searches the help structure, not the full text of the articles. Each section is divided into focused subsections, so you can read an article in order or go directly to the question that interests you. Links to the previous and next topics appear at the bottom of the page.',
        2 => 'The user sections explain what features are for and the usual way to work with them. The API section is intended for setting up integrations and describes requests and responses. The technical documentation covers application architecture, the database, security, configuration, and deployment. For a day-to-day CRM task, begin with the relevant user section; for questions about how the platform works or is maintained, go to the technical documentation.',
        3 => 'The help language follows the selected interface language. The content will be expanded as ContactCore develops, so the help center can serve as the main starting point for learning the system’s capabilities and clarifying its workflows.',
      ),
    ),
  ),
);
