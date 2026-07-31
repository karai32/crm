<?php

return [
    'title' => 'Estructura interna de la API',
    'description' => 'Arquitectura de la API pública de ContactCore: enrutamiento, Basic Auth, scopes, servicios de recursos, transacciones, respuestas y registro.',
    'icon' => 'ph-arrow-elbow-down-right',
    'sections' => [
        [
            'id' => 'api-internal-boundary',
            'title' => 'Lugar de la API en la aplicación',
            'paragraphs' => [
                'La API pública es una frontera HTTP independiente del mismo monolito modular de ContactCore. Trabaja con la base de datos, los repositorios y las entidades de dominio comunes, pero no utiliza la sesión del navegador, vistas HTML ni contratos AJAX. Todas las rutas de la versión actual se encuentran bajo /api/v1 y devuelven JSON.',
                'La API está destinada principalmente a integraciones server-to-server: formularios de los sitios de clientes, sincronización con aplicaciones externas e intercambio por lotes. Las rutas internas /ajax/* utilizan la sesión y CSRF, mientras que /api/v1/* utiliza HTTP Basic Auth y scopes. Estas interfaces no son intercambiables aunque invoquen el mismo repositorio.',
                'Arquitectónicamente, una petición pasa por Router, el ApiController común, ApiAuthenticator, el ApiService del recurso y Repository. El controlador gestiona el protocolo HTTP común, el servicio se ocupa del comportamiento de contactos o clientes y el repositorio, de SQL. ApiController vuelve a generar la respuesta y el registro de la petición.',
            ],
            'examples' => [
                [
                    'title' => 'Recorrido completo de una petición de la API',
                    'code' => <<<'CODE'
Sistema externo
      │ HTTPS + Basic Auth + JSON
      ▼
public_html/index.php
      ▼
Router
      ▼
ApiController::handle()
      ├── ApiAuthenticator
      ├── comprobación del scope
      ├── decodificación de JSON
      ▼
ContactApiService / ClientApiService
      ▼
Repository → MySQL
      ▼
ApiResult → respuesta JSON → api_logs
CODE,
                ],
            ],
        ],
        [
            'id' => 'api-internal-routes',
            'title' => 'Recursos y enrutamiento',
            'paragraphs' => [
                'Actualmente se publican dos recursos: contacts y clients. Ambos tienen el mismo conjunto CRUD. La colección admite GET y POST y cada registro concreto admite GET, PATCH y DELETE. Los sectores y las etiquetas se transmiten dentro de estos recursos y no tienen rutas independientes.',
                'Router guarda por separado las rutas exactas y las parametrizadas. Cuando coincide un patrón, el valor de {id} se escribe en $_GET y ApiController::routeId() lo convierte a int. Los valores cero, negativos o no numéricos se convierten en un error 404. Router no transmite los parámetros como argumentos del método del controlador.',
                'La versión forma parte de la URL, no de una cabecera. Un cambio incompatible del contrato debe recibir un prefijo nuevo, por ejemplo /api/v2, manteniendo v1 durante el periodo de transición acordado. Añadir un campo opcional o un filtro nuevo suele ser compatible dentro de v1.',
            ],
            'examples' => [
                [
                    'title' => 'Contrato CRUD actual',
                    'code' => <<<'CODE'
GET     /api/v1/{resource}
GET     /api/v1/{resource}/{id}
POST    /api/v1/{resource}
PATCH   /api/v1/{resource}/{id}
DELETE  /api/v1/{resource}/{id}

resource = contacts | clients
CODE,
                ],
            ],
        ],
        [
            'id' => 'api-internal-controller',
            'title' => 'Controlador común de recursos',
            'paragraphs' => [
                'ApiController atiende ambos recursos y recibe en su constructor el nombre del recurso y una instancia del servicio. Esto elimina clases vacías que solo se diferenciaban por la cadena contacts o clients. Los recursos permitidos se comprueban mediante una allowlist; public_html/index.php crea dos instancias configuradas del controlador.',
                'Los métodos index y show requieren el scope {resource}:read; create, update y destroy requieren {resource}:write. La ruta transmitida a handle() se utiliza en el registro, por lo que las rutas de un registro concreto se guardan como el patrón /{id}, no como la URL real con un número.',
                'Al comenzar handle() se lee php://input, se realiza la autenticación, se crea un request id de 24 caracteres y se envía X-Request-Id. A continuación se comprueba el scope, se actualiza last_used_at y se invoca el servicio. ApiException, PDOException y los demás Throwable se convierten en una respuesta JSON estable; un error interno no gestionado no revela al cliente el texto de la excepción.',
            ],
            'examples' => [
                [
                    'title' => 'Configuración de los controladores de recursos',
                    'code' => <<<'PHP'
$apiControllers = [
    'contacts' => new ApiController('contacts', new ContactApiService()),
    'clients' => new ApiController('clients', new ClientApiService()),
];

// Un bucle común registra las cinco rutas CRUD de cada recurso.
PHP,
                ],
            ],
        ],
        [
            'id' => 'api-internal-auth',
            'title' => 'Claves de API y Basic Auth',
            'paragraphs' => [
                'La cuenta de una integración se compone de client_id y un secreto aleatorio. ApiKeyController genera un client_id con el prefijo crm_ y 16 bytes aleatorios, y un secreto a partir de 32 bytes aleatorios. En este contexto, Client ID identifica al cliente de la API y no guarda ninguna relación con la entidad Client del CRM.',
                'El secreto se muestra al administrador una sola vez y solo se guarda como hash SHA-256. ApiAuthenticator obtiene las credenciales Basic desde PHP_AUTH_USER/PHP_AUTH_PW o analiza manualmente Authorization desde HTTP_AUTHORIZATION, REDIRECT_HTTP_AUTHORIZATION o getallheaders(). Esto tiene en cuenta las diferencias entre PHP-FPM y las configuraciones del servidor web.',
                'Después de localizar una clave activa por client_id, se calcula el hash del secreto proporcionado y se compara mediante hash_equals(). Basic Auth no cifra las credenciales, por lo que la API solo debe utilizarse mediante HTTPS. Una clave revocada tiene is_active = 0 y el autenticador deja de encontrarla; si se vuelve a activar, recupera el acceso con el mismo secreto.',
                'last_used_at se actualiza como máximo una vez cada cinco minutos para evitar una escritura adicional en cada petición de la API. La eliminación de una clave es física; los api_logs relacionados se conservan gracias a ON DELETE SET NULL, aunque pierden la referencia al nombre de la integración.',
            ],
            'examples' => [
                [
                    'title' => 'Comprobación de las credenciales',
                    'code' => <<<'CODE'
Authorization: Basic base64(CLIENT_ID:SECRET)

client_id → SELECT de la fila activa de api_keys
SECRET    → sha256(SECRET)
stored    → api_keys.secret_hash

hash_equals(stored, provided) → clave de API autenticada
CODE,
                ],
            ],
        ],
        [
            'id' => 'api-internal-scopes',
            'title' => 'Scopes y autorización de operaciones',
            'paragraphs' => [
                'Los scopes se guardan como un array JSON en api_keys. Para contactos y clientes se definen read y write, cuatro valores en total dentro de ApiController::SCOPES. Las claves nuevas reciben esta lista; syncScopes sustituye por completo el conjunto de una clave antigua, incluidos los scopes sectors:* y tags:* anteriores.',
                'hasScope() busca primero una coincidencia exacta. Para una operación de lectura también se acepta el write correspondiente: contacts:write concede implícitamente contacts:read. Lo contrario no es válido. Un JSON no válido en scopes se interpreta como ausencia de permisos.',
                'Los scopes se comprueban antes de decodificar JSON y de acceder al servicio del recurso. Un error de autenticación devuelve 401 y WWW-Authenticate; un scope insuficiente, 403. La comprobación se realiza de forma independiente para cada ruta: disponer de una clave no implica acceso a todos los recursos.',
            ],
            'examples' => [
                [
                    'title' => 'Matriz de acceso',
                    'code' => <<<'CODE'
contacts:read
  ✓ GET /api/v1/contacts
  ✓ GET /api/v1/contacts/{id}
  ✗ POST / PATCH / DELETE

contacts:write
  ✓ GET /api/v1/contacts
  ✓ GET /api/v1/contacts/{id}
  ✓ POST / PATCH / DELETE
CODE,
                ],
            ],
        ],
        [
            'id' => 'api-internal-input',
            'title' => 'Lectura y análisis de los datos de entrada',
            'paragraphs' => [
                'ApiController lee y aplica trim a php://input una vez por petición. PATCH invoca jsonObject() y solo acepta un objeto JSON no vacío. POST invoca jsonBatch(): un objeto individual se convierte en un array de un elemento y un array JSON se utiliza como lote. Un cuerpo vacío, un escalar, JSON dañado o un lote con más de 100 elementos devuelve 422.',
                'Actualmente no se comprueba la cabecera Content-Type: en la práctica se acepta cualquier cuerpo que pueda analizarse como JSON. Para mantener un contrato público predecible, el cliente debe enviar application/json; si el servidor se hace más estricto en el futuro, un Content-Type incorrecto deberá responderse con el estado 415.',
                'Los filtros GET se leen desde $_GET dentro del servicio del recurso. Tanto los contactos como los clientes normalizan page a un mínimo de 1 y per_page al intervalo 1–100, con 25 como valor predeterminado.',
                'PATCH tiene semántica de actualización parcial: una clave ausente conserva el valor actual y un null explícito o una cadena vacía limpia un campo opcional compatible. Para tags y clients hay que distinguir entre una clave ausente y un conjunto vacío enviado: un conjunto vacío elimina todas las relaciones correspondientes. Un objeto custom_fields vacío no modifica nada; para limpiar un campo personalizado concreto hay que enviar su slug con null o una cadena vacía.',
            ],
            'examples' => [
                [
                    'title' => 'Normalización de POST',
                    'code' => <<<'CODE'
Objeto individual:
{"full_name":"Ana"}
→ items[0] = {"full_name":"Ana"}

Lote:
[
  {"full_name":"Ana"},
  {"full_name":"Luis"}
]
→ items[0], items[1]

Máximo: 100 elementos
CODE,
                ],
            ],
        ],
        [
            'id' => 'api-internal-services',
            'title' => 'Capa de servicios de los recursos',
            'paragraphs' => [
                'AbstractApiService define una superficie CRUD común: index(), show(), createBatch(), update() y destroy(). También contiene operaciones compartidas para cadenas nullable, búsqueda de registros, etiquetas, preparación de campos personalizados, procesamiento por lotes y listas de entrada. El servicio del recurso se encarga de validar la API, resolver nombres externos en id y definir la forma de data en la respuesta.',
                'ContactApiService y ClientApiService no escriben directamente las entidades principales ni sus relaciones: el contrato preparado se transmite a ContactWriteService o ClientWriteService. Estos servicios comunes también se utilizan desde HTML y la importación. No existen SectorApiService ni TagApiService independientes: los catálogos se resuelven como valores anidados mediante AbstractApiService y los repositorios correspondientes.',
                'Los servicios no devuelven directamente las filas de la base. Los métodos detail() y format() convierten los id a int, los indicadores a bool y seleccionan explícitamente los campos de la respuesta. Esto protege el contrato frente a la aparición accidental de password_hash, indicadores internos o columnas nuevas después de un SELECT *.',
                'Las reglas de dominio comunes de contactos y clientes se comparten con la interfaz HTML y la importación mediante ContactWriteService y ClientWriteService. El servicio de API del recurso solo comprueba la estructura JSON y convierte los nombres externos en id; el servicio de escritura comprueba los campos obligatorios, el formato del correo, el correo interno y los duplicados. Las reglas nuevas que afecten a todos los canales deben añadirse en esta frontera común.',
            ],
            'examples' => [
                [
                    'title' => 'Responsabilidades de las capas',
                    'code' => <<<'CODE'
ApiController
  método HTTP, auth, scope, JSON, estado, cabeceras y registro

ApiService
  validación, operación de negocio, transacción y DTO de respuesta

Repository
  SQL preparado, persistencia y consultas
CODE,
                ],
            ],
        ],
        [
            'id' => 'api-internal-batch',
            'title' => 'Lotes y límites transaccionales',
            'paragraphs' => [
                'Todos los POST de creación se procesan mediante AbstractApiService::batch(). Database::transaction() abre para cada elemento una transacción sobre la conexión común de Illuminate Database y el servicio del recurso invoca después el servicio de escritura común. El servicio de escritura anidado detecta la transacción abierta y se une a ella. ApiException, PDOException o cualquier otro error revierte solo el elemento actual; los elementos siguientes siguen procesándose.',
                'Por tanto, el lote completo no es atómico. Si se crean los primeros nueve registros y el décimo falla, los nueve permanecen en la base. Sin embargo, todos los componentes de un elemento —registro principal, catálogos creados automáticamente, relaciones, etiquetas y campos personalizados— deben guardarse o revertirse juntos.',
                'El resultado de POST siempre tiene HTTP 207 Multi-Status, aunque se envíe un solo objeto y aunque todos los elementos sean correctos o todos fallen. El success superior significa que el lote se ha analizado y procesado, no que se haya creado cada registro. La integración debe comprobar data.results[*].success y conservar index para relacionar el resultado con el array original.',
                'El PATCH de un contacto o cliente abre una transacción alrededor de la resolución de los valores de catálogo nuevos y la llamada al servicio de escritura. Null para un conjunto de relaciones significa «no modificar» y un array vacío enviado significa «limpiar». ClientWriteService combina los cambios parciales con el registro actual, por lo que conserva is_active e is_web_connected si la API no los modifica. POST no dispone de idempotencia integrada: repetirlo tras un timeout de red puede crear un duplicado.',
            ],
            'examples' => [
                [
                    'title' => 'Lote parcialmente correcto',
                    'code' => <<<'JSON'
HTTP/1.1 207 Multi-Status

{
  "success": true,
  "data": {
    "processed": 2,
    "created": 1,
    "failed": 1,
    "results": [
      {"index": 0, "success": true, "data": {"contact_id": 125}},
      {
        "index": 1,
        "success": false,
        "error": {
          "code": "duplicate_contact",
          "details": ["Contact with this email already exists"]
        }
      }
    ]
  }
}
JSON,
                ],
            ],
        ],
        [
            'id' => 'api-internal-relations',
            'title' => 'Relaciones, catálogos y campos personalizados',
            'paragraphs' => [
                'tags y clients aceptan un nombre, una cadena de nombres separados por comas o un array JSON. splitNames() elimina los valores vacíos e ignora los elementos complejos de un array. Los nombres se resuelven en id; las etiquetas ausentes se crean automáticamente y ContactApiService también crea un cliente mínimo a partir de commercial_name. ClientApiService crea de forma análoga un sector que no exista.',
                'En PATCH, tags o clients enviados representan el conjunto final completo y sustituyen las relaciones existentes mediante sync(). No se trata de una operación de adición. Si la clave está ausente, las relaciones no cambian. Una integración debe obtener primero el estado actual si desea conservar los elementos anteriores y añadir uno nuevo.',
                'custom_fields admite un objeto anidado y claves custom_fields.{slug}. expandCustomFieldKeys() reúne las claves con notación de punto en un array anidado. saveCustomFields() solo encuentra campos creados previamente para el entity_type adecuado; un slug desconocido se omite silenciosamente. Al crear se aplican los default_value de los campos que la integración no haya enviado.',
                'Los tipos de la respuesta se normalizan: number se convierte en float; checkbox, en bool; date y el texto permanecen como cadenas; y un valor ausente es null. La obligatoriedad y las opciones permitidas de select no se garantizan mediante un validador común de la API, por lo que la evolución del contrato requiere comprobar por separado los tipos e is_required.',
            ],
            'examples' => [
                [
                    'title' => 'Formatos de entrada equivalentes',
                    'code' => <<<'JSON'
{"tags":"Lead,Newsletter"}
{"tags":["Lead","Newsletter"]}

{"custom_fields":{"language":"es","consent":true}}
{"custom_fields.language":"es","custom_fields.consent":true}
JSON,
                ],
            ],
        ],
        [
            'id' => 'api-internal-results',
            'title' => 'ApiResult, errores y estados HTTP',
            'paragraphs' => [
                'ApiResult es un objeto de resultado sencillo con status, data y un itemsCount opcional para el registro. Los métodos correctos del servicio lo devuelven explícitamente. Si una action devuelve otro tipo, el controlador común lo considera un error de programación y genera un 500.',
                'ApiException transporta un error esperado de la aplicación: estado HTTP, errorCode estable, mensaje y details. ApiController lo convierte en {success:false,error:{code,message,details}}. Una infracción de una restricción de la base con SQLSTATE 23000 se convierte en 409 conflict; las demás PDOException y Throwable se registran en el servidor y devuelven un 500 server_error seguro.',
                'Las operaciones de lectura, PATCH y DELETE suelen devolver 200. La creación mediante POST devuelve 207. También se utilizan 401, 403, 404, 409, 422 y 500; un error de un servicio externo no constituye actualmente un flujo independiente en esta API. Una respuesta 401 incluye además WWW-Authenticate; todas las respuestas incluyen Content-Type JSON y X-Request-Id.',
                'El cliente debe tomar decisiones basándose conjuntamente en el estado HTTP y el cuerpo. En particular, 207 es un 2xx correcto para una biblioteca HTTP, pero puede contener errores de elementos. error.code está destinado a la lógica del programa y message y details, al diagnóstico; la lógica de una integración no debe comparar el texto inglés completo de message.',
            ],
            'examples' => [
                [
                    'title' => 'Error normal del controlador',
                    'code' => <<<'JSON'
HTTP/1.1 422 Unprocessable Entity
X-Request-Id: a8d94b7b912ac2aeaa15cc11

{
  "success": false,
  "error": {
    "code": "validation_error",
    "message": "Full name is required."
  }
}
JSON,
                ],
            ],
        ],
        [
            'id' => 'api-internal-logging',
            'title' => 'Registro y observabilidad',
            'paragraphs' => [
                'finish() envía primero el JSON y después intenta escribir api_logs. El registro contiene api_key_id, request_id, método, patrón de ruta, estado, error_code, items_count, IP, duración, origin, cuerpo de la petición y cuerpo de la respuesta. También se registra un intento no autorizado, pero su api_key_id es NULL.',
                'Cada cuerpo se limita a unos 64 KB. Origin se toma de Origin o, si está ausente, de Referer, y se recorta a 255 caracteres. Un error al escribir el registro no modifica la respuesta de la API que ya se ha generado: se envía a PHP error_log junto con el request id.',
                'El administrador consulta el registro en /api-logs y puede filtrarlo por clave, método, grupo de estado, ruta y fechas. X-Request-Id debe enviarse al servicio de soporte y conservarse en el sistema externo. Para rutas con id, el registro contiene actualmente /api/v1/resource/{id}, por lo que el campo path por sí solo no permite determinar el registro concreto.',
                'request_body y response_body pueden contener nombres, correos, teléfonos, direcciones y valores de campos personalizados. Actualmente no se han implementado ni el enmascaramiento ni una política automática de conservación. Antes de utilizarlo en producción hay que definir un periodo de conservación, limitar el acceso y ocultar o no registrar los campos secretos y sensibles.',
            ],
            'examples' => [
                [
                    'title' => 'Correlación de los registros de dos sistemas',
                    'code' => <<<'CODE'
Registro externo:
  crm_request_id=a8d94b7b912ac2aeaa15cc11
  local_form_submission=98731

ContactCore api_logs:
  request_id=a8d94b7b912ac2aeaa15cc11
  response_status=422
  error_code=validation_error
  duration_ms=18
CODE,
                ],
            ],
        ],
        [
            'id' => 'api-internal-security',
            'title' => 'Seguridad y limitaciones operativas',
            'paragraphs' => [
                'La API pública está excluida de la comprobación CSRF de sesión porque no utiliza autenticación mediante cookies. Su frontera de seguridad está formada por HTTPS, un secreto aleatorio, Basic Auth, el estado de la clave y los scopes. El secreto no debe transmitirse a JavaScript del navegador, una URL, el repositorio ni el registro de la aplicación que realiza la integración.',
                'La API no genera cabeceras CORS, por lo que el navegador suele bloquear una petición directa desde otro origin. Esto corresponde al modelo server-to-server. Si en el futuro se necesita un cliente de navegador, no bastará con permitir Access-Control-Allow-Origin: será necesario un modelo independiente de credenciales de corta duración, origins limitados y amenazas.',
                'La API no incluye rate limiter, cuotas, idempotency key, protección contra replay adicional a Basic Auth ni un límite del tamaño total del cuerpo HTTP antes de json_decode. El límite de 100 se refiere al número de elementos, no a los bytes. Estas limitaciones deben implementarse en el reverse proxy o en la aplicación antes de conectar fuentes no fiables o con mucha carga.',
                'EmailInspector realiza una consulta DNS en tiempo real al crear un contacto y al modificar email mediante PATCH. Un lote con muchos dominios distintos puede aumentar el tiempo de respuesta. El sistema externo debe utilizar un timeout razonable y reintentos con backoff, pero un POST sin idempotencia no debe repetirse a ciegas.',
            ],
            'examples' => [
                [
                    'title' => 'Entorno mínimo de producción',
                    'code' => <<<'CODE'
Internet
   ▼
Reverse proxy HTTPS
  - límite del tamaño del cuerpo
  - rate limit de peticiones
  - registros de acceso y errores
  - reenvío de Authorization
   ▼
PHP-FPM / ContactCore
  - Basic Auth
  - scopes
  - validación y transacciones
  - api_logs con política de conservación
CODE,
                ],
            ],
        ],
        [
            'id' => 'api-internal-known-gaps',
            'title' => 'Desajustes actuales y deuda técnica',
            'paragraphs' => [
                'El contrato de estados del cliente está unificado. ClientWriteService genera el conjunto completo de columnas: al crear sin valores explícitos establece is_web_connected = 0 e is_active = 1, y durante PATCH conserva los estados actuales. ClientRepository aplica además los mismos valores predeterminados seguros en su frontera. La creación de clientes desde ClientApiService, ContactApiService y la importación pasa por el servicio común.',
                'La API comprueba previamente la unicidad del email de un contacto y commercial_name de un cliente para devolver un error de dominio comprensible antes de escribir. Los índices UNIQUE siguen siendo la protección final contra una carrera entre peticiones concurrentes; un conflicto tardío devuelve HTTP 409 con el código conflict. Los slugs de campos personalizados desconocidos todavía se omiten sin error, por lo que esta regla debe centralizarse y cubrirse con pruebas.',
                'El proyecto no dispone de una suite automatizada completa de pruebas de la API ni de una especificación OpenAPI publicada y legible por máquinas. Por tanto, modificar una ruta o respuesta puede romper una integración externa sin afectar visiblemente a la interfaz del CRM. Las mejoras prioritarias son las integration tests, OpenAPI, redaction de registros, rate limiting e idempotencia.',
            ],
            'examples' => [
                [
                    'title' => 'Valores esperados del cliente en la frontera del repositorio',
                    'code' => <<<'PHP'
$data = [
    'commercial_name'  => $commercialName,
    // Los demás campos y estados pueden omitirse durante la creación.
];

$clientId = $this->clientWriter->create($data);

// El contrato normalizado del repositorio contiene:
// is_web_connected = 0, is_active = 1
PHP,
                ],
            ],
        ],
        [
            'id' => 'api-internal-extension',
            'title' => 'Adición o modificación de un recurso',
            'paragraphs' => [
                'Un recurso independiente nuevo requiere un repositorio, una clase ApiService, scopes read/write en ApiController::SCOPES, la carga del servicio y un elemento en el array $apiControllers. El bucle común de public_html/index.php registrará las cinco rutas CRUD. Si el recurso no admite alguna operación, no debe utilizarse el bucle universal: la ausencia de la operación debe expresarse conscientemente mediante un 405 o el contrato correspondiente.',
                'Antes de publicarlo se definen los campos de petición y respuesta, los filtros, límites de paginación, reglas de PATCH, frontera transaccional, error.code estables y consecuencias de DELETE. Los campos de la respuesta deben componerse explícitamente. Cualquier valor nuevo que pueda contener datos personales debe revisarse en el registro.',
                'La comprobación mínima abarca: ausencia de Authorization, secreto incorrecto y revocado, scope insuficiente, lectura de un id existente y ausente, JSON dañado, PATCH vacío, POST individual y por lotes, éxito parcial, conflicto de la base, rollback de relaciones, filtros, límites de page/per_page y escritura en api_logs. La prueba debe comprobar tanto el status como la estructura JSON y X-Request-Id.',
            ],
            'examples' => [
                [
                    'title' => 'Lista de comprobación de un recurso nuevo',
                    'code' => <<<'CODE'
[ ] Repository y cambio manual de estructura/índices
[ ] ResourceApiService implementa los 5 métodos
[ ] nombre de recurso permitido mediante ApiController::SCOPES
[ ] require_once del servicio y elemento en $apiControllers
[ ] resource:read y resource:write en ApiController::SCOPES
[ ] validación, DTO de respuesta y códigos de error estables
[ ] frontera transaccional y semántica de eliminación
[ ] impacto sobre redaction/conservación de api_logs
[ ] pruebas de integración y ayuda de la API
[ ] compatibilidad hacia atrás o nueva /api/v2
CODE,
                ],
            ],
        ],
    ],
];
