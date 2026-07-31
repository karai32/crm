<?php

return array (
  'id' => 'api',
  'title' => 'API',
  'description' => 'Conexión de sistemas externos con los datos y las operaciones de ContactCore.',
  'icon' => 'ph-plugs-connected',
  'sections' =>
  array (
    0 =>
    array (
      'id' => 'api-purpose',
      'title' => 'Finalidad de la API',
      'paragraphs' =>
      array (
        0 => 'La API permite que sitios web y aplicaciones externas trabajen con los datos de ContactCore sin abrir la interfaz del CRM. A través de ella se pueden crear, consultar, modificar y eliminar contactos y clientes. Los sectores y las etiquetas se transmiten como campos de estos registros, pero no tienen endpoints independientes. El intercambio se realiza mediante HTTPS, los datos se envían en formato JSON y todas las rutas de la versión actual comienzan por /api/v1.',
        1 => 'El principal caso práctico es la conexión de formularios de los sitios web de clientes. Después de enviar el formulario, el sitio transmite la solicitud a ContactCore, donde la persona se crea como contacto y se relaciona con el cliente correspondiente. De este modo, las solicitudes de distintos sitios web se recopilan en una sola base de datos, pero conservan la relación con el proyecto del que proceden.',
        2 => 'La API también es adecuada para sincronizar servicios internos, cargar registros por lotes y obtener datos para informes. Utiliza las mismas fichas, relaciones, etiquetas y campos personalizados que la interfaz habitual: no se crea una base de datos paralela independiente para las integraciones.',
      ),
    ),
    1 =>
    array (
      'id' => 'api-forms',
      'title' => 'Conexión de formularios web',
      'paragraphs' =>
      array (
        0 => 'Para transmitir una solicitud se utiliza una petición POST a /api/v1/contacts. El único campo obligatorio es full_name. No es necesario enviar email, pero, si se incluye, debe tener un formato válido y no pertenecer ya a otro contacto. También se pueden enviar el teléfono, la empresa en la que trabaja la persona, el cliente, las etiquetas y los valores de campos personalizados creados previamente.',
        1 => 'El campo company indica el lugar de trabajo del propio contacto. Para especificar el sitio o proyecto desde el que llegó la solicitud se utiliza el campo clients, que contiene el nombre comercial del cliente de ContactCore. Si el cliente todavía no existe, la API crea automáticamente una ficha mínima, aunque es más fiable crearlo previamente y utilizar exactamente el mismo nombre en todas las peticiones.',
        2 => 'La opción «Conectado a Web/API» de la ficha del cliente sirve como estado informativo para los empleados y no crea por sí sola una integración. Después de configurar el formulario hay que activarla por separado. Las credenciales de la API no deben incluirse en JavaScript ni en ningún otro código accesible para los visitantes del sitio: la petición debe enviarse desde el servidor del sitio o desde la parte de servidor del plugin de formularios.',
      ),
      'examples' =>
      array (
        0 =>
        array (
          'title' => 'Envío de una solicitud desde un sitio web',
          'code' => 'curl --request POST "https://crm.example.com/api/v1/contacts" \\
  --user "CLIENT_ID:SECRET" \\
  --header "Content-Type: application/json" \\
  --data \'{
    "full_name": "Iván Pérez",
    "email": "ivan@example.org",
    "phone": "+34 600 123 456",
    "company": "Example Group",
    "clients": ["Acme Agency"],
    "tags": ["Solicitud web", "Nueva solicitud"],
    "custom_fields": {
      "interested_service": "SEO",
      "consent": true
    }
  }\'',
        ),
        1 =>
        array (
          'title' => 'Respuesta correcta al crear un contacto',
          'code' => '{
  "success": true,
  "data": {
    "processed": 1,
    "created": 1,
    "failed": 0,
    "results": [
      {
        "index": 0,
        "success": true,
        "data": {
          "contact_id": 125,
          "client_created": false,
          "tag_created": true
        }
      }
    ]
  }
}',
        ),
      ),
    ),
    2 =>
    array (
      'id' => 'api-credentials',
      'title' => 'Credenciales y seguridad',
      'paragraphs' =>
      array (
        0 => 'El administrador crea las credenciales desde la sección de gestión de la API. Se asigna un nombre descriptivo a la integración y el sistema genera un Client ID y un secreto. En las peticiones se utilizan como nombre de usuario y contraseña de HTTP Basic Auth. El Client ID identifica la integración y no está relacionado con ninguna ficha de cliente del CRM.',
        1 => 'El secreto solo se muestra una vez, inmediatamente después de crearlo. ContactCore guarda su hash y no puede volver a mostrar el valor original. Debe conservarse en una configuración protegida de la parte de servidor del sitio, sin enviarlo en la dirección URL ni incluirlo en el repositorio. Si se pierde o puede haber quedado expuesto, lo más seguro es crear credenciales nuevas y desactivar las anteriores.',
        2 => 'En los comandos de ejemplo, la dirección https://crm.example.com y los valores CLIENT_ID y SECRET son ficticios. Hay que sustituirlos por la dirección de la instalación de ContactCore y las credenciales de la integración correspondiente.',
        3 => 'Los permisos de la API están separados en lectura y escritura para contactos y clientes. Una integración nueva recibe los cuatro permisos actuales; el permiso de escritura también permite leer el recurso correspondiente. Una integración puede desactivarse temporalmente, volver a activarse o eliminarse definitivamente. Para las claves antiguas, el botón de sincronización sustituye los permisos guardados por el conjunto actual y elimina los scopes obsoletos de sectores y etiquetas.',
      ),
    ),
    3 =>
    array (
      'id' => 'api-resources',
      'title' => 'Recursos, rutas y métodos',
      'paragraphs' =>
      array (
        0 => 'La API contiene dos recursos: contacts y clients. Ambos admiten las mismas operaciones principales: GET /api/v1/{resource} obtiene una lista; GET /api/v1/{resource}/{id}, un registro; POST /api/v1/{resource} crea registros; PATCH /api/v1/{resource}/{id} modifica un registro; y DELETE /api/v1/{resource}/{id} lo elimina. Los sectores y las etiquetas se crean y relacionan mediante los campos sector y tags dentro de las peticiones de clientes y contactos.',
        1 => 'POST admite tanto un único objeto JSON como un array de hasta 100 objetos. Esto permite que un formulario envíe una solicitud individual y que una integración administrativa cree registros por lotes. Se devuelve un resultado independiente para cada elemento: un error en un registro no anula los elementos contiguos que se hayan procesado correctamente.',
        2 => 'Las listas de contactos y clientes admiten paginación mediante los parámetros page y per_page; cada petición devuelve como máximo 100 registros. Los contactos pueden filtrarse, por ejemplo, por nombre, correo electrónico, teléfono, empresa, cliente, etiqueta y fecha de creación. Para los clientes hay filtros por nombres, ubicación, sector, etiqueta y fechas. Las fechas se envían en el formato YYYY-MM-DD.',
        3 => 'PATCH realiza una actualización parcial: los campos ausentes en la petición no cambian. Sin embargo, los arrays tags y clients enviados sustituyen por completo el conjunto actual de relaciones. DELETE elimina definitivamente el contacto o cliente seleccionado. Los propios catálogos de etiquetas y sectores se administran desde la interfaz web, no desde la API pública.',
      ),
      'examples' =>
      array (
        0 =>
        array (
          'title' => 'Obtención de los contactos de un cliente durante un periodo',
          'code' => 'curl --user "CLIENT_ID:SECRET" \\
  "https://crm.example.com/api/v1/contacts?client_id=42&created_from=2026-07-01&created_to=2026-07-31&per_page=50"',
        ),
        1 =>
        array (
          'title' => 'Creación de un cliente',
          'code' => 'curl --request POST "https://crm.example.com/api/v1/clients" \\
  --user "CLIENT_ID:SECRET" \\
  --header "Content-Type: application/json" \\
  --data \'{
    "commercial_name": "Acme Agency",
    "legal_name": "Acme Agency SL",
    "website": "https://acme.example.com",
    "city": "Madrid",
    "country": "España",
    "sector": "Marketing",
    "tags": ["Proyecto activo"]
  }\'',
        ),
      ),
    ),
    4 =>
    array (
      'id' => 'api-fields',
      'title' => 'Campos, relaciones y creación automática',
      'paragraphs' =>
      array (
        0 => 'Un contacto puede contener full_name, email, phone y company, además de clients, tags y custom_fields. Durante la creación, la API comprueba el correo electrónico, determina su tipo y no permite direcciones repetidas. Los valores clients y tags pueden enviarse como un único nombre, como una cadena con varios nombres separados por comas o como un array JSON. Estas opciones funcionan tanto al crear como al actualizar; el campo tags de las peticiones de clientes admite los mismos formatos.',
        1 => 'En el formato de cadena, el separador es exclusivamente la coma: «Lead,Newsletter». Los puntos y coma y las barras verticales que admite la importación de archivos no separan nombres en la API. Es preferible utilizar un array JSON, ya que separa los elementos sin ambigüedades y permite nombres que contengan una coma.',
        2 => 'Un cliente se crea con el campo commercial_name obligatorio. También se admiten legal_name, cif, los campos de dirección, website, notes, sector, tags y custom_fields. Los nombres desconocidos de etiquetas y sectores se crean automáticamente. Por eso, las integraciones deben utilizar nombres acordados y uniformes; de lo contrario, aparecerán duplicados similares en los catálogos.',
        3 => 'Un campo personalizado debe crearse primero en ContactCore para el tipo de registro correspondiente. En la API puede enviarse dentro de un objeto anidado identificado por su slug —{"custom_fields":{"language":"es"}}— o como una clave independiente con notación de punto: {"custom_fields.language":"es"}. Ambos formatos son equivalentes y se admiten al crear y actualizar contactos y clientes.',
        4 => 'Para un campo personalizado numérico es preferible enviar un número JSON; para una casilla, true o false; y para una fecha, una cadena con formato YYYY-MM-DD. Los slugs desconocidos se omiten sin crear campos nuevos, por lo que después de configurar una integración hay que revisar la primera ficha creada. Al crear un registro, se aplican los valores predeterminados de los campos personalizados que no se hayan enviado.',
      ),
      'examples' =>
      array (
        0 =>
        array (
          'title' => 'Formatos admitidos para etiquetas y relaciones con clientes',
          'code' => 'Una etiqueta:
{"tags": "Lead"}

Varias etiquetas en una cadena:
{"tags": "Lead,Newsletter"}

Varias etiquetas en un array — opción recomendada:
{"tags": ["Lead", "Newsletter"]}

El campo clients admite los mismos formatos:
{"clients": "Acme Agency"}
{"clients": "Acme Agency,Example Group"}
{"clients": ["Acme Agency", "Example Group"]}',
        ),
        1 =>
        array (
          'title' => 'Dos formatos equivalentes para campos personalizados',
          'code' => 'Objeto anidado:
{
  "custom_fields": {
    "language": "es",
    "consent": true
  }
}

Notación de punto:
{
  "custom_fields.language": "es",
  "custom_fields.consent": true
}',
        ),
        2 =>
        array (
          'title' => 'Actualización parcial de un contacto',
          'code' => 'curl --request PATCH "https://crm.example.com/api/v1/contacts/125" \\
  --user "CLIENT_ID:SECRET" \\
  --header "Content-Type: application/json" \\
  --data \'{
    "phone": "+34 611 987 654",
    "tags": ["Cualificado"],
    "custom_fields": {
      "interested_service": "Publicidad en buscadores"
    }
  }\'',
        ),
      ),
    ),
    5 =>
    array (
      'id' => 'api-responses',
      'title' => 'Respuestas, errores y registro de peticiones',
      'paragraphs' =>
      array (
        0 => 'Las operaciones normales de lectura, modificación y eliminación devuelven HTTP 200. La creación devuelve HTTP 207 Multi-Status incluso para un solo objeto, porque la respuesta tiene una estructura por lotes. La integración debe comprobar tanto el estado HTTP general como el valor success de cada elemento de data.results: una respuesta 207 puede contener al mismo tiempo registros correctos y fallidos.',
        1 => 'Los errores principales son: 401, credenciales ausentes o incorrectas; 403, la clave no tiene el permiso necesario; 404, registro no encontrado; 409, conflicto o duplicado; 422, JSON incorrecto o error de validación de datos; y 500, error interno. El cuerpo de la respuesta contiene un código, un mensaje y, cuando es posible, información adicional.',
        2 => 'Cada respuesta contiene la cabecera X-Request-Id. Conviene guardarla en el registro del sistema externo, ya que el identificador facilita la localización de una petición concreta al investigar un error. En la sección de registros de la API, el administrador puede filtrar las peticiones por integración, método, ruta, estado y fecha, además de consultar el tiempo de ejecución, la dirección IP, el cuerpo de la petición y la respuesta.',
        3 => 'Antes de activar un formulario en un sitio web en producción, hay que enviar varias solicitudes de prueba y comprobar los contactos creados, las relaciones con el cliente, las etiquetas y los campos personalizados. La integración debe gestionar correctamente el reenvío de formularios, la indisponibilidad temporal del CRM y las respuestas por lotes parcialmente correctas, sin generar reintentos infinitos.',
      ),
    ),
  ),
);
