<?php

return [
    'title' => 'Interfaz web y AJAX',
    'description' => 'Interfaz HTML de servidor de ContactCore, flujos del cliente, contratos AJAX y reglas para desarrollar funciones interactivas.',
    'icon' => 'ph-arrow-elbow-down-right',
    'sections' => [
        [
            'id' => 'web-overview',
            'title' => 'Modelo general de la interfaz',
            'paragraphs' => [
                'ContactCore utiliza renderizado en el servidor con progressive enhancement selectivo. PHP genera una página HTML completa que puede abrirse y enviarse sin un framework del lado del cliente, mientras que JavaScript normal añade interactividad: paneles desplegables, búsquedas sin recargar, token picker, acciones masivas, copia de valores y las herramientas de IA.',
                'No es una SPA. La navegación entre las páginas principales, el filtrado de listas, la ordenación, la paginación y la mayoría de las operaciones de guardado se realizan mediante peticiones HTTP normales que cargan el documento completo. AJAX se utiliza cuando actualizar una pequeña parte de la interfaz resulta claramente más cómodo que cambiar de página: autocompletado, búsqueda global, selección de valores y operaciones largas por etapas.',
                'El proyecto no dispone de un compilador de frontend, un cargador de módulos ni un framework de JavaScript. El CSS y JS se guardan en public_html/assets y se cargan directamente. Por ello, las dependencias entre scripts deben ser sencillas y explícitas, y cada archivo específico de una página debe finalizar de forma segura si no encuentra en ella el elemento DOM que necesita.',
            ],
            'examples' => [
                [
                    'title' => 'Dos tipos de flujos de usuario',
                    'code' => "Operación HTML completa\nBrowser → Router → Controller → Repository → View → Layout → HTML\n\nOperación AJAX selectiva\nBrowser fetch() → /ajax/* → AjaxController → Repository/Service → JSON\n                       ↓\n                 modificación de una parte del DOM",
                ],
            ],
        ],
        [
            'id' => 'web-rendering',
            'title' => 'Renderizado de una página HTML',
            'paragraphs' => [
                'El controlador prepara los datos e invoca View::render(). El primer argumento indica la vista relativa a app/Views, el segundo contiene las variables de la página y el tercero permite cambiar el layout cuando sea necesario. View extrae los datos mediante extract(..., EXTR_SKIP), almacena en un búfer la salida de la vista y transmite el fragmento resultante al layout en la variable content.',
                'El layout principal se encuentra en app/Views/layouts/main.php. Genera la estructura común: sidebar, topbar, búsqueda global, perfil, área principal y un conjunto de traducciones comunes en window.I18N. base.css y admin.js siempre se cargan. El controlador transmite los archivos CSS y JS adicionales en los arrays styles y scripts; los scripts específicos de cada página se cargan después de admin.js.',
                'Las URL incluidas en HTML deben construirse mediante el helper url(), que invoca Auth::url() y escapa el resultado. La aplicación puede instalarse fuera de la raíz del dominio y un enlace fijo como /ajax/tags/search podría perder la ruta base. El título, los estilos, los scripts y todos los datos necesarios para la vista deben declararse en una única llamada de renderizado.',
            ],
            'examples' => [
                [
                    'title' => 'Llamada habitual a una vista',
                    'code' => "View::render('projects/index', [\n    'title'    => Lang::get('projects.title'),\n    'styles'   => ['data.css', 'projects.css'],\n    'scripts'  => ['list-page.js', 'projects.js'],\n    'projects' => \$projects,\n    'page'     => \$page,\n]);",
                ],
            ],
        ],
        [
            'id' => 'web-views',
            'title' => 'Vistas y reutilización del marcado',
            'paragraphs' => [
                'Las vistas se agrupan por secciones: app/Views/contacts, clients, users, etc. Los archivos index.php muestran las listas, un _form.php común sirve directamente para la creación y edición y show.php muestra la ficha. El controlador transmite el modo del formulario mediante isEdit. Los elementos reutilizables que no pertenecen a una sola sección se sitúan en app/Views/partials; así está implementada la entrada de campos personalizados.',
                'Una vista no debe ejecutar SQL ni tomar decisiones de negocio. Puede preparar texto, componer URL, seleccionar una clase CSS y transformar ligeramente datos ya cargados. Si una plantilla necesita otro conjunto de registros, el controlador debe obtenerlo mediante un repositorio antes de llamar a View::render().',
                'Los datos del usuario y de la base se muestran mediante el helper corto e() de Illuminate Support. Las traducciones se obtienen mediante t() o Lang::get(); la segunda opción se utiliza cuando hacen falta sustituciones. Un array destinado a un atributo data se serializa primero con json_encode() y después se escapa mediante e(). Los valores dinámicos no deben insertarse en JavaScript inline concatenando cadenas.',
            ],
            'examples' => [
                [
                    'title' => 'Transmisión segura de datos desde PHP a un componente',
                    'code' => "<?php \$selectedJson = json_encode(\$selected, JSON_UNESCAPED_UNICODE); ?>\n\n<div class=\"token-picker\"\n     data-endpoint=\"<?= url('/ajax/projects/search') ?>\"\n     data-name=\"project_ids[]\"\n     data-selected=\"<?= e(\$selectedJson) ?>\">\n</div>",
                ],
            ],
        ],
        [
            'id' => 'web-assets',
            'title' => 'CSS, JavaScript y contratos del DOM',
            'paragraphs' => [
                'base.css contiene la estructura común, la tipografía, los botones, los campos y los componentes globales. Los archivos temáticos como contacts.css, settings.css o api.css se ocupan de un área concreta. data.css se utiliza en las páginas de importación y exportación. Los estilos nuevos deben añadirse a un archivo ya adecuado o a un archivo específico de la página si el componente es particular y su tamaño justifica la separación.',
                'admin.js contiene los componentes globales: búsqueda en la topbar, token picker, búsqueda dinámica en catálogos, selector de iconos, menú del perfil y sidebar móvil. list-page.js gestiona los paneles de filtros, la selección de filas y las acciones masivas. Los demás archivos están asociados a una única página. La mayoría de los scripts están envueltos en una IIFE para evitar variables globales; las excepciones antiguas que usan llamadas inline deben migrarse gradualmente a atributos data y addEventListener().',
                'La relación entre HTML y JavaScript se establece mediante atributos data-* y clases estables de componentes. PHP proporciona la URL, el token CSRF, los mensajes traducidos y el estado inicial, y JS lee dataset. El servidor no debe confiar en el estado visual de un componente: un botón oculto, disabled o una clase CSS no constituyen una comprobación de acceso ni una validación.',
            ],
            'examples' => [
                [
                    'title' => 'Script autónomo para una página',
                    'code' => "(function () {\n    var root = document.querySelector('[data-project-widget]');\n    if (!root) {\n        return;\n    }\n\n    var endpoint = root.dataset.endpoint;\n    root.addEventListener('click', function (event) {\n        var button = event.target.closest('[data-project-action]');\n        if (!button) return;\n        // Interactividad del componente.\n    });\n})();",
                ],
            ],
        ],
        [
            'id' => 'web-lists',
            'title' => 'Listas, filtros y acciones masivas',
            'paragraphs' => [
                'Las listas de clientes y contactos siguen generándose en el servidor. Los parámetros GET describen los filtros, sort, dir, page y per_page; el repositorio los aplica a count y paginate; y la vista conserva los parámetros activos en los enlaces de ordenación, paginación y chips. Así se puede copiar la URL, actualizar la página sin perder la selección y utilizar la navegación del navegador de forma predecible.',
                'Las funciones comunes thSort(), renderPagination() y el formato de fechas mediante formatDate() se encuentran en app/Helpers/view_helpers.php. El controlador solo debe permitir nombres de ordenación conocidos y el repositorio debe relacionarlos con una allowlist de columnas SQL. Los valores de los filtros se transmiten mediante bindings de Query Builder; el nombre de la columna nunca se toma directamente de la petición.',
                'list-page.js no envía datos por sí mismo. Abre los paneles, cuenta las filas seleccionadas, sincroniza select-all y muestra la cantidad. La operación masiva final sigue siendo un formulario POST normal con un token CSRF. El servidor comprueba el permiso de forma independiente para cada valor de bulk_action.',
            ],
            'examples' => [
                [
                    'title' => 'Estado de una lista en la URL',
                    'code' => "/contacts\n  ?tag_ids[]=4\n  &client_id=12\n  &email_status=valid\n  &sort=created_at\n  &dir=desc\n  &page=3",
                ],
            ],
        ],
        [
            'id' => 'web-forms',
            'title' => 'Formularios y operaciones de modificación',
            'paragraphs' => [
                'La creación, edición, eliminación, importación, exportación y configuración utilizan POST. Todos los formularios HTML incluyen Csrf::field(). Después de guardar correctamente, el controlador ejecuta una redirección: es la implementación de Post/Redirect/Get que evita volver a enviar el formulario al actualizar la página.',
                'La validación HTML con required, type=email y otros atributos mejora la interfaz, pero no es un límite de confianza. El controlador o servicio vuelve a normalizar y comprobar los valores de entrada. Si se produce un error, el formulario se renderiza de nuevo con los valores introducidos y un mensaje comprensible; los secretos y las contraseñas no deben devolverse en el HTML.',
                'Una eliminación se implementa como formulario POST, aunque visualmente parezca un enlace o un icono. Para varias acciones, un formulario puede utilizar formaction y formnovalidate. La confirmación mediante confirm() reduce el riesgo de una pulsación accidental, pero no sustituye al permiso, CSRF ni a la comprobación del identificador en el servidor.',
            ],
            'examples' => [
                [
                    'title' => 'Formulario seguro mínimo',
                    'code' => "<form method=\"post\" action=\"<?= url('/projects/store') ?>\">\n    <?= Csrf::field() ?>\n    <input name=\"name\" required>\n    <button type=\"submit\">Guardar</button>\n</form>",
                ],
            ],
        ],
        [
            'id' => 'ajax-surface',
            'title' => 'Rutas AJAX actuales',
            'paragraphs' => [
                'Las rutas AJAX se registran en public_html/index.php y son atendidas por AjaxController. Se trata de una interfaz interna para el navegador que utiliza autenticación de sesión. No debe mezclarse con la ruta pública /api/v1: la API tiene su propia autenticación, scopes, controladores, formato de respuesta y registro.',
                'Las rutas GET de búsqueda no modifican datos. global-search combina contactos y clientes; clients/search y tags/search admiten page y has_more; clients/field devuelve los valores únicos de un campo estándar; custom-field/values, los valores de texto únicos de un campo personalizado. La búsqueda de sectores e iconos requiere sectors.manage.',
                'Las rutas POST realizan la comprobación de correos por lotes y las operaciones de IA: determinar una empresa, guardar el resultado y omitir una fila. Todas pasan por la comprobación CSRF global. La página /ai, la comprobación de correos por lotes y los tres manejadores relacionados con la IA tienen la misma política auth = admin; un POST directo de un usuario normal recibe un JSON 403.',
            ],
            'examples' => [
                [
                    'title' => 'Mapa de rutas internas',
                    'code' => "GET  /ajax/global-search\nGET  /ajax/clients/search\nGET  /ajax/clients/field\nGET  /ajax/tags/search\nGET  /ajax/sectors/search          sectors.manage\nGET  /ajax/icons/search            sectors.manage\nGET  /ajax/custom-field/values\n\nPOST /ajax/contacts/inspect-email-batch  admin\nPOST /ajax/contacts/gemini-company      admin\nPOST /ajax/contacts/company             admin\nPOST /ajax/contacts/company/skip        admin",
                ],
            ],
        ],
        [
            'id' => 'ajax-contract',
            'title' => 'Formato de las peticiones y respuestas',
            'paragraphs' => [
                'Una búsqueda GET recibe q y, cuando sea necesario, page mediante la query string. El contrato básico de una lista es el objeto items; las sugerencias paginadas también devuelven has_more. Cada item debe contener al menos id y name, ya que TokenPicker depende de este formato. Los componentes pueden utilizar campos adicionales como color, slug, icon, type, meta o url.',
                'En el código actual, los POST AJAX se envían como application/x-www-form-urlencoded mediante URLSearchParams, por lo que los datos están disponibles en $_POST. El cuerpo debe incluir _csrf_token. Las cabeceras Accept: application/json y X-Requested-With ayudan a indicar la intención del cliente, aunque el servidor no modifica actualmente su comportamiento en función de ellas.',
                'AjaxController no utiliza una estructura envelope común. La respuesta correcta depende de la operación y un error suele tener el formato {error: string} con el estado HTTP adecuado: 401, 403, 404, 422, 500 o 502. El cliente comprueba primero response.ok y después utiliza el JSON. Hay una excepción importante: la comprobación CSRF global se ejecuta antes del controlador y, si se produce un error 419, devuelve texto normal; por tanto, un cliente universal no debe invocar response.json() incondicionalmente.',
            ],
            'examples' => [
                [
                    'title' => 'Contrato de una búsqueda paginada',
                    'code' => "GET /ajax/clients/search?q=acme&page=2\n\n{\n  \"items\": [\n    {\"id\": 42, \"name\": \"Acme Studio\"}\n  ],\n  \"has_more\": false\n}",
                ],
                [
                    'title' => 'Error habitual de una operación',
                    'code' => "HTTP/1.1 422 Unprocessable Entity\nContent-Type: application/json; charset=utf-8\n\n{\"error\": \"El contacto no tiene una dirección de correo válida\"}",
                ],
            ],
        ],
        [
            'id' => 'ajax-client',
            'title' => 'Petición del cliente y actualización del DOM',
            'paragraphs' => [
                'La búsqueda se inicia después de un debounce para no enviar una petición con cada pulsación de tecla. El usuario debe poder distinguir los estados loading, empty, error y success. Durante una operación que modifica datos, el botón se bloquea y vuelve a quedar disponible en finally al terminar. Una segunda pulsación no debe iniciar una escritura duplicada.',
                'Los datos de la respuesta se insertan en el DOM mediante textContent y la creación de elementos. innerHTML puede utilizarse para una plantilla estática predefinida, pero no para name, error ni otros datos del servidor. Los enlaces de la respuesta deben ser generados por el servidor mediante Auth::url() o construirse a partir de un id fiable y una ruta conocida de antemano.',
                'Los componentes de búsqueda actuales no cancelan el fetch anterior. En una red lenta, una respuesta antigua puede llegar después de una nueva y sustituir los resultados más recientes. Para los componentes nuevos se recomienda AbortController o un contador de versión de la petición. También debe gestionarse la pérdida de la sesión: un 401 no debe parecer un resultado vacío.',
            ],
            'examples' => [
                [
                    'title' => 'Búsqueda GET fiable que cancela la petición anterior',
                    'code' => "var activeRequest = null;\n\nasync function search(query) {\n    if (activeRequest) activeRequest.abort();\n    activeRequest = new AbortController();\n\n    var response = await fetch(\n        endpoint + '?q=' + encodeURIComponent(query),\n        {\n            headers: { Accept: 'application/json' },\n            signal: activeRequest.signal,\n        }\n    );\n\n    if (!response.ok) throw new Error('La búsqueda ha fallado');\n    return response.json();\n}",
                ],
            ],
        ],
        [
            'id' => 'web-token-picker',
            'title' => 'TokenPicker y catálogos remotos',
            'paragraphs' => [
                'TokenPicker es un componente común de admin.js para seleccionar uno o varios valores. El div original describe el componente mediante los atributos data-name, data-selected, data-placeholder, data-endpoint o data-options. JavaScript construye el campo de búsqueda, el dropdown, los chips y los input ocultos, por lo que el servidor recibe valores de formulario normales y no depende del formato JavaScript del cuerpo.',
                'data-options activa el modo local sin AJAX. data-endpoint activa la búsqueda remota. data-max=1 convierte el componente en una selección única; data-with-color=1 muestra el color de una etiqueta; y data-paginate=1 añade page y carga la siguiente página al desplazarse. data-selected debe ser un array JSON de objetos con id y name.',
                'El endpoint remoto devuelve {items: [{id, name}], has_more}. Los valores seleccionados se guardan como hidden input con el nombre de data-name. Por ello, para una selección múltiple, el nombre debe terminar en [], por ejemplo tag_ids[]. Al volver a renderizar el formulario, el controlador debe transmitir los objetos seleccionados, no solo sus id, ya que de lo contrario no se puede asignar texto a los chips.',
            ],
            'examples' => [
                [
                    'title' => 'Selección múltiple remota',
                    'code' => "<div class=\"token-picker\"\n     data-endpoint=\"/ajax/tags/search\"\n     data-name=\"tag_ids[]\"\n     data-selected='[{\"id\":4,\"name\":\"Hot\",\"color\":\"#ef4444\"}]'\n     data-with-color=\"1\"\n     data-paginate=\"1\"\n     data-placeholder=\"Buscar etiquetas\">\n</div>",
                ],
            ],
        ],
        [
            'id' => 'ajax-security',
            'title' => 'Autorización, permisos y CSRF',
            'paragraphs' => [
                'El acceso a AJAX se define en public_html/index.php mediante la política de la ruta y Router la comprueba antes de invocar AjaxController. Para los endpoints internos, indique response = json: un invitado recibirá JSON 401 y un usuario sin permiso, JSON 403. auth = admin limita las acciones administrativas y permission comprueba un permiso funcional. Comprobar la visibilidad de una página, un elemento del menú o un botón no protege frente a una petición HTTP directa.',
                'En public_html/index.php, todos los POST salvo /api/v1 se comprueban mediante Csrf::validate() antes de dispatch. El token se crea para la sesión y se transmite en _csrf_token. GET no debe utilizarse para modificar datos, ya que no está sujeto a CSRF. La API pública se excluye de la comprobación CSRF de sesión porque utiliza su propia firma de peticiones.',
                'CSP solo permite self en connect-src, por lo que fetch en el navegador puede acceder a ContactCore, pero no a un dominio externo arbitrario. PHP invoca Gemini desde el servidor mediante el cliente HTTP Guzzle. CSP todavía permite unsafe-inline para mantener la compatibilidad con onclick y style inline; los componentes nuevos no deben aumentar esta dependencia.',
            ],
            'examples' => [
                [
                    'title' => 'Ruta AJAX protegida y manejador ligero',
                    'code' => "\$router->get('/ajax/contacts/edit-search', [\$ajaxController, 'editableContactsSearch'], [\n    'permission' => 'contacts.edit',\n    'response' => 'json',\n]);\n\npublic function editableContactsSearch(): void\n{\n    \$query = trim(\$_GET['q'] ?? '');\n    \$items = \$this->contacts->search(\$query, 20);\n\n    \$this->json(['items' => \$items]);\n}",
                ],
            ],
        ],
        [
            'id' => 'web-new-feature',
            'title' => 'Adición de un nuevo flujo interactivo',
            'paragraphs' => [
                'Determine primero si AJAX es realmente necesario. Si la operación termina abriendo una ficha o una lista, un formulario POST normal es más sencillo, accesible y fiable. AJAX está justificado para sugerencias, cambios locales de estado, pasos en segundo plano u operaciones en las que una recarga completa destruiría el contexto de trabajo.',
                'Un flujo nuevo requiere un método del repositorio o servicio, un método de AjaxController, una ruta en public_html/index.php, un contrato data en la vista y un manejador en el archivo JS adecuado. Si el componente solo se utiliza en una página, el archivo se incluye mediante scripts desde el controlador. La regla de negocio no debe duplicarse en AjaxController: este adapta HTTP e invoca el servicio común.',
                'Antes de terminar hay que comprobar una respuesta correcta, un resultado vacío, un error de validación, 401, 403, 419 y 500, una red lenta, una pulsación repetida y un elemento eliminado del DOM. La interfaz debe seguir siendo comprensible en una pantalla móvil y poder manejarse con el teclado; un botón interactivo necesita type=button, un nombre accesible y valores actualizados de aria-expanded o aria-disabled cuando corresponda.',
            ],
            'examples' => [
                [
                    'title' => 'Orden de implementación',
                    'code' => "1. Describir la entrada, el resultado, los errores y permission\n2. Implementar la operación reutilizable en Service/Repository\n3. Añadir AjaxController::method() y la ruta\n4. Transmitir endpoint, CSRF y textos mediante data-*\n5. Cargar el JS de la página mediante scripts\n6. Actualizar el DOM de forma segura y mostrar los estados de la petición\n7. Comprobar el flujo HTML, el acceso, CSRF, los idiomas y la base path",
                ],
            ],
        ],
    ],
];
