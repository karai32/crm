<?php

return array (
  'title' => 'Estructura del código',
  'description' => 'Arquitectura de ContactCore, ciclo de vida de las peticiones y normas para desarrollar nuevas funciones.',
  'icon' => 'ph-arrow-elbow-down-right',
  'sections' =>
  array (
    0 =>
    array (
      'id' => 'code-architecture',
      'title' => 'Arquitectura de la aplicación',
      'paragraphs' =>
      array (
        0 => 'ContactCore es una aplicación PHP de servidor sin un framework completo. Está construida como un monolito modular: la interfaz, la API, las operaciones de negocio y el acceso a datos se encuentran en un único proyecto y trabajan con una base de datos MySQL común. El código se divide en capas según sus responsabilidades, por lo que resulta más sencillo entenderlo como una arquitectura MVC simplificada con repositorios y servicios independientes.',
        1 => 'Los principales patrones del proyecto son: Front Controller en public_html/index.php, Router para seleccionar el manejador, Controller para el flujo HTTP, Repository para SQL, Service Layer para los procesos de negocio y View con layout para HTML. La API utiliza composición: un único ApiController recibe el nombre del recurso y el servicio correspondiente; ApiResult representa el resultado y ApiException, un error esperado.',
        2 => 'La separación entre capas es más importante que el nombre de una clase: el controlador gestiona la petición, el servicio toma las decisiones de negocio, el repositorio se ocupa del almacenamiento y la vista solo muestra los datos preparados. El código nuevo debe colocarse siguiendo esta regla, no en el archivo que parezca más cercano.',
      ),
      'examples' =>
      array (
        0 =>
        array (
          'title' => 'Esquema simplificado de las capas',
          'code' => 'Navegador o servicio externo
            │
            ▼
public_html/index.php  — Front Controller y bootstrap
            │
            ▼
Router                 — ruta y método HTTP
            │
            ▼
Controller             — acceso, entrada y selección del flujo
        │           │
        ▼           ▼
     Service     Repository
        │           │
        └─────┬─────┘
              ▼
            MySQL
      (Query Builder)
              │
              ▼
View + Layout → HTML   o   ApiResult → JSON',
        ),
      ),
    ),
    1 =>
    array (
      'id' => 'code-directories',
      'title' => 'Directorios del proyecto',
      'paragraphs' =>
      array (
        0 => 'Todo el código ejecutable de la aplicación se encuentra en app, mientras que el punto de entrada público y los archivos estáticos están en public_html. La configuración y storage se sitúan deliberadamente por encima del document root para impedir el acceso mediante una petición HTTP normal.',
        1 => 'Los controladores, repositorios y vistas suelen agruparse por entidad: ContactController trabaja con ContactRepository y con las vistas de app/Views/contacts. Los procesos complejos disponen de una carpeta independiente dentro de Services, como ocurre con la importación, la exportación y la API.',
      ),
      'examples' =>
      array (
        0 =>
        array (
          'title' => 'Finalidad de los principales directorios',
          'code' => 'app/
├── Controllers/       Flujos HTTP de la interfaz, AJAX y API
├── Core/              Router, Database, Auth, View, Lang y CSRF
├── Helpers/           Funciones compartidas de las vistas
├── Repositories/      Consultas y obtención de datos mediante Query Builder
├── Services/          Procesos de negocio e integraciones
└── Views/             Plantillas PHP, layouts y partials

public_html/
├── index.php          Único punto de entrada PHP
└── assets/            CSS, JavaScript y plantillas CSV/XLSX preparados

bin/                   Comandos ejecutados desde CLI y cron
config/                Configuración local y secretos
database/              Estructura SQL inicial
lang/                  Traducciones de la interfaz
storage/               Datos modificables de la aplicación y registros
vendor/                Dependencias de Composer',
        ),
      ),
    ),
    2 =>
    array (
      'id' => 'code-request-lifecycle',
      'title' => 'Ciclo de vida de una petición HTTP',
      'paragraphs' =>
      array (
        0 => 'Nginx envía la URL virtual a public_html/index.php. El punto de entrada selecciona el directorio de sesiones, inicia la sesión, establece las cabeceras de seguridad, carga Composer y las clases de la aplicación, carga el idioma, crea los controladores y registra las rutas.',
        1 => 'Antes de dispatch se realiza una comprobación CSRF común para todas las peticiones POST salvo /api/v1. A continuación, Router separa la ruta de la query string, tiene en cuenta la instalación en un subdirectorio, busca una ruta exacta o parametrizada e invoca el método asignado. Router guarda en $_GET los valores de segmentos como {id}, por lo que los métodos de los controladores no los reciben como argumentos.',
        2 => 'Las excepciones PDOException y Throwable no gestionadas se capturan al final del punto de entrada. El usuario recibe una respuesta 500 neutra y el mensaje técnico se escribe en error_log y storage/app.log.',
      ),
      'examples' =>
      array (
        0 =>
        array (
          'title' => 'Secuencia de una petición normal',
          'code' => 'GET /clients/show?id=42
  → Nginx: /index.php
  → session_start() y Lang::load()
  → Router::dispatch(\'GET\', \'/clients/show?id=42\')
  → Router comprueba la policy: auth = user
  → ClientController::show()
  → ClientRepository::find(42)
  → View::render(\'clients/show\', $data)
  → app/Views/layouts/main.php
  → Respuesta HTML',
        ),
      ),
    ),
    3 =>
    array (
      'id' => 'code-bootstrap-routing',
      'title' => 'Bootstrap, carga de clases y rutas',
      'paragraphs' =>
      array (
        0 => 'Composer solo carga automáticamente las bibliotecas externas. Las clases propias todavía no utilizan namespaces ni PSR-4: cada archivo PHP nuevo debe añadirse mediante require_once en public_html/index.php antes de crear cualquier objeto que dependa de él. El orden de carga es importante para la herencia y las declaraciones de tipos.',
        1 => 'Después de las inclusiones, el punto de entrada crea manualmente las instancias de los controladores y relaciona cada método y ruta HTTP con un callable. Router admite GET, POST, PATCH y DELETE, rutas exactas y parámetros {name}. El tercer argumento de una ruta es una política de acceso con auth, permission y el formato de denegación response. No existe una búsqueda automática del controlador a partir de la URL.',
        2 => 'Registre primero las rutas más específicas y después las rutas parametrizadas generales. Cada ruta debe tener una policy: auth = public para un punto deliberadamente abierto; auth = user/admin o permission para uno protegido. Router rechaza el registro de rutas sin política. Utilice Auth::url() para generar enlaces internos; de lo contrario, una instalación de la aplicación en un subdirectorio podría dejar de funcionar.',
      ),
      'examples' =>
      array (
        0 =>
        array (
          'title' => 'Carga y registro de un controlador nuevo',
          'code' => 'require_once __DIR__ . \'/../app/Repositories/ProjectRepository.php\';
require_once __DIR__ . \'/../app/Services/ProjectService.php\';
require_once __DIR__ . \'/../app/Controllers/ProjectController.php\';

$projectController = new ProjectController();

// Añada primero projects.manage a Auth::permissionDefinitions().

$router->get(\'/projects\', [$projectController, \'index\'], [\'auth\' => \'user\']);
$router->get(\'/projects/create\', [$projectController, \'create\'], [
    \'permission\' => \'projects.manage\',
]);
$router->post(\'/projects/store\', [$projectController, \'store\'], [
    \'permission\' => \'projects.manage\',
]);
$router->get(\'/projects/{id}\', [$projectController, \'show\'], [\'auth\' => \'user\']);',
        ),
      ),
    ),
    4 =>
    array (
      'id' => 'code-controllers',
      'title' => 'Controladores',
      'paragraphs' =>
      array (
        0 => 'Un controlador actúa como adaptador entre HTTP y el código de la aplicación. Router comprueba el acceso, el rol o el permiso antes de invocar el método público. El controlador lee $_GET, $_POST o $_FILES, convierte los valores sencillos a los tipos esperados, llama a un repositorio o servicio y elige la respuesta: HTML, redirección, JSON o código de error.',
        1 => 'El controlador puede coordinar un flujo y realizar una validación sencilla del formulario. Las reglas complejas, las operaciones reutilizables y las transacciones deben trasladarse preferentemente a un servicio. No se debe añadir al controlador SQL, marcado HTML ni lectura directa de archivos de configuración.',
        2 => 'Después de un POST correcto se suele utilizar Post/Redirect/Get: los datos se guardan y se ejecuta Auth::redirect(). Esto evita volver a enviar el formulario al actualizar la página. Si se produce un error, el controlador vuelve a renderizar el formulario con los valores introducidos y un mensaje comprensible.',
      ),
      'examples' =>
      array (
        0 =>
        array (
          'title' => 'Operación de creación habitual',
          'code' => 'public function store(): void
{
    $name = trim($_POST[\'name\'] ?? \'\');
    if ($name === \'\') {
        View::render(\'projects/create\', [
            \'title\' => Lang::get(\'projects.create_title\'),
            \'error\' => Lang::get(\'projects.name_required\'),
            \'name\'  => $name,
        ]);
        return;
    }

    $this->projects->create($name);
    Auth::redirect(\'/projects\');
}',
        ),
      ),
    ),
    5 =>
    array (
      'id' => 'code-repositories',
      'title' => 'Repositorios y acceso a datos',
      'paragraphs' =>
      array (
        0 => 'Un Repository aísla el acceso a los datos de un área determinada y devuelve arrays PHP normales, números o null. Las consultas se construyen mediante Database::table(), mientras que Database::rows() y Database::row() convierten los resultados de Illuminate Database al formato de arrays adoptado por la aplicación.',
        1 => 'Los valores se transmiten mediante los bindings de Query Builder. Los nombres de las columnas y la dirección de ordenación no pueden obtenerse directamente de la petición, por lo que solo se seleccionan desde una allowlist fija. Un repositorio puede aplicar una regla pequeña directamente relacionada con el almacenamiento, como eliminar un sector sin relaciones o desactivar uno que esté en uso.',
        2 => 'No transfiera un Builder modificable fuera de la capa de datos salvo que sea necesario y no incluya valores sin comprobar en expresiones raw. Un servicio coordina las operaciones que abarcan varios repositorios y el límite de la transacción se establece para el escenario de negocio completo.',
      ),
      'examples' =>
      array (
        0 =>
        array (
          'title' => 'Consulta segura con una allowlist de ordenación',
          'code' => 'public function paginate(int $page, int $perPage, string $sort): array
{
    $allowed = [\'name\' => \'name\', \'created_at\' => \'created_at\'];
    $column = $allowed[$sort] ?? \'name\';
    return Database::rows(
        Database::table(\'projects\')
            ->orderBy($column)
            ->limit($perPage)
            ->offset(($page - 1) * $perPage)
    );
}',
        ),
      ),
    ),
    6 =>
    array (
      'id' => 'code-services',
      'title' => 'Servicios y procesos de negocio',
      'paragraphs' =>
      array (
        0 => 'La Service Layer se utiliza cuando una acción no se reduce a una consulta sobre una única tabla. ContactWriteService y ClientWriteService son el único punto de aplicación para crear y actualizar contactos y clientes: normalizan y validan el registro principal, comprueban duplicados y guardan etiquetas, relaciones y campos personalizados. Los controladores HTML, los servicios de la API y los procesadores de importación solo adaptan sus datos de entrada y convierten las WriteException comunes en una respuesta de su propio transporte.',
        1 => 'Un servicio puede utilizar varios repositorios y otros servicios, pero no debe depender de HTML. Las operaciones compuestas se ejecutan mediante Database::transaction(). El método abre una transacción si todavía no existe o se une a la transacción del lote de la API o de la fila importada. De este modo, un servicio anidado no intenta abrir otra nested transaction.',
        2 => 'Un servicio pequeño puede crearse directamente en el constructor del controlador: el proyecto todavía no dispone de un contenedor de DI. A medida que evolucione el código, conserve las dependencias en propiedades private tipadas para que la composición del objeto siga siendo visible.',
      ),
      'examples' =>
      array (
        0 =>
        array (
          'title' => 'Flujo de negocio transaccional',
          'code' => '$contactId = $this->contactWriter->create(
    data: $contact,
    tagIds: $tagIds,
    clientIds: $clientIds,
    customFields: $fields,
    customValues: $values
);

// ContactWriteService guarda todos los componentes del registro mediante
// Database::transaction() y devuelve el id después de un commit correcto.',
        ),
      ),
    ),
    7 =>
    array (
      'id' => 'code-views',
      'title' => 'Vistas, layouts y JavaScript',
      'paragraphs' =>
      array (
        0 => 'View::render() recibe la ruta de una plantilla y un array de datos, convierte las claves del array en variables locales mediante extract(EXTR_SKIP), almacena el resultado en un búfer y lo inserta en el layout. De forma predeterminada se utiliza app/Views/layouts/main.php, mientras que las páginas de acceso utilizan el layout auth. Las partes comunes de un formulario se guardan en archivos partial cuyo nombre comienza por un guion bajo.',
        1 => 'La vista debe recibir datos ya preparados. No se permite incluir SQL ni decisiones de negocio en una plantilla. Los valores dinámicos se escapan mediante la función e() de Illuminate Support y las URL internas se construyen con el helper url() de app/Helpers/view_helpers.php. La función t() ya devuelve una traducción escapada y los formularios POST incluyen Csrf::field().',
        2 => 'El CSS y JavaScript no se procesan con un bundler. El controlador transmite los nombres de los archivos adicionales en styles y scripts, y el layout los carga desde public_html/assets. JavaScript se ocupa del comportamiento de la interfaz y AJAX, pero el servidor vuelve a comprobar el acceso y los datos de entrada: la validación del navegador no es una medida de seguridad.',
      ),
      'examples' =>
      array (
        0 =>
        array (
          'title' => 'Transmisión de datos desde el controlador',
          'code' => 'View::render(\'projects/index\', [
    \'title\'    => Lang::get(\'projects.title\'),
    \'styles\'   => [\'settings.css\'],
    \'scripts\'  => [\'projects.js\'],
    \'projects\' => $this->projects->paginate($page, $perPage, $sort),
]);',
        ),
        1 =>
        array (
          'title' => 'Plantilla PHP segura para un formulario',
          'code' => '<form method="post" action="<?= url(\'/projects/store\') ?>">
    <?= Csrf::field() ?>
    <input
        name="name"
        value="<?= e($name ?? \'\') ?>"
        required
    >
    <button type="submit"><?= t(\'common.save\') ?></button>
</form>',
        ),
      ),
    ),
    8 =>
    array (
      'id' => 'code-auth-security',
      'title' => 'Autorización y límites de seguridad',
      'paragraphs' =>
      array (
        0 => 'Auth guarda en la sesión los datos mínimos del usuario autenticado, restaura el remember-login y calcula los permisos. Router aplica de forma centralizada la política de cada ruta web y AJAX antes de invocar el manejador. Un administrador recibe todos los permisos conocidos; una clave desconocida, una fila ausente o un error de carga deniegan la acción. Ocultar un elemento del menú solo afecta a la interfaz y no sustituye a una policy.',
        1 => 'El token CSRF se guarda en la sesión. El punto de entrada comprueba de forma centralizada las peticiones POST normales, por lo que todos estos formularios deben incluir Csrf::field(). La API se excluye de la comprobación CSRF porque utiliza HTTP Basic con client_id y secret, scopes y un registro de peticiones propio.',
        2 => 'Los datos procedentes de $_GET, $_POST, $_FILES, JSON y las cabeceras siempre se consideran no fiables. Deben normalizarse, comprobarse mediante una allowlist y solo después transmitirse a otras capas. El escapado se realiza al generar HTML, no al guardar los datos en la base.',
      ),
      'examples' =>
      array (
        0 =>
        array (
          'title' => 'Selección del nivel de acceso',
          'code' => '$router->get(\'/dashboard\', [$dashboardController, \'index\'], [
    \'auth\' => \'user\',
]);

$router->post(\'/contacts/update\', [$contactController, \'update\'], [
    \'permission\' => \'contacts.edit\',
]);

$router->post(\'/ajax/admin-task\', [$ajaxController, \'adminTask\'], [
    \'auth\' => \'admin\',
    \'response\' => \'json\',
]);',
        ),
      ),
    ),
    9 =>
    array (
      'id' => 'code-localization',
      'title' => 'Localización',
      'paragraphs' =>
      array (
        0 => 'El idioma se guarda en la sesión y se carga mediante Lang::load(). Se admiten ru, es y en; si el archivo ruso o español no contiene una clave, Lang añade como fallback el valor inglés. Las traducciones se encuentran en los arrays planos lang/ru.php, lang/es.php y lang/en.php.',
        1 => 'Utilice Lang::get() en la lógica PHP y t() en el HTML para los textos de la interfaz. Cada clave nueva debe añadirse con el mismo nombre a todos los archivos de idioma. Los valores introducidos por los usuarios o recibidos desde la base de datos no son traducciones y se escapan por separado.',
      ),
      'examples' =>
      array (
        0 =>
        array (
          'title' => 'Clave con sustitución de parámetros',
          'code' => '// lang/es.php
\'projects.created\' => \'Se ha creado el proyecto «:name».\',

// Controlador o servicio
$message = Lang::get(\'projects.created\', [\'name\' => $project[\'name\']]);

// Vista: t() escapa directamente el resultado
<h1><?= t(\'projects.title\') ?></h1>',
        ),
      ),
    ),
    10 =>
    array (
      'id' => 'code-api-internals',
      'title' => 'Estructura interna de la API',
      'paragraphs' =>
      array (
        0 => 'La API utiliza el mismo Front Controller y Router, pero dispone de una cadena de clases independiente. Un único ApiController implementa los métodos CRUD estándar, la autenticación, los scopes, el análisis de JSON, un formato de errores común, X-Request-Id y la escritura en api_logs. En el punto de entrada se crean dos instancias con ContactApiService y ClientApiService. Las diferencias entre recursos se encuentran en los servicios y no se utilizan microclases de controladores.',
        1 => 'Cada método de un servicio de API devuelve un ApiResult con el estado, el cuerpo y el número de elementos. Un error de negocio esperado se representa mediante ApiException con un estado, un código y detalles. Las excepciones inesperadas no se muestran al cliente, pero se escriben en el registro del servidor junto con el request ID.',
        2 => 'La creación por lotes se procesa mediante AbstractApiService::batch(): cada elemento obtiene una transacción y un resultado independientes, y la respuesta general tiene el estado 207. Para añadir un recurso a la API hay que crear un servicio con la lógica correspondiente y configurar el controlador común, no copiar el procesamiento de claves, JSON y registros.',
      ),
      'examples' =>
      array (
        0 =>
        array (
          'title' => 'Configuración del controlador de un recurso',
          'code' => '$apiControllers = [
    \'contacts\' => new ApiController(\'contacts\', new ContactApiService()),
    \'clients\' => new ApiController(\'clients\', new ClientApiService()),
];

// Un solo bucle registra GET/POST/PATCH/DELETE para cada recurso.',
        ),
        1 =>
        array (
          'title' => 'Resultado y error esperado',
          'code' => 'return new ApiResult(200, [
    \'success\' => true,
    \'data\' => $project,
], 1);

throw new ApiException(
    422,
    \'validation_error\',
    \'La validación del proyecto ha fallado\',
    [\'el nombre es obligatorio\']
);',
        ),
      ),
    ),
    11 =>
    array (
      'id' => 'code-feature-flow',
      'title' => 'Recorrido de una operación',
      'paragraphs' =>
      array (
        0 => 'Por ejemplo, la creación de un contacto desde la interfaz comienza con la comprobación de contacts.create por parte de la política de la ruta POST. ContactController extrae los campos del formulario y transmite los datos, los id de etiquetas y clientes y los valores personalizados a ContactWriteService. El servicio normaliza y valida el registro, clasifica el correo y crea en una única transacción el registro principal y sus relaciones; el controlador solo convierte WriteException y ejecuta la redirección.',
        1 => 'La misma operación a través de la API llega a la instancia de ApiController configurada para contacts y después a ContactApiService. El servicio de la API comprueba la estructura JSON, convierte los nombres externos de etiquetas y clientes en id e invoca el mismo ContactWriteService. Los errores de dominio comunes reciben un código estable de la API mediante WriteException, la transacción del lote incluye los catálogos creados automáticamente y el resultado se registra en api_logs.',
        2 => 'La importación utiliza el mismo límite de escritura: ContactImportProcessor y ClientImportProcessor convierten las columnas de una fila e invocan ContactWriteService o ClientWriteService dentro de la transacción de esa fila. La validación común y la semántica de duplicados ya no se copian en los procesadores; ImportManager convierte WriteException en error o skipped. Para el procesamiento masivo solo se desactiva explícitamente en el servicio la lenta comprobación DNS del correo.',
      ),
      'examples' =>
      array (
        0 =>
        array (
          'title' => 'Dos entradas a un mismo dominio',
          'code' => 'Formulario HTML
  → ContactController
  → ContactWriteService [validación + EmailInspector + transacción]
  → redirección + HTML

API JSON
  → ApiController::handle() [contacts]
  → ContactApiService
  → ContactWriteService
  → ApiResult + api_logs',
        ),
      ),
    ),
    12 =>
    array (
      'id' => 'code-new-feature',
      'title' => 'Proceso para añadir una sección nueva',
      'paragraphs' =>
      array (
        0 => 'Defina primero la entidad, los flujos de usuario y los permisos. A continuación, prepare el cambio de la base de datos; este paso se explicará detalladamente en la siguiente sección de la documentación. Después de la base de datos, cree el Repository, el Service si es necesario, el Controller, las rutas y las Views. Por último, añada las traducciones, el elemento del menú, el CSS/JavaScript y las comprobaciones de acceso en el servidor.',
        1 => 'Para un catálogo sencillo suele ser suficiente Repository + Controller + Views. Una operación que abarque varias entidades necesita un Service. Para un nuevo recurso JSON externo se añade un ApiService con su lógica y la configuración del ApiController común. Solo el protocolo HTTP es universal; la lógica de negocio de áreas diferentes no debe mezclarse en un mismo servicio.',
        2 => 'Antes de terminar, compruebe el flujo correcto, los datos vacíos e incorrectos, un usuario sin permisos, un registro ausente, el reenvío del formulario, el escapado de la salida y el rollback de la transacción. Para la API, compruebe además una clave incorrecta, un scope insuficiente, JSON no válido y el registro del request ID.',
      ),
      'examples' =>
      array (
        0 =>
        array (
          'title' => 'Conjunto mínimo para una entidad nueva',
          'code' => 'app/Repositories/ProjectRepository.php
app/Controllers/ProjectController.php
app/Views/projects/index.php
app/Views/projects/create.php
app/Views/projects/edit.php
app/Views/projects/_form.php
public_html/assets/js/projects.js        # si necesita comportamiento
public_html/assets/css/projects.css      # si los estilos básicos no son suficientes
lang/ru.php, lang/en.php, lang/es.php
public_html/index.php                    # require_once, objeto y rutas',
        ),
      ),
    ),
    13 =>
    array (
      'id' => 'code-conventions',
      'title' => 'Convenciones y limitaciones actuales',
      'paragraphs' =>
      array (
        0 => 'Las clases de la aplicación todavía se encuentran en el espacio de nombres global y las dependencias se crean manualmente. El nombre del archivo debe corresponder a la finalidad de la clase, cada archivo debe contener una clase principal y las propiedades y valores devueltos deben estar tipados. No cambie este estilo de forma aislada en un único módulo: la migración a namespaces, PSR-4 o DI debe realizarse como una refactorización independiente y coordinada.',
        1 => 'El proyecto no utiliza modelos de Eloquent ni dispone de un sistema de migraciones: el acceso a datos se realiza mediante Illuminate Database Query Builder y el administrador prepara y aplica manualmente los cambios de una base existente mediante un cliente SQL. database/schema.sql está destinado únicamente a la primera instalación y representa la estructura completa actual. Todavía no existe un directorio de pruebas automatizadas, por lo que los cambios se comprueban mediante PHP lint, flujos de la interfaz y la API y revisión de los registros; es recomendable cubrir gradualmente las reglas de dominio críticas con pruebas unitarias o de integración.',
        2 => 'Los comentarios deben explicar un motivo o una limitación, no repetir el código. La lógica nueva no debe mostrar secretos ni excepciones internas al usuario. Los errores inesperados se registran con suficiente contexto, pero las contraseñas, los secretos de API, las claves SMTP y los payloads sensibles completos no se escriben en el registro normal.',
      ),
      'examples' =>
      array (
        0 =>
        array (
          'title' => 'Comprobaciones básicas de archivos PHP modificados',
          'code' => 'php -l app/Controllers/ProjectController.php
php -l app/Repositories/ProjectRepository.php
php -l app/Views/projects/index.php
composer check-platform-reqs --no-dev',
        ),
      ),
    ),
  ),
);
