<?php

return [
    'title' => 'Autorización y seguridad',
    'description' => 'Modelo de seguridad de ContactCore: autenticación de usuarios e integraciones, gestión de sesiones, comprobación de permisos, CSRF, restricciones del navegador, almacenamiento de secretos y riesgos conocidos.',
    'icon' => 'ph-arrow-elbow-down-right',
    'sections' => [
        [
            'id' => 'security-boundaries',
            'title' => 'Límites de seguridad',
            'paragraphs' => [
                'ContactCore utiliza dos modelos de autenticación independientes. La interfaz web usa una sesión PHP y, si el usuario lo elige, un token persistente de remember-me. La API pública /api/v1 utiliza el par client_id + secret mediante HTTP Basic Auth y comprueba los scopes. Las rutas internas /ajax/* pertenecen a la interfaz web: utilizan la misma sesión y, en las solicitudes POST que modifican datos, el token CSRF común.',
                'La protección se construye por capas. HTTPS y la configuración de cookies protegen el transporte y las credenciales; Auth determina la identidad y los permisos; Router aplica la política de acceso antes de llamar al controlador; CSRF protege las solicitudes POST autenticadas mediante cookies; los repositorios utilizan SQL parametrizado; las vistas deben codificar la salida; y las cabeceras del navegador limitan la ejecución y la inserción de contenido. Ninguna capa sustituye a las demás.',
                'El document root debe apuntar únicamente a public_html. Los directorios config, storage, database, vendor y app no deben ser accesibles por HTTP. Contienen las contraseñas de la base de datos y SMTP, la clave de Gemini, sesiones, tokens remember-me, el registro de la aplicación y los archivos originales de las importaciones. Un error en la raíz del host virtual elude por completo muchas medidas de la aplicación.',
            ],
            'examples' => [
                [
                    'title' => 'Tres perímetros de entrada',
                    'code' => <<<'CODE'
Browser pages
  HTTPS → session cookie → Auth → permission → controller → HTML

Internal AJAX
  HTTPS → session cookie → Auth → permission → controller → JSON
  POST additionally requires CSRF token

Public API
  HTTPS → Basic client_id:secret → scope → ApiService → JSON
  no browser session and no CSRF
CODE,
                ],
            ],
        ],
        [
            'id' => 'web-login',
            'title' => 'Inicio de sesión mediante la interfaz web',
            'paragraphs' => [
                'GET /login crea o continúa una sesión PHP y muestra el formulario con un token CSRF. POST /login normaliza el correo electrónico mediante trim(), comprueba primero LoginThrottle y después AuthService busca al usuario por email, exige is_active = 1 y llama a password_verify(). Para un usuario inexistente, una cuenta desactivada o una contraseña incorrecta se devuelve el mismo mensaje, lo que dificulta la enumeración de direcciones registradas.',
                'Tras una comprobación correcta, se borra el contador de fallos, se actualiza users.last_login_at y se llama a Auth::login(). El método regenera el identificador de sesión eliminando el id anterior y guarda en $_SESSION únicamente id, name, email y el nombre textual del rol. Si se ha seleccionado «Recordarme», se emite además un token persistente después del inicio de sesión. El flujo termina con una redirección a /dashboard.',
                'La capa del controlador se ocupa de la secuencia; AuthService, de comprobar la cuenta; UserRepository, de la consulta y last_login_at; y Auth, de la sesión. Esta separación debe conservarse: la contraseña no debe verificarse en la vista ni en un controlador cualquiera, y la creación de $_SESSION[user] no debe duplicarse fuera de Auth::login().',
            ],
            'examples' => [
                [
                    'title' => 'Flujo correcto de inicio de sesión',
                    'code' => <<<'CODE'
GET /login → session + CSRF form
POST /login
  → LoginThrottle::isLocked(email)
  → UserRepository::findByEmail(email)
  → is_active === 1
  → password_verify(password, password_hash)
  → updateLastLogin(user_id)
  → session_regenerate_id(true)
  → $_SESSION['user'] = {id, name, email, role}
  → optional remember token
  → /dashboard
CODE,
                ],
            ],
        ],
        [
            'id' => 'passwords',
            'title' => 'Contraseñas y cuentas',
            'paragraphs' => [
                'Las contraseñas no se almacenan como texto sin cifrar. UserController utiliza password_hash($password, PASSWORD_DEFAULT), y users.password_hash tiene una longitud de 255 caracteres, suficiente para futuros cambios del algoritmo PASSWORD_DEFAULT de PHP. La comprobación solo se realiza mediante password_verify(); no se deben comparar los hashes como cadenas ni intentar descifrarlos.',
                'En la interfaz actual, al crear una cuenta solo se exige que la contraseña no esté vacía. No se han definido una longitud mínima, una comprobación de contraseñas comunes o comprometidas ni una longitud máxima en el servidor. El campo HTML tampoco establece minlength. El navegador valida el correo mediante type=email, pero el servidor se limita a trim(); la unicidad está garantizada por el índice UNIQUE de users.email con la collation utf8mb4_unicode_ci, que no distingue mayúsculas y minúsculas.',
                'password_needs_rehash() no se invoca después de un inicio de sesión correcto. Por tanto, los hashes existentes no se actualizarán automáticamente cuando cambien el algoritmo o los parámetros de PHP. La política de contraseñas debe ser común para la creación del administrador durante la instalación, la creación de usuarios y el cambio de contraseña; la actualización del hash debe realizarse después de un password_verify() correcto.',
            ],
            'examples' => [
                [
                    'title' => 'Punto recomendado para actualizar el hash',
                    'code' => <<<'PHP'
if (!password_verify($password, $user['password_hash'])) {
    return null;
}

if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
    $users->updatePassword(
        (int) $user['id'],
        password_hash($password, PASSWORD_DEFAULT)
    );
}
PHP,
                ],
            ],
        ],
        [
            'id' => 'login-throttle',
            'title' => 'Limitación de intentos de inicio de sesión',
            'paragraphs' => [
                'LoginThrottle cuenta los fallos por correo electrónico convertido a minúsculas. Cinco intentos fallidos en un período de 15 minutos establecen un bloqueo de otros 15 minutos. Un inicio de sesión correcto elimina el registro. El estado se guarda en storage/login_throttle.json y la escritura se protege con un flock exclusivo; los elementos caducados se eliminan durante posteriores modificaciones del archivo.',
                'La implementación basada en archivos solo es adecuada para un servidor con un storage local común. Varios nodos PHP tendrán contadores independientes si no utilizan un almacenamiento compartido. Los errores al crear el directorio, abrir o escribir el archivo se silencian y, en la práctica, desactivan la limitación sin avisar al operador. read() tampoco bloquea el archivo durante la lectura.',
                'La clave es únicamente el correo electrónico, no la IP ni una combinación de IP y cuenta. Esto limita los intentos contra un usuario concreto, pero permite ataques distribuidos contra muchas direcciones y posibilita que un tercero bloquee intencionadamente una cuenta conocida. El propio correo se guarda en el archivo. Un esquema escalable requiere un contador centralizado en Redis o la base de datos, varias dimensiones de limitación, desbloqueo controlado, registro de eventos y métricas.',
            ],
            'examples' => [
                [
                    'title' => 'Parámetros actuales',
                    'code' => <<<'CODE'
key              = lowercase(trim(email))
window           = 15 minutes
maximum failures = 5
lockout          = 15 minutes
storage          = storage/login_throttle.json
success          = remove counter
CODE,
                ],
            ],
        ],
        [
            'id' => 'sessions',
            'title' => 'Sesión PHP',
            'paragraphs' => [
                'El punto de entrada guarda las sesiones en storage/sessions, con permisos 0700 para el directorio, y establece session.gc_maxlifetime en 30 días. Esto evita una limpieza demasiado agresiva en un alojamiento compartido, pero no define para la aplicación un timeout de inactividad ni una duración absoluta. La cookie de sesión principal sigue siendo, por defecto, una cookie de sesión salvo que php.ini indique lo contrario.',
                'Durante el inicio de sesión y la recuperación mediante el token remember-me, el identificador se regenera eliminando el anterior, lo que protege frente a la fijación de sesión. Logout vacía $_SESSION, elimina la cookie de sesión con sus parámetros actuales y llama a session_destroy(). El token CSRF reside en la misma sesión y desaparece con ella.',
                'La aplicación no llama a session_set_cookie_params() y depende de la configuración de PHP para HttpOnly, Secure, SameSite y el modo estricto. En producción son obligatorios session.cookie_httponly=1, session.cookie_secure=1, session.cookie_samesite=Lax y session.use_strict_mode=1. HTTPS debe imponerse antes de emitir la cookie. Para un CRM con datos sensibles conviene añadir un timeout de inactividad del lado del servidor, una duración absoluta de la sesión y una nueva autenticación antes de las acciones críticas.',
            ],
            'examples' => [
                [
                    'title' => 'Configuración mínima de PHP para producción',
                    'code' => <<<'INI'
session.cookie_httponly = 1
session.cookie_secure = 1
session.cookie_samesite = Lax
session.use_strict_mode = 1

; La aplicación establece:
session.gc_maxlifetime = 2592000
INI,
                ],
            ],
        ],
        [
            'id' => 'remember-me',
            'title' => 'Mecanismo «Recordarme»',
            'paragraphs' => [
                'Auth::issueRememberToken() genera 32 bytes aleatorios, los codifica como 64 caracteres hexadecimales y crea el archivo storage/remember/{token}. El JSON interior contiene user_id y expires; la duración es de 30 días. La cookie remember_token tiene Path=/, HttpOnly y SameSite=Lax. Durante la recuperación se comprueban el formato del token, el archivo, la fecha de caducidad, la existencia de un usuario activo y su rol actual.',
                'Una recuperación correcta rota la credencial para un solo uso: se elimina el archivo anterior, se emite un token nuevo y después se crea una sesión normal con un session id nuevo. Logout solo revoca el token remember-me del navegador actual. Los demás tokens del mismo usuario, por ejemplo los de otro dispositivo, siguen siendo válidos hasta que caduquen o se eliminen manualmente sus archivos.',
                'La cookie remember_token se crea actualmente sin Secure, por lo que este indicador es false independientemente de session.cookie_secure. Si HTTP está disponible, el navegador puede enviar la credencial persistente antes de la redirección a HTTPS. Secure debe establecerse directamente en setcookie(). Los archivos reciben como nombre el propio bearer token y contienen su vínculo con el servidor en texto sin cifrar: acceder a storage/remember equivale a poder secuestrar la sesión. Se requieren permisos estrictos, copias de seguridad cifradas, revocación masiva por user_id y limpieza de archivos caducados.',
            ],
            'examples' => [
                [
                    'title' => 'Ciclo de vida del token persistente',
                    'code' => <<<'CODE'
login + remember_me
  → random 256-bit token
  → storage/remember/{token}
  → HttpOnly, SameSite=Lax cookie for 30 days

request without session
  → validate token file and expiry
  → reload active user from DB
  → delete old token
  → issue new token
  → regenerate session id and login
CODE,
                ],
            ],
        ],
        [
            'id' => 'authorization-model',
            'title' => 'Roles y permisos',
            'paragraphs' => [
                'El esquema contiene los roles admin y user. El administrador recibe todos los permisos registrados en permissionDefinitions y es el único que gestiona usuarios, claves de API, registros de API y herramientas de IA. Estas restricciones se definen mediante las políticas de las rutas. Para un usuario normal, las decisiones individuales se almacenan en user_permissions con la clave compuesta user_id + permission_key.',
                'Hay once permisos definidos: contacts.create/edit/delete, clients.create/edit/delete, exports.use, imports.manage, sectors.manage, tags.manage y custom_fields.manage. No existen permisos independientes para leer contactos y clientes: sus listas y fichas están disponibles para cualquier usuario que haya iniciado sesión. El dashboard, la ayuda, los ajustes del usuario y los catálogos AJAX de búsqueda también requieren, en general, únicamente una sesión válida.',
                'El menú oculta los elementos no disponibles por comodidad, pero no constituye una protección. La restricción del servidor se especifica junto al registro de la ruta en public_html/index.php y Router la aplica antes de llamar al action. No se puede comprobar un permiso únicamente en la vista o en JavaScript: las URL y las solicitudes POST pueden invocarse directamente.',
            ],
            'examples' => [
                [
                    'title' => 'Políticas de acceso al registrar rutas',
                    'code' => <<<'PHP'
$router->get('/dashboard', [$dashboardController, 'index'], [
    'auth' => 'user',
]);

$router->post('/contacts/update', [$contactController, 'update'], [
    'permission' => 'contacts.edit',
]);

$router->get('/ai', [$aiController, 'index'], [
    'auth' => 'admin',
]);
PHP,
                ],
            ],
        ],
        [
            'id' => 'authorization-resolution',
            'title' => 'Cómo se determina un permiso',
            'paragraphs' => [
                'Auth::can() aplica un contrato fail-closed. Primero exige la presencia de session user y comprueba que la clave recibida esté registrada en permissionDefinitions. Por tanto, un error tipográfico o una clave desconocida producen false incluso para un administrador. Para una clave conocida, admin obtiene acceso sin consultar user_permissions.',
                'Para un usuario normal, userPermissions() lee una vez por solicitud HTTP sus filas y almacena en caché un array asociativo. Un is_allowed = 1 explícito permite la acción; is_allowed = 0 la prohíbe. La ausencia de una fila también significa denegación. Al crear y editar un usuario, UserController guarda una decisión explícita para cada clave conocida; cuando se añade un permission nuevo, debe concederse a los usuarios existentes de forma independiente mediante los ajustes o un comando SQL ejecutado manualmente. La estructura actual define para is_allowed el DEFAULT 0 seguro.',
                'Si la lectura de user_permissions termina con una excepción, Auth registra el error y utiliza un conjunto de permisos vacío. En esa solicitud, un usuario normal no obtiene operaciones protegidas. Se prioriza la seguridad frente a la disponibilidad: un fallo de la base de datos no debe ampliar temporalmente los privilegios.',
            ],
            'examples' => [
                [
                    'title' => 'Tabla actual de decisiones de Auth::can()',
                    'code' => <<<'CODE'
no session                         → false
unknown permission key             → false
known permission, role = admin     → true
explicit is_allowed = 1            → true
explicit is_allowed = 0            → false
permission row is absent           → false
permission query failed            → false
CODE,
                ],
            ],
        ],
        [
            'id' => 'route-enforcement',
            'title' => 'Protección de páginas y AJAX',
            'paragraphs' => [
                'Router almacena la política de la ruta junto con su manejador. auth = user exige una sesión válida; auth = admin, el rol de administrador; y permission, un permiso conocido por Auth. En una acción masiva, permission puede ser callable y seleccionar el permiso según el tipo de operación. La comprobación se ejecuta antes del action, de modo que el controlador se ocupa de los datos de entrada y del flujo, sin repetir la autorización.',
                'El campo response selecciona la forma de denegación. En HTML, un invitado es redirigido a /login y la falta de permisos produce 403 Access denied. En AJAX, la política response = json devuelve JSON con 401 o 403 sin llamar al action. Cada ruta debe tener una política: los puntos deliberadamente abiertos, como login, declaran auth = public; /api/v1 solo utiliza public respecto a la sesión y ejecuta después su propia autenticación mediante Basic y scopes.',
                'La página /ai y todas las rutas POST relacionadas, gemini-company, company y company/skip, están alineadas con auth = admin. La comprobación masiva de correo también es administrativa, pues se inicia desde un flujo de administración. CSRF sigue siendo una capa independiente: confirma el origen de la solicitud en la sesión, pero no sustituye a la comprobación del rol o permiso.',
            ],
            'examples' => [
                [
                    'title' => 'Adición segura de una acción AJAX',
                    'code' => <<<'PHP'
$router->post(
    '/ajax/contacts/update-something',
    [$ajaxController, 'updateSomething'],
    ['permission' => 'contacts.edit', 'response' => 'json']
);

// El action solo se invoca después de validar la política.
PHP,
                ],
            ],
        ],
        [
            'id' => 'csrf',
            'title' => 'Protección CSRF',
            'paragraphs' => [
                'Csrf::token() crea 32 bytes aleatorios en formato hexadecimal y guarda el valor en $_SESSION[_csrf_token]. Csrf::field() lo codifica y añade un _csrf_token oculto a un formulario normal. AJAX obtiene el mismo token de un atributo data y lo envía como parámetro del formulario. validate() utiliza hash_equals(), evitando una comparación normal susceptible a filtraciones temporales.',
                'Antes de Router, el punto de entrada comprueba cada POST, excepto las URL que contienen /api/v1/. Un token incorrecto o ausente termina la solicitud con el estado 419 antes de llegar al controlador. Por ello, la protección también se aplica a /login, los ajustes, la importación, la exportación y los POST AJAX internos. La API pública queda excluida intencionadamente: no utiliza una sesión mediante cookies y se autentica con su propia cabecera Authorization.',
                'La comprobación global solo cubre POST. PATCH y DELETE se utilizan actualmente en la API pública y están protegidos mediante Basic Auth, mientras que las modificaciones del navegador se implementan con POST. Si en el futuro aparece un PATCH o DELETE basado en sesión, deberá incluirse en la comprobación CSRF. GET /logout modifica el estado sin CSRF y permite forzar el cierre de sesión mediante un enlace externo; el contrato correcto es POST /logout con token.',
            ],
            'examples' => [
                [
                    'title' => 'Recorrido de un POST protegido',
                    'code' => <<<'CODE'
HTML:  <?= Csrf::field() ?>
AJAX:  _csrf_token=<value from page>

POST request
  → public_html/index.php
  → not /api/v1/*
  → Csrf::validate()
      false → HTTP 419, controller is not called
      true  → Router → controller → Auth/permission check
CODE,
                ],
            ],
        ],
        [
            'id' => 'two-factor',
            'title' => 'Verificación en dos pasos',
            'paragraphs' => [
                'TwoFactorService implementa un código de un solo uso de seis cifras enviado por correo electrónico. La sesión no guarda el código, sino un password_hash, una copia de los datos mínimos del usuario, una duración de 10 minutos, la hora del último envío y un contador de intentos. El reenvío se permite después de 60 segundos y genera un código nuevo. Tras superar cinco comprobaciones fallidas se elimina el estado pending.',
                'Las rutas /login/verify y /login/resend-code, la vista y el envío mediante MailerService están registrados. Sin embargo, la llamada a TwoFactorService::start() está comentada en AuthController::login(). El flujo actual de producción llama directamente a completeLogin() después de validar la contraseña; por tanto, 2FA no es actualmente una medida de seguridad activa y no debe anunciarse a los usuarios como habilitada.',
                'Quitar los comentarios no basta para disponer de una 2FA madura. Hay que definir si es obligatoria por usuario o rol, proteger el cambio de correo electrónico, vincular remember-me a una segunda comprobación correcta, registrar los eventos, diseñar la recuperación y los códigos de respaldo, limitar de forma centralizada los intentos y envíos, y probar los fallos SMTP. Un código por correo protege menos que TOTP o WebAuthn y depende de la seguridad del buzón.',
            ],
            'examples' => [
                [
                    'title' => 'Comportamiento actual y comportamiento preparado',
                    'code' => <<<'CODE'
Current:
  password valid → completeLogin() → dashboard

Implemented but disabled:
  password valid → TwoFactorService::start()
                 → email code
                 → /login/verify
                 → completeLogin() → dashboard
CODE,
                ],
            ],
        ],
        [
            'id' => 'api-authentication',
            'title' => 'Autenticación de la API',
            'paragraphs' => [
                'Un administrador crea la clave de API. client_id contiene el prefijo crm_ y 16 bytes aleatorios; secret contiene 32 bytes aleatorios. El secret abierto solo se muestra una vez mediante session flash; api_keys guarda su SHA-256. ApiAuthenticator obtiene las credenciales Basic de PHP_AUTH_USER/PHP_AUTH_PW o Authorization, busca un client_id activo, calcula el hash del secret recibido y lo compara mediante hash_equals().',
                'Después de la autenticación, ApiController exige el scope contacts:read/write o clients:read/write. Revocar una clave establece is_active=0 y revoked_at; volver a activarla restaura la clave con el mismo secret. last_used_at se actualiza como máximo una vez cada cinco minutos. La API no utiliza la sesión del navegador y está exenta de CSRF, por lo que HTTPS es obligatorio: Basic codifica, no cifra.',
                'No existen un rate limiter integrado, fecha de caducidad de las claves, restricción por IP/origin ni rotación automática. El registro de la API contiene los cuerpos de solicitudes y respuestas, que pueden incluir datos personales. El ciclo de vida detallado, los scopes y el formato de errores se describen en «Estructura interna de la API»; aquí es importante no confundir una API key con un remember-token o una session-cookie.',
            ],
            'examples' => [
                [
                    'title' => 'Credencial comprobada',
                    'code' => <<<'CODE'
Authorization: Basic base64(client_id:secret)

DB:
  client_id   = crm_...
  secret_hash = sha256(secret)
  is_active   = 1
  scopes      = ["contacts:read", "contacts:write", ...]

compare: hash_equals(stored_hash, sha256(provided_secret))
CODE,
                ],
            ],
        ],
        [
            'id' => 'data-safety',
            'title' => 'SQL, entrada y salida segura',
            'paragraphs' => [
                'Los repositorios utilizan bindings del Query Builder. Cuando se selecciona dinámicamente el orden o una columna exportada, el valor debe pasar por una lista blanca fija antes de incluirse en SQL. Convertir un id a int resulta útil para normalizarlo, pero no sustituye la comprobación de la existencia de la entidad ni del permiso sobre ella.',
                'View es un wrapper PHP ligero y no aplica escaping automático. La seguridad del HTML depende del uso explícito de htmlspecialchars($value, ENT_QUOTES, UTF-8) en cada contexto. En textos multilínea, primero se aplica htmlspecialchars y después nl2br. Para JSON se utiliza json_encode, y los datos incluidos en atributos data de HTML se codifican además como atributos. Al añadir JavaScript, no deben construirse valores de usuario mediante innerHTML sin sanearlos.',
                'La codificación HTML no comprueba el significado de una URL. El campo website se inserta en href después de htmlspecialchars, pero ClientController no limita el esquema en el servidor; los datos también pueden llegar mediante importación o API. Solo deben admitirse http y https después de parse_url y la normalización. Del mismo modo, la comprobación MIME de las importaciones, los nombres de archivo permitidos, los límites numéricos y la validación del dominio deben realizarse en el servidor, aunque el formulario utilice type=email o type=url.',
            ],
            'examples' => [
                [
                    'title' => 'Codificación según el contexto',
                    'code' => <<<'PHP'
// Texto o atributo HTML
htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

// Texto multilínea
nl2br(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));

// URL: primero validar el esquema y luego codificar para el atributo
$scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
if (in_array($scheme, ['http', 'https'], true)) {
    echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
}
PHP,
                ],
            ],
        ],
        [
            'id' => 'browser-transport',
            'title' => 'HTTPS y cabeceras del navegador',
            'paragraphs' => [
                'public_html/index.php añade X-Frame-Options: SAMEORIGIN, X-Content-Type-Options: nosniff y Referrer-Policy: strict-origin-when-cross-origin. La CSP limita default-src a self, prohíbe object, fija base-uri y form-action en self, solo permite frames del mismo origin y restringe browser connect-src al propio sitio. img-src admite self y data:, mientras que las fuentes y estilos se limitan a los endpoints indicados de Google y jsDelivr.',
                'script-src y style-src contienen actualmente unsafe-inline porque las vistas usan onclick y style inline. Esto debilita considerablemente la CSP frente a XSS. Una transición madura consiste en trasladar manejadores y estilos a assets estáticos y después utilizar nonce o eliminar por completo los permisos inline. Hasta entonces, la CSP sigue siendo un límite útil para los orígenes, pero no debe considerarse una protección completa frente a HTML inyectado.',
                'La aplicación no envía HSTS; debe habilitarse en el reverse proxy solo cuando HTTPS sea estable. HTTP debe redirigir a HTTPS y el proxy debe pasar Authorization a PHP. También conviene definir Permissions-Policy y una política de caché para páginas con datos personales. Las cabeceras deben comprobarse tanto en HTML como en JSON y en los errores del proxy que puedan generarse antes de PHP.',
            ],
            'examples' => [
                [
                    'title' => 'CSP actual en forma abreviada',
                    'code' => <<<'CODE'
default-src 'self'
script-src  'self' 'unsafe-inline'
style-src   'self' 'unsafe-inline' fonts.googleapis.com cdn.jsdelivr.net
font-src    'self' fonts.gstatic.com cdn.jsdelivr.net
img-src     'self' data:
connect-src 'self'
object-src  'none'
base-uri    'self'
form-action 'self'
frame-ancestors 'self'
CODE,
                ],
            ],
        ],
        [
            'id' => 'secrets-logs-storage',
            'title' => 'Secretos, registros y almacenamiento de archivos',
            'paragraphs' => [
                'Los archivos activos config/database.php, config/mail.php y config/gemini.php están excluidos de Git y no deben tener permisos más amplios que root:www-data 0640. Como alternativa, GEMINI_API_KEY se lee del entorno. Los secretos no deben introducirse en JavaScript, URL, mensajes para el usuario, volcados de errores ni copias de seguridad públicas. Para producción se recomienda un secret manager externo o un entorno de proceso protegido.',
                'storage solo debe pertenecer a la aplicación y a sus operadores. Contiene archivos de sesión, archivos bearer de remember-me en texto sin cifrar, login_throttle.json, app.log e importaciones con datos personales. Se necesitan permisos separados, control del uso del disco, copias de seguridad cifradas y una política de conservación. Publicar storage mediante el servidor web constituye un incidente crítico.',
                'logApplicationError() escribe el texto completo de la excepción en error_log del sistema y storage/app.log, mientras que el usuario recibe una respuesta 500 genérica. Es una separación correcta entre interfaz y diagnóstico, pero los mensajes de PDO, SMTP o servicios externos pueden contener detalles del entorno. api_logs guarda además request_body y response_body. Actualmente no se han centralizado el enmascaramiento de secretos y datos personales, el control de acceso, la rotación ni el período de conservación.',
            ],
        ],
        [
            'id' => 'account-lifecycle',
            'title' => 'Ciclo de vida del acceso y auditoría',
            'paragraphs' => [
                'Un administrador puede desactivar un usuario, cambiar su rol, permisos, correo y contraseña, o eliminar definitivamente un registro ya inactivo. La interfaz no permite desactivar ni eliminar la propia cuenta actual. Al eliminar un usuario se borran en cascada sus user_permissions, mientras que los user_id asociados en otras tablas de registro suelen convertirse en NULL.',
                'Una sesión activa no vuelve a comprobar users.is_active ni el rol en la base de datos en cada solicitud. El rol se almacena como cadena en session user, por lo que un usuario desactivado puede seguir trabajando hasta que termine su sesión existente, y un cambio de rol solo entra en vigor tras volver a iniciar sesión. Cambiar la contraseña tampoco destruye las sesiones activas ni todos los tokens remember-me. En la recuperación mediante remember-me sí se vuelven a comprobar la actividad y el rol actual.',
                'schema.sql contiene audit_logs, pero el código actual no registra en esa tabla los inicios y cierres de sesión, intentos fallidos, cambios de roles y permisos, creación de claves ni cambios administrativos. last_login_at y api_logs solo cubren una parte. Para investigar incidentes se necesita una auditoría inmutable con actor, action, target, hora, IP, request id y una descripción segura del cambio sin contraseñas ni secretos.',
            ],
            'examples' => [
                [
                    'title' => 'Eventos que deben revocar el acceso',
                    'code' => <<<'CODE'
user deactivated
password changed/reset
role changed
administrator requests “sign out everywhere”
suspected account compromise

Required effect:
  - invalidate all server sessions for user_id
  - delete all remember tokens for user_id
  - optionally revoke related API keys by explicit ownership policy
  - write security audit event
CODE,
                ],
            ],
        ],
        [
            'id' => 'security-testing',
            'title' => 'Comprobación de cambios de seguridad',
            'paragraphs' => [
                'Los cambios de una función protegida deben probarse al menos con cuatro identidades: invitado, usuario normal sin permiso, usuario normal con permiso y administrador. Para cada action se comprueban la URL directa y el método HTTP real, no solo la visibilidad del botón. También deben comprobarse por separado los estados 401/403/419, la ausencia de cambios colaterales cuando se deniega la solicitud y la ausencia de datos sensibles en la respuesta.',
                'La autenticación requiere pruebas con contraseñas correctas e incorrectas, usuarios desactivados, regeneración del session id, bloqueo y caducidad del throttle, archivos remember dañados, tokens caducados y rotados, logout y recuperación después de cambiar el rol. CSRF debe probarse sin token, con un token ajeno y con uno válido para cada modificación del navegador.',
                'Para la API deben probarse la ausencia de Authorization, client_id y secret incorrectos, claves revocadas, scope insuficiente, lecturas y escrituras correctas, ausencia de dependencia de CSRF, registro y garantía de que secret no aparezca en la base de datos ni en los logs. Las comprobaciones de CSP, indicadores de cookies, redirección HTTPS y HSTS se realizan en el servidor desplegado, porque una prueba CLI no observa el comportamiento del reverse proxy ni del navegador.',
            ],
            'examples' => [
                [
                    'title' => 'Matriz mínima de acceso',
                    'code' => <<<'CODE'
                     guest   user:no   user:yes   admin
read protected page  302      200        200       200
edit protected page  302      403        200       200
POST without CSRF    419      419        419       419
admin page           302      403        403       200
API without key      401      401        401       401
API with scope       —        —          —         2xx
CODE,
                ],
            ],
        ],
        [
            'id' => 'security-known-gaps',
            'title' => 'Deuda técnica prioritaria',
            'paragraphs' => [
                'Los permisos ya aplican fail-closed, las políticas de las rutas web están centralizadas en Router y el acceso a la página de IA y a su AJAX está alineado con el nivel admin. Al añadir un permission sigue siendo necesario asignar manualmente valores explícitos a los usuarios existentes. La prioridad alta pendiente es comprobar la actividad y el rol actual del usuario en una sesión viva, y revocar todas las sesiones y tokens remember-me cuando se desactive la cuenta o se cambien la contraseña o el rol.',
                'El siguiente nivel consiste en añadir Secure a la cookie persistente, convertir logout en un POST con CSRF, introducir timeouts explícitos de inactividad y duración absoluta, una política estricta de contraseñas y rehash, y decidir si se activa realmente o se elimina el código 2FA inactivo. LoginThrottle debe trasladarse a un almacenamiento centralizado fiable y complementarse con límites por IP/riesgo sin facilitar el bloqueo de cuentas ajenas.',
                'Después se necesitan validación de esquemas URL en el servidor, eliminación de unsafe-inline en la CSP, security audit automático, enmascaramiento y retention de registros, rate limiting de la API, caducidad y rotación de claves de API, análisis de dependencias y pruebas de integración periódicas de la matriz de acceso. Estos puntos describen los límites actuales de la implementación; la documentación debe actualizarse al mismo tiempo que se corrige cada contrato.',
            ],
            'examples' => [
                [
                    'title' => 'Orden de refuerzo',
                    'code' => <<<'CODE'
P0  revoke access on account/password/role changes

P1  Secure remember cookie + POST logout
P1  explicit session timeouts and password policy
P1  decide and complete 2FA
P1  centralized login throttling and security audit

P2  strict URL validation and CSP without unsafe-inline
P2  API rate limits, expiry and key rotation
P2  log redaction/retention and automated security tests
CODE,
                ],
            ],
        ],
    ],
];
