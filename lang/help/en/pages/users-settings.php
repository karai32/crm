<?php

return array (
  'id' => 'users-settings',
  'title' => 'Users and settings',
  'description' => 'Accounts, interface preferences, and system management.',
  'icon' => 'ph-users-three',
  'sections' =>
  array (
    0 =>
    array (
      'id' => 'user-accounts',
      'title' => 'Accounts and roles',
      'paragraphs' =>
      array (
        0 => 'An account determines the name and capabilities under which a person works in ContactCore. Each user has a name, unique email address, password, role, and active status. The list also shows the last login date, helping administrators understand whether an account is being used.',
        1 => 'The system provides two roles: administrator and user. An administrator has full access and manages accounts and administrative sections. Access for the User role is configured separately through permissions. Only an administrator can create and edit users.',
      ),
    ),
    1 =>
    array (
      'id' => 'user-permissions',
      'title' => 'User permissions',
      'paragraphs' =>
      array (
        0 => 'Permissions apply only to regular users. An administrator always has full access regardless of the checkbox settings. When a regular user is created, all permissions are initially selected; the administrator can then leave enabled only the actions that employee needs.',
        1 => 'Creation, editing, and deletion are configured separately for contacts and clients. For example, a user can retain access to view the database and create records while being prevented from modifying or permanently deleting existing ones. Edit permission is also required for bulk actions that change record relationships or tags.',
        2 => 'Separate permissions control data export, import, sectors, tags, and custom fields. When a specific permission is disabled, the corresponding section or action is hidden from the interface, and an attempt to open the protected address directly is denied.',
        3 => 'User management, API integrations, and AI tools are administrative capabilities and are not assigned to a regular user through separate checkboxes. Before granting export, import, or deletion permissions, bear in mind that these operations allow access to large volumes of data or make bulk changes.',
      ),
    ),
    2 =>
    array (
      'id' => 'account-status',
      'title' => 'Account activity and deletion',
      'paragraphs' =>
      array (
        0 => 'An active account can be used to sign in. If an employee stops working with ContactCore temporarily or permanently, it is better to deactivate the account first: they will no longer be able to authenticate on their next login attempt, while the user record and its related history will be preserved.',
        1 => 'An administrator cannot deactivate or delete their own current account. Permanent deletion is available only for an already inactive user and should be used when the account no longer needs to be retained. This action must be performed carefully; deactivation is sufficient in most working situations.',
        2 => 'When editing a user, the name, email, role, permissions, and status can be changed. A new password is set only when the corresponding field is filled in; leaving the field empty keeps the existing password unchanged.',
      ),
    ),
    3 =>
    array (
      'id' => 'personal-settings',
      'title' => 'Personal settings',
      'paragraphs' =>
      array (
        0 => 'The Settings section is available to every signed-in user. Here you can choose how many rows appear on each table page: 20, 50, 100, or 200. This preference applies to lists throughout the system and is stored separately for the current account.',
        1 => 'The interface language can be selected on the same page: Spanish, English, or Russian. Changing the language affects section names, buttons, and system messages, but does not translate data entered by users in records.',
        2 => 'Administrators also have access to batch email validation. It processes small batches of contacts whose email type has not yet been identified, classifies the addresses, and checks their domains’ MX records. This validation is described in detail in the Contacts section.',
      ),
    ),
    4 =>
    array (
      'id' => 'weekly-report',
      'title' => 'Weekly report for administrators',
      'paragraphs' =>
      array (
        0 => 'The weekly report is intended for active administrators. When the maintenance script is scheduled to run weekly on the server, the report is automatically sent to every active administrator’s email address and covers the previous seven days. Recipient addresses come from their accounts.',
        1 => 'The report includes the number of new contacts and clients, the ten clients with the most new contacts, clients connected to the platform, and clients that were deactivated. The email also contains links to the corresponding selections in ContactCore.',
        2 => 'An administrator can send the report manually from the Settings page. In that case, the summary covers the period from the start of the current week until the time it is sent and is delivered only to the current administrator’s email. Manual sending is useful for testing the email configuration or receiving an up-to-date summary before the scheduled run.',
        3 => 'Automatic delivery requires a scheduler to be configured on the server, and both report types require a working outbound email configuration. If the email cannot be sent, the system displays an error and writes the technical details to the application log.',
      ),
    ),
  ),
);
