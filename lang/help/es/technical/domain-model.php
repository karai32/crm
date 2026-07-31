<?php

return [
    'title' => 'Modelo de dominio',
    'description' => 'Significado de las principales entidades de ContactCore, sus relaciones, estados y reglas de negocio que debe preservar el código de la aplicación.',
    'icon' => 'ph-arrow-elbow-down-right',
    'sections' => [
        [
            'id' => 'domain-purpose',
            'title' => 'Finalidad del modelo de dominio',
            'paragraphs' => [
                'El modelo de dominio no describe tablas ni pantallas, sino el significado de los datos con los que trabaja ContactCore. Responde a preguntas como: a quién denomina cliente y contacto el sistema, qué relaciones se permiten entre ellos, qué significan los estados de los registros y qué condiciones deben seguir siendo ciertas después de cualquier operación. La estructura de almacenamiento se explica en la sección «Base de datos»; aquí se analizan los mismos datos desde el punto de vista del negocio y del código de la aplicación.',
                'El proyecto actual no dispone de clases Entity ni de modelos ORM independientes: las entidades se transmiten entre controladores, servicios y repositorios como arrays asociativos. Por eso, las restricciones del dominio no están reunidas en un único objeto. Algunas reglas se encuentran en los controladores de la interfaz, otras en los procesadores de importación y la API y otras en las restricciones SQL. Al modificar el modelo, el desarrollador debe comprobar todas estas vías de entrada.',
                'La tarea principal de la plataforma es mantener una base de personas que han enviado solicitudes desde los sitios web de los clientes de nuestra organización. Por tanto, la cadena central es la siguiente: nuestra organización colabora con una empresa cliente, el sitio web del cliente recibe una solicitud y la persona que la envía se convierte en contacto y se relaciona con ese cliente.',
            ],
            'examples' => [
                [
                    'title' => 'Cadena principal del dominio',
                    'code' => "Nuestra organización\n        │ colabora con\n        ▼\nCliente — empresa para la que se recopilan solicitudes\n        │ recibe una solicitud mediante el sitio u otro canal\n        ▼\nContacto — persona que ha proporcionado sus datos\n        │ se clasifica además mediante\n        ├── etiquetas\n        └── campos personalizados",
                ],
            ],
        ],
        [
            'id' => 'domain-map',
            'title' => 'Mapa del modelo y sus límites',
            'paragraphs' => [
                'Client y Contact forman el núcleo del modelo. Client representa a una empresa que es cliente de nuestra organización. Contact representa a una persona dentro de la base común de contactos. No existe una relación de propiedad entre ellos: un contacto puede pertenecer a varios clientes y un cliente puede tener muchos contactos.',
                'Sector, Tag y CustomField complementan el núcleo. Un sector define una actividad del cliente. Las etiquetas permiten clasificar libremente clientes y contactos. Los campos personalizados amplían las fichas sin modificar la tabla principal. User, Role y Permission controlan quién puede realizar operaciones, pero no convierten al usuario en propietario de los registros que ha creado.',
                'Las importaciones, exportaciones, claves de API, registros de API y auditorías son modelos auxiliares. Describen el origen, la entrega y el historial de los datos, pero no sustituyen a las entidades de cliente y contacto. Por ejemplo, una fila importada puede hacer referencia al contacto creado; sin embargo, al terminar la importación, el propio Contact sigue siendo la fuente de verdad.',
            ],
            'examples' => [
                [
                    'title' => 'Mapa simplificado de entidades',
                    'code' => "                         Sector\n                            │ 0..1\n                            ▼\nTag N ─────────────── N Client N ─────────────── N Contact N ─────────────── N Tag\n                            │                          │\n                            └──── Campos personalizados ─────┘\n\nUser ── realiza operaciones, pero no es propietario de Client ni Contact\nImport / Export / API ── crean y transmiten las entidades del núcleo",
                ],
            ],
        ],
        [
            'id' => 'domain-client',
            'title' => 'Cliente',
            'paragraphs' => [
                'Un cliente es una empresa con la que trabaja nuestra organización y para la que se recopila una base de solicitudes. No es el visitante final de un sitio web ni la empresa en la que trabaja un contacto. El identificador principal del cliente es el id numérico; el nombre comercial obligatorio commercial_name se utiliza para mostrar y buscar, pero no es un identificador técnico fiable.',
                'La ficha contiene la razón social, el CIF, la dirección, el sitio web, notas y un sector. El cliente puede tener cualquier número de etiquetas, contactos y valores de campos personalizados. La mayoría de los datos son opcionales: la API y la importación de contactos pueden crear una ficha mínima de cliente solo con commercial_name para completarla después manualmente.',
                'is_active significa que actualmente existe una colaboración activa con el cliente. is_web_connected indica que los formularios de su sitio están configurados para enviar solicitudes a ContactCore. Estos indicadores son independientes: un cliente conectado mediante la API puede estar inactivo y uno activo puede no tener integración web. Los campos is_active_date e is_web_connected_date guardan la hora de la última modificación del indicador correspondiente, no la fecha de creación del cliente.',
                'El indicador is_web_connected solo describe el estado del cliente. No crea una clave de API, no define scopes ni bloquea peticiones. El acceso real de la integración se controla por separado mediante ApiKey y ApiAuthenticator.',
            ],
            'examples' => [
                [
                    'title' => 'Ficha mínima y ficha completa',
                    'code' => "Cliente mínimo válido\n  id: 42\n  commercial_name: Acme Studio\n\nFicha ampliada\n  legal_name, cif, address, postal_code\n  city, province, country, website, notes\n  sector_id\n  is_active, is_web_connected\n  tags[], contacts[], custom_fields{}",
                ],
            ],
        ],
        [
            'id' => 'domain-contact',
            'title' => 'Contacto',
            'paragraphs' => [
                'Un contacto es una persona cuyos datos han llegado a la plataforma, normalmente después de enviar una solicitud desde el sitio de un cliente, aunque también puede crearse manualmente, importarse o recibirse mediante la API. El contacto existe en una base común y no se copia por separado para cada cliente. full_name es obligatorio; email y phone pueden estar ausentes.',
                'El id numérico sigue siendo la identidad principal del contacto y debe utilizarse en claves foráneas y URL. Cuando está rellenado, email es único en toda la tabla contacts: la interfaz, la API y la importación realizan una comprobación previa comprensible y un índice UNIQUE protege la regla frente a peticiones concurrentes. Se permiten varios contactos sin correo, ya que NULL no entra en conflicto con otro NULL.',
                'EmailInspector determina por separado el tipo de la dirección y su estado técnico. is_corporate_email indica si el dominio se considera corporativo, mientras que email_status puede ser valid, invalid o unknown. valid significa que el formato es correcto y que se ha encontrado un registro MX, pero no confirma la existencia del buzón concreto. La comprobación DNS se desactiva durante las importaciones masivas, por lo que una dirección correctamente clasificada recibe unknown.',
                'company es un campo de texto normal que contiene el supuesto lugar de trabajo de la persona. Puede rellenarse manualmente o localizarse mediante Gemini para una dirección corporativa. No tiene una clave foránea a Client y no debe utilizarse en lugar de client_contacts. company_change_date registra una modificación automática o la confirmación manual del resultado de la IA.',
            ],
            'examples' => [
                [
                    'title' => 'Dos empresas distintas en los datos de un contacto',
                    'code' => "Contact #108\n  full_name: Ana García\n  company: Northwind Logistics     ← lugar de trabajo, texto libre\n  clients:                         ← formularios que originaron el contacto\n    - Acme Studio\n    - Contoso Events",
                ],
            ],
        ],
        [
            'id' => 'domain-client-contact',
            'title' => 'Relación entre cliente y contacto',
            'paragraphs' => [
                'Client y Contact mantienen una relación muchos a muchos mediante client_contacts. Este modelo es necesario porque una persona puede enviar solicitudes desde los sitios de varios clientes y un cliente suele tener muchos contactos. Eliminar una relación no elimina ni a la persona ni a la empresa.',
                'Al guardar la ficha de un contacto, syncClients sustituye todo el conjunto de relaciones: primero elimina las filas existentes y después escribe los client_id seleccionados. La acción masiva addClientsToContacts funciona de otra manera: solo añade las relaciones que faltan. El desarrollador debe elegir conscientemente la operación: sync significa «conjunto final completo» y add, «añadir al conjunto existente».',
                'client_contacts incluye relation_label e is_primary. Los repositorios leen estos valores, pero los formularios normales, la importación y la API crean actualmente la relación con sus valores predeterminados: relation_label = NULL e is_primary = 0. Estos atributos todavía no deben considerarse una función de usuario completa.',
            ],
            'examples' => [
                [
                    'title' => 'Semántica de las operaciones sobre relaciones',
                    'code' => "Antes: Contact #108 → [Client #4, Client #7]\n\nsyncClients(108, [7, 9])\nDespués: Contact #108 → [Client #7, Client #9]\n\naddClientsToContacts([108], [12])\nDespués: Contact #108 → [Client #7, Client #9, Client #12]",
                ],
            ],
        ],
        [
            'id' => 'domain-classification',
            'title' => 'Sectores y etiquetas',
            'paragraphs' => [
                'Un sector es un catálogo administrado de actividades. Un cliente puede tener como máximo un sector_id y un contacto no tiene sector propio. Cuando la interfaz filtra contactos por sector, este se determina a través de los clientes relacionados. Eliminar un sector utilizado se implementa como una desactivación, mientras que se permite eliminar uno que no se utiliza; la relación externa del cliente también admite NULL al eliminar el sector.',
                'Una etiqueta es un marcador flexible común. El mismo catálogo tags se utiliza para clientes y contactos, pero las asignaciones se guardan por separado. Que un cliente tenga una etiqueta no significa que sus contactos relacionados la reciban automáticamente, ni al contrario. Esta propagación solo sería válida como una regla de negocio independiente y explícitamente descrita.',
                'La API y la importación pueden crear automáticamente por nombre los sectores y etiquetas que falten. Por tanto, el nombre del catálogo participa realmente en la resolución de los datos de entrada. La comparación se realiza mediante una consulta a la base de datos y, una vez resuelto, el id sigue siendo la referencia estable; cambiar el nombre no debe romper las relaciones guardadas.',
            ],
            'examples' => [
                [
                    'title' => 'Cuándo utilizar un sector y cuándo una etiqueta',
                    'code' => "sector: Technology       ← una actividad relativamente estable del cliente\ntags: Hot, Newsletter     ← varios indicadores de trabajo modificables\n\nClient.sector_id → Sector.id\nClient ↔ Tag\nContact ↔ Tag\nContact ↛ Sector directamente",
                ],
            ],
        ],
        [
            'id' => 'domain-custom-fields',
            'title' => 'Campos personalizados como ampliación del modelo',
            'paragraphs' => [
                'CustomField describe un atributo adicional para client o contact. El par entity_type + slug es el nombre único del campo dentro de un tipo de entidad. El tipo determina la columna de valor: los tipos de texto y select utilizan value_text; number, value_number; date, value_date; y checkbox, value_bool.',
                'La definición del campo y su valor tienen ciclos de vida diferentes. Cambiar el nombre del campo no modifica automáticamente el slug ni debe provocar la pérdida de valores. Las opciones admitidas de select se encuentran en custom_field_options. default_value se aplica al crear un registro cuando no se proporciona ningún valor; is_filterable determina si el campo aparece en los filtros.',
                'is_required es una regla de la aplicación, no una restricción de la base de datos. Del mismo modo, custom_field_values utiliza el par polimórfico entity_type + entity_id sin una clave foránea a clients o contacts. Al eliminar la entidad principal, la base de datos no elimina estos valores en cascada. El código de eliminación y las comprobaciones de integridad deben tener en cuenta la limpieza de valores huérfanos.',
            ],
            'examples' => [
                [
                    'title' => 'Un valor lógico en el almacenamiento',
                    'code' => "CustomField\n  entity_type: contact\n  slug: employees\n  field_type: number\n\nCustomFieldValue\n  field_id: 15\n  entity_type: contact\n  entity_id: 108\n  value_number: 240\n  value_text/value_date/value_bool: NULL",
                ],
            ],
        ],
        [
            'id' => 'domain-entry-points',
            'title' => 'Canales de creación y reglas comunes',
            'paragraphs' => [
                'Las mismas entidades se crean por cuatro vías: la interfaz HTML, la importación, la API pública y herramientas internas como la IA. La preparación y la validación de transporte de los datos de entrada permanecen en los adaptadores correspondientes, pero la creación y actualización del registro principal, las etiquetas, las relaciones y los valores personalizados se centralizan en ContactWriteService y ClientWriteService. No deben utilizarse directamente los métodos create/update de sus repositorios fuera de estos servicios.',
                'Cada registro compuesto es atómico, independientemente del canal. La interfaz obtiene su propia transacción del servicio de escritura; un elemento de la API se une a la transacción del lote y una importación, a la transacción de la fila. Un error revierte el registro principal, sus relaciones, etiquetas y campos personalizados. Sin embargo, el lote completo de la API y la importación no son atómicos: los elementos o filas procesados correctamente con anterioridad se conservan.',
                'La API y la importación pueden crear entidades de catálogo a partir de su nombre. La API de contactos también crea un cliente mínimo si todavía no encuentra el nombre indicado. Este comportamiento útil forma parte del modelo de dominio, no solo del análisis de la petición: modificarlo afecta al número de clientes, la deduplicación y los informes.',
            ],
            'examples' => [
                [
                    'title' => 'Qué comprobar al añadir una regla nueva',
                    'code' => "[ ] HTML: Controller y formulario\n[ ] API: ApiService, formato de error y transacción\n[ ] Import: mapping, processor y error de fila\n[ ] Repository: consultas de lectura y escritura\n[ ] Export: campo nuevo y filtros\n[ ] Database: constraint, índice o cambio manual de estructura\n[ ] Help: descripción de usuario y técnica",
                ],
            ],
        ],
        [
            'id' => 'domain-lifecycle',
            'title' => 'Ciclo de vida y eliminación',
            'paragraphs' => [
                'Un cliente admite una desactivación lógica de negocio mediante is_active, pero la orden de eliminación ejecuta un DELETE físico. Los contactos no tienen un indicador de actividad independiente y su eliminación también es física. Por tanto, «ya no colaboramos» debe expresarse desactivando el cliente; la eliminación solo debe utilizarse cuando el registro realmente no deba permanecer en la base de trabajo.',
                'Al eliminar un cliente o contacto, la base de datos elimina en cascada las filas de client_contacts y las asignaciones de etiquetas correspondientes. Un contacto no se elimina junto con un cliente, ni un cliente junto con un contacto. Los valores de campos personalizados, la auditoría y algunas referencias de los registros utilizan enlaces polimórficos u opcionales y requieren una política de limpieza específica.',
                'created_at y updated_at describen el tiempo técnico de existencia de una fila. La estructura incluye created_by y updated_by para registrar la autoría de los cambios, pero ClientRepository y ContactRepository no los escriben actualmente. No debe interpretarse NULL como un autor del sistema sin comprobar la vía concreta de escritura.',
            ],
            'examples' => [
                [
                    'title' => 'Resultado de eliminar un cliente',
                    'code' => "DELETE Client #7\n  se elimina: Client #7\n  se eliminan: sus client_tags y client_contacts\n  se conserva: cada Contact relacionado\n  requiere control: custom_field_values(entity_type='client', entity_id=7)\n\nSi la colaboración simplemente ha terminado:\n  Client #7.is_active = 0",
                ],
            ],
        ],
        [
            'id' => 'domain-invariants',
            'title' => 'Invariantes para el desarrollo',
            'paragraphs' => [
                'Un invariante es una condición que debe ser cierta con independencia de cómo se modifiquen los datos. Los invariantes básicos de ContactCore son la presencia de commercial_name en un cliente y full_name en un contacto, un tipo válido de campo personalizado, la existencia de los id relacionados y la ausencia de duplicados en las tablas de relaciones. Deben utilizarse claves únicas y foráneas siempre que la regla sea realmente incondicional.',
                'La base de datos garantiza nombres no vacíos y normalizados, la unicidad del email rellenado de un contacto y del commercial_name de un cliente, valores booleanos válidos y como máximo un valor tipado por fila de custom_field_values. La obligatoriedad de un campo personalizado concreto permanece en la capa de aplicación y la relación de custom_field_values con la entidad principal es polimórfica. Estas reglas restantes no deben darse por garantizadas: hay que respetarlas en el servicio común de escritura y cubrirlas con pruebas.',
                'Las funciones nuevas deben basarse en id, distinguir explícitamente entre Client y contact.company, tener en cuenta la relación muchos a muchos y mantener la coherencia de una operación compuesta mediante una transacción. Si una regla es igual para la interfaz, la API y la importación, debe residir en un servicio de dominio reutilizable; los controladores y procesadores solo deben adaptar los datos de entrada.',
            ],
            'examples' => [
                [
                    'title' => 'Límite recomendado de una operación de creación de contacto',
                    'code' => "BEGIN\n  validate Contact\n  resolve or create referenced Client records\n  resolve or create Tag records\n  INSERT contacts\n  SYNC client_contacts\n  SYNC contact_tags\n  SAVE custom_field_values\nCOMMIT\n\nAnte cualquier error → ROLLBACK de toda la operación",
                ],
            ],
        ],
    ],
];
