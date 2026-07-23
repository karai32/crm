<?php

class HelpController
{
    public function index(): void
    {
        $this->renderTopic('start');
    }

    public function show(): void
    {
        $topic = trim((string) ($_GET['topic'] ?? ''), '/');

        $aliases = [
            'getting-started' => 'start',
            'tags-sectors' => 'sectors-tags',
            'imports' => 'import-export',
            'exports' => 'import-export',
            'ai' => 'ai-tools',
            'users-roles' => 'users-settings',
            'technical-guide' => 'technical',
        ];

        $this->renderTopic($aliases[$topic] ?? $topic);
    }

    private function renderTopic(string $topic): void
    {
        Auth::requireLogin();

        $locale = in_array(Lang::locale(), ['ru', 'es', 'en'], true) ? Lang::locale() : 'en';
        $copy = $this->copy()[$locale];
        $navigation = $copy['navigation'];
        $activeIndex = array_search($topic, array_column($navigation, 'id'), true);

        if ($activeIndex === false) {
            Auth::redirect('/help');
            return;
        }

        $page = $navigation[$activeIndex];
        $page['sections'] = $topic === 'technical'
            ? $copy['technical_sections']
            : $this->articleSections($topic, $page, $copy['article']);

        View::render('help/index', [
            'title' => $page['title'] . ' — ' . $copy['center_title'],
            'styles' => ['help.css'],
            'scripts' => ['help.js'],
            'locale' => $locale,
            'copy' => $copy,
            'navigation' => $navigation,
            'activeIndex' => $activeIndex,
            'page' => $page,
        ]);
    }

    private function articleSections(string $topic, array $page, array $article): array
    {
        $headings = $article['headings'][$topic] ?? $article['default_headings'];

        return [
            [
                'id' => 'overview',
                'title' => $headings[0],
                'paragraphs' => [
                    $page['description'],
                    $article['overview'],
                ],
            ],
            [
                'id' => 'workflow',
                'title' => $headings[1],
                'paragraphs' => [$article['workflow']],
            ],
            [
                'id' => 'details',
                'title' => $headings[2],
                'paragraphs' => [$article['details']],
            ],
        ];
    }

    private function copy(): array
    {
        return [
            'ru' => [
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
                'navigation' => [
                    ['id' => 'start', 'title' => 'Начало', 'description' => 'Знакомство с системой, навигацией и основным рабочим процессом.', 'icon' => 'ph-house-line'],
                    ['id' => 'clients', 'title' => 'Клиенты', 'description' => 'Работа с организациями, их реквизитами и связанными данными.', 'icon' => 'ph-buildings'],
                    ['id' => 'contacts', 'title' => 'Контакты', 'description' => 'Работа с людьми, контактными данными и связями с клиентами.', 'icon' => 'ph-address-book'],
                    ['id' => 'sectors-tags', 'title' => 'Сектора и теги', 'description' => 'Классификация записей с помощью отраслей и гибких меток.', 'icon' => 'ph-tag'],
                    ['id' => 'custom-fields', 'title' => 'Пользовательские поля', 'description' => 'Расширение карточек клиентов и контактов собственными полями.', 'icon' => 'ph-sliders-horizontal'],
                    ['id' => 'import-export', 'title' => 'Импорт и экспорт', 'description' => 'Загрузка, проверка и выгрузка данных в CSV и XLSX.', 'icon' => 'ph-arrows-down-up'],
                    ['id' => 'ai-tools', 'title' => 'ИИ-инструменты', 'description' => 'Автоматическое определение компаний и контроль результатов ИИ.', 'icon' => 'ph-sparkle'],
                    ['id' => 'users-settings', 'title' => 'Пользователи и настройки', 'description' => 'Учётные записи, параметры интерфейса и управление системой.', 'icon' => 'ph-users-three'],
                    ['id' => 'api', 'title' => 'API', 'description' => 'Подключение внешних систем к данным и операциям ContactCore.', 'icon' => 'ph-plugs-connected'],
                    ['id' => 'technical', 'title' => 'Техническая документация', 'description' => 'Архитектура, безопасность, конфигурация и эксплуатация платформы.', 'icon' => 'ph-code'],
                ],
                'article' => [
                    'overview' => 'В статье последовательно объясняется назначение раздела и место его данных в общей структуре CRM.',
                    'workflow' => 'Работа рассматривается как связный процесс: от открытия раздела и поиска нужной записи до сохранения результата и проверки связанных данных.',
                    'details' => 'Отдельное внимание уделяется ограничениям, связанным возможностям и ситуациям, в которых выбранный инструмент используется наиболее эффективно.',
                    'default_headings' => ['О разделе', 'Основной порядок работы', 'Важные особенности'],
                    'headings' => [
                        'start' => ['О системе', 'Как устроена работа', 'Как пользоваться справкой'],
                        'clients' => ['Что такое клиент', 'Работа с карточкой клиента', 'Связанные данные'],
                        'contacts' => ['Что такое контакт', 'Работа с карточкой контакта', 'Связи и классификация'],
                        'sectors-tags' => ['Принципы классификации', 'Сектора', 'Теги'],
                        'custom-fields' => ['Назначение полей', 'Создание и настройка', 'Хранение и использование значений'],
                        'import-export' => ['Обмен данными', 'Импорт', 'Экспорт'],
                        'ai-tools' => ['Назначение ИИ-инструментов', 'Подготовка и обработка данных', 'Проверка результата'],
                        'users-settings' => ['Учётные записи', 'Настройки пользователя', 'Администрирование'],
                        'api' => ['Назначение API', 'Аутентификация и запросы', 'Ресурсы и ответы'],
                    ],
                ],
                'technical_sections' => [
                    ['id' => 'platform', 'title' => 'Обзор платформы', 'description' => 'Назначение системы, используемые технологии и ключевые архитектурные решения.'],
                    ['id' => 'architecture', 'title' => 'Архитектура приложения', 'description' => 'Слои приложения, структура каталогов, маршрутизация и жизненный цикл HTTP-запроса.'],
                    ['id' => 'database', 'title' => 'База данных', 'description' => 'Модель данных, связи между сущностями, индексы и правила изменения схемы.'],
                    ['id' => 'security', 'title' => 'Аутентификация и безопасность', 'description' => 'Сессии, права доступа, CSRF, API-ключи и защита конфиденциальных данных.'],
                    ['id' => 'configuration', 'title' => 'Конфигурация и интеграции', 'description' => 'Настройка базы данных, почты, Gemini, API и фоновых задач.'],
                    ['id' => 'deployment', 'title' => 'Развёртывание и обслуживание', 'description' => 'Требования окружения, установка, журналы, диагностика и регулярное обслуживание.'],
                ],
            ],
            'en' => [
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
                'navigation' => [
                    ['id' => 'start', 'title' => 'Getting started', 'description' => 'An introduction to the system, navigation and core workflow.', 'icon' => 'ph-house-line'],
                    ['id' => 'clients', 'title' => 'Clients', 'description' => 'Organizations, company details and related records.', 'icon' => 'ph-buildings'],
                    ['id' => 'contacts', 'title' => 'Contacts', 'description' => 'People, contact details and their links to clients.', 'icon' => 'ph-address-book'],
                    ['id' => 'sectors-tags', 'title' => 'Sectors and tags', 'description' => 'Classifying records with industries and flexible labels.', 'icon' => 'ph-tag'],
                    ['id' => 'custom-fields', 'title' => 'Custom fields', 'description' => 'Extending client and contact records with your own fields.', 'icon' => 'ph-sliders-horizontal'],
                    ['id' => 'import-export', 'title' => 'Import and export', 'description' => 'Loading, validating and exporting CSV and XLSX data.', 'icon' => 'ph-arrows-down-up'],
                    ['id' => 'ai-tools', 'title' => 'AI tools', 'description' => 'Automated company discovery and review of AI results.', 'icon' => 'ph-sparkle'],
                    ['id' => 'users-settings', 'title' => 'Users and settings', 'description' => 'Accounts, interface preferences and system management.', 'icon' => 'ph-users-three'],
                    ['id' => 'api', 'title' => 'API', 'description' => 'Connecting external systems to ContactCore data and operations.', 'icon' => 'ph-plugs-connected'],
                    ['id' => 'technical', 'title' => 'Technical documentation', 'description' => 'Architecture, security, configuration and platform operations.', 'icon' => 'ph-code'],
                ],
                'article' => [
                    'overview' => 'The article explains the purpose of this area and how its data fits into the wider CRM.',
                    'workflow' => 'The workflow is presented from opening the section and finding a record through saving the result and reviewing related data.',
                    'details' => 'Additional notes cover constraints, connected features and the situations where this part of the system is most useful.',
                    'default_headings' => ['About this section', 'Core workflow', 'Important details'],
                    'headings' => [],
                ],
                'technical_sections' => [
                    ['id' => 'platform', 'title' => 'Platform overview', 'description' => 'System purpose, technology stack and key architectural decisions.'],
                    ['id' => 'architecture', 'title' => 'Application architecture', 'description' => 'Application layers, directory structure, routing and the HTTP request lifecycle.'],
                    ['id' => 'database', 'title' => 'Database', 'description' => 'Data model, entity relationships, indexes and schema change rules.'],
                    ['id' => 'security', 'title' => 'Authentication and security', 'description' => 'Sessions, access control, CSRF, API keys and confidential data protection.'],
                    ['id' => 'configuration', 'title' => 'Configuration and integrations', 'description' => 'Database, mail, Gemini, API and scheduled task configuration.'],
                    ['id' => 'deployment', 'title' => 'Deployment and operations', 'description' => 'Environment requirements, installation, logs, diagnostics and maintenance.'],
                ],
            ],
            'es' => [
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
                'navigation' => [
                    ['id' => 'start', 'title' => 'Primeros pasos', 'description' => 'Introducción al sistema, la navegación y el flujo de trabajo principal.', 'icon' => 'ph-house-line'],
                    ['id' => 'clients', 'title' => 'Clientes', 'description' => 'Organizaciones, datos de empresa y registros relacionados.', 'icon' => 'ph-buildings'],
                    ['id' => 'contacts', 'title' => 'Contactos', 'description' => 'Personas, datos de contacto y sus relaciones con clientes.', 'icon' => 'ph-address-book'],
                    ['id' => 'sectors-tags', 'title' => 'Sectores y tags', 'description' => 'Clasificación mediante industrias y etiquetas flexibles.', 'icon' => 'ph-tag'],
                    ['id' => 'custom-fields', 'title' => 'Campos personalizados', 'description' => 'Ampliación de clientes y contactos con campos propios.', 'icon' => 'ph-sliders-horizontal'],
                    ['id' => 'import-export', 'title' => 'Importación y exportación', 'description' => 'Carga, validación y exportación de datos CSV y XLSX.', 'icon' => 'ph-arrows-down-up'],
                    ['id' => 'ai-tools', 'title' => 'Herramientas de IA', 'description' => 'Detección automática de empresas y revisión de resultados.', 'icon' => 'ph-sparkle'],
                    ['id' => 'users-settings', 'title' => 'Usuarios y ajustes', 'description' => 'Cuentas, preferencias de interfaz y gestión del sistema.', 'icon' => 'ph-users-three'],
                    ['id' => 'api', 'title' => 'API', 'description' => 'Conexión de sistemas externos con los datos y operaciones de ContactCore.', 'icon' => 'ph-plugs-connected'],
                    ['id' => 'technical', 'title' => 'Documentación técnica', 'description' => 'Arquitectura, seguridad, configuración y operación de la plataforma.', 'icon' => 'ph-code'],
                ],
                'article' => [
                    'overview' => 'El artículo explica la finalidad del área y cómo encajan sus datos en el conjunto del CRM.',
                    'workflow' => 'El flujo se presenta desde la apertura de la sección y la búsqueda de un registro hasta el guardado y la revisión de datos relacionados.',
                    'details' => 'Las notas adicionales cubren limitaciones, funciones relacionadas y los casos en que esta parte del sistema resulta más útil.',
                    'default_headings' => ['Acerca de esta sección', 'Flujo de trabajo', 'Detalles importantes'],
                    'headings' => [],
                ],
                'technical_sections' => [
                    ['id' => 'platform', 'title' => 'Resumen de la plataforma', 'description' => 'Propósito del sistema, tecnologías y decisiones arquitectónicas principales.'],
                    ['id' => 'architecture', 'title' => 'Arquitectura de la aplicación', 'description' => 'Capas, estructura de directorios, rutas y ciclo de una petición HTTP.'],
                    ['id' => 'database', 'title' => 'Base de datos', 'description' => 'Modelo de datos, relaciones, índices y reglas de cambio del esquema.'],
                    ['id' => 'security', 'title' => 'Autenticación y seguridad', 'description' => 'Sesiones, control de acceso, CSRF, claves API y protección de datos.'],
                    ['id' => 'configuration', 'title' => 'Configuración e integraciones', 'description' => 'Configuración de base de datos, correo, Gemini, API y tareas programadas.'],
                    ['id' => 'deployment', 'title' => 'Despliegue y operación', 'description' => 'Requisitos, instalación, registros, diagnóstico y mantenimiento.'],
                ],
            ],
        ];
    }
}
