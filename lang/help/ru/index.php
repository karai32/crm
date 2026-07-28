<?php

return [
    'ui' => array (
  'center_label' => 'База знаний',
  'center_title' => 'Справочный центр',
  'center_intro' => 'Руководство по работе с ContactCore и техническая документация платформы.',
  'search_placeholder' => 'Найти раздел',
  'search_empty' => 'Разделы не найдены',
  'navigation_label' => 'Разделы справки',
  'on_this_page' => 'На этой странице',
  'article_label' => 'Руководство',
  'technical_label' => 'Техническая документация',
  'updated_label' => 'Документация ContactCore',
  'previous_label' => 'Предыдущий раздел',
  'next_label' => 'Следующий раздел',
  'open_navigation' => 'Открыть разделы',
  'close_navigation' => 'Закрыть разделы',
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
  'title' => 'Техническая документация',
  'description' => 'Архитектура, безопасность, конфигурация и эксплуатация платформы.',
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

