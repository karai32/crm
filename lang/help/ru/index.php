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
        'start' => array (
  'title' => 'Начало',
  'description' => 'Знакомство с системой, навигацией и основным рабочим процессом.',
  'icon' => 'ph-house-line',
  'file' => __DIR__ . '/pages/start.php',
),
        'clients' => array (
  'title' => 'Клиенты',
  'description' => 'Работа с организациями, их реквизитами и связанными данными.',
  'icon' => 'ph-buildings',
  'file' => __DIR__ . '/pages/clients.php',
),
        'contacts' => array (
  'title' => 'Контакты',
  'description' => 'Работа с людьми, контактными данными и связями с клиентами.',
  'icon' => 'ph-address-book',
  'file' => __DIR__ . '/pages/contacts.php',
),
        'sectors-tags' => array (
  'title' => 'Сектора и теги',
  'description' => 'Классификация записей с помощью отраслей и гибких меток.',
  'icon' => 'ph-tag',
  'file' => __DIR__ . '/pages/sectors-tags.php',
),
        'custom-fields' => array (
  'title' => 'Пользовательские поля',
  'description' => 'Расширение карточек клиентов и контактов собственными полями.',
  'icon' => 'ph-sliders-horizontal',
  'file' => __DIR__ . '/pages/custom-fields.php',
),
        'import-export' => array (
  'title' => 'Импорт и экспорт',
  'description' => 'Загрузка, проверка и выгрузка данных в CSV и XLSX.',
  'icon' => 'ph-arrows-down-up',
  'file' => __DIR__ . '/pages/import-export.php',
),
        'ai-tools' => array (
  'title' => 'ИИ-инструменты',
  'description' => 'Автоматическое определение компаний и контроль результатов ИИ.',
  'icon' => 'ph-sparkle',
  'file' => __DIR__ . '/pages/ai-tools.php',
),
        'users-settings' => array (
  'title' => 'Пользователи и настройки',
  'description' => 'Учётные записи, параметры интерфейса и управление системой.',
  'icon' => 'ph-users-three',
  'file' => __DIR__ . '/pages/users-settings.php',
),
        'api' => array (
  'title' => 'API',
  'description' => 'Подключение внешних систем к данным и операциям ContactCore.',
  'icon' => 'ph-plugs-connected',
  'file' => __DIR__ . '/pages/api.php',
),
    ],
    'technical' => [
        'meta' => array (
  'id' => 'technical',
  'title' => 'Техническая документация',
  'description' => 'Архитектура, безопасность, конфигурация и эксплуатация платформы.',
  'icon' => 'ph-code',
),
        'pages' => [
            'server' => array (
  'title' => 'Сервер',
  'description' => 'Подготовка операционной системы, PHP-FPM, Nginx, сети и HTTPS для работы ContactCore.',
  'icon' => 'ph-arrow-elbow-down-right',
  'file' => __DIR__ . '/technical/server.php',
),
            'installation' => array (
  'title' => 'Установка',
  'description' => 'Установка ContactCore, подготовка базы и конфигурации, первый запуск и проверка платформы.',
  'icon' => 'ph-arrow-elbow-down-right',
  'file' => __DIR__ . '/technical/installation.php',
),
            'code-structure' => array (
  'title' => 'Структура кода',
  'description' => 'Архитектура ContactCore, жизненный цикл запроса и правила разработки новых функций.',
  'icon' => 'ph-arrow-elbow-down-right',
  'file' => __DIR__ . '/technical/code-structure.php',
),
            'database' => array (
  'title' => 'База данных',
  'description' => 'Схема MySQL, связи между сущностями, целостность данных и правила изменения модели.',
  'icon' => 'ph-arrow-elbow-down-right',
  'file' => __DIR__ . '/technical/database.php',
),
            'domain-model' => array (
  'title' => 'Предметная модель',
  'description' => 'Смысл основных сущностей ContactCore, их связи, состояния и обязательные бизнес-правила.',
  'icon' => 'ph-arrow-elbow-down-right',
  'file' => __DIR__ . '/technical/domain-model.php',
),
            'web-interface-ajax' => array (
  'title' => 'Веб-интерфейс и AJAX',
  'description' => 'Серверный HTML-интерфейс, клиентские компоненты, AJAX-контракты и интерактивные сценарии.',
  'icon' => 'ph-arrow-elbow-down-right',
  'file' => __DIR__ . '/technical/web-interface-ajax.php',
),
        ],
    ],
];
