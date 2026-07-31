<?php

return array (
  'id' => 'ai-tools',
  'title' => 'AI tools',
  'description' => 'Automatic company identification and review of AI results.',
  'icon' => 'ph-sparkle',
  'sections' =>
  array (
    0 =>
    array (
      'id' => 'ai-purpose',
      'title' => 'What AI is used for',
      'paragraphs' =>
      array (
        0 => 'ContactCore’s AI tools are powered by Google Gemini. In the current version, they have one specific task: finding the company name for contacts with a business email address whose Company field is still empty.',
        1 => 'For the search, Gemini receives the email domain, such as example.com, and attempts to identify the organization’s official name from the associated website. This is not a general-purpose AI assistant or a tool for creating clients, writing text, or processing other data.',
      ),
    ),
    1 =>
    array (
      'id' => 'ai-queue',
      'title' => 'Which contacts appear in the list',
      'paragraphs' =>
      array (
        0 => 'The table shows only contacts whose email is classified as business, whose Company field is empty, and for whom no company decision has yet been made. Above the table, the number of such contacts and the number of unique email domains are displayed.',
        1 => 'Each row shows the contact’s name, email, and domain. Clicking the domain opens the likely website in a new tab so that the result can be checked manually. The number beside the domain indicates how many queued contacts use the same domain.',
      ),
    ),
    2 =>
    array (
      'id' => 'ai-actions',
      'title' => 'Buttons and result review',
      'paragraphs' =>
      array (
        0 => 'The star button sends the contact’s domain to Gemini. The company name it finds appears in the row’s text field but is not yet saved in the record. Review the response and correct it manually if necessary.',
        1 => 'The check-mark button saves the content of the text field as the contact’s company. The row disappears from the queue after it is saved. Click the check mark only after reviewing the name.',
        2 => 'The cross button marks the company as not found or not requiring processing. The Company field remains empty, but the contact is removed from the queue and is no longer offered for another search.',
        3 => 'The “Auto” button above the table runs a Gemini search for the visible rows on the page in sequence. It only fills in the suggested results and does not save them automatically: each result must still be confirmed with the check mark or rejected with the cross. The “Stop” button prevents any further automatic requests.',
      ),
    ),
  ),
);
