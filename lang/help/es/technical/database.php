<?php

return [
    'title' => 'Base de datos',
    'description' => 'Estructura MySQL, relaciones entre entidades, integridad de los datos y reglas para modificar el modelo.',
    'icon' => 'ph-arrow-elbow-down-right',
    'sections' => [
        [
            'id' => 'database-overview',
            'title' => 'Finalidad y estructura de la base de datos',
            'paragraphs' => [
                'ContactCore utiliza una única base de datos relacional MySQL o MariaDB. La estructura inicial se encuentra en database/schema.sql y crea 21 tablas. Todas utilizan InnoDB, la codificación utf8mb4 y la intercalación utf8mb4_unicode_ci, lo que proporciona transacciones, claves foráneas y un almacenamiento correcto de texto multilingüe.',
                'La base de datos puede dividirse en cinco áreas: usuarios y acceso; clientes, contactos y clasificación; campos personalizados; historial de importaciones y exportaciones; y API y registros técnicos. No son bases independientes ni módulos aislados: existen claves foráneas y relaciones de aplicación entre ellas.',
                'La aplicación no utiliza modelos Eloquent: los repositorios, la autorización, los informes y la exportación construyen las consultas de manera uniforme mediante Illuminate Database Query Builder y devuelven arrays asociativos. Las expresiones raw solo se emplean para funciones de MySQL como GROUP_CONCAT, CASE y otras funciones especiales. El controlador está configurado con ATTR_STRINGIFY_FETCHES, por lo que los valores numéricos de un SELECT pueden llegar como cadenas; el código convierte explícitamente a int los identificadores, contadores e indicadores cuando resulta importante.',
            ],
            'examples' => [
                [
                    'title' => 'Grupos de tablas',
                    'code' => 'Acceso
  roles, users, user_permissions, user_preferences

Datos principales
  sectors, clients, contacts
  tags, client_tags, contact_tags, client_contacts

Campos personalizados
  custom_fields, custom_field_options, custom_field_values

Intercambio de datos
  import_batches, import_rows, import_errors, export_batches

Integraciones e historial
  api_keys, api_logs, audit_logs',
                ],
            ],
        ],
        [
            'id' => 'database-relations-map',
            'title' => 'Mapa de las relaciones principales',
            'paragraphs' => [
                'clients y contacts constituyen el centro del modelo de dominio. Un cliente representa una organización y un contacto, una persona que ha enviado una solicitud. Un contacto puede estar relacionado con varios clientes y un cliente puede tener varios contactos, por lo que la relación se guarda en la tabla independiente client_contacts.',
                'Un sector se asigna directamente a un cliente mediante una relación de uno a muchos. Las etiquetas son comunes para clientes y contactos, pero utilizan dos tablas de relación diferentes. Los campos personalizados se definen por separado y sus valores se relacionan con un cliente o contacto mediante el par entity_type + entity_id.',
                'Un usuario puede crear y modificar los registros principales y ejecutar importaciones y exportaciones. Al eliminar un usuario no se eliminan los datos de negocio ni el historial: las claves foráneas created_by, updated_by y user_id de las tablas de registros pasan a NULL.',
            ],
            'examples' => [
                [
                    'title' => 'Diagrama ER simplificado',
                    'code' => 'roles 1 ─────── N users
                    ├── N user_permissions
                    └── N user_preferences

sectors 1 ───── N clients
                    │
                    N
              client_contacts
                    N
                    │
                contacts

clients  N ── client_tags  ── N tags
contacts N ── contact_tags ── N tags

custom_fields 1 ── N custom_field_options
custom_fields 1 ── N custom_field_values

users 1 ── N import_batches ── N import_rows ── N import_errors
users 1 ── N export_batches
api_keys 1 ── N api_logs',
                ],
            ],
        ],
        [
            'id' => 'database-conventions',
            'title' => 'Tipos, identificadores y campos temporales',
            'paragraphs' => [
                'Las entidades normales utilizan INT UNSIGNED AUTO_INCREMENT. Los registros de crecimiento rápido, los lotes de importación y los valores de campos personalizados utilizan BIGINT UNSIGNED. Una clave foránea debe tener el mismo tamaño y el mismo atributo UNSIGNED que la clave primaria relacionada; si los tipos no coinciden, no se podrá crear el constraint.',
                'Los valores booleanos se guardan como TINYINT(1), los conjuntos limitados de estados como ENUM y las estructuras de parámetros variables como JSON. ENUM es cómodo para un estado fijo, pero añadir un valor nuevo requiere cambiar la estructura. JSON solo se utiliza cuando la estructura es realmente variable: mapping de importación, filtros de exportación, scopes e instantáneas de auditoría.',
                'created_at suele rellenarse con CURRENT_TIMESTAMP y updated_at se modifica automáticamente mediante ON UPDATE CURRENT_TIMESTAMP. Las fechas de dominio como last_login_at, started_at y finished_at son DATETIME y las establece la aplicación. La hora del servidor, PHP y MySQL debe estar coordinada; de lo contrario, los filtros y los informes mostrarán periodos desplazados.',
            ],
            'examples' => [
                [
                    'title' => 'Estructura habitual de una tabla',
                    'code' => 'CREATE TABLE projects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(190) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_projects_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_projects_name (name),
    INDEX idx_projects_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;',
                ],
            ],
        ],
        [
            'id' => 'database-users',
            'title' => 'Usuarios, roles y configuración',
            'paragraphs' => [
                'roles contiene los roles del sistema admin y user. users guarda el nombre, un correo electrónico único, password_hash, el estado de actividad y la fecha del último acceso. La contraseña nunca se almacena como texto sin cifrar: PHP la genera mediante password_hash() y el inicio de sesión la comprueba con password_verify(). ON DELETE RESTRICT impide eliminar un rol que esté en uso.',
                'user_permissions guarda la decisión individual para cada permission_key. La PRIMARY KEY compuesta (user_id, permission_key) impide crear dos valores del mismo permiso para un usuario. Al eliminar un usuario, sus permisos se eliminan en cascada.',
                'user_preferences es un almacenamiento key-value ampliable para la configuración de la interfaz. Actualmente, la aplicación utiliza la clave per_page. El par único user_id + preference_key permite utilizar INSERT ... ON DUPLICATE KEY UPDATE. La configuración no debe mezclarse con los permisos: una preference afecta a la comodidad de la interfaz y un permission, al acceso a una operación.',
            ],
            'examples' => [
                [
                    'title' => 'Usuario y sus permisos explícitos',
                    'code' => 'SELECT
    u.id,
    u.name,
    u.email,
    r.name AS role,
    up.permission_key,
    up.is_allowed
FROM users u
INNER JOIN roles r ON r.id = u.role_id
LEFT JOIN user_permissions up ON up.user_id = u.id
WHERE u.id = :user_id
ORDER BY up.permission_key;',
                ],
            ],
        ],
        [
            'id' => 'database-clients-contacts',
            'title' => 'Clientes y contactos',
            'paragraphs' => [
                'clients guarda una organización: el nombre comercial y la razón social, el CIF, la dirección, el sitio web, el sector, las notas y dos estados independientes: la colaboración activa y la conexión del sitio mediante la API. Los campos is_active_date e is_web_connected_date registran el momento en que cambió el estado correspondiente.',
                'contacts guarda una persona y los medios de contacto disponibles. company es el nombre de una empresa introducido manualmente o obtenido mediante Gemini y no sustituye la relación con clients. is_corporate_email y email_status son el resultado de clasificar la dirección; NULL significa que no hay resultado y unknown, que se ha clasificado sin una comprobación MX en tiempo real.',
                'created_by y updated_by indican el usuario que realizó la acción desde la interfaz cuando se dispone de ese contexto. ON DELETE SET NULL conserva el propio registro al eliminar el usuario. contacts.email cuando está rellenado y clients.commercial_name tienen índices UNIQUE; las comprobaciones previas de la aplicación mejoran el mensaje de error, pero MySQL garantiza el invariante final.',
            ],
            'examples' => [
                [
                    'title' => 'Contactos de un cliente seleccionado',
                    'code' => 'SELECT
    c.id,
    c.full_name,
    c.email,
    c.phone,
    cc.relation_label,
    cc.is_primary
FROM client_contacts cc
INNER JOIN contacts c ON c.id = cc.contact_id
WHERE cc.client_id = :client_id
ORDER BY cc.is_primary DESC, c.full_name ASC;',
                ],
            ],
        ],
        [
            'id' => 'database-classification',
            'title' => 'Sectores, etiquetas y tablas de relación',
            'paragraphs' => [
                'sectors es el catálogo de actividades de los clientes. clients.sector_id admite NULL y la eliminación de un sector utiliza ON DELETE SET NULL, por lo que el cliente se conserva sin clasificación. En la práctica, el repositorio intenta desactivar un sector utilizado en lugar de eliminarlo para preservar el significado de los datos históricos.',
                'tags es un catálogo común de etiquetas flexibles. Las relaciones contact_tags y client_tags implementan many-to-many. Sus claves primarias compuestas actúan también como restricción única: una misma etiqueta no puede asignarse dos veces a una entidad. Los índices inversos por tag_id aceleran la selección de todos los clientes o contactos con una etiqueta.',
                'client_contacts también es una relación many-to-many, pero contiene propiedades de la propia relación: relation_label e is_primary. La PRIMARY KEY (client_id, contact_id) permite una sola relación para cada par concreto. Si una misma persona debe tener dos funciones para un cliente, actualmente deben describirse en un único relation_label o modificarse el modelo.',
            ],
            'examples' => [
                [
                    'title' => 'Clientes con etiquetas sin duplicar filas',
                    'code' => 'SELECT
    c.id,
    c.commercial_name,
    GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR \', \') AS tags
FROM clients c
LEFT JOIN client_tags ct ON ct.client_id = c.id
LEFT JOIN tags t ON t.id = ct.tag_id
GROUP BY c.id, c.commercial_name
ORDER BY c.commercial_name;',
                ],
            ],
        ],
        [
            'id' => 'database-custom-fields',
            'title' => 'Modelo de campos personalizados',
            'paragraphs' => [
                'Los campos personalizados se implementan como un modelo EAV tipado. custom_fields describe el campo, su entidad, slug, tipo, obligatoriedad, filtrabilidad, valor predeterminado y orden. UNIQUE (entity_type, slug) permite utilizar el mismo slug para un cliente y un contacto, pero no repetirlo dentro de una misma entidad.',
                'custom_field_options guarda las opciones permitidas de select. Al eliminar la definición de un campo, las opciones y todos los valores se eliminan en cascada. custom_field_values contiene una fila por combinación de field_id + entity_type + entity_id. En función de field_type solo se rellena una columna: value_text, value_number, value_date o value_bool. El repositorio guarda el valor mediante ON DUPLICATE KEY UPDATE.',
                'entity_type + entity_id es una referencia polimórfica: una misma columna entity_id puede representar contacts.id o clients.id. MySQL no puede crear una clave foránea a dos tablas a la vez, por lo que no existe un constraint a la propia entidad. La base no impide que el tipo del campo y el del valor no coincidan ni que queden valores huérfanos después de eliminar un cliente o contacto; es responsabilidad del código de los servicios y de las comprobaciones periódicas.',
                'is_filterable no crea automáticamente un índice independiente. El indicador solo permite mostrar el campo en los filtros de la interfaz; los índices compuestos field_id + valor tipado proporcionan el rendimiento. Existe un FULLTEXT para el texto, pero los filtros actuales de los repositorios utilizan LIKE.',
            ],
            'examples' => [
                [
                    'title' => 'Cómo se guarda el campo language de un contacto',
                    'code' => '-- Definición
INSERT INTO custom_fields
    (entity_type, name, slug, field_type, is_filterable)
VALUES
    (\'contact\', \'Idioma\', \'language\', \'text\', 1);

-- Valor para contacts.id = 125
INSERT INTO custom_field_values
    (field_id, entity_type, entity_id, value_text)
VALUES
    (:language_field_id, \'contact\', 125, \'es\')
ON DUPLICATE KEY UPDATE value_text = VALUES(value_text);',
                ],
                [
                    'title' => 'Búsqueda de valores huérfanos',
                    'code' => 'SELECT cfv.*
FROM custom_field_values cfv
LEFT JOIN contacts c
    ON cfv.entity_type = \'contact\' AND c.id = cfv.entity_id
LEFT JOIN clients cl
    ON cfv.entity_type = \'client\' AND cl.id = cfv.entity_id
WHERE (cfv.entity_type = \'contact\' AND c.id IS NULL)
   OR (cfv.entity_type = \'client\' AND cl.id IS NULL);',
                ],
            ],
        ],
        [
            'id' => 'database-import-export',
            'title' => 'Importación y exportación',
            'paragraphs' => [
                'import_batches es la cabecera de una carga: usuario, nombre original y guardado del archivo, formato, tipo de entidad, status, contadores y asignación JSON de las columnas. Los estados forman el ciclo uploaded → previewed → processing → completed o partial; failed se utiliza para un error general. El UPDATE condicional de claimForProcessing impide que dos peticiones se apropien simultáneamente del mismo lote.',
                'import_rows e import_errors contienen información de diagnóstico por filas. El proceso actual escribe en import_rows principalmente las filas omitidas y erróneas junto con raw_data, mientras que import_errors proporciona una lista independiente de mensajes. Al eliminar un lote se eliminan en cascada sus filas y errores; eliminar el contacto o cliente creado solo establece related_*_id en NULL.',
                'export_batches guarda el historial de generación de una exportación: filtros y campos seleccionados en JSON, nombre, formato, número de filas, estado y hora de finalización. Actualmente, CSV/XLSX se envía directamente a php://output; stored_filename es el nombre de descarga y el registro del historial, no una garantía de que exista un archivo preparado en el disco.',
            ],
            'examples' => [
                [
                    'title' => 'Estados de una importación',
                    'code' => 'uploaded
   │
   ├── previewed ──┐
   │               │
   └───────────────┴── processing
                           │
                  ┌────────┼────────┐
                  ▼        ▼        ▼
              completed  partial  failed',
                ],
                [
                    'title' => 'Adquisición segura de un lote',
                    'code' => 'UPDATE import_batches
SET status = \'processing\', started_at = NOW()
WHERE id = :id
  AND status IN (\'uploaded\', \'previewed\');

-- Solo inicia el procesamiento el proceso cuyo rowCount() === 1.',
                ],
            ],
        ],
        [
            'id' => 'database-api',
            'title' => 'Claves de API y registro de peticiones',
            'paragraphs' => [
                'api_keys guarda el nombre de la integración, un client_id único, el hash SHA-256 del secret, un array JSON de scopes, el estado de actividad y las fechas de uso o revocación. El secret sin cifrar solo se muestra al crearlo y no se escribe en la tabla. Se comprueba mediante hash_equals, por lo que no es posible recuperar de la base un secret perdido: hay que emitir una clave nueva.',
                'api_logs recibe una fila por cada petición de la API, incluidas las autenticaciones fallidas. request_id es único y se devuelve al cliente en X-Request-Id. El registro contiene el método, la ruta lógica, el estado, el código de error, la duración, la IP, el origin y los cuerpos truncados de la petición y la respuesta. El código limita cada cuerpo a unos 64 KB.',
                'Al eliminar una clave de API, api_key_id pasa a NULL en el registro, pero se conservan request_id y los demás datos. Todavía no existe una política automática de eliminación de api_logs, por lo que en producción debe definirse un periodo de conservación teniendo en cuenta el volumen, las necesidades de diagnóstico y los requisitos sobre datos personales.',
            ],
            'examples' => [
                [
                    'title' => 'Errores de la API durante el último día',
                    'code' => 'SELECT
    request_id,
    method,
    path,
    response_status,
    error_code,
    duration_ms,
    created_at
FROM api_logs
WHERE response_status >= 400
  AND created_at >= NOW() - INTERVAL 1 DAY
ORDER BY id DESC;',
                ],
            ],
        ],
        [
            'id' => 'database-audit',
            'title' => 'Auditoría de cambios',
            'paragraphs' => [
                'La tabla audit_logs está prevista para guardar el historial de acciones de los usuarios: action, tipo e ID de la entidad, valores anteriores y nuevos en JSON, IP, user agent y hora. La clave foránea al usuario utiliza SET NULL para que el historial sobreviva a la eliminación de la cuenta.',
                'Importante: el código actual no contiene un AuditRepository ni un servicio que escriba filas en audit_logs. La existencia de la tabla no significa que los cambios de clientes y contactos ya se estén auditando. Hasta que se implemente la escritura, estos datos no pueden utilizarse para investigar las acciones de un usuario.',
                'Una implementación correcta debe escribir la auditoría en la misma transacción que modifica la entidad o mediante una cola garantizada. Solo deben guardarse los campos necesarios y hay que ocultar contraseñas, secrets de API y otros valores sensibles. Un error de auditoría no debe crear silenciosamente una falsa sensación de disponer de un historial completo.',
            ],
            'examples' => [
                [
                    'title' => 'Posible registro de auditoría',
                    'code' => 'INSERT INTO audit_logs (
    user_id, action, entity_type, entity_id,
    old_values, new_values, ip_address, user_agent
) VALUES (
    :user_id, \'contact.updated\', \'contact\', :contact_id,
    :old_values_json, :new_values_json, :ip, :user_agent
);',
                ],
            ],
        ],
        [
            'id' => 'database-integrity',
            'title' => 'Claves foráneas y reglas de eliminación',
            'paragraphs' => [
                'CASCADE se utiliza para datos dependientes que no tienen sentido sin su propietario: user_permissions, user_preferences, relaciones de etiquetas, client_contacts, opciones de campos, filas importadas y errores. SET NULL se aplica a referencias históricas: el autor de un registro, el usuario de un lote, el sector de un cliente, un objeto creado mediante importación y una clave de API en el registro.',
                'RESTRICT protege un rol del sistema mientras tenga usuarios relacionados. UNIQUE garantiza la unicidad de negocio: el correo de un usuario y contacto, commercial_name de un cliente, name y slug de los catálogos, client_id de la API, request_id del registro y los pares compuestos de las relaciones. CHECK prohíbe cadenas clave vacías o sin normalizar, indicadores booleanos no válidos y el almacenamiento simultáneo de varios valores tipados en custom_field_values.',
                'No todas las reglas de dominio se encuentran en constraints. No se limita a uno el contacto primary de un cliente, la aplicación comprueba la obligatoriedad de un campo personalizado y custom_field_values, al ser polimórfico, no tiene una clave foránea a la entidad. Estos invariantes deben respetarse explícitamente al realizar cambios SQL directos o crear repositorios.',
            ],
            'examples' => [
                [
                    'title' => 'Principales consecuencias de la eliminación',
                    'code' => 'DELETE users
  → CASCADE: user_permissions, user_preferences
  → SET NULL: created_by, updated_by, import/export user_id, audit user_id

DELETE clients o contacts
  → CASCADE: client_contacts y las relaciones tag correspondientes
  → custom_field_values NO se limpia mediante una clave foránea

DELETE custom_fields
  → CASCADE: custom_field_options, custom_field_values

DELETE api_keys
  → SET NULL: api_logs.api_key_id, el registro se conserva',
                ],
            ],
        ],
        [
            'id' => 'database-indexes',
            'title' => 'Índices, búsqueda y rendimiento',
            'paragraphs' => [
                'Las claves primarias y únicas son índices automáticamente. Otros índices B-tree cubren las claves foráneas, los estados, las fechas y los campos de filtro utilizados con frecuencia. En las tablas de relación, una PRIMARY KEY compuesta funciona bien desde la primera columna y un índice independiente sobre la segunda proporciona la dirección inversa de búsqueda.',
                'contacts y custom_field_values tienen índices FULLTEXT, pero los repositorios actuales no utilizan MATCH ... AGAINST: la búsqueda de texto se realiza mediante LIKE con el patrón %valor%. Normalmente, este patrón no utiliza un índice B-tree normal. Es aceptable en una base pequeña, pero a medida que crezca el número de contactos habrá que medir la búsqueda mediante EXPLAIN ANALYZE y, si es necesario, migrarla a FULLTEXT o a un servicio de búsqueda independiente.',
                'Los índices se crean para consultas concretas, no para todas las columnas. Los índices innecesarios ocupan espacio y ralentizan INSERT/UPDATE. En un índice compuesto importa el orden de las columnas: idx_api_logs_key_created resulta útil para WHERE api_key_id = ? ORDER BY created_at, pero no sustituye a un índice que comience por created_at para un intervalo temporal general.',
            ],
            'examples' => [
                [
                    'title' => 'Comprobación del plan de una consulta',
                    'code' => 'EXPLAIN ANALYZE
SELECT id, full_name, email
FROM contacts
WHERE created_at >= \'2026-01-01 00:00:00\'
  AND email_status = \'valid\'
ORDER BY created_at DESC
LIMIT 50;',
                ],
                [
                    'title' => 'FULLTEXT que todavía no utiliza la aplicación',
                    'code' => 'SELECT id, full_name, email, phone
FROM contacts
WHERE MATCH(full_name, email, phone)
      AGAINST(:query IN NATURAL LANGUAGE MODE)
ORDER BY created_at DESC
LIMIT 50;',
                ],
            ],
        ],
        [
            'id' => 'database-transactions',
            'title' => 'Transacciones y cambios concurrentes',
            'paragraphs' => [
                'Una transacción debe abarcar todo el invariante de negocio. Si se crea un contacto y después sus relaciones con clientes, etiquetas y campos personalizados, el commit solo puede realizarse cuando todos los pasos han terminado correctamente. De lo contrario, una excepción podría dejar un objeto parcialmente creado.',
                'Todas las consultas utilizan una única conexión de Illuminate Database. Las operaciones compuestas deben ejecutarse mediante Database::transaction(): el helper abre y finaliza la transacción si es su propietario, o añade el callback a una transacción ya abierta por un lote de la API o una fila de importación. Una excepción provoca automáticamente el rollback del propietario y vuelve a propagarse.',
                'Una transacción por sí sola no impide que se tomen dos decisiones simultáneas basadas en datos obsoletos. Para adquirir un trabajo se debe utilizar un UPDATE condicional y rowCount(), como en la importación; para una edición estricta, SELECT ... FOR UPDATE u optimistic locking mediante una versión/updated_at. La unicidad debe garantizarse preferentemente con un índice UNIQUE y el conflicto debe tratarse como un error esperado.',
            ],
            'examples' => [
                [
                    'title' => 'Límite de una transacción en un servicio',
                    'code' => 'Database::transaction(function (): int {
    $contactId = $this->contacts->create($contact);
    $this->contacts->syncClients($contactId, $clientIds);
    $this->entityTags->sync(\'contact\', $contactId, $tagIds);
    $this->customFields->saveValues(\'contact\', $contactId, $fields, $values);

    return $contactId;
});',
                ],
            ],
        ],
        [
            'id' => 'database-schema-changes',
            'title' => 'Cambios de estructura',
            'paragraphs' => [
                'database/schema.sql es una instantánea completa para una instalación limpia. Al principio desactiva la comprobación de claves foráneas y ejecuta DROP TABLE, por lo que utilizar este archivo sobre una base de datos en producción destruiría los datos. El proyecto no dispone de un sistema de migraciones, archivos SQL de actualización sucesivos ni una tabla de versiones. El administrador modifica manualmente una base existente mediante un cliente SQL.',
                'Cada cambio de estructura se prepara y comprueba primero sobre una copia de la base; después se aplica manualmente en producción y se registra en un historial de despliegue externo. Tras comprobarlo, database/schema.sql se actualiza para que las instalaciones nuevas reciban la estructura actual. La instantánea actual ya contiene los valores fail-closed de los permisos, índices UNIQUE y restricciones CHECK.',
                'Antes de ejecutar ALTER TABLE, haga una copia de seguridad, evalúe el tamaño y el bloqueo de la tabla y prepare la compatibilidad del código para un despliegue por etapas. DDL en MySQL puede realizar un commit implícito, por lo que no debe suponerse que un START TRANSACTION normal revertirá con seguridad un cambio de estructura. El rollback debe describirse por separado y probarse en una base de datos de prueba.',
            ],
            'examples' => [
                [
                    'title' => 'Fuente de la estructura actual',
                    'code' => 'database/
└── schema.sql    # estructura completa para una instalación limpia',
                ],
                [
                    'title' => 'Ejemplo de cambio manual de estructura',
                    'code' => 'ALTER TABLE contacts
    ADD COLUMN source VARCHAR(100) NULL AFTER company,
    ADD INDEX idx_contacts_source (source);

-- Después de comprobar la aplicación, el mismo campo se añade al schema.sql actual.',
                ],
            ],
        ],
        [
            'id' => 'database-development',
            'title' => 'Trabajo del desarrollador con la base de datos',
            'paragraphs' => [
                'Una modificación del modelo comienza por la estructura y los flujos de datos; después se actualizan Repository, Service, Controller, la API, la importación, la exportación y las vistas. Una columna nueva rara vez se limita a un SELECT: compruebe la creación, edición, filtrado, acciones masivas, formato de la API y restauración desde una copia de seguridad.',
                'Para el diagnóstico, utilice SHOW CREATE TABLE, SHOW INDEX, INFORMATION_SCHEMA, EXPLAIN ANALYZE y consultas SELECT precisas. No corrija manualmente datos de producción sin guardar la consulta, ejecutar primero un SELECT y disponer de una copia de seguridad. Un UPDATE masivo debe probarse primero como SELECT con el mismo WHERE, dentro de una transacción o sobre una copia de la base.',
                'Los datos de prueba no deben contener información personal real. Una instantánea de la base de producción destinada al desarrollo debe anonimizarse: el correo, los teléfonos, los nombres, las IP, request_body, response_body y los valores de campos personalizados pueden contener datos personales.',
            ],
            'examples' => [
                [
                    'title' => 'Comprobación de la estructura antes de modificarla',
                    'code' => 'SHOW CREATE TABLE contacts;
SHOW INDEX FROM contacts;

SELECT
    TABLE_ROWS,
    DATA_LENGTH,
    INDEX_LENGTH
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = \'contacts\';',
                ],
                [
                    'title' => 'Lista de comprobación para cambiar el modelo',
                    'code' => '[ ] cambio manual comprobado sobre una copia de la base
[ ] base de producción actualizada y cambio registrado externamente
[ ] database/schema.sql actualizado para una instalación limpia
[ ] tipos compatibles de claves foráneas
[ ] restricciones UNIQUE, FOREIGN KEY e índices necesarios
[ ] Repository y límite transaccional del Service
[ ] formularios, filtros, API, importación y exportación
[ ] tratamiento de la eliminación y NULL
[ ] prueba con datos existentes y vacíos
[ ] copia de seguridad y procedimiento de rollback claro',
                ],
            ],
        ],
        [
            'id' => 'database-health',
            'title' => 'Comprobación de la integridad y mantenimiento',
            'paragraphs' => [
                'Compruebe periódicamente el crecimiento de las tablas grandes: contacts, custom_field_values, import_rows, import_errors y api_logs. Debe existir una política de conservación acordada para los registros y las importaciones. El historial se elimina en lotes pequeños y solo después de entender los cascades, para evitar bloqueos prolongados y un gran aumento del binary log.',
                'CHECK TABLE no sustituye a las comprobaciones lógicas. Busque por separado valores polimórficos huérfanos, permission_key desconocidos, import_batches bloqueados en el estado processing y registros de API sin una política de conservación. Después de eliminaciones grandes, evalúe las tablas y los índices, pero no ejecute automáticamente OPTIMIZE TABLE sobre tablas grandes de producción sin una ventana de mantenimiento.',
                'Una copia de seguridad solo se considera funcional después de probar su restauración. Para obtener un dump coherente de InnoDB se utiliza mysqldump --single-transaction; la copia debe guardarse fuera del servidor de la aplicación. La restauración debe comprobarse junto con una versión compatible del código y la configuración.',
            ],
            'examples' => [
                [
                    'title' => 'Varias comprobaciones lógicas',
                    'code' => '-- Importaciones bloqueadas durante más de dos horas
SELECT id, original_filename, started_at
FROM import_batches
WHERE status = \'processing\'
  AND started_at < NOW() - INTERVAL 2 HOUR;

-- Las claves de permisos desconocidas deben compararse con Auth::permissionDefinitions()
SELECT DISTINCT permission_key
FROM user_permissions
ORDER BY permission_key;

-- Tamaño de las tablas de crecimiento más rápido
SELECT TABLE_NAME, TABLE_ROWS, DATA_LENGTH, INDEX_LENGTH
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
ORDER BY DATA_LENGTH + INDEX_LENGTH DESC;',
                ],
            ],
        ],
    ],
];
