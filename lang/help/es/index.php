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
        'start' => array (
  'title' => 'Primeros pasos',
  'description' => 'Introducción al sistema, la navegación y el flujo de trabajo principal.',
  'icon' => 'ph-house-line',
  'file' => __DIR__ . '/pages/start.php',
),
        'clients' => array (
  'title' => 'Clientes',
  'description' => 'Organizaciones, datos de empresa y registros relacionados.',
  'icon' => 'ph-buildings',
  'file' => __DIR__ . '/pages/clients.php',
),
        'contacts' => array (
  'title' => 'Contactos',
  'description' => 'Personas, datos de contacto y sus relaciones con clientes.',
  'icon' => 'ph-address-book',
  'file' => __DIR__ . '/pages/contacts.php',
),
        'sectors-tags' => array (
  'title' => 'Sectores y tags',
  'description' => 'Clasificación mediante industrias y etiquetas flexibles.',
  'icon' => 'ph-tag',
  'file' => __DIR__ . '/pages/sectors-tags.php',
),
        'custom-fields' => array (
  'title' => 'Campos personalizados',
  'description' => 'Ampliación de clientes y contactos con campos propios.',
  'icon' => 'ph-sliders-horizontal',
  'file' => __DIR__ . '/pages/custom-fields.php',
),
        'import-export' => array (
  'title' => 'Importación y exportación',
  'description' => 'Carga, validación y exportación de datos CSV y XLSX.',
  'icon' => 'ph-arrows-down-up',
  'file' => __DIR__ . '/pages/import-export.php',
),
        'ai-tools' => array (
  'title' => 'Herramientas de IA',
  'description' => 'Detección automática de empresas y revisión de resultados.',
  'icon' => 'ph-sparkle',
  'file' => __DIR__ . '/pages/ai-tools.php',
),
        'users-settings' => array (
  'title' => 'Usuarios y ajustes',
  'description' => 'Cuentas, preferencias de interfaz y gestión del sistema.',
  'icon' => 'ph-users-three',
  'file' => __DIR__ . '/pages/users-settings.php',
),
        'api' => array (
  'title' => 'API',
  'description' => 'Conexión de sistemas externos con los datos y operaciones de ContactCore.',
  'icon' => 'ph-plugs-connected',
  'file' => __DIR__ . '/pages/api.php',
),
    ],
    'technical' => [
        'meta' => array (
  'id' => 'technical',
  'title' => 'Documentación técnica',
  'description' => 'Arquitectura, seguridad, configuración y operación de la plataforma.',
  'icon' => 'ph-code',
),
        'pages' => [
            'server' => array (
  'title' => 'Servidor',
  'description' => '',
  'icon' => 'ph-arrow-elbow-down-right',
  'file' => __DIR__ . '/technical/server.php',
),
            'installation' => array (
  'title' => 'Instalación',
  'description' => '',
  'icon' => 'ph-arrow-elbow-down-right',
  'file' => __DIR__ . '/technical/installation.php',
),
            'code-structure' => array (
  'title' => 'Estructura del código',
  'description' => '',
  'icon' => 'ph-arrow-elbow-down-right',
  'file' => __DIR__ . '/technical/code-structure.php',
),
            'database' => array (
  'title' => 'Base de datos',
  'description' => '',
  'icon' => 'ph-arrow-elbow-down-right',
  'file' => __DIR__ . '/technical/database.php',
),
        ],
    ],
];

