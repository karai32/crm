<?php

return [
    'title' => 'Технический справочник',
    'description' => 'Сводная карта ContactCore для повседневной разработки: точки входа, каталоги, настройки, маршруты, имена сущностей, разрешения, состояния, лимиты и команды обслуживания.',
    'icon' => 'ph-arrow-elbow-down-right',
    'sections' => [
        [
            'id' => 'reference-purpose',
            'title' => 'Назначение справочника',
            'paragraphs' => [
                'Этот раздел предназначен для быстрого поиска точного имени, пути или значения во время разработки и сопровождения. Он не заменяет подробные статьи о сервере, установке, структуре кода, базе данных, предметной модели, веб-интерфейсе, API, импорте и безопасности. Здесь собраны их основные контракты в компактном виде.',
                'Источником истины остаётся исполняемый код и database/schema.sql. Маршруты определяются в public_html/index.php, разрешения — в Auth::permissionDefinitions(), API scopes — в ApiController::SCOPES, форматы импорта — в ImportMapping и ImportFileReader, а допустимые значения базы — в ENUM и ограничениях schema.sql. При изменении источника справочник обновляется в том же изменении.',
                'Особое внимание следует уделять числу сущности. URL и import/export batches используют contacts и clients во множественном числе. custom_fields, custom_field_values и EntityTagRepository используют contact и client в единственном. Эти значения не взаимозаменяемы.',
            ],
            'examples' => [
                [
                    'title' => 'Основные источники истины',
                    'code' => <<<'CODE'
Routes                 public_html/index.php
Database contract      database/schema.sql + database/migrations/*.sql
Permissions            app/Core/Auth.php
API authentication     app/Services/Api/ApiAuthenticator.php
API protocol/scopes    app/Controllers/Api/ApiController.php
API resource rules     app/Services/Api/*ApiService.php
Import fields/types    app/Services/Import/ImportMapping.php
Help navigation        lang/help/{locale}/index.php
Translations           lang/{locale}.php
CODE,
                ],
            ],
        ],
        [
            'id' => 'reference-runtime',
            'title' => 'Среда выполнения и зависимости',
            'paragraphs' => [
                'Рабочий минимум проекта — PHP 8.3. public_html/index.php намеренно не подключает Composer autoload на более старой версии. Нужна MySQL-совместимая база с InnoDB, utf8mb4, внешними ключами, JSON и FULLTEXT. Рекомендуемая серверная схема — Nginx или Apache, PHP-FPM и отдельный PHP CLI той же версии.',
                'Composer устанавливает illuminate/database ~13.0, guzzlehttp/guzzle ^8.0, phpoffice/phpspreadsheet ^5.8 и phpmailer/phpmailer ^7.1. Illuminate Database предоставляет Query Builder и общее подключение без установки Laravel; Guzzle выполняет внешние HTTP-запросы; PhpSpreadsheet читает и создаёт XLSX; PHPMailer отправляет еженедельные отчёты и подготовленные 2FA-письма. В проекте нет package.json, сборщика и npm-зависимостей: CSS и JavaScript хранятся как готовые assets.',
                'Критичные PHP-возможности: PDO MySQL, mbstring, fileinfo, dom, SimpleXML, XMLReader/XMLWriter, zip, zlib, gd, iconv, ctype, filter, hash и OpenSSL. Для Guzzle рекомендуется расширение curl; без него библиотека может использовать PHP streams. Код также использует random_bytes, password_hash/password_verify, checkdnsrr, flock, finfo, set_time_limit и файловые сессии.',
            ],
            'examples' => [
                [
                    'title' => 'Быстрая проверка среды',
                    'code' => <<<'SHELL'
php8.3 --version
composer check-platform-reqs --no-dev
php8.3 -m | grep -E 'curl|dom|fileinfo|gd|mbstring|PDO|pdo_mysql|SimpleXML|xmlreader|xmlwriter|zip'
mysql --version
SHELL,
                ],
            ],
        ],
        [
            'id' => 'reference-entry-points',
            'title' => 'Точки входа',
            'paragraphs' => [
                'public_html/index.php — единственная HTTP-точка входа. Она настраивает файловые сессии и защитные заголовки, подключает классы, создаёт контроллеры, регистрирует маршруты, выполняет глобальную CSRF-проверку и передаёт запрос Router. Неизвестные физические пути направляются в этот файл через Nginx try_files или public_html/.htaccess.',
                'bin/weekly-report.php — единственная CLI-точка входа. Она не загружает весь HTTP-bootstrap, а подключает Composer, Database, MailerService и WeeklyReportService напрямую. Скрипт выбирает активных администраторов и отправляет им отчёт за последние семь дней. Каталог bin нельзя публиковать через веб-сервер.',
                'Статические CSS, JavaScript, favicon, каталог иконок и шаблоны импорта обслуживаются непосредственно из public_html/assets. Они не проходят Router, Auth или CSRF. Размещать там конфигурацию, пользовательские загрузки и диагностические файлы нельзя.',
            ],
            'examples' => [
                [
                    'title' => 'Жизненный цикл HTTP-запроса',
                    'code' => <<<'CODE'
Web server
  → public_html/index.php
  → session_start + headers
  → require_once classes
  → instantiate controllers
  → register routes
  → global POST CSRF check
  → Router::dispatch(method, URI)
  → controller → service/repository → View or JSON
CODE,
                ],
            ],
        ],
        [
            'id' => 'reference-directories',
            'title' => 'Карта каталогов',
            'paragraphs' => [
                'app содержит исполняемый код по слоям. Controllers принимают HTTP-ввод и выбирают ответ; Services координируют прикладную логику; Repositories инкапсулируют SQL; Core содержит инфраструктуру; Views формируют HTML; Helpers — общие функции представлений. API и import/export имеют дополнительные подкаталоги для своих семейств классов.',
                'config содержит рабочие секреты и .example.php-шаблоны. database содержит полную исходную схему. lang/{locale}.php — короткие строки интерфейса, lang/help/{locale} — крупные страницы справки и их манифест. bin содержит CLI. public_html является document root. storage создаётся и изменяется приложением.',
            ],
            'examples' => [
                [
                    'title' => 'Структура верхнего уровня',
                    'code' => <<<'CODE'
app/
  Controllers/          HTML, AJAX and API controllers
  Core/                 Router, View, Database, Auth, Csrf, helpers
  Helpers/              global view helpers
  Repositories/         SQL access
  Services/             application logic and shared entity writers
  Views/                PHP templates and layouts
bin/                    CLI entry points
config/                 local configuration and secrets
database/               schema.sql and manual migrations
lang/                   UI and help translations
public_html/            document root and static assets
storage/                runtime state and private files
vendor/                 Composer dependencies
CODE,
                ],
            ],
        ],
        [
            'id' => 'reference-configuration',
            'title' => 'Конфигурация и переменные окружения',
            'paragraphs' => [
                'Рабочие PHP-конфиги создаются из четырёх файлов .example.php. config/app.php содержит внешний base_url для ссылок в отчётах. config/database.php задаёт host, database, user, password и charset. config/mail.php задаёт отправителя и SMTP. config/gemini.php содержит api_key для ИИ-инструмента.',
                'Для Gemini переменная окружения GEMINI_API_KEY имеет приоритет над config/gemini.php. Остальные настройки читаются только из PHP-файлов. Универсального .env-загрузчика и единого Config-класса нет. Добавление новой настройки означает явное чтение в нужном сервисе и обновление example-файла и документации.',
                'Секретные файлы не должны попадать в Git. На Linux рекомендуются владелец root, группа www-data и права 0640. base_url должен быть внешним HTTPS-адресом без завершающего слеша. charset базы должен оставаться utf8mb4, если схема и подключение не меняются согласованно.',
            ],
            'examples' => [
                [
                    'title' => 'Ключи конфигурации',
                    'code' => <<<'CODE'
config/app.php
  base_url

config/database.php
  host, database, user, password, charset

config/mail.php
  from_email, from_name
  smtp_host, smtp_port, smtp_username, smtp_password, smtp_secure

config/gemini.php
  api_key

Environment
  GEMINI_API_KEY    overrides config/gemini.php
CODE,
                ],
            ],
        ],
        [
            'id' => 'reference-storage',
            'title' => 'Файлы runtime и storage',
            'paragraphs' => [
                'storage/sessions хранит PHP-сессии; storage/remember — файлы постоянного входа; storage/imports — исходные CSV/XLSX; storage/login_throttle.json — счётчики неудачных входов; storage/app.log — прикладные ошибки. Cron дополнительно может писать storage/weekly-report-cron.log. Ни один из этих объектов не должен отдаваться по HTTP.',
                'Приложение создаёт некоторые каталоги автоматически, но установка должна заранее назначить владельца и права. PHP-FPM и CLI-пользователь cron должны читать config и писать необходимые части storage. У проекта нет общего cleanup worker: срок хранения импортов, remember-файлов и журналов задаётся эксплуатационной политикой.',
            ],
            'examples' => [
                [
                    'title' => 'Назначение runtime-файлов',
                    'code' => <<<'CODE'
storage/sessions/*                PHP session data
storage/remember/{64hex}          remember-me bearer records
storage/imports/*.{csv,xlsx}      uploaded source files
storage/login_throttle.json       login failure counters
storage/app.log                   application diagnostics
storage/weekly-report-cron.log    optional cron stdout/stderr
CODE,
                ],
            ],
        ],
        [
            'id' => 'reference-web-routes',
            'title' => 'Маршруты веб-интерфейса',
            'paragraphs' => [
                'HTML-маршруты используют GET для чтения и форм, POST для изменений. Идентификаторы старых HTML-страниц передаются query-параметром id, а не сегментом пути. Router применяет policy до вызова action: auth = user/admin либо permission с известным ключом. Все browser POST, включая login, проходят глобальную CSRF-проверку.',
                'CRUD контактов и клиентов следует одинаковому шаблону index/create/store/edit/update/show/delete плюс bulk-action. Сектора, теги и пользовательские поля не имеют show и bulk-action. Импорт и экспорт являются отдельными workflow. Пользователи, API-ключи, API-журналы и ИИ-инструменты доступны только администратору.',
            ],
            'examples' => [
                [
                    'title' => 'Сводка HTML-маршрутов',
                    'code' => <<<'CODE'
Authentication
  GET  /login
  POST /login
  GET  /login/verify
  POST /login/verify
  POST /login/resend-code
  GET  /logout

Core pages
  GET  /dashboard
  GET  /contacts | /contacts/create | /contacts/edit?id= | /contacts/show?id=
  POST /contacts/store | /contacts/update | /contacts/delete | /contacts/bulk-action
  GET  /clients  | /clients/create  | /clients/edit?id=  | /clients/show?id=
  POST /clients/store  | /clients/update  | /clients/delete  | /clients/bulk-action

Classification and fields
  GET/POST /sectors/*
  GET/POST /tags/*
  GET/POST /custom-fields/*

Data exchange
  GET  /imports | /imports/errors?id=
  POST /imports/upload | /imports/process
  GET  /exports
  POST /exports/download

Administration and system
  GET/POST /users/*
  GET/POST /api-keys/*
  GET      /api-logs
  GET      /ai
  GET/POST /settings*
  POST     /lang/switch

Help
  GET /help
  GET /help/{topic}
  GET /help/technical/{section}
CODE,
                ],
            ],
        ],
        [
            'id' => 'reference-ajax-routes',
            'title' => 'Маршруты внутреннего AJAX',
            'paragraphs' => [
                'Внутренние endpoints имеют префикс /ajax и возвращают JSON. GET используется для поисковых списков и не требует CSRF, но защищается политикой маршрута. POST изменяет данные либо запускает обработку и дополнительно проверяется глобальным CSRF до Router.',
                'Типовой поиск принимает q и иногда page, возвращая items и has_more. Значения id приводятся к int. Новый AJAX-action должен зарегистрировать маршрут с auth или permission и response = json, проверить входные данные и завершить ответ через json(), чтобы получить корректный Content-Type и статус.',
            ],
            'examples' => [
                [
                    'title' => 'Текущие AJAX-endpoints',
                    'code' => <<<'CODE'
GET  /ajax/global-search
GET  /ajax/clients/search
GET  /ajax/clients/field
GET  /ajax/tags/search
GET  /ajax/sectors/search
GET  /ajax/icons/search
GET  /ajax/custom-field/values

POST /ajax/contacts/inspect-email-batch  admin
POST /ajax/contacts/gemini-company      admin
POST /ajax/contacts/company             admin
POST /ajax/contacts/company/skip        admin
CODE,
                ],
            ],
        ],
        [
            'id' => 'reference-api-routes',
            'title' => 'Маршруты и протокол API',
            'paragraphs' => [
                'Публичная версия API находится под /api/v1 и включает resources contacts и clients. Каждый ресурс имеет одинаковую CRUD-поверхность. Collection GET требует resource:read; POST, PATCH и DELETE требуют resource:write. Write-scope также удовлетворяет read-проверке этого же ресурса. Сектора и теги передаются внутри этих ресурсов без самостоятельных endpoint’ов.',
                'Authorization использует HTTP Basic с client_id как username и secret как password. Тела POST и PATCH — JSON. POST принимает один объект или массив до 100 элементов и возвращает 207 Multi-Status с результатом каждой позиции, даже для одного объекта. PATCH принимает один непустой объект. Каждый ответ получает 24-символьный hex X-Request-Id и записывается в api_logs.',
                'Contacts и clients поддерживают page по умолчанию 1 и per_page по умолчанию 25, от 1 до 100. Подробные поля и поведение связей находятся в разделах «API» и «Внутреннее устройство API».',
            ],
            'examples' => [
                [
                    'title' => 'Единая CRUD-матрица API',
                    'code' => <<<'CODE'
GET     /api/v1/{resource}       {resource}:read
GET     /api/v1/{resource}/{id}  {resource}:read
POST    /api/v1/{resource}       {resource}:write
PATCH   /api/v1/{resource}/{id}  {resource}:write
DELETE  /api/v1/{resource}/{id}  {resource}:write

resource = contacts | clients
CODE,
                ],
            ],
        ],
        [
            'id' => 'reference-access-keys',
            'title' => 'Роли, разрешения и scopes',
            'paragraphs' => [
                'Роли базы: admin и user. Для известных ключей admin обходит индивидуальные разрешения; неизвестный ключ всегда запрещён. user использует строки user_permissions по fail-closed правилу. users.manage не является настраиваемым ключом: управление пользователями ограничено политикой auth = admin. Чтение контактов и клиентов требует auth = user, но не отдельного read permission.',
                'API scopes не связаны с пользовательскими разрешениями и принадлежат api_key. Их четыре: read/write для contacts и clients. Нельзя передавать значение permission в scope или наоборот. Актуальный набор централизован в ApiController::SCOPES и используется созданием ключа, syncScopes и представлением.',
            ],
            'examples' => [
                [
                    'title' => 'Разрешения веб-пользователя',
                    'code' => <<<'CODE'
contacts.create
contacts.edit
contacts.delete
clients.create
clients.edit
clients.delete
exports.use
imports.manage
sectors.manage
tags.manage
custom_fields.manage
CODE,
                ],
                [
                    'title' => 'Scopes API-ключа',
                    'code' => <<<'CODE'
contacts:read     contacts:write
clients:read      clients:write
CODE,
                ],
            ],
        ],
        [
            'id' => 'reference-entity-names',
            'title' => 'Имена сущностей и соглашения',
            'paragraphs' => [
                'Основные PHP-классы называются в единственном числе: ContactController, ContactRepository, ContactApiService. Таблицы и URL обычно во множественном: contacts, clients, sectors, tags. Методы EntityTagRepository принимают строго contact или client, потому что по этому ключу выбирают таблицу связи.',
                'custom_fields.entity_type и custom_field_values.entity_type используют contact/client. import_batches.entity_type и export_batches.entity_type используют contacts/clients. API resource и scope также используют множественное число. Неправильная форма не всегда даст явную ошибку: иногда код применит fallback, создаст пустой набор или выберет другую ветку.',
                'Пользовательские поля API передаются вложенным объектом custom_fields либо плоскими ключами custom_fields.{slug}. Импорт не использует этот синтаксис: там исходная колонка сопоставляется со специальным значением __custom, а имя и slug создаются из заголовка.',
            ],
            'examples' => [
                [
                    'title' => 'Шпаргалка по числу сущности',
                    'code' => <<<'CODE'
Context                              Values
PHP domain name                      Contact | Client
Database main tables                 contacts | clients
HTML/API URL                         /contacts | /clients
API scopes                           contacts:* | clients:*
import/export batch entity_type      contacts | clients
custom field entity_type             contact | client
EntityTagRepository entity argument  contact | client
CODE,
                ],
            ],
        ],
        [
            'id' => 'reference-database-map',
            'title' => 'Карта таблиц базы',
            'paragraphs' => [
                'schema.sql создаёт 21 таблицу. Пользователи и доступ отделены от бизнес-данных. Связи many-to-many вынесены в contact_tags, client_tags и client_contacts. Пользовательские поля используют определения, варианты select и общую типизированную таблицу значений. Импорт, экспорт, API и предпочтения имеют собственные журнальные таблицы.',
                'audit_logs присутствует в схеме, но текущий код его не заполняет. export_batches хранит метаданные выгрузки, а не сам файл. import_rows в текущей реализации записывает только skipped и error. Эти различия важны при диагностике и построении отчётов.',
            ],
            'examples' => [
                [
                    'title' => 'Таблицы по подсистемам',
                    'code' => <<<'CODE'
Identity
  roles, users, user_permissions, user_preferences

CRM
  sectors, tags, clients, contacts
  contact_tags, client_tags, client_contacts

Custom fields
  custom_fields, custom_field_options, custom_field_values

Import/export
  import_batches, import_rows, import_errors, export_batches

API and audit
  api_keys, api_logs, audit_logs
CODE,
                ],
            ],
        ],
        [
            'id' => 'reference-types-statuses',
            'title' => 'Типы, состояния и ENUM',
            'paragraphs' => [
                'Тип пользовательского поля определяет колонку хранения значения. number использует value_number, date — value_date, checkbox — value_bool, остальные типы — value_text. select также хранится как текст, а допустимые варианты находятся в custom_field_options.',
                'Статусы импорта являются автоматом задания, а status строки описывает только конкретную строку. Экспорт имеет более короткий автомат. email_status отражает только результат проверки адреса, а is_corporate_email — отдельная nullable boolean-классификация домена.',
            ],
            'examples' => [
                [
                    'title' => 'Допустимые значения схемы',
                    'code' => <<<'CODE'
custom_fields.entity_type
  contact | client

custom_fields.field_type
  text | textarea | number | date | email | url | select | checkbox

contacts.email_status
  valid | invalid | unknown | NULL

import_batches.file_type
  csv | xlsx
import_batches.entity_type
  contacts | clients
import_batches.status
  uploaded | previewed | processing | completed | partial | failed

import_rows.status
  pending | imported | skipped | error

export_batches.file_type
  csv | xlsx
export_batches.entity_type
  contacts | clients
export_batches.status
  processing | completed | failed
CODE,
                ],
            ],
        ],
        [
            'id' => 'reference-limits',
            'title' => 'Лимиты и значения по умолчанию',
            'paragraphs' => [
                'Лимиты находятся в разных слоях и пока не собраны в конфигурации. Изменять число следует вместе с оценкой памяти, таймаутов, интерфейса и базы. Особенно это относится к XLSX, полным выгрузкам и пакетному API.',
                'Пользовательская пагинация интерфейса хранится под preference_key per_page, по умолчанию равна 20 и допускает 5–500. Настройка применяется к спискам, использующим SortableTrait::pageParams(). AJAX-справочники обычно читают по 20 элементов плюс один для has_more.',
            ],
            'examples' => [
                [
                    'title' => 'Текущие числовые ограничения',
                    'code' => <<<'CODE'
Import upload                     20 MB
Import preview                    first 10 rows; full file counted
Import issues screen              maximum 500 issues
Import formats                    csv, xlsx

Web per_page default              20
Web per_page allowed              5..500
AJAX select page                  20 (+1 probe for has_more)
Email inspection AJAX batch       50 contacts

API POST batch                    maximum 100 items
API contacts/clients per_page     default 25; allowed 1..100
API logged request/response body  64,000 bytes each before suffix

Remember-me lifetime              30 days
Session GC lifetime               30 days
Login throttle                    5 failures / 15 min; lock 15 min
Prepared 2FA code                 10 min; resend 60 sec; 5 attempts

Export row limit                  none
XLSX                              workbook accumulated in memory
GROUP_CONCAT session limit        65,535 bytes during export
CODE,
                ],
            ],
        ],
        [
            'id' => 'reference-http',
            'title' => 'HTTP-ответы и ошибки',
            'paragraphs' => [
                'HTML-контроллеры обычно перенаправляют после успешного POST. Неаутентифицированный веб-пользователь получает redirect на login; недостаточное право — 403 с коротким текстом; отсутствующая запись — 404. Глобальный неверный CSRF возвращает 419. Необработанные ошибки в bootstrap дают общий 500, а подробность уходит в журнал.',
                'AJAX возвращает application/json; guard использует 401 для отсутствующей сессии и 403 для недостатка права. API всегда возвращает JSON и X-Request-Id. Обычные успешные операции используют 200, пакетный POST — 207. Создание не использует 201, удаление не использует 204.',
            ],
            'examples' => [
                [
                    'title' => 'Основные статусы API',
                    'code' => <<<'CODE'
200  successful list/show/update/delete
207  batch POST result, including partial success
401  missing or invalid API key
403  missing scope
404  record not found or invalid route id
409  database integrity conflict
422  invalid JSON, validation error, empty PATCH, batch > 100
500  internal error
CODE,
                ],
                [
                    'title' => 'Форма API-ошибки',
                    'code' => <<<'JSON'
{
  "success": false,
  "error": {
    "code": "validation_error",
    "message": "...",
    "details": ["..."]
  }
}
JSON,
                ],
            ],
        ],
        [
            'id' => 'reference-core-classes',
            'title' => 'Основные инфраструктурные классы',
            'paragraphs' => [
                'Core-классы не образуют framework, но задают общие соглашения. Router сопоставляет методы и пути; View подключает шаблон и layout; Database настраивает Illuminate Database и предоставляет общее подключение Query Builder/PDO; Auth управляет сессией и правами; Csrf создаёт и проверяет токен; Lang загружает локаль; LoginThrottle ограничивает вход.',
                'IdList нормализует массив положительных уникальных id. Illuminate Support формирует slug через Str::slug() с транслитерацией Unicode. SortableTrait валидирует sort/dir и рассчитывает страницы. ControllerHelperTrait обрабатывает nullable strings, id-массивы, tag-фильтры и значения пользовательских фильтров.',
            ],
            'examples' => [
                [
                    'title' => 'Краткий указатель классов',
                    'code' => <<<'CODE'
Router                 HTTP method/path dispatch
View                   PHP view + layout rendering
Database               shared Query Builder/PDO connection
Auth                   session, remember-me, roles, permissions
Csrf                   session token and hidden field
LoginThrottle          file-backed login limiting
Lang                   locale dictionary
IdList                 positive unique integer arrays
Illuminate Support     Unicode slug и работа с датами через Carbon
SortableTrait          sort, direction and pagination
ControllerHelperTrait  common request normalization
CODE,
                ],
            ],
        ],
        [
            'id' => 'reference-commands',
            'title' => 'Команды разработки и обслуживания',
            'paragraphs' => [
                'Проект не определяет Composer scripts, PHPUnit-конфигурацию или отдельный migration runner. Установка зависимостей выполняется Composer, схема разворачивается импортом database/schema.sql, а синтаксис проверяется php -l. Изменение схемы после первого запуска требует отдельного согласованного SQL-скрипта обновления: повторный schema.sql удалит существующие таблицы и данные.',
                'Еженедельный отчёт запускается только через PHP CLI. По умолчанию collect() берёт последние семь дней и отправляет письмо всем активным admin. Перед cron команда выполняется вручную от того же системного пользователя. npm install и сборку assets запускать не требуется.',
            ],
            'examples' => [
                [
                    'title' => 'Типовые команды',
                    'code' => <<<'SHELL'
cd /var/www/contactcore

composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
composer check-platform-reqs --no-dev

php8.3 -l public_html/index.php
find app config bin lang public_html -name '*.php' -print0 | xargs -0 -n1 php8.3 -l

# Только для чистой базы: schema.sql содержит DROP TABLE
mysql -u crm_user -p crm < database/schema.sql

# Для существующей базы: применить ещё не выполненные миграции по порядку
mysql -u crm_user -p crm < database/migrations/20260729_fail_closed_permissions.sql
mysql -u crm_user -p crm < database/migrations/20260729_enforce_database_constraints.sql

sudo -u www-data /usr/bin/php8.3 bin/weekly-report.php
tail -n 50 storage/app.log
SHELL,
                ],
                [
                    'title' => 'Cron еженедельного отчёта',
                    'code' => <<<'CRON'
SHELL=/bin/sh
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin

0 8 * * 1 www-data cd /var/www/contactcore && /usr/bin/php8.3 bin/weekly-report.php >> storage/weekly-report-cron.log 2>&1
CRON,
                ],
            ],
        ],
        [
            'id' => 'reference-change-checklist',
            'title' => 'Контроль согласованности изменений',
            'paragraphs' => [
                'ContactCore использует явную регистрацию вместо автоматического discovery. Новый класс нужно подключить require_once в public_html/index.php до создания зависимого объекта. Новый контроллер необходимо создать, зарегистрировать маршрут и защитить Auth/CSRF. Новый asset передаётся через styles или scripts в View::render. Новый текст интерфейса добавляется во все поддерживаемые локали.',
                'Изменение сущности обычно затрагивает schema или SQL-обновление, Repository, Controller/Service, View, фильтры, импорт, экспорт, API, отчёты и документацию. Не каждый модуль обязательно меняется, но каждый должен быть проверен осознанно. Для нового permission или scope особенно важны миграция существующих записей и fail-closed поведение.',
                'Перед передачей изменения проверяются синтаксис всех PHP-файлов, реальные HTTP-пути, матрица доступа, CSRF, SQL на чистой и существующей базе, локали, мобильное отображение, журнал ошибок и связанные пакетные операции. Технический справочник обновляется только точными значениями из принятого кода.',
            ],
            'examples' => [
                [
                    'title' => 'Универсальный checklist новой функции',
                    'code' => <<<'CODE'
[ ] database schema/update SQL and indexes
[ ] Repository queries and transaction boundary
[ ] Service/domain rules
[ ] Controller and require_once
[ ] Route and HTTP method
[ ] Auth permission/admin guard
[ ] CSRF for browser mutation
[ ] View + contextual escaping
[ ] CSS/JS assets
[ ] translations: en, es, ru
[ ] import/export impact
[ ] API fields, scopes and backward compatibility
[ ] reports, logs and retention impact
[ ] integration and access-matrix tests
[ ] user and technical documentation
CODE,
                ],
            ],
        ],
    ],
];
