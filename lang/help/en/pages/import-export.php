<?php

return array (
  'id' => 'import-export',
  'title' => 'Import and export',
  'description' => 'Loading, validating, and exporting data in CSV and XLSX formats.',
  'icon' => 'ph-arrows-down-up',
  'sections' =>
  array (
    0 =>
    array (
      'id' => 'data-exchange',
      'title' => 'What import and export are for',
      'paragraphs' =>
      array (
        0 => 'Import is used when many clients or contacts need to be added to ContactCore from a spreadsheet at once. Instead of creating each record manually, the system reads the file rows, offers to map its columns to CRM fields, and then processes the records in sequence. Client and contact imports are run separately, so select the correct tab before uploading a file.',
        1 => 'Export performs the opposite task: it creates a spreadsheet from data already stored in the system. It can be used to prepare a report, share a selection with colleagues, process data further, or create a working backup copy. Export does not modify the original client and contact records.',
        2 => 'Both tools support CSV and XLSX. CSV is convenient for exchanging data between different systems, while XLSX is suitable for viewing and further work in spreadsheet applications.',
      ),
    ),
    1 =>
    array (
      'id' => 'import-templates',
      'title' => 'Preparing a file and using templates',
      'paragraphs' =>
      array (
        0 => 'Ready-made templates can be downloaded directly from the Import page. First select the Contacts or Clients tab, then download a CSV or XLSX example from the Import templates area. The client and contact templates differ because these record types have different standard fields.',
        1 => 'A template shows the correct spreadsheet structure: the first row contains column names and the next row contains an example record. You do not have to fill every column or preserve the original column order, but using the prepared headers helps the system suggest the correct mappings. Unnecessary columns can be removed and additional ones can be added.',
        2 => 'Each subsequent non-empty row in the file must describe one client or one contact. A full name is required when importing contacts; a commercial name is required for clients. CSV and XLSX files up to 20 MB are supported.',
      ),
    ),
    2 =>
    array (
      'id' => 'field-mapping',
      'title' => 'Preview and field mapping',
      'paragraphs' =>
      array (
        0 => 'After the file is uploaded, the system shows its first rows and the total number of records found. A mapping table appears below: for every source-file column, specify which ContactCore field should receive its value. Familiar names such as “email,” “phone,” “sector,” and “tags” are recognized automatically, but the suggested mappings must still be reviewed before processing begins.',
        1 => 'A column can be mapped to a standard field, excluded from the import, or used to create a custom field. An incorrect mapping does not alter the source data, but it writes that data to the wrong place—for example, a city value may be placed in the country field. Carefully check columns with similar meanings and make sure one is mapped to the required field: the contact’s full name or the client’s commercial name.',
        2 => 'Column names may differ from those in the template if the user selects the correct mappings manually. The entire source file therefore usually does not need to be rebuilt: it is enough to clean up the headers, remove administrative rows, and configure the mapping carefully.',
      ),
    ),
    3 =>
    array (
      'id' => 'import-custom-fields',
      'title' => 'Creating custom fields during import',
      'paragraphs' =>
      array (
        0 => 'If no standard field is suitable for a column, select “Create custom field” in the mapping list. Then choose its type: text, multiline text, number, date, email, URL, select, or checkbox. The column header becomes the name of the new field, and its technical identifier is generated automatically.',
        1 => 'A field created this way belongs to the imported record type: a contact import creates it for contacts, while a client import creates it for clients. The new field is optional and filterable. If a field with the same technical identifier already exists for the selected record type, the system uses it and writes the values there instead of creating another field.',
        2 => 'Review the names of these columns and their selected types before importing. Different spellings of the same attribute can create several similar fields. For a checkbox, 1, yes, true, and si are treated as “on”; all other values are stored as “off.” For a select field, it is better to create the options in the settings beforehand or review and configure them after the import.',
      ),
    ),
    4 =>
    array (
      'id' => 'import-processing',
      'title' => 'Processing records and related data',
      'paragraphs' =>
      array (
        0 => 'Import creates new records and is not intended for bulk updates of existing records. Contacts whose email already exists in the database or is repeated in the same file are skipped. Clients with an existing commercial name are also skipped. Rows without the required name or with an invalid email are marked as errors.',
        1 => 'Tags and sectors are matched by name. If a specified tag or sector does not exist, the system creates it automatically, so identical values should be written consistently. Multiple tags in one cell can be separated by commas, semicolons, or vertical bars.',
        2 => 'When importing contacts, a value in the client field links the contact to an existing client by commercial name or creates a minimal record for a new client. When importing clients, a value in the contact column must instead refer to an existing contact; if no person with that name is found, the row ends with an error.',
        3 => 'Once processing is complete, the numbers of imported, skipped, and erroneous rows are shown. Every run is saved in the import history. For rows with problems, a separate list displays the row number, original data, and the reason why it was not added.',
      ),
    ),
    5 =>
    array (
      'id' => 'export-data',
      'title' => 'Exporting data',
      'paragraphs' =>
      array (
        0 => 'On the Export page, first select the data type: contacts or clients. Then select the fields to include in the file. Standard fields are grouped by purpose, and custom fields created in the system are also available.',
        1 => 'Choose CSV or XLSX before downloading. The first row of the resulting file contains the names of the selected fields, and each following row represents one record. The filename is generated automatically and includes the data type and creation time.',
        2 => 'The bottom of the page contains the export history: the data type, file name and format, number of rows, author, and execution time. This history records that an export took place, but the downloaded file should be stored in a suitable protected location because it may contain contact details and other business data.',
      ),
    ),
  ),
);
