<?php

class HelpController
{
    public function index(): void
    {
        Auth::requireLogin();

        $locale  = $this->localeFromRequest();
        $content = $this->content()[$locale];

        $cards = array_map(static fn($s) => [
            'id'      => $s['id'],
            'icon'    => $s['icon'],
            'accent'  => $s['accent'],
            'title'   => $s['title'],
            'summary' => $s['summary'],
        ], $content['sections']);

        $cards[] = [
            'id'      => 'api',
            'icon'    => 'key',
            'accent'  => 'slate',
            'title'   => 'API Reference',
            'summary' => $locale === 'es'
                ? 'Documentacion completa de la API REST: autenticacion con clave, endpoints de contactos, clientes, sectores y tags, con ejemplos de uso.'
                : 'Full REST API documentation: key authentication, endpoints for contacts, clients, sectors and tags, with usage examples.',
        ];

        View::render('help/index', [
            'title'            => $content['page_title'],
            'styles'           => ['help.css'],
            'locale'           => $locale,
            'availableLocales' => ['es' => 'ES', 'en' => 'EN'],
            'content'          => $content,
            'cards'            => $cards,
        ]);
    }

    public function show(): void
    {
        Auth::requireLogin();

        $topic  = (string) ($_GET['topic'] ?? '');
        $locale = $this->localeFromRequest();

        // Topics that have their own dedicated view file
        $dedicatedViews = [
            'api'             => ['help/api',             ['help.css', 'api.css'], 'API Reference'],
            'getting-started' => ['help/getting-started', ['help.css'],            'Primeros pasos'],
            'contacts'        => ['help/contacts',        ['help.css'],            'Contactos'],
            'clients'         => ['help/clients',         ['help.css'],            'Clientes'],
        ];

        if (isset($dedicatedViews[$topic])) {
            [$view, $styles, $title] = $dedicatedViews[$topic];
            View::render($view, [
                'title'            => $title,
                'styles'           => $styles,
                'locale'           => $locale,
                'availableLocales' => ['es' => 'ES', 'en' => 'EN'],
            ]);
            return;
        }

        // Generic checklist template for sections not yet migrated
        $content = $this->content()[$locale];
        $section = null;

        foreach ($content['sections'] as $s) {
            if ($s['id'] === $topic) {
                $section = $s;
                break;
            }
        }

        if ($section === null) {
            Auth::redirect('/help');
            return;
        }

        View::render('help/show', [
            'title'            => $section['title'],
            'styles'           => ['help.css'],
            'locale'           => $locale,
            'availableLocales' => ['es' => 'ES', 'en' => 'EN'],
            'content'          => $content,
            'section'          => $section,
        ]);
    }

    private function localeFromRequest(): string
    {
        $locale = strtolower((string) ($_GET['lang'] ?? 'es'));
        return in_array($locale, ['es', 'en'], true) ? $locale : 'es';
    }

    private function content(): array
    {
        return [

            // ── Spanish ────────────────────────────────────────────────────
            'es' => [
                'page_title'         => 'Centro de ayuda',
                'title'              => 'Centro de ayuda',
                'intro'              => 'Todo lo que necesitas saber para sacar el maximo partido al CRM: contactos, clientes, importaciones, exportaciones, filtros y mucho mas.',
                'language_label'     => 'Idioma',
                'quick_topics_label' => 'Contenido',
                'sections' => [
                    [
                        'id'      => 'getting-started',
                        'accent'  => 'violet',
                        'icon'    => 'map',
                        'title'   => 'Primeros pasos',
                        'summary' => 'ContactCore es un CRM ligero organizado en modulos accesibles desde el menu lateral. El flujo habitual es: crear contactos → vincularlos a clientes → clasificar con tags → filtrar y exportar cuando lo necesites.',
                        'items'   => [
                            'El <strong>Dashboard</strong> es la pantalla de inicio: muestra el total de contactos, clientes, sectores y tags, y los accesos directos a las secciones principales.',
                            'El <strong>menu lateral</strong> agrupa todo el sistema: <em>Contacts</em>, <em>Clients</em>, <em>Sectors</em>, <em>Tags</em> y la seccion de configuracion (Custom Fields, Imports, Exports, Users, API Credentials).',
                            'La <strong>barra de busqueda superior</strong> localiza contactos y clientes en tiempo real tecleando el nombre, email o empresa. Los resultados aparecen en un desplegable instantaneo.',
                            'El <strong>menu de perfil</strong> (avatar en la esquina superior derecha) muestra tu nombre, rol y el enlace para cerrar sesion.',
                            'El boton <strong>☰ (hamburguesa)</strong> en movil colapsa o expande el menu lateral para ganar espacio en pantallas pequenas.',
                            'La opcion <strong>"Recordarme"</strong> en el login mantiene la sesion activa durante 30 dias sin necesidad de volver a introducir las credenciales.',
                            'La interfaz esta disponible en <strong>Espanol e Ingles</strong>: cambia el idioma desde el selector que aparece en la esquina superior derecha de esta pagina de ayuda.',
                            'Si tu cuenta tiene <strong>verificacion en dos pasos activada</strong>, recibes un codigo por email tras el login que debes introducir antes de acceder.',
                        ],
                        'tip' => 'Flujo recomendado para empezar: crea 2-3 contactos → crea un cliente → vincula los contactos a ese cliente → aplica un tag como "Lead" a los contactos. Con eso ya tendras datos reales para explorar los filtros y la exportacion.',
                    ],
                    [
                        'id'      => 'contacts',
                        'accent'  => 'blue',
                        'icon'    => 'person',
                        'title'   => 'Contactos',
                        'summary' => 'Los contactos son la pieza central del CRM. Cada persona tiene su propia ficha con datos de contacto, tags, clientes vinculados y campos personalizados.',
                        'items'   => [
                            'Los campos principales son: nombre, apellido, email, telefono y la opcion "Is company" para personas juridicas.',
                            'Desde la ficha de un contacto puedes vincularlo a uno o varios clientes con un solo clic.',
                            'Los <strong>tags</strong> sirven para clasificar el estado del contacto: Lead, Client, Partner, Hot, Cold, Prospect, etc.',
                            'Puedes <strong>seleccionar varios contactos</strong> en el listado y aplicar o quitar tags en bloque desde la barra flotante inferior.',
                            'Los <strong>filtros</strong> permiten segmentar por nombre, email, tag, cliente, sector, fechas y campos personalizados.',
                            'Cada contacto puede tener <strong>campos personalizados</strong> para guardar informacion especifica de tu proceso.',
                        ],
                        'tip' => 'Usa el campo "Is company" cuando el contacto represente a una empresa o autónomo y no a una persona fisica concreta.',
                    ],
                    [
                        'id'      => 'clients',
                        'accent'  => 'green',
                        'icon'    => 'building',
                        'title'   => 'Clientes',
                        'summary' => 'Los clientes representan empresas, marcas u organizaciones. Un cliente puede tener multiples contactos asociados y un mismo contacto puede pertenecer a varios clientes.',
                        'items'   => [
                            'Los campos disponibles son: nombre comercial, razon social, CIF/NIF, direccion, codigo postal, ciudad, provincia, pais, sector, web y notas.',
                            'Desde la ficha del cliente puedes ver todos sus contactos vinculados y anadir nuevos.',
                            'Los <strong>tags en clientes</strong> funcionan igual que en contactos: clasifica, filtra y actua en bloque.',
                            'El campo <strong>Sector</strong> vincula el cliente con un sector industrial para facilitar el analisis y el filtrado.',
                            'Los clientes tambien admiten <strong>campos personalizados</strong>, independientes de los de contactos.',
                        ],
                        'tip' => 'Si trabajas con grupos empresariales, crea un cliente por empresa y vincula los mismos contactos a varias de ellas.',
                    ],
                    [
                        'id'      => 'tags-sectors',
                        'accent'  => 'amber',
                        'icon'    => 'tag',
                        'title'   => 'Tags y sectores',
                        'summary' => 'Los tags son etiquetas de colores que clasifican contactos y clientes. Los sectores agrupan clientes por industria. Ambos se gestionan desde el menu lateral.',
                        'items'   => [
                            'Cada tag tiene un <strong>nombre y un color</strong> que aparece en listados y fichas.',
                            'Los tags se aplican desde la ficha individual o en bloque seleccionando varios registros en el listado.',
                            'Usa tags para representar el <strong>estado comercial</strong> (Lead → Prospect → Client), la <strong>prioridad</strong> (Hot, Cold) o el <strong>origen</strong> (Inbound, Referral).',
                            'Los <strong>sectores</strong> se asignan a clientes para agruparlos por actividad: Tecnologia, Salud, Construccion, etc.',
                            'Tanto tags como sectores son <strong>editables y eliminables</strong> por usuarios con permiso de administrador.',
                        ],
                    ],
                    [
                        'id'      => 'custom-fields',
                        'accent'  => 'teal',
                        'icon'    => 'sliders',
                        'title'   => 'Campos personalizados',
                        'summary' => 'Los campos personalizados amplian la ficha de contactos o clientes con cualquier dato adicional que necesites, sin tocar codigo.',
                        'items'   => [
                            'Tipos disponibles: <strong>text, textarea, number, date, email, url, select y checkbox</strong>.',
                            'Se configuran en <em>Settings → Custom Fields</em> y se asignan a <strong>contacts</strong> o <strong>clients</strong> por separado.',
                            'Los campos de tipo <em>select</em> permiten definir una lista de opciones cerradas.',
                            'Activa <strong>"Filterable"</strong> para que el campo aparezca en el panel de filtros avanzados del listado.',
                            'Durante una importacion, las columnas sin mapeo pueden <strong>crearse automaticamente</strong> como campos personalizados.',
                        ],
                        'tip' => 'Crea un campo "Presupuesto estimado" (number) o "Siguiente accion" (select) para adaptar el CRM a tu proceso de ventas.',
                    ],
                    [
                        'id'      => 'imports',
                        'accent'  => 'indigo',
                        'icon'    => 'upload',
                        'title'   => 'Importacion de datos',
                        'summary' => 'Importa contactos desde un archivo CSV o XLSX en cuatro pasos: sube el archivo, revisa la previsualizacion, mapea las columnas y confirma.',
                        'items'   => [
                            '<strong>Paso 1 – Subir:</strong> sube un archivo CSV o XLSX. La primera fila debe contener los nombres de columna.',
                            '<strong>Paso 2 – Previsualizar:</strong> el sistema muestra las primeras filas para que compruebes que los datos son correctos.',
                            '<strong>Paso 3 – Mapear:</strong> relaciona cada columna del archivo con un campo del sistema (o descartala / conviertela en campo personalizado).',
                            '<strong>Paso 4 – Procesar:</strong> el sistema crea los contactos, omite emails duplicados y registra los errores linea a linea.',
                            'El <strong>historial de importaciones</strong> muestra el resultado de cada carga: filas importadas, omitidas y con error.',
                            'Descarga la plantilla CSV desde <em>Exports → Import templates</em> para usar el formato correcto desde el inicio.',
                        ],
                        'tip' => 'La columna "full_name" es obligatoria. Si el archivo tiene emails duplicados respecto a contactos existentes, esas filas se omiten automaticamente.',
                    ],
                    [
                        'id'      => 'exports',
                        'accent'  => 'sky',
                        'icon'    => 'download',
                        'title'   => 'Exportacion de datos',
                        'summary' => 'Desde el apartado Exports puedes descargar contactos o clientes en CSV o XLSX eligiendo exactamente que campos incluir, incluyendo relaciones y campos personalizados.',
                        'items'   => [
                            'Selecciona la entidad a exportar: <strong>Contacts</strong> o <strong>Clients</strong>.',
                            'Elige los campos por grupos: <em>Basic info</em>, <em>Related data</em> (tags, clientes vinculados, sector) y <em>Custom fields</em>.',
                            'Usa los botones <strong>All / None</strong> de cada grupo para seleccionar o deseleccionar rapidamente.',
                            'Elige el formato: <strong>CSV</strong> (compatible con cualquier hoja de calculo) o <strong>XLSX</strong> (requiere PhpSpreadsheet).',
                            'El <strong>historial de exportaciones</strong> al pie de pagina muestra las ultimas descargas con fecha, entidad y numero de filas.',
                            'Las <strong>plantillas de importacion</strong> tambien estan disponibles en este apartado para no empezar de cero.',
                        ],
                    ],
                    [
                        'id'      => 'search-filters',
                        'accent'  => 'cyan',
                        'icon'    => 'search',
                        'title'   => 'Busqueda y filtros',
                        'summary' => 'El CRM ofrece tres niveles de busqueda: busqueda global en la barra superior, filtros rapidos con chips y filtros avanzados expandibles.',
                        'items'   => [
                            'La <strong>busqueda global</strong> (barra superior) localiza contactos y clientes al instante por nombre, email o empresa.',
                            'El boton <strong>Filters</strong> en el listado abre el panel de filtros. Los activos se muestran como chips junto al boton.',
                            'Haz clic en la <strong>X de un chip</strong> para eliminar ese filtro sin afectar al resto.',
                            'Los <strong>filtros avanzados</strong> permiten filtrar por cliente vinculado, sector, pais, fechas de creacion/modificacion y campos personalizados filtrables.',
                            'El enlace <strong>"Reset all"</strong> limpia todos los filtros a la vez.',
                            'Los filtros se conservan en la URL, por lo que puedes guardar o compartir un segmento concreto.',
                        ],
                        'tip' => 'Combina varios tags y un rango de fechas para crear listas de seguimiento muy precisas, como "Leads calientes creados este mes".',
                    ],
                    [
                        'id'      => 'users-roles',
                        'accent'  => 'slate',
                        'icon'    => 'users',
                        'title'   => 'Usuarios y permisos',
                        'summary' => 'El sistema tiene dos roles: Administrator y User. Los administradores tienen acceso total; los usuarios tienen permisos configurables por separado.',
                        'items'   => [
                            '<strong>Administrator:</strong> acceso completo a todas las funciones, incluida la gestion de usuarios, campos personalizados, tags y sectores.',
                            '<strong>User:</strong> puede crear, editar y eliminar contactos y clientes segun los permisos que el administrador le otorgue.',
                            'Los permisos granulares son: crear contactos, editar contactos, eliminar contactos, crear clientes, editar clientes, eliminar clientes, exportar datos, importar datos.',
                            'Gestiona los usuarios desde <em>Settings → Users</em>. Solo los administradores ven esta seccion.',
                            'Desactiva un usuario para revocar su acceso sin borrar su historial de actividad.',
                        ],
                        'tip' => 'Crea un usuario con rol User y permisos de solo lectura para equipos externos que necesiten consultar datos sin modificarlos.',
                    ],
                    [
                        'id'      => 'two-factor',
                        'accent'  => 'red',
                        'icon'    => 'shield',
                        'title'   => 'Verificacion en dos pasos',
                        'summary' => 'La autenticacion de dos factores (2FA) anade una capa adicional de seguridad al inicio de sesion enviando un codigo temporal por email.',
                        'items'   => [
                            'Tras introducir el email y la contrasena, el CRM envia un <strong>codigo de un solo uso</strong> a la direccion de correo del usuario.',
                            'El codigo debe introducirse en la <strong>pantalla de verificacion</strong> antes de acceder al sistema.',
                            'Si el codigo caduca o no llega, usa el boton <strong>"Resend code"</strong> para solicitar uno nuevo.',
                            'Cada codigo es de uso unico y expira tras unos minutos por razones de seguridad.',
                            'Revisa la carpeta de <strong>spam o correo no deseado</strong> si el codigo no aparece en la bandeja de entrada.',
                        ],
                    ],
                ],
            ],

            // ── English ────────────────────────────────────────────────────
            'en' => [
                'page_title'         => 'Help Center',
                'title'              => 'Help Center',
                'intro'              => 'Everything you need to get the most out of the CRM: contacts, clients, imports, exports, filters, and more.',
                'language_label'     => 'Language',
                'quick_topics_label' => 'Contents',
                'sections' => [
                    [
                        'id'      => 'getting-started',
                        'accent'  => 'violet',
                        'icon'    => 'map',
                        'title'   => 'Getting started',
                        'summary' => 'ContactCore is a lightweight CRM organized into modules accessible from the sidebar. The typical flow is: create contacts → link them to clients → classify with tags → filter and export when needed.',
                        'items'   => [
                            'The <strong>Dashboard</strong> is the home screen: it shows totals for contacts, clients, sectors, and tags, plus shortcuts to the main sections.',
                            'The <strong>sidebar</strong> groups the entire system: <em>Contacts</em>, <em>Clients</em>, <em>Sectors</em>, <em>Tags</em>, and the settings section (Custom Fields, Imports, Exports, Users, API Credentials).',
                            'The <strong>top search bar</strong> finds contacts and clients in real time as you type a name, email, or company. Results appear instantly in a dropdown.',
                            'The <strong>profile menu</strong> (avatar in the top-right corner) shows your name, role, and the link to log out.',
                            'The <strong>☰ (hamburger) button</strong> on mobile collapses or expands the sidebar to free up screen space.',
                            'The <strong>"Remember me"</strong> option at login keeps your session active for 30 days without re-entering credentials.',
                            'The interface is available in <strong>Spanish and English</strong>: switch language using the selector in the top-right corner of this help page.',
                            'If your account has <strong>two-factor authentication enabled</strong>, you will receive a one-time code by email after login that must be entered before access is granted.',
                        ],
                        'tip' => 'Recommended starting flow: create 2–3 contacts → create a client → link those contacts to the client → apply a tag like "Lead". That gives you real data to explore filters and exports.',
                    ],
                    [
                        'id'      => 'contacts',
                        'accent'  => 'blue',
                        'icon'    => 'person',
                        'title'   => 'Contacts',
                        'summary' => 'Contacts are the core of the CRM. Each person has their own record with contact details, tags, linked clients, and custom fields.',
                        'items'   => [
                            'Main fields: first name, last name, email, phone, and the "Is company" flag for legal entities.',
                            'From a contact\'s record you can link them to one or more clients with a single click.',
                            '<strong>Tags</strong> classify the contact\'s status: Lead, Client, Partner, Hot, Cold, Prospect, etc.',
                            'You can <strong>select multiple contacts</strong> in the list and bulk-apply or remove tags from the floating bottom bar.',
                            '<strong>Filters</strong> let you segment by name, email, tag, client, sector, dates, and custom fields.',
                            'Each contact can have <strong>custom fields</strong> to store information specific to your process.',
                        ],
                        'tip' => 'Use the "Is company" flag when the contact represents a business or sole trader rather than an individual person.',
                    ],
                    [
                        'id'      => 'clients',
                        'accent'  => 'green',
                        'icon'    => 'building',
                        'title'   => 'Clients',
                        'summary' => 'Clients represent companies, brands, or organizations. A client can have multiple linked contacts, and the same contact can belong to several clients.',
                        'items'   => [
                            'Available fields: commercial name, legal name, CIF/NIF, address, postal code, city, province, country, sector, website, and notes.',
                            'From a client\'s record you can see all linked contacts and add new ones.',
                            '<strong>Tags on clients</strong> work exactly like on contacts: classify, filter, and bulk-act.',
                            'The <strong>Sector</strong> field links the client to an industry category for easier analysis and filtering.',
                            'Clients also support <strong>custom fields</strong>, independent from those on contacts.',
                        ],
                        'tip' => 'If you work with corporate groups, create one client per company and link the same contacts to multiple clients.',
                    ],
                    [
                        'id'      => 'tags-sectors',
                        'accent'  => 'amber',
                        'icon'    => 'tag',
                        'title'   => 'Tags and sectors',
                        'summary' => 'Tags are coloured labels that classify contacts and clients. Sectors group clients by industry. Both are managed from the sidebar.',
                        'items'   => [
                            'Each tag has a <strong>name and a colour</strong> shown in lists and records.',
                            'Tags can be applied from an individual record or in bulk by selecting multiple rows in the list.',
                            'Use tags to track <strong>commercial status</strong> (Lead → Prospect → Client), <strong>priority</strong> (Hot, Cold), or <strong>source</strong> (Inbound, Referral).',
                            '<strong>Sectors</strong> are assigned to clients to group them by activity: Technology, Healthcare, Construction, etc.',
                            'Tags and sectors are <strong>fully editable and deletable</strong> by users with administrator access.',
                        ],
                    ],
                    [
                        'id'      => 'custom-fields',
                        'accent'  => 'teal',
                        'icon'    => 'sliders',
                        'title'   => 'Custom fields',
                        'summary' => 'Custom fields extend contact and client records with any additional data your process requires — no code changes needed.',
                        'items'   => [
                            'Available types: <strong>text, textarea, number, date, email, url, select, and checkbox</strong>.',
                            'Configured in <em>Settings → Custom Fields</em> and assigned to <strong>contacts</strong> or <strong>clients</strong> separately.',
                            'The <em>select</em> type lets you define a fixed list of options.',
                            'Enable <strong>"Filterable"</strong> to make the field appear in the advanced filter panel on the list page.',
                            'During import, unmapped columns can be <strong>automatically created</strong> as custom fields.',
                        ],
                        'tip' => 'Create an "Estimated budget" (number) or "Next action" (select) field to adapt the CRM to your sales workflow.',
                    ],
                    [
                        'id'      => 'imports',
                        'accent'  => 'indigo',
                        'icon'    => 'upload',
                        'title'   => 'Importing data',
                        'summary' => 'Import contacts from a CSV or XLSX file in four steps: upload the file, review the preview, map the columns, and confirm.',
                        'items'   => [
                            '<strong>Step 1 – Upload:</strong> upload a CSV or XLSX file. The first row must contain column names.',
                            '<strong>Step 2 – Preview:</strong> the system shows the first rows so you can verify the data looks correct.',
                            '<strong>Step 3 – Map columns:</strong> match each file column to a system field (or discard it / turn it into a custom field).',
                            '<strong>Step 4 – Process:</strong> the system creates contacts, skips duplicate emails, and logs errors row by row.',
                            'The <strong>import history</strong> shows the result of each upload: rows imported, skipped, and with errors.',
                            'Download the CSV template from <em>Exports → Import templates</em> to use the correct format from the start.',
                        ],
                        'tip' => 'The "full_name" column is required. If the file contains emails that already exist in the system, those rows are automatically skipped.',
                    ],
                    [
                        'id'      => 'exports',
                        'accent'  => 'sky',
                        'icon'    => 'download',
                        'title'   => 'Exporting data',
                        'summary' => 'From the Exports section you can download contacts or clients as CSV or XLSX, choosing exactly which fields to include — including related data and custom fields.',
                        'items'   => [
                            'Select the entity to export: <strong>Contacts</strong> or <strong>Clients</strong>.',
                            'Choose fields by group: <em>Basic info</em>, <em>Related data</em> (tags, linked clients, sector), and <em>Custom fields</em>.',
                            'Use the <strong>All / None</strong> buttons per group to select or deselect quickly.',
                            'Choose a format: <strong>CSV</strong> (compatible with any spreadsheet) or <strong>XLSX</strong> (requires PhpSpreadsheet).',
                            'The <strong>export history</strong> at the bottom of the page lists recent downloads with date, entity, and row count.',
                            '<strong>Import templates</strong> are also available in this section so you never start from a blank file.',
                        ],
                    ],
                    [
                        'id'      => 'search-filters',
                        'accent'  => 'cyan',
                        'icon'    => 'search',
                        'title'   => 'Search and filters',
                        'summary' => 'The CRM offers three levels of search: global search in the topbar, quick filter chips, and expandable advanced filters.',
                        'items'   => [
                            'The <strong>global search</strong> (topbar) finds contacts and clients instantly by name, email, or company.',
                            'The <strong>Filters button</strong> on the list opens the filter panel. Active filters appear as chips next to the button.',
                            'Click the <strong>× on a chip</strong> to remove that filter without affecting the rest.',
                            '<strong>Advanced filters</strong> let you filter by linked client, sector, country, creation/update dates, and filterable custom fields.',
                            'The <strong>"Reset all"</strong> link clears every active filter at once.',
                            'Filters are preserved in the URL so you can bookmark or share a specific segment.',
                        ],
                        'tip' => 'Combine multiple tags with a date range to create precise follow-up lists, such as "Hot leads created this month".',
                    ],
                    [
                        'id'      => 'users-roles',
                        'accent'  => 'slate',
                        'icon'    => 'users',
                        'title'   => 'Users and permissions',
                        'summary' => 'The system has two roles: Administrator and User. Administrators have full access; users have individually configurable permissions.',
                        'items'   => [
                            '<strong>Administrator:</strong> full access to everything, including user management, custom fields, tags, and sectors.',
                            '<strong>User:</strong> can create, edit, and delete contacts and clients according to the permissions an administrator grants.',
                            'Granular permissions: create contacts, edit contacts, delete contacts, create clients, edit clients, delete clients, export data, import data.',
                            'Manage users from <em>Settings → Users</em>. Only administrators can see this section.',
                            'Deactivate a user to revoke access without deleting their activity history.',
                        ],
                        'tip' => 'Create a User with read-only permissions for external teams who need to browse data without making changes.',
                    ],
                    [
                        'id'      => 'two-factor',
                        'accent'  => 'red',
                        'icon'    => 'shield',
                        'title'   => 'Two-factor authentication',
                        'summary' => 'Two-factor authentication (2FA) adds an extra security layer to sign-in by sending a temporary code to the user\'s email address.',
                        'items'   => [
                            'After entering email and password, the CRM sends a <strong>one-time code</strong> to the user\'s email address.',
                            'The code must be entered on the <strong>verification screen</strong> before gaining access to the system.',
                            'If the code expires or doesn\'t arrive, use the <strong>"Resend code"</strong> button to request a new one.',
                            'Each code is single-use and expires within minutes for security reasons.',
                            'Check your <strong>spam or junk mail folder</strong> if the code doesn\'t appear in your inbox.',
                        ],
                    ],
                ],
            ],
        ];
    }
}
