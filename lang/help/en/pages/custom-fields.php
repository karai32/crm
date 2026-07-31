<?php

return array (
  'id' => 'custom-fields',
  'title' => 'Custom fields',
  'description' => 'Extending client and contact records with your own fields.',
  'icon' => 'ph-sliders-horizontal',
  'sections' =>
  array (
    0 =>
    array (
      'id' => 'custom-fields-purpose',
      'title' => 'Purpose of custom fields',
      'paragraphs' =>
      array (
        0 => 'Custom fields store information that has no dedicated place in a standard client or contact record. For example, a client might need an internal contract number, current plan, or next renewal date, while a contact might need a lead source, service of interest, or consent to a particular type of communication.',
        1 => 'Each field is created either for clients or for contacts. A client field does not appear in a contact record, and vice versa. If the same information is needed for both record types, create two separate fields with clear names.',
        2 => 'Custom fields extend the system’s standard structure but do not replace it. For example, an additional email field does not participate in the validation and classification of the contact’s primary address, while a text field containing an industry does not replace the client’s sector.',
      ),
    ),
    1 =>
    array (
      'id' => 'custom-field-types',
      'title' => 'Field types',
      'paragraphs' =>
      array (
        0 => 'The “text” type is intended for a short, single-line value such as a code, name, source, or brief comment. “textarea” is used for longer multiline text, such as an additional description or internal notes.',
        1 => 'The “number” type stores a numeric value, while “date” stores a calendar date. Use them instead of plain text when the data format is known in advance: this keeps input consistent and provides the appropriate filter control.',
        2 => 'The “email” and “url” types are intended for email addresses and web links respectively. They help users enter values in the expected format, but an email in such a field remains an additional value and does not undergo the special MX check used for a contact’s primary email.',
        3 => 'The “select” type creates a list of predefined options from which the user chooses one. Each option is entered on a separate line when configuring the field. A “checkbox” is a simple two-state control—on or off—and is suitable for attributes such as consent, the existence of a contract, or participation in a program.',
      ),
    ),
    2 =>
    array (
      'id' => 'custom-field-settings',
      'title' => 'Creation and configuration',
      'paragraphs' =>
      array (
        0 => 'When creating a field, specify its target—client or contact—along with its name, technical identifier, type, and display order. Users see the name in records and forms. The technical identifier, or slug, is used by the system, import, and API; if it is not entered manually, it is generated from the name.',
        1 => 'The slug must be unique among fields for the same record type. Once an import or integration has begun using it, avoid changing it, because the external system will continue submitting data under the previous identifier. The field type and target should also be decided before the database is populated: changing them for a field already in use can make previously saved values inaccessible or incompatible with the new format.',
        2 => 'The default value is inserted automatically when a new record is created unless the user or integration provides another value. It is not applied retroactively to existing records. The “Required” setting is saved and shown in the field list, but in the current version it does not prevent a record from being saved with an empty value.',
      ),
    ),
    3 =>
    array (
      'id' => 'filterable-custom-fields',
      'title' => 'What “Filterable” means',
      'paragraphs' =>
      array (
        0 => 'When “Filterable” is enabled, the field becomes available in the additional filters for the corresponding list. A client field appears in the Clients filters, while a contact field appears in the Contacts filters. Turning this setting off does not hide the field in records or delete saved values; it only removes the ability to use the field as a filtering condition in the interface.',
        1 => 'The filter control depends on the field type. For “text,” you can select one of the values already in use; for “select,” one of the configured options; and for “checkbox,” “Yes” or “No.” The “number” and “date” types display the corresponding input fields.',
        2 => 'The current version provides dedicated filter controls for “text,” “select,” “checkbox,” “number,” and “date.” Fields of type “textarea,” “email,” and “url” can be marked as filterable in the settings, but no dedicated filter for them is currently shown in the list.',
      ),
    ),
    4 =>
    array (
      'id' => 'custom-field-values',
      'title' => 'Using fields and preserving data',
      'paragraphs' =>
      array (
        0 => 'Once created, the field appears in the form and record for the selected entity type. Its values are stored separately for each client or contact, included in exports, and can be populated during import. The display order lets you place frequently used fields above the others.',
        1 => 'Custom field values can also be sent and retrieved through the API. The field slug is used for this purpose, so the required field must first be created for clients or contacts. Request formats and examples are provided separately in the API section.',
        2 => 'Deleting a custom field also deletes every value previously stored in it for clients or contacts. The records themselves remain, but the values cannot be restored through the interface. Before deleting a field that is in use, make sure the data is no longer needed or save it through an export first.',
      ),
    ),
  ),
);
