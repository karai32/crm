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
        'start' => array (
  'title' => 'Getting started',
  'description' => 'An introduction to the system, navigation and core workflow.',
  'icon' => 'ph-house-line',
  'file' => __DIR__ . '/pages/start.php',
),
        'clients' => array (
  'title' => 'Clients',
  'description' => 'Organizations, company details and related records.',
  'icon' => 'ph-buildings',
  'file' => __DIR__ . '/pages/clients.php',
),
        'contacts' => array (
  'title' => 'Contacts',
  'description' => 'People, contact details and their links to clients.',
  'icon' => 'ph-address-book',
  'file' => __DIR__ . '/pages/contacts.php',
),
        'sectors-tags' => array (
  'title' => 'Sectors and tags',
  'description' => 'Classifying records with industries and flexible labels.',
  'icon' => 'ph-tag',
  'file' => __DIR__ . '/pages/sectors-tags.php',
),
        'custom-fields' => array (
  'title' => 'Custom fields',
  'description' => 'Extending client and contact records with your own fields.',
  'icon' => 'ph-sliders-horizontal',
  'file' => __DIR__ . '/pages/custom-fields.php',
),
        'import-export' => array (
  'title' => 'Import and export',
  'description' => 'Loading, validating and exporting CSV and XLSX data.',
  'icon' => 'ph-arrows-down-up',
  'file' => __DIR__ . '/pages/import-export.php',
),
        'ai-tools' => array (
  'title' => 'AI tools',
  'description' => 'Automated company discovery and review of AI results.',
  'icon' => 'ph-sparkle',
  'file' => __DIR__ . '/pages/ai-tools.php',
),
        'users-settings' => array (
  'title' => 'Users and settings',
  'description' => 'Accounts, interface preferences and system management.',
  'icon' => 'ph-users-three',
  'file' => __DIR__ . '/pages/users-settings.php',
),
        'api' => array (
  'title' => 'API',
  'description' => 'Connecting external systems to ContactCore data and operations.',
  'icon' => 'ph-plugs-connected',
  'file' => __DIR__ . '/pages/api.php',
),
    ],
    'technical' => [
        'meta' => array (
  'id' => 'technical',
  'title' => 'Technical documentation',
  'description' => 'Architecture, security, configuration and platform operations.',
  'icon' => 'ph-code',
),
        'pages' => [
            'server' => array (
  'title' => 'Server',
  'description' => '',
  'icon' => 'ph-arrow-elbow-down-right',
  'file' => __DIR__ . '/technical/server.php',
),
            'installation' => array (
  'title' => 'Installation',
  'description' => '',
  'icon' => 'ph-arrow-elbow-down-right',
  'file' => __DIR__ . '/technical/installation.php',
),
            'code-structure' => array (
  'title' => 'Code structure',
  'description' => '',
  'icon' => 'ph-arrow-elbow-down-right',
  'file' => __DIR__ . '/technical/code-structure.php',
),
            'database' => array (
  'title' => 'Database',
  'description' => '',
  'icon' => 'ph-arrow-elbow-down-right',
  'file' => __DIR__ . '/technical/database.php',
),
        ],
    ],
];

