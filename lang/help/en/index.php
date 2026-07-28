<?php

return [
    'ui' => array (
  'center_label' => 'Knowledge base',
  'center_title' => 'Help Center',
  'center_intro' => 'Guidance for working with ContactCore and technical platform documentation.',
  'search_placeholder' => 'Find a section',
  'search_empty' => 'No sections found',
  'navigation_label' => 'Help sections',
  'on_this_page' => 'On this page',
  'article_label' => 'Guide',
  'technical_label' => 'Technical documentation',
  'updated_label' => 'ContactCore documentation',
  'previous_label' => 'Previous section',
  'next_label' => 'Next section',
  'open_navigation' => 'Open sections',
  'close_navigation' => 'Close sections',
),
    'pages' => [
        'start' => require __DIR__ . '/pages/start.php',
        'clients' => require __DIR__ . '/pages/clients.php',
        'contacts' => require __DIR__ . '/pages/contacts.php',
        'sectors-tags' => require __DIR__ . '/pages/sectors-tags.php',
        'custom-fields' => require __DIR__ . '/pages/custom-fields.php',
        'import-export' => require __DIR__ . '/pages/import-export.php',
        'ai-tools' => require __DIR__ . '/pages/ai-tools.php',
        'users-settings' => require __DIR__ . '/pages/users-settings.php',
        'api' => require __DIR__ . '/pages/api.php',
    ],
    'technical' => [
        'meta' => array (
  'id' => 'technical',
  'title' => 'Technical documentation',
  'description' => 'Architecture, security, configuration and platform operations.',
  'icon' => 'ph-code',
),
        'pages' => [
            'server' => require __DIR__ . '/technical/server.php',
            'installation' => require __DIR__ . '/technical/installation.php',
            'code-structure' => require __DIR__ . '/technical/code-structure.php',
            'database' => require __DIR__ . '/technical/database.php',
        ],
    ],
];

