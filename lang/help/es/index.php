<?php

return [
    'ui' => array (
  'center_label' => 'Base de conocimiento',
  'center_title' => 'Centro de ayuda',
  'center_intro' => 'Guía de uso de ContactCore y documentación técnica de la plataforma.',
  'search_placeholder' => 'Buscar una sección',
  'search_empty' => 'No se encontraron secciones',
  'navigation_label' => 'Secciones de ayuda',
  'on_this_page' => 'En esta página',
  'article_label' => 'Guía',
  'technical_label' => 'Documentación técnica',
  'updated_label' => 'Documentación de ContactCore',
  'previous_label' => 'Sección anterior',
  'next_label' => 'Sección siguiente',
  'open_navigation' => 'Abrir secciones',
  'close_navigation' => 'Cerrar secciones',
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
  'title' => 'Documentación técnica',
  'description' => 'Arquitectura, seguridad, configuración y operación de la plataforma.',
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

