<?php

return [
    'title' => 'Importación y exportación',
    'description' => 'Funcionamiento interno del intercambio de datos por lotes: carga y lectura de CSV/XLSX, asignación de columnas, transacciones, registro de resultados y entrega de archivos mediante streaming.',
    'icon' => 'ph-arrow-elbow-down-right',
    'sections' => [
        [
            'id' => 'import-export-boundary',
            'title' => 'Lugar del subsistema en la aplicación',
            'paragraphs' => [
                'La importación y la exportación son flujos independientes del lado del servidor dentro de la interfaz web. No forman parte de la API pública /api/v1 ni utilizan colas en segundo plano. ImportController gestiona la carga, la vista previa, el inicio de la importación y la consulta de errores; ExportController genera la página de selección de campos y entrega inmediatamente el archivo terminado en la respuesta HTTP. Ambos controladores trabajan con la sesión habitual del usuario y la protección CSRF común para las solicitudes POST.',
                'El acceso se divide mediante dos permisos. Todas las rutas /imports requieren imports.manage y las rutas /exports, exports.use. Un usuario con el permiso correspondiente ve el historial general de operaciones, no solo sus propias tareas. Las tablas guardan el user_id del iniciador; si se elimina el usuario, la clave foránea lo convierte en NULL sin borrar el historial.',
                'La lógica principal se encuentra fuera de los controladores. ImportManager coordina el archivo, el lote y el procesador de la entidad; ImportFileReader lee los formatos; ImportMapping define los destinos de columna permitidos. En la exportación, ExportManager prepara el plan, ExportService construye el Query Builder, ExportWriter escribe el resultado, e ImportRepository y ExportRepository almacenan el estado de las operaciones.',
            ],
            'examples' => [
                [
                    'title' => 'Rutas del subsistema',
                    'code' => <<<'CODE'
GET  /imports                 ImportController::index
POST /imports/upload          ImportController::storeUpload
POST /imports/process         ImportController::process
GET  /imports/errors?id={id}  ImportController::errors

GET  /exports                 ExportController::index
POST /exports/download        ExportController::download
CODE,
                ],
            ],
        ],
        [
            'id' => 'import-upload',
            'title' => 'Carga del archivo de importación',
            'paragraphs' => [
                'La importación solo admite CSV y XLSX de hasta 20 MB. ImportManager comprueba el código de error de PHP, el tamaño, la extensión y el tipo MIME. Para XLSX también verifica la disponibilidad de OpenSpout. Si la extensión y el tipo real no coinciden, el archivo se rechaza. Si la extensión fileinfo no está disponible, la comprobación MIME se omite, por lo que la extensión del archivo queda como única validación del formato.',
                'El archivo aceptado se mueve a storage/imports, fuera del directorio público public_html. El directorio se crea con permisos 0770. El nombre original se conserva solo para la interfaz, mientras que el nombre físico se forma a partir del tipo de entidad, la fecha y hora, y bytes aleatorios. Para acceder al archivo se utiliza basename(), lo que impide que el valor stored_filename salga del directorio de importación.',
                'El límite de la aplicación debe estar coordinado con PHP y el servidor web. upload_max_filesize, post_max_size y client_max_body_size deben permitir un archivo de más de 20 MB teniendo en cuenta la envoltura multipart. Las plantillas preparadas contacts-import-template y clients-import-template se encuentran en public_html/assets/templates en formatos CSV y XLSX y se sirven como archivos estáticos. Al modificar los campos importables, las plantillas deben actualizarse junto con el código de asignación.',
            ],
            'examples' => [
                [
                    'title' => 'Nombre y ubicación del archivo aceptado',
                    'code' => <<<'CODE'
Original: contacts-july.xlsx
Stored:   storage/imports/contacts-2026-07-29-14-35-08-a93f10c42e77.xlsx

Application limit: 20 MB
Formats:           csv | xlsx
CODE,
                ],
            ],
        ],
        [
            'id' => 'import-reading',
            'title' => 'Lectura de CSV y XLSX',
            'paragraphs' => [
                'ImportFileReader proporciona un generador rows() común para ambos formatos. La primera fila siempre se considera la cabecera, se omiten las filas de datos vacías y el número de fila se conserva tal como lo ve el usuario en la tabla. Se eliminan los espacios de los extremos de cabeceras y valores; el BOM de UTF-8 se elimina de la primera cabecera. Una cabecera vacía se ignora al combinar la fila con las columnas.',
                'Los CSV se leen con la función estándar fgetcsv() y la configuración predeterminada de PHP. Esto implica delimitador de coma, escape estándar y ausencia de detección automática de codificación o delimitador. Los archivos delimitados por punto y coma o con una codificación distinta de UTF-8 deben convertirse previamente o requieren una ampliación específica del reader.',
                'Los XLSX se leen mediante OpenSpout en auténtico modo de streaming. Se utiliza la hoja activa, las fechas se formatean para la importación, los valores calculados de las fórmulas se leen a partir del resultado guardado y las filas vacías se conservan en el iterador interno para que sus números coincidan con la hoja original. El libro completo no se carga en memoria.',
            ],
            'examples' => [
                [
                    'title' => 'Contrato del generador de filas',
                    'code' => <<<'PHP'
foreach ($reader->rows($path, $fileType) as $item) {
    $item['row_number']; // 2, 3, 4... — número en el archivo original
    $item['headers'];    // cabeceras normalizadas de la primera fila
    $item['row'];        // ['Full name' => 'Ana Ruiz', ...]
}
PHP,
                ],
            ],
        ],
        [
            'id' => 'import-preview-mapping',
            'title' => 'Vista previa y asignación de columnas',
            'paragraphs' => [
                'Después de la carga se crea un import_batches con estado uploaded y el navegador redirige a /imports?id={batch}. El método preview() lee el archivo y muestra como máximo diez filas, pero recorre todas para calcular total_rows. En un CSV grande esto supone una pasada completa adicional; en un XLSX, una lectura completa adicional del libro antes de la importación real.',
                'ImportMapping contiene una lista blanca de campos del sistema para contacts y clients, además de un conjunto de alias de cabeceras en inglés. suggest() solo propone correspondencias; el usuario puede cambiar cada destino, excluir una columna o seleccionar __custom. Antes del procesamiento, clean() vuelve a descartar los destinos desconocidos, por lo que manipular el valor del formulario no permite pasar un nombre de columna arbitrario al repositorio.',
                'Para los contactos debe existir una columna asignada a full_name; para los clientes, a commercial_name. Se comprueba la presencia de la asignación, mientras que el valor se valida por separado en cada fila. Es posible dirigir varias columnas de origen al mismo campo del sistema, pero mapRow() conservará el último valor; las cabeceras duplicadas del propio archivo también se fusionan en el array asociativo. Estos archivos deben considerarse ambiguos y rechazarse mediante una futura validación estricta.',
            ],
            'examples' => [
                [
                    'title' => 'Datos del formulario de asignación',
                    'code' => <<<'CODE'
mapping[Name]                = full_name
mapping[Email address]       = email
mapping[Labels]              = tags
mapping[Preferred language]  = __custom

custom_fields[Preferred language][field_type] = select
CODE,
                ],
            ],
        ],
        [
            'id' => 'import-processors',
            'title' => 'Procesadores y patrón de tratamiento de filas',
            'paragraphs' => [
                'ImportManager selecciona ContactImportProcessor o ClientImportProcessor según entity_type. Ambos heredan de AbstractImportProcessor, que reúne la resolución de etiquetas, sectores y contactos, y la preparación de campos personalizados. El procesador adapta la fila importada, mientras que la escritura final se delega al ContactWriteService o ClientWriteService común, el mismo que utilizan HTML y la API.',
                'Durante el procesamiento se llama a set_time_limit(0); después, el archivo se lee por segunda vez y cada fila no vacía recorre el mismo ciclo mediante Database::transaction(): asignación de valores, procesamiento del dominio, commit o rollback, y registro del problema. El servicio de escritura se incorpora a la transacción ya abierta para la fila. Tras un error, el procesador se crea de nuevo para que sus cachés no contengan identificadores de entidades cuya creación se haya revertido.',
                'Eliminar el límite de PHP no anula los timeouts de Nginx, FastCGI, el balanceador o el navegador. Como no existen una cola ni un worker independiente, una importación larga sigue ligada a una única solicitud HTTP. Para conjuntos realmente grandes, el siguiente paso arquitectónico es una tarea en segundo plano con bloques, heartbeat y una pantalla de progreso independiente.',
            ],
            'examples' => [
                [
                    'title' => 'Ciclo de vida de una fila',
                    'code' => <<<'CODE'
raw row
  → ImportMapping::mapRow()
  → beginTransaction()
  → ContactImportProcessor | ClientImportProcessor
      → main entity
      → relations
      → custom fields
  → commit()

ImportRowException | Throwable
  → rollback()
  → import_rows + import_errors
CODE,
                ],
            ],
        ],
        [
            'id' => 'contact-import-rules',
            'title' => 'Reglas de importación de contactos',
            'paragraphs' => [
                'Para un contacto, full_name es obligatorio y no puede estar vacío. El correo electrónico es opcional, pero, si se proporciona, debe superar FILTER_VALIDATE_EMAIL. La coincidencia del correo con un contacto existente o con una fila importada anteriormente en la misma ejecución se considera una omisión, no un error. El índice UNIQUE de contacts.email protege definitivamente esta regla en importaciones simultáneas; un conflicto de restricción también se registra como skipped.',
                'El contacto se crea con full_name, email, phone y company. EmailInspector se invoca con la comprobación DNS desactivada: la dirección se clasifica como corporativa o personal, pero email_status recibe unknown. Esto evita deliberadamente miles de consultas MX bloqueantes durante una operación masiva.',
                'Si la columna client contiene un nombre, se busca por commercial_name. Si el cliente no existe, se crea automáticamente como activo y sin conexión a la API; el sector indicado también puede crearse. A continuación, el contacto se vincula con el cliente. Las etiquetas del contacto se sincronizan con este y se añaden además al cliente relacionado, sin eliminar las etiquetas que el cliente ya tuviera.',
            ],
            'examples' => [
                [
                    'title' => 'Resultados de la validación de una fila de contacto',
                    'code' => <<<'CODE'
empty full_name       → error
invalid email format  → error
duplicate email       → skipped
empty email           → allowed
unknown client name   → create active client, then link contact
CODE,
                ],
            ],
        ],
        [
            'id' => 'client-import-rules',
            'title' => 'Reglas de importación de clientes',
            'paragraphs' => [
                'Para un cliente, commercial_name es obligatorio y no puede estar vacío. La coincidencia del nombre comercial con la base de datos o con una fila anterior de la ejecución actual se marca como skipped. Un cliente nuevo recibe los datos fiscales y los campos de dirección de la asignación, is_active = 1 e is_web_connected = 0.',
                'El sector se busca por nombre y, si no existe, se crea automáticamente. Las etiquetas también se crean cuando es necesario y después se sincronizan con el nuevo cliente. En el valor tags se admiten como separadores la coma, el punto y coma y la barra vertical; los nombres repetidos en una fila se reducen a identificadores únicos.',
                'La columna contact funciona de forma más estricta: cada nombre ya debe existir en la base de datos. Los nombres se separan mediante coma, punto y coma o barra vertical. Si no se encuentra al menos un contacto, se revierte toda la fila del cliente. Los contactos no se crean automáticamente a partir de un único nombre, porque una ficha completa puede requerir datos adicionales y una identificación inequívoca.',
            ],
        ],
        [
            'id' => 'import-custom-fields',
            'title' => 'Campos personalizados durante la importación',
            'paragraphs' => [
                'El destino __custom significa que la cabecera de la columna de origen se utiliza como nombre del campo personalizado. El slug se genera mediante Illuminate\\Support\\Str::slug(); si no es posible obtenerlo, se utiliza un sufijo SHA-256 estable. Se busca un campo existente por la pareja entity_type + slug y, si no existe, se crea un campo nuevo, opcional y filtrable, con sort_order = 0.',
                'Se admiten los tipos text, textarea, number, date, email, url, select y checkbox. Para checkbox, los valores 1, yes, true y si se consideran verdaderos sin distinguir mayúsculas y minúsculas; todos los demás se convierten en 0. Number se convierte a float, date se guarda como cadena en value_date y el resto de tipos, en value_text. Durante la importación no se aplica una validación específica al correo electrónico, la URL, la fecha o las opciones de select.',
                'Si ya existe un campo con ese slug, el tipo seleccionado durante la importación no modifica su esquema: se utiliza el campo encontrado y su field_type real al guardar. Para un select nuevo no se crean automáticamente opciones en custom_field_options, por lo que un valor puede quedar guardado sin aparecer entre las opciones de la interfaz. Estos casos requieren una comprobación explícita al ampliar la importación.',
            ],
            'examples' => [
                [
                    'title' => 'Definición del campo creado',
                    'code' => <<<'PHP'
[
    'entity_type'  => 'contact',
    'name'         => 'Preferred language',
    'slug'         => 'preferred-language',
    'field_type'   => 'select',
    'is_required'  => 0,
    'is_filterable'=> 1,
    'sort_order'   => 0,
    'default_value'=> null,
]
PHP,
                ],
            ],
        ],
        [
            'id' => 'import-state-errors',
            'title' => 'Estados, transacciones y errores de importación',
            'paragraphs' => [
                'import_batches es el registro de la tarea. La secuencia normal de estados es uploaded → previewed → processing → completed o partial. Un UPDATE atómico en claimForProcessing() cambia a processing únicamente desde uploaded o previewed y protege el lote frente a una doble ejecución. El estado partial se asigna si existe al menos una fila omitida o un error; una excepción no controlada de todo el flujo produce failed.',
                'La transacción abarca una fila, no el archivo completo. Por tanto, un error de fila revierte la entidad creada, sus relaciones, etiquetas, sector y valores personalizados, pero las filas anteriores completadas correctamente permanecen en la base de datos. Ante un fallo global, un lote puede tener el estado failed y contener al mismo tiempo registros ya importados.',
                'Actualmente, import_rows solo se crea para skipped y error; las filas correctas y sus related_contact_id/related_client_id no se registran. import_errors duplica el mensaje del problema y se vincula con import_rows. La pantalla de errores devuelve un máximo de 500 registros junto con raw_data. En una nueva ejecución se limpiarían los detalles, pero los lotes finalizados y los bloqueados en processing no pueden volver a ejecutarse: no existen recuperación automática, heartbeat ni comando de reanudación.',
            ],
            'examples' => [
                [
                    'title' => 'Máquina de estados',
                    'code' => <<<'CODE'
uploaded ──preview──> previewed
   │                    │
   └──────process───────┴──> processing
                                  ├──> completed  (all rows imported)
                                  ├──> partial    (skipped or errors)
                                  └──> failed     (pipeline failure)

Terminal states are not retryable by the current UI or manager.
CODE,
                ],
            ],
        ],
        [
            'id' => 'export-pipeline',
            'title' => 'Flujo de exportación',
            'paragraphs' => [
                'La exportación comienza en /exports con la selección de la entidad, el conjunto de campos y el formato. ExportController normaliza entity y format, reúne los parámetros del formulario y los pasa a ExportManager. El manager obtiene las definiciones de los campos del sistema y personalizados, limpia la selección mediante una lista blanca, construye un plan de Query Builder y crea un registro export_batches con estado processing.',
                'ExportService no devuelve datos, sino un plan compuesto por el Builder y las cabeceras. ExportWriter llama a cursor() y pasa las filas secuencialmente a CSV u OpenSpout. Tras una escritura correcta, ExportRepository cambia el lote a completed y guarda el número de filas; una excepción lo cambia a failed y se propaga hacia arriba.',
                'Si el usuario no selecciona ningún campo válido, sanitizeFields() utiliza id. El orden de las columnas coincide con el orden de fields[] en POST. Los textos de las cabeceras proceden de fieldDefinitions(); por ello, al localizar la interfaz hay que tener en cuenta que los nombres actuales de los grupos y las columnas de exportación están definidos en inglés dentro de ExportService, no en los archivos de idioma.',
            ],
            'examples' => [
                [
                    'title' => 'Distribución de responsabilidades',
                    'code' => <<<'CODE'
ExportController  → HTTP input and download headers
ExportManager     → normalize, create batch, coordinate result
ExportService     → whitelist fields, compose Query Builder
ExportWriter      → iterate cursor, write CSV or XLSX
ExportRepository  → processing/completed/failed history
CODE,
                ],
            ],
        ],
        [
            'id' => 'export-query',
            'title' => 'Campos, relaciones y filtros de exportación',
            'paragraphs' => [
                'Los contactos pueden exportar los campos básicos, tags, client_names y todos los campos personalizados de la entidad contact. Los clientes pueden exportar campos básicos y de dirección, sector_name, tags, contact_count y los campos personalizados de client. Los nombres relacionados y las etiquetas se reúnen mediante subconsultas de agregación independientes con GROUP_CONCAT, mientras que los campos personalizados seleccionados se obtienen mediante MAX(CASE...) condicionales basados en sus identificadores numéricos.',
                'Las subconsultas y los JOIN solo se añaden para las columnas realmente seleccionadas. Los valores de los filtros se pasan mediante bindings del Query Builder, los nombres de los campos normales se toman de arrays fijos y los identificadores personalizados pasan primero por la lista blanca de definiciones. Las expresiones raw se limitan a los agregados GROUP_CONCAT y MAX(CASE...), por lo que la construcción dinámica de SELECT permanece dentro de límites controlados.',
                'El contrato interno admite filtros de contactos por nombre, correo electrónico y teléfono mediante LIKE, por la presencia de company, y por cliente y etiquetas. Para los clientes están disponibles commercial_name, legal_name, city, country, province, sector_id y las etiquetas. Seleccionar varias etiquetas significa coincidir con al menos una de ellas. La página /exports actual no muestra controles para estos filtros, por lo que el formulario estándar envía valores vacíos y exporta todos los registros de la entidad seleccionada.',
            ],
            'examples' => [
                [
                    'title' => 'Ejemplo de un plan de exportación de contactos',
                    'code' => <<<'SQL'
SELECT contacts.full_name,
       contacts.email,
       COALESCE(_tags_agg.tag_names, '') AS tags,
       COALESCE(_cfv_agg.cf_12, '') AS cf_12
FROM contacts
LEFT JOIN (...) _tags_agg ON _tags_agg.contact_id = contacts.id
LEFT JOIN (...) _cfv_agg  ON _cfv_agg.entity_id = contacts.id
WHERE contacts.full_name LIKE ?
ORDER BY contacts.id DESC
SQL,
                ],
            ],
        ],
        [
            'id' => 'export-writers',
            'title' => 'Generación de CSV y XLSX',
            'paragraphs' => [
                'El CSV se escribe directamente en php://output mediante fputcsv(): primero las cabeceras y después las filas del cursor() del Query Builder. Es la opción más eficiente en memoria. El XLSX se genera con OpenSpout: las cabeceras y las mismas filas se escriben secuencialmente en el flujo de salida sin acumular el libro completo en memoria. El tamaño de la exportación sigue estando limitado por la duración de la solicitud HTTP y el espacio temporal disponible en disco.',
                'Antes de escribir, cada celda de texto pasa por safeCell(). Los valores que empiezan por =, +, @ o por un signo menos seguido de una expresión no numérica reciben un apóstrofo inicial. Es una protección frente a la inyección de fórmulas: los datos de usuario exportados no deben convertirse en fórmulas al abrir el archivo en Excel o LibreOffice.',
                'Content-Type y Content-Disposition se envían antes de la generación real. Si se produce un error después de comenzar el flujo CSV o la escritura XLSX, el servidor ya no podrá devolver una página de error HTML normal; el usuario puede recibir un archivo parcial o dañado, mientras que export_batches se marcará como failed. Para exportaciones grandes o críticas es más seguro escribir primero en un archivo temporal no público, marcarlo como listo de forma atómica y solo después ofrecer la descarga.',
            ],
            'examples' => [
                [
                    'title' => 'Protección del contenido de una celda',
                    'code' => <<<'CODE'
=HYPERLINK("https://example.test")  → '=HYPERLINK("https://example.test")
@SUM(A1:A2)                          → '@SUM(A1:A2)
-125.50                              → -125.50
-cmd|' /C calc'!A0                   → '-cmd|' /C calc'!A0
CODE,
                ],
            ],
        ],
        [
            'id' => 'export-history-storage',
            'title' => 'Historial y almacenamiento de resultados',
            'paragraphs' => [
                'export_batches guarda el iniciador, la entidad, el formato, el nombre, el JSON de filtros, el JSON de los campos seleccionados, el número de filas, el estado y la hora de finalización. En la implementación actual, el campo stored_filename contiene únicamente el nombre propuesto al navegador. El CSV o XLSX no se guarda en disco, por lo que el historial constituye una auditoría de la operación y no un archivo que pueda volver a descargarse.',
                'La importación se comporta de otra forma: los archivos originales permanecen físicamente en storage/imports. El proyecto no incluye una política de eliminación automática para archivos, import_batches, import_rows, import_errors o export_batches. Para producción deben definirse el período de conservación, la limpieza de datos personales y el tamaño admisible del directorio, e implementarse mediante un comando cron independiente o un procedimiento administrativo.',
                'Al eliminar import_batches se borran en cascada sus filas y errores, pero la base de datos no puede eliminar el archivo del disco. La limpieza debe seleccionar primero con precisión los lotes caducados, eliminar de forma segura solo basename(stored_filename) dentro de storage/imports y después borrar el registro, o ejecutar las operaciones en un orden coordinado y con registro. La ruta de eliminación no debe construirse directamente a partir de un valor de base de datos sin validar.',
            ],
        ],
        [
            'id' => 'import-export-extension',
            'title' => 'Ampliación y comprobaciones obligatorias',
            'paragraphs' => [
                'Para añadir un campo del sistema a la importación hay que actualizar conjuntamente la plantilla, ImportMapping::fields(), los alias de suggest() y el procesador correspondiente. Una entidad nueva requiere un procesador independiente, un entity_type permitido en todos los controladores y managers, un valor ENUM o una migración de las tablas de historial, pestañas en la interfaz y pruebas del dominio. Un formato de archivo se añade mediante la validación de carga, el mapa MIME, el reader y el writer; ampliar únicamente accept en el HTML no modifica el comportamiento del servidor.',
                'Para la exportación, un campo nuevo se añade a fieldDefinitions() y a la rama correspondiente del constructor SQL. Nunca debe aceptarse un nombre SQL directamente desde POST. Una relación nueva debe agregarse a una sola fila por entidad, pues de otro modo el JOIN multiplicará los resultados. Los campos personalizados deben seguir vinculados a identificadores de la lista blanca realmente cargada.',
                'Las comprobaciones automáticas mínimas deben cubrir ambos formatos, el BOM y las filas CSV vacías, un archivo que solo contenga cabecera, cabeceras repetidas, un MIME incorrecto y el límite de tamaño; los campos obligatorios, duplicados y el rollback de relaciones y valores personalizados; todas las transiciones de batch status y el fallo global; el escape de fórmulas, una selección de columnas vacía, filtros, agregados, CSV grandes y falta de memoria con XLSX. También son necesarias pruebas para la ejecución simultánea de un mismo import batch y para dos importaciones con el mismo correo electrónico.',
            ],
            'examples' => [
                [
                    'title' => 'Lista de comprobación para modificar la importación',
                    'code' => <<<'CODE'
[ ] public_html/assets/templates/*.csv and *.xlsx
[ ] ImportMapping::fields() and suggest()
[ ] ContactImportProcessor or ClientImportProcessor
[ ] validation and duplicate semantics
[ ] transaction includes entity, relations and custom values
[ ] preview and result screens
[ ] import_batches status/counts and issue details
[ ] CSV + XLSX integration tests
[ ] retention and recovery behavior
CODE,
                ],
            ],
        ],
        [
            'id' => 'import-export-known-gaps',
            'title' => 'Limitaciones actuales y deuda técnica',
            'paragraphs' => [
                'La importación es síncrona, lee el archivo dos veces y no ofrece progreso, cancelación ni recuperación de un estado processing bloqueado. El XLSX se carga por completo en memoria. El CSV no detecta el delimitador ni la codificación. Las filas correctas no se registran por separado, y el límite de 500 registros de la pantalla de errores no incluye paginación ni exportación de un informe completo.',
                'La unicidad del correo electrónico del contacto y del commercial_name del cliente está garantizada por índices UNIQUE; las comprobaciones SELECT previas proporcionan mensajes comprensibles y permiten omitir antes la fila. Al crear un select personalizado no se crean sus opciones, y los valores number y date carecen de una validación estricta del dominio. No existe una limpieza automática de archivos de origen ni del historial.',
                'La exportación se ejecuta sin límite de filas y sin una tarea en segundo plano. CSV consume relativamente poca memoria, pero XLSX puede agotar memory_limit. Los filtros están implementados en la capa del servidor, pero no aparecen en el formulario actual. El historial no permite volver a descargar el resultado porque el archivo no se almacena. Estas propiedades son límites de la implementación actual, no un contrato prometido; al corregirlas deben actualizarse al mismo tiempo el esquema, la interfaz, los procedimientos operativos y esta documentación.',
            ],
        ],
    ],
];
