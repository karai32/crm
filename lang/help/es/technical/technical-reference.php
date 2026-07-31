<?php

return [
    'title' => 'Referencia técnica',
    'description' => 'Mapa resumido de ContactCore para el desarrollo diario: puntos de entrada, directorios, ajustes, rutas, nombres de entidades, permisos, estados, límites y comandos de mantenimiento.',
    'icon' => 'ph-arrow-elbow-down-right',
    'sections' => [
        [
            'id' => 'reference-purpose',
            'title' => 'Finalidad de la referencia',
            'paragraphs' => [
                'Esta sección permite localizar rápidamente un nombre, una ruta o un valor exactos durante el desarrollo y el mantenimiento. No sustituye a los artículos detallados sobre el servidor, la instalación, la estructura del código, la base de datos, el modelo de dominio, la interfaz web, la API, la importación y la seguridad. Aquí se reúnen sus principales contratos de forma compacta.',
                'El código ejecutable y database/schema.sql siguen siendo las fuentes de verdad. Las rutas se definen en public_html/index.php; los permisos, en Auth::permissionDefinitions(); los scopes de la API, en ApiController::SCOPES; los formatos de importación, en ImportMapping e ImportFileReader; y los valores admitidos por la base de datos, en los ENUM y las restricciones de schema.sql. Cuando cambia una fuente, esta referencia se actualiza en el mismo cambio.',
                'Debe prestarse especial atención al número gramatical de la entidad. Las URL y los batches de importación/exportación utilizan contacts y clients en plural. custom_fields, custom_field_values y EntityTagRepository utilizan contact y client en singular. Estos valores no son intercambiables.',
            ],
            'examples' => [
                [
                    'title' => 'Principales fuentes de verdad',
                    'code' => <<<'CODE'
Routes                 public_html/index.php
Database contract      database/schema.sql
Permissions            app/Core/Auth.php
API authentication     app/Services/Api/ApiAuthenticator.php
API protocol/scopes    app/Controllers/ApiController.php
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
            'title' => 'Entorno de ejecución y dependencias',
            'paragraphs' => [
                'Las versiones compatibles del proyecto son PHP 8.4 y 8.5. public_html/index.php no carga deliberadamente el autoload de Composer en una versión no compatible. Se necesita una base de datos compatible con MySQL que disponga de InnoDB, utf8mb4, claves foráneas, JSON y FULLTEXT. La configuración de servidor recomendada es Nginx o Apache, PHP-FPM y una instalación independiente de PHP CLI de la misma versión.',
                'Composer instala illuminate/database ~13.0, guzzlehttp/guzzle ^8.0, openspout/openspout ^5.8 y phpmailer/phpmailer ^7.1. Illuminate Database proporciona Query Builder y una conexión común sin instalar Laravel; Guzzle realiza solicitudes HTTP externas; OpenSpout lee y crea XLSX mediante streaming; PHPMailer envía los informes semanales y los correos 2FA preparados. El proyecto no incluye package.json, bundler ni dependencias npm: CSS y JavaScript se almacenan como assets ya preparados.',
                'Las capacidades PHP críticas son PDO MySQL, mbstring, fileinfo, dom, SimpleXML, XMLReader/XMLWriter, zip, zlib, gd, iconv, ctype, filter, hash y OpenSSL. Para Guzzle se recomienda la extensión curl; sin ella, la biblioteca puede usar los streams de PHP. El código también utiliza random_bytes, password_hash/password_verify, checkdnsrr, flock, finfo, set_time_limit y sesiones basadas en archivos.',
            ],
            'examples' => [
                [
                    'title' => 'Comprobación rápida del entorno',
                    'code' => <<<'SHELL'
php8.5 --version
composer check-platform-reqs --no-dev
php8.5 -m | grep -E 'curl|dom|fileinfo|gd|mbstring|PDO|pdo_mysql|SimpleXML|xmlreader|xmlwriter|zip'
mysql --version
SHELL,
                ],
            ],
        ],
        [
            'id' => 'reference-entry-points',
            'title' => 'Puntos de entrada',
            'paragraphs' => [
                'public_html/index.php es el único punto de entrada HTTP. Configura las sesiones basadas en archivos y las cabeceras de seguridad, carga las clases, crea los controladores, registra las rutas, realiza la comprobación CSRF global y entrega la solicitud a Router. Las rutas físicas desconocidas se dirigen a este archivo mediante try_files de Nginx o public_html/.htaccess.',
                'bin/weekly-report.php es el único punto de entrada CLI. No carga todo el bootstrap HTTP, sino que incluye directamente Composer, Database, MailerService y WeeklyReportService. El script selecciona a los administradores activos y les envía un informe de los últimos siete días. El directorio bin no debe publicarse mediante el servidor web.',
                'Los archivos CSS y JavaScript estáticos, el favicon, el catálogo de iconos y las plantillas de importación se sirven directamente desde public_html/assets. No pasan por Router, Auth ni CSRF. No deben colocarse allí configuraciones, archivos subidos por usuarios ni archivos de diagnóstico.',
            ],
            'examples' => [
                [
                    'title' => 'Ciclo de vida de una solicitud HTTP',
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
            'title' => 'Mapa de directorios',
            'paragraphs' => [
                'app contiene el código ejecutable organizado por capas. Controllers recibe la entrada HTTP y selecciona la respuesta; Services coordina la lógica de la aplicación; Repositories encapsula SQL; Core contiene la infraestructura; Views genera HTML; y Helpers reúne funciones comunes de las vistas. La API y la importación/exportación tienen subdirectorios adicionales para sus familias de clases.',
                'config contiene los secretos activos y las plantillas .example.php. database contiene el esquema de origen completo. lang/{locale}.php contiene las cadenas breves de la interfaz; lang/help/{locale}, las páginas extensas de ayuda y su manifiesto. bin contiene la CLI. public_html es el document root. storage es creado y modificado por la aplicación.',
            ],
            'examples' => [
                [
                    'title' => 'Estructura de nivel superior',
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
database/               schema.sql para instalaciones limpias
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
            'title' => 'Configuración y variables de entorno',
            'paragraphs' => [
                'Los archivos de configuración PHP activos se crean a partir de cuatro archivos .example.php. config/app.php contiene el base_url externo para los enlaces de los informes. config/database.php define host, database, user, password y charset. config/mail.php define el remitente y SMTP. config/gemini.php contiene api_key para la herramienta de IA.',
                'Para Gemini, la variable de entorno GEMINI_API_KEY tiene prioridad sobre config/gemini.php. Los demás ajustes solo se leen de archivos PHP. No existe un cargador .env universal ni una clase Config común. Añadir un ajuste nuevo implica leerlo explícitamente en el servicio correspondiente y actualizar el archivo de ejemplo y la documentación.',
                'Los archivos secretos no deben incorporarse a Git. En Linux se recomiendan el propietario root, el grupo www-data y permisos 0640. base_url debe ser una dirección HTTPS externa sin barra final. El charset de la base de datos debe seguir siendo utf8mb4 salvo que se modifiquen de forma coordinada el esquema y la conexión.',
            ],
            'examples' => [
                [
                    'title' => 'Claves de configuración',
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
            'title' => 'Archivos de runtime y storage',
            'paragraphs' => [
                'storage/sessions almacena las sesiones PHP; storage/remember, los archivos de acceso persistente; storage/imports, los CSV/XLSX originales; storage/login_throttle.json, los contadores de intentos fallidos; y storage/app.log, los errores de la aplicación. Cron también puede escribir en storage/weekly-report-cron.log. Ninguno de estos objetos debe servirse por HTTP.',
                'La aplicación crea algunos directorios automáticamente, pero la instalación debe asignar previamente el propietario y los permisos. PHP-FPM y el usuario CLI de cron deben poder leer config y escribir en las partes necesarias de storage. El proyecto no incluye un cleanup worker común: el período de conservación de las importaciones, los archivos remember y los registros se define mediante la política operativa.',
            ],
            'examples' => [
                [
                    'title' => 'Finalidad de los archivos de runtime',
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
            'title' => 'Rutas de la interfaz web',
            'paragraphs' => [
                'Las rutas HTML utilizan GET para la lectura y los formularios, y POST para los cambios. Los identificadores de las páginas HTML antiguas se pasan mediante el parámetro de consulta id, no como segmento de la ruta. Router aplica la policy antes de llamar al action: auth = user/admin o permission con una clave conocida. Todos los POST del navegador, incluido login, pasan por la comprobación CSRF global.',
                'El CRUD de contactos y clientes sigue el mismo patrón: index/create/store/edit/update/show/delete más bulk-action. Los sectores, las etiquetas y los campos personalizados no tienen show ni bulk-action. La importación y la exportación son workflows independientes. Los usuarios, las claves de API, los registros de API y las herramientas de IA solo están disponibles para el administrador.',
            ],
            'examples' => [
                [
                    'title' => 'Resumen de rutas HTML',
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
            'title' => 'Rutas AJAX internas',
            'paragraphs' => [
                'Los endpoints internos tienen el prefijo /ajax y devuelven JSON. GET se utiliza para las listas de búsqueda y no requiere CSRF, pero está protegido por la política de la ruta. POST modifica datos o inicia un procesamiento y, además, se comprueba mediante el CSRF global antes de Router.',
                'Una búsqueda típica acepta q y, en ocasiones, page, y devuelve items y has_more. Los valores id se convierten a int. Una acción AJAX nueva debe registrar la ruta con auth o permission y response = json, comprobar los datos de entrada y finalizar la respuesta mediante json() para obtener el Content-Type y el estado correctos.',
            ],
            'examples' => [
                [
                    'title' => 'Endpoints AJAX actuales',
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
            'title' => 'Rutas y protocolo de la API',
            'paragraphs' => [
                'La versión pública de la API se encuentra bajo /api/v1 e incluye los resources contacts y clients. Cada recurso tiene la misma superficie CRUD. El GET de una colección requiere resource:read; POST, PATCH y DELETE requieren resource:write. El write-scope también satisface la comprobación read del mismo recurso. Los sectores y las etiquetas se incluyen dentro de estos recursos sin endpoints propios.',
                'Authorization utiliza HTTP Basic con client_id como username y secret como password. Los cuerpos de POST y PATCH son JSON. POST acepta un objeto o un array de hasta 100 elementos y devuelve 207 Multi-Status con el resultado de cada posición, incluso para un solo objeto. PATCH acepta un único objeto no vacío. Cada respuesta recibe un X-Request-Id hexadecimal de 24 caracteres y se registra en api_logs.',
                'Contacts y clients admiten page con valor predeterminado 1 y per_page con valor predeterminado 25, entre 1 y 100. Los campos detallados y el comportamiento de las relaciones se encuentran en las secciones «API» y «Estructura interna de la API».',
            ],
            'examples' => [
                [
                    'title' => 'Matriz CRUD común de la API',
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
            'title' => 'Roles, permisos y scopes',
            'paragraphs' => [
                'Los roles de la base de datos son admin y user. Para las claves conocidas, admin elude los permisos individuales; una clave desconocida siempre se deniega. user utiliza las filas de user_permissions según una regla fail-closed. users.manage no es una clave configurable: la gestión de usuarios está limitada por la política auth = admin. La lectura de contactos y clientes requiere auth = user, pero no un read permission independiente.',
                'Los scopes de la API no están relacionados con los permisos de los usuarios y pertenecen a la api_key. Son cuatro: read/write para contacts y clients. No se puede pasar un valor permission como scope ni viceversa. El conjunto actual está centralizado en ApiController::SCOPES y se utiliza para crear claves, en syncScopes y en la vista.',
            ],
            'examples' => [
                [
                    'title' => 'Permisos del usuario web',
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
                    'title' => 'Scopes de una clave de API',
                    'code' => <<<'CODE'
contacts:read     contacts:write
clients:read      clients:write
CODE,
                ],
            ],
        ],
        [
            'id' => 'reference-entity-names',
            'title' => 'Nombres de entidades y convenciones',
            'paragraphs' => [
                'Las clases PHP principales se nombran en singular: ContactController, ContactRepository, ContactApiService. Las tablas y las URL suelen utilizar el plural: contacts, clients, sectors, tags. Los métodos de EntityTagRepository aceptan estrictamente contact o client porque seleccionan la tabla de relación mediante esa clave.',
                'custom_fields.entity_type y custom_field_values.entity_type utilizan contact/client. import_batches.entity_type y export_batches.entity_type utilizan contacts/clients. Los resources y scopes de la API también emplean el plural. Una forma incorrecta no siempre produce un error explícito: a veces el código aplica un fallback, crea un conjunto vacío o selecciona otra rama.',
                'Los campos personalizados de la API se pasan mediante el objeto anidado custom_fields o mediante claves planas custom_fields.{slug}. La importación no utiliza esta sintaxis: la columna de origen se asigna al valor especial __custom, y el nombre y el slug se crean a partir de la cabecera.',
            ],
            'examples' => [
                [
                    'title' => 'Guía rápida del número de la entidad',
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
            'title' => 'Mapa de tablas de la base de datos',
            'paragraphs' => [
                'schema.sql crea 21 tablas. Los usuarios y el acceso están separados de los datos de negocio. Las relaciones many-to-many se encuentran en contact_tags, client_tags y client_contacts. Los campos personalizados utilizan definiciones, opciones select y una tabla común de valores tipados. La importación, la exportación, la API y las preferencias tienen sus propias tablas de registro.',
                'audit_logs existe en el esquema, pero el código actual no lo rellena. export_batches guarda los metadatos de la exportación, no el propio archivo. En la implementación actual, import_rows solo registra skipped y error. Estas diferencias son importantes al diagnosticar problemas y crear informes.',
            ],
            'examples' => [
                [
                    'title' => 'Tablas por subsistema',
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
            'title' => 'Tipos, estados y ENUM',
            'paragraphs' => [
                'El tipo de campo personalizado determina la columna en la que se almacena el valor. number utiliza value_number; date, value_date; checkbox, value_bool; y los demás tipos, value_text. select también se guarda como texto, mientras que las opciones permitidas se encuentran en custom_field_options.',
                'Los estados de importación constituyen la máquina de estados de una tarea, mientras que el status de una fila describe únicamente esa fila. La exportación tiene una máquina de estados más corta. email_status refleja solo el resultado de la comprobación de la dirección; is_corporate_email es una clasificación booleana nullable independiente del dominio.',
            ],
            'examples' => [
                [
                    'title' => 'Valores admitidos por el esquema',
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
            'title' => 'Límites y valores predeterminados',
            'paragraphs' => [
                'Los límites se encuentran en distintas capas y todavía no se han centralizado en la configuración. Cada número debe modificarse junto con una evaluación de la memoria, los timeouts, la interfaz y la base de datos. Esto resulta especialmente importante para XLSX, las exportaciones completas y la API por lotes.',
                'La paginación elegida por el usuario en la interfaz se guarda bajo preference_key per_page; su valor predeterminado es 20 y admite de 5 a 500. El ajuste se aplica a las listas que utilizan SortableTrait::pageParams(). Los catálogos AJAX suelen leer 20 elementos más uno para has_more.',
            ],
            'examples' => [
                [
                    'title' => 'Límites numéricos actuales',
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
            'title' => 'Respuestas HTTP y errores',
            'paragraphs' => [
                'Los controladores HTML suelen redirigir después de un POST correcto. Un usuario web no autenticado recibe una redirección a login; un permiso insuficiente, un 403 con un texto breve; y un registro ausente, un 404. Un CSRF global incorrecto devuelve 419. Los errores no controlados del bootstrap generan un 500 genérico y los detalles se envían al registro.',
                'AJAX devuelve application/json; el guard utiliza 401 cuando no hay sesión y 403 cuando faltan permisos. La API siempre devuelve JSON y X-Request-Id. Las operaciones correctas normales utilizan 200; el POST por lotes, 207. La creación no utiliza 201 y la eliminación no utiliza 204.',
            ],
            'examples' => [
                [
                    'title' => 'Principales estados de la API',
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
                    'title' => 'Formato de error de la API',
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
            'title' => 'Principales clases de infraestructura',
            'paragraphs' => [
                'Las clases de Core no forman un framework, pero establecen convenciones comunes. Router relaciona métodos y rutas; View carga la plantilla y el layout; Database configura Illuminate Database y proporciona Query Builder y transacciones; Auth gestiona la sesión y los permisos; Csrf crea y comprueba el token; Lang carga el idioma; y LoginThrottle limita los intentos de inicio de sesión.',
                'IdList normaliza un array de identificadores positivos y únicos. Illuminate Support genera slugs mediante Str::slug() con transliteración Unicode. SortableTrait valida sort/dir y calcula las páginas. ControllerHelperTrait procesa strings nullable, arrays de identificadores, filtros de tags y valores de filtros personalizados.',
            ],
            'examples' => [
                [
                    'title' => 'Índice breve de clases',
                    'code' => <<<'CODE'
Router                 HTTP method/path dispatch
View                   PHP view + layout rendering
Database               shared Query Builder connection and transactions
Auth                   session, remember-me, roles, permissions
Csrf                   session token and hidden field
LoginThrottle          file-backed login limiting
Lang                   locale dictionary
IdList                 positive unique integer arrays
Illuminate Support     Unicode slug y gestión de fechas mediante Carbon
SortableTrait          sort, direction and pagination
ControllerHelperTrait  common request normalization
CODE,
                ],
            ],
        ],
        [
            'id' => 'reference-commands',
            'title' => 'Comandos de desarrollo y mantenimiento',
            'paragraphs' => [
                'El proyecto no define scripts de Composer, una configuración de PHPUnit ni un sistema de migraciones. Las dependencias se instalan mediante Composer, una base limpia se despliega importando database/schema.sql y la sintaxis se comprueba con php -l. El administrador prepara los cambios de una base existente, los comprueba sobre una copia y los aplica manualmente mediante un cliente SQL. Volver a ejecutar schema.sql eliminará las tablas y los datos existentes.',
                'El informe semanal solo se ejecuta mediante PHP CLI. De forma predeterminada, collect() toma los últimos siete días y envía un correo a todos los admin activos. Antes de programarlo en cron, el comando debe ejecutarse manualmente con el mismo usuario del sistema. No es necesario ejecutar npm install ni compilar assets.',
            ],
            'examples' => [
                [
                    'title' => 'Comandos habituales',
                    'code' => <<<'SHELL'
cd /var/www/contactcore

composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
composer check-platform-reqs --no-dev

php8.5 -l public_html/index.php
find app config bin lang public_html -name '*.php' -print0 | xargs -0 -n1 php8.5 -l

# Solo para una base de datos vacía: schema.sql contiene DROP TABLE
mysql -u crm_user -p crm < database/schema.sql

# Para una base existente no hay ningún comando automático:
# prepare y compruebe el SQL y aplíquelo manualmente mediante un cliente SQL

sudo -u www-data /usr/bin/php8.5 bin/weekly-report.php
tail -n 50 storage/app.log
SHELL,
                ],
                [
                    'title' => 'Cron del informe semanal',
                    'code' => <<<'CRON'
SHELL=/bin/sh
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin

0 8 * * 1 www-data cd /var/www/contactcore && /usr/bin/php8.5 bin/weekly-report.php >> storage/weekly-report-cron.log 2>&1
CRON,
                ],
            ],
        ],
        [
            'id' => 'reference-change-checklist',
            'title' => 'Control de coherencia de los cambios',
            'paragraphs' => [
                'ContactCore utiliza registro explícito, no discovery automático. Una clase nueva debe cargarse mediante require_once en public_html/index.php antes de crear un objeto que dependa de ella. Un controlador nuevo debe crearse, registrar su ruta y protegerse mediante Auth/CSRF. Un asset nuevo se pasa a través de styles o scripts en View::render. Un texto nuevo de la interfaz se añade a todos los idiomas compatibles.',
                'Modificar una entidad suele afectar al schema o a una actualización SQL manual, Repository, Controller/Service, View, los filtros, la importación, la exportación, la API, los informes y la documentación. No todos los módulos tienen que cambiar necesariamente, pero cada uno debe revisarse de forma consciente. Para un permission o scope nuevo son especialmente importantes la actualización manual de los registros existentes y el comportamiento fail-closed.',
                'Antes de entregar un cambio se comprueban la sintaxis de todos los archivos PHP, las rutas HTTP reales, la matriz de acceso, CSRF, SQL sobre una base limpia y otra existente, los idiomas, la visualización móvil, el registro de errores y las operaciones por lotes relacionadas. La referencia técnica solo se actualiza con valores exactos del código aceptado.',
            ],
            'examples' => [
                [
                    'title' => 'Checklist universal para una función nueva',
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
