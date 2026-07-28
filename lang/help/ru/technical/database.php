<?php

return [
    'title' => 'База данных',
    'description' => 'Схема MySQL, связи между сущностями, целостность данных и правила изменения модели.',
    'icon' => 'ph-arrow-elbow-down-right',
    'sections' => [
        require __DIR__ . '/database/database-overview.php',
        require __DIR__ . '/database/database-relations-map.php',
        require __DIR__ . '/database/database-conventions.php',
        require __DIR__ . '/database/database-users.php',
        require __DIR__ . '/database/database-clients-contacts.php',
        require __DIR__ . '/database/database-classification.php',
        require __DIR__ . '/database/database-custom-fields.php',
        require __DIR__ . '/database/database-import-export.php',
        require __DIR__ . '/database/database-api.php',
        require __DIR__ . '/database/database-audit.php',
        require __DIR__ . '/database/database-integrity.php',
        require __DIR__ . '/database/database-indexes.php',
        require __DIR__ . '/database/database-transactions.php',
        require __DIR__ . '/database/database-schema-changes.php',
        require __DIR__ . '/database/database-development.php',
        require __DIR__ . '/database/database-health.php',
    ],
];

