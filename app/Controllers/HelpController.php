<?php

class HelpController
{
    public function index(): void
    {
        Auth::requireLogin();

        $content = $this->content()[$this->locale()];

        $cards = [];

        foreach ($content['sections'] as $section) {
            if (!$this->canViewTopic($section['id'])) {
                continue;
            }
            $cards[] = [
                'id'      => $section['id'],
                'icon'    => $section['icon'],
                'title'   => $section['title'],
                'summary' => $section['summary'],
            ];
        }

        if ($this->canViewTopic('api')) {
            $cards[] = [
                'id'      => 'api',
                'icon'    => 'key',
                'title'   => 'API Reference',
                'summary' => $content['api_summary'],
            ];
        }

        if ($this->canViewTopic('technical-guide')) {
            $cards[] = [
                'id'      => 'technical-guide',
                'icon'    => 'code',
                'title'   => $content['technical_guide_title'],
                'summary' => $content['technical_guide_summary'],
            ];
        }

        View::render('help/index', [
            'title'   => $content['page_title'],
            'styles'  => ['help.css'],
            'content' => $content,
            'cards'   => $cards,
        ]);
    }

    public function show(): void
    {
        Auth::requireLogin();

        $topic  = (string) ($_GET['topic'] ?? '');
        $locale = $this->locale();

        if (!$this->canViewTopic($topic)) {
            Auth::redirect('/help');
            return;
        }

        if ($topic === 'api') {
            View::render('help/api', [
                'title'   => 'API Reference',
                'styles'  => ['help.css', 'api.css'],
                'scripts' => ['help.js'],
                'locale'  => $locale,
            ]);
            return;
        }

        if ($topic === 'technical-guide') {
            View::render('help/technical-guide', [
                'title'   => 'Platform Technical Guide',
                'styles'  => ['help.css'],
                'scripts' => ['help.js'],
                'locale'  => $locale,
            ]);
            return;
        }

        foreach ($this->content()[$locale]['sections'] as $section) {
            if ($section['id'] === $topic) {
                View::render('help/show', [
                    'title'   => $section['title'],
                    'styles'  => ['help.css'],
                    'locale'  => $locale,
                    'section' => $section,
                ]);
                return;
            }
        }

        Auth::redirect('/help');
    }

    /**
     * Role-based visibility: a topic is hidden when it documents features
     * the current user cannot access. Mirrors the checks used by each
     * module's controller (Auth::can / Auth::requireAdmin).
     */
    private function canViewTopic(string $topic): bool
    {
        return match ($topic) {
            'tags-sectors'  => Auth::can('tags.manage') || Auth::can('sectors.manage'),
            'custom-fields' => Auth::can('custom_fields.manage'),
            'imports'       => Auth::can('imports.manage'),
            'exports'       => Auth::can('exports.use'),
            'users-roles',
            'api',
            'technical-guide' => Auth::isAdmin(),
            default           => true,
        };
    }

    // Help content follows the global UI language (Settings). It exists in
    // Spanish and English; other UI locales fall back to English.
    private function locale(): string
    {
        return Lang::locale() === 'es' ? 'es' : 'en';
    }

    private function content(): array
    {
        return [

            // ── Spanish ────────────────────────────────────────────────────
            'es' => [
                'page_title' => 'Centro de ayuda',
                'title'      => 'Centro de ayuda',
                'intro'      => 'Referencia rapida de los modulos del CRM.',

                'api_summary'             => 'API REST: autenticacion con claves y scopes; endpoints de contactos, clientes, sectores y tags.',
                'technical_guide_title'   => 'Guia tecnica de la plataforma',
                'technical_guide_summary' => 'Arquitectura, esquema de base de datos, modelo de seguridad y despliegue. Solo en ingles.',

                'sections' => [
                    [
                        'id'      => 'getting-started',
                        'icon'    => 'map',
                        'title'   => 'Primeros pasos',
                        'summary' => 'Estructura de la interfaz, busqueda global y cuenta.',
                        'items'   => [
                            'El <strong>menu lateral</strong> muestra los modulos disponibles; las secciones sin permiso se ocultan.',
                            'La <strong>busqueda global</strong> de la barra superior localiza contactos y clientes por nombre, email o empresa mientras escribes.',
                            'El <strong>Dashboard</strong> muestra los totales de contactos, clientes, sectores y tags.',
                            '<strong>"Recordarme"</strong> en el login mantiene la sesion 30 dias.',
                            'Con la <strong>verificacion en dos pasos</strong> activada, tras el login se envia un codigo de un solo uso por email.',
                            'El idioma de la interfaz se cambia en <strong>Ajustes</strong>; el centro de ayuda lo sigue automaticamente.',
                        ],
                    ],
                    [
                        'id'      => 'contacts',
                        'icon'    => 'person',
                        'title'   => 'Contactos',
                        'summary' => 'Fichas de personas: campos, clientes vinculados, tags y acciones en bloque.',
                        'items'   => [
                            'Campos: <strong>nombre completo</strong> (obligatorio), email, telefono y empresa.',
                            'Un contacto puede vincularse a <strong>varios clientes</strong> desde su ficha.',
                            'Los <strong>tags</strong> marcan el estado (Lead, Client, Hot, Cold...); se aplican en la ficha o en bloque desde el listado.',
                            'Acciones en bloque sobre la seleccion: anadir/quitar tags, vincular a cliente, eliminar.',
                            'Filtros por nombre, email, tag, cliente, sector, fechas y campos personalizados filtrables.',
                        ],
                    ],
                    [
                        'id'      => 'clients',
                        'icon'    => 'building',
                        'title'   => 'Clientes',
                        'summary' => 'Fichas de empresas: campos, sector y contactos vinculados.',
                        'items'   => [
                            'Campos: <strong>nombre comercial</strong> (obligatorio), razon social, CIF/NIF, direccion, codigo postal, ciudad, provincia, pais, web y notas.',
                            'Un cliente agrupa <strong>varios contactos</strong>; un contacto puede pertenecer a varios clientes.',
                            'El <strong>sector</strong> clasifica el cliente por industria y funciona como filtro.',
                            'Tags y acciones en bloque funcionan igual que en contactos.',
                            'Los <strong>campos personalizados</strong> de clientes son independientes de los de contactos.',
                        ],
                    ],
                    [
                        'id'      => 'tags-sectors',
                        'icon'    => 'tag',
                        'title'   => 'Tags y sectores',
                        'summary' => 'Clasificacion: tags de colores para contactos y clientes, sectores para clientes.',
                        'items'   => [
                            'Un tag tiene <strong>nombre y color</strong>, visibles en listados y fichas.',
                            'Los tags se aplican a contactos y clientes, de forma individual o en bloque.',
                            'Los <strong>sectores</strong> solo se asignan a clientes y los agrupan por industria.',
                            'Eliminar un tag lo quita de todas las fichas; un sector en uso se <strong>desactiva</strong> en lugar de eliminarse.',
                        ],
                    ],
                    [
                        'id'      => 'custom-fields',
                        'icon'    => 'sliders',
                        'title'   => 'Campos personalizados',
                        'summary' => 'Campos adicionales en contactos y clientes, sin tocar codigo.',
                        'items'   => [
                            'Tipos: <strong>text, textarea, number, date, email, url, select, checkbox</strong>.',
                            'Se definen por separado para <strong>contactos</strong> y <strong>clientes</strong>.',
                            'Los campos <strong>select</strong> usan una lista cerrada de opciones; cada campo admite un valor por defecto.',
                            'Los campos <strong>"Filterable"</strong> aparecen en el panel de filtros avanzados del listado.',
                            'Durante una importacion, las columnas sin mapear pueden <strong>crearse automaticamente</strong> como campos personalizados.',
                        ],
                    ],
                    [
                        'id'      => 'imports',
                        'icon'    => 'upload',
                        'title'   => 'Importacion',
                        'summary' => 'Carga de contactos o clientes desde CSV/XLSX.',
                        'items'   => [
                            'Formatos: <strong>CSV y XLSX</strong>; la primera fila debe contener los encabezados.',
                            'Flujo: <strong>subir → previsualizar → mapear columnas → procesar</strong>.',
                            'Para contactos la columna <strong>full_name</strong> es obligatoria; las filas con email ya existente se omiten.',
                            'Cada fila y cada error quedan registrados; el historial muestra importadas / omitidas / con error por lote.',
                            'Descarga las <strong>plantillas</strong> con los encabezados correctos desde Exports.',
                        ],
                    ],
                    [
                        'id'      => 'exports',
                        'icon'    => 'download',
                        'title'   => 'Exportacion',
                        'summary' => 'Descarga de contactos o clientes en CSV o XLSX.',
                        'items'   => [
                            'Elige la entidad (<strong>contactos / clientes</strong>) y el formato (<strong>CSV / XLSX</strong>).',
                            'Seleccion de campos por grupos: datos basicos, datos relacionados (tags, clientes, sector) y campos personalizados.',
                            'El <strong>historial</strong> muestra las exportaciones con fecha, entidad y numero de filas.',
                            'Las <strong>plantillas de importacion</strong> tambien se descargan desde esta pagina.',
                        ],
                    ],
                    [
                        'id'      => 'search-filters',
                        'icon'    => 'search',
                        'title'   => 'Busqueda y filtros',
                        'summary' => 'Busqueda global, filtros del listado y filtros avanzados.',
                        'items'   => [
                            'La <strong>busqueda global</strong> (barra superior) da resultados instantaneos de contactos y clientes.',
                            'El boton <strong>Filters</strong> abre el panel; los filtros activos aparecen como chips y se quitan uno a uno.',
                            'Filtros avanzados: cliente vinculado, sector, pais, fechas de creacion/edicion y campos personalizados filtrables.',
                            'Los filtros se conservan en la <strong>URL</strong>: puedes guardar o compartir una vista filtrada.',
                            '<strong>"Reset all"</strong> limpia todos los filtros.',
                        ],
                    ],
                    [
                        'id'      => 'users-roles',
                        'icon'    => 'users',
                        'title'   => 'Usuarios y permisos',
                        'summary' => 'Roles, permisos por usuario y gestion de cuentas.',
                        'items'   => [
                            'Dos roles: <strong>Administrador</strong> (acceso total) y <strong>Usuario</strong> (permisos configurables).',
                            'Permisos por usuario: crear/editar/eliminar contactos y clientes, importar, exportar, gestionar tags, sectores y campos personalizados.',
                            'Un permiso sin configuracion explicita esta <strong>permitido por defecto</strong>; para restringir, desmarcalo.',
                            '<strong>Desactivar</strong> un usuario revoca el acceso pero conserva el historial; un usuario desactivado puede eliminarse definitivamente.',
                            'Se gestionan en <strong>Users</strong> (solo administradores).',
                        ],
                    ],
                ],
            ],

            // ── English ────────────────────────────────────────────────────
            'en' => [
                'page_title' => 'Help Center',
                'title'      => 'Help Center',
                'intro'      => 'Quick reference for the CRM modules.',

                'api_summary'             => 'REST API: key-based authentication with scopes; endpoints for contacts, clients, sectors and tags.',
                'technical_guide_title'   => 'Platform Technical Guide',
                'technical_guide_summary' => 'Architecture, database schema, security model and deployment. English only.',

                'sections' => [
                    [
                        'id'      => 'getting-started',
                        'icon'    => 'map',
                        'title'   => 'Getting started',
                        'summary' => 'Interface layout, global search and account basics.',
                        'items'   => [
                            'The <strong>sidebar</strong> lists the modules available to you; sections you have no permission for are hidden.',
                            'The <strong>global search</strong> in the topbar finds contacts and clients by name, email or company as you type.',
                            'The <strong>Dashboard</strong> shows current totals for contacts, clients, sectors and tags.',
                            '<strong>"Remember me"</strong> at login keeps the session for 30 days.',
                            'With <strong>two-factor authentication</strong> enabled, a one-time code is emailed after login.',
                            'The interface language is set in <strong>Settings</strong>; the Help Center follows it.',
                        ],
                    ],
                    [
                        'id'      => 'contacts',
                        'icon'    => 'person',
                        'title'   => 'Contacts',
                        'summary' => 'Records for people: fields, linked clients, tags and bulk actions.',
                        'items'   => [
                            'Fields: <strong>full name</strong> (required), email, phone and company.',
                            'A contact can be linked to <strong>any number of clients</strong> from its record.',
                            '<strong>Tags</strong> track status (Lead, Client, Hot, Cold...); apply them on the record or in bulk from the list.',
                            'Bulk actions on the list selection: add/remove tags, link to a client, delete.',
                            'Filters cover name, email, tag, client, sector, dates and filterable custom fields.',
                        ],
                    ],
                    [
                        'id'      => 'clients',
                        'icon'    => 'building',
                        'title'   => 'Clients',
                        'summary' => 'Records for companies: fields, sector and linked contacts.',
                        'items'   => [
                            'Fields: <strong>commercial name</strong> (required), legal name, CIF/NIF, address, postal code, city, province, country, website and notes.',
                            'A client groups <strong>any number of contacts</strong>; a contact can belong to several clients.',
                            'The <strong>sector</strong> classifies the client by industry and is available as a filter.',
                            'Tags and bulk actions work exactly as in contacts.',
                            'Client <strong>custom fields</strong> are independent from contact custom fields.',
                        ],
                    ],
                    [
                        'id'      => 'tags-sectors',
                        'icon'    => 'tag',
                        'title'   => 'Tags and sectors',
                        'summary' => 'Classification: coloured tags for contacts and clients, sectors for clients.',
                        'items'   => [
                            'A tag has a <strong>name and colour</strong>, shown in lists and records.',
                            'Tags apply to both contacts and clients — individually or in bulk.',
                            '<strong>Sectors</strong> apply to clients only and group them by industry.',
                            'Deleting a tag removes it from every record; a sector in use is <strong>deactivated</strong> instead of deleted.',
                        ],
                    ],
                    [
                        'id'      => 'custom-fields',
                        'icon'    => 'sliders',
                        'title'   => 'Custom fields',
                        'summary' => 'Extra fields on contact and client records, defined without code changes.',
                        'items'   => [
                            'Types: <strong>text, textarea, number, date, email, url, select, checkbox</strong>.',
                            'Fields are defined separately for <strong>contacts</strong> and for <strong>clients</strong>.',
                            '<strong>Select</strong> fields use a fixed option list; a default value can be set per field.',
                            'Fields marked <strong>"Filterable"</strong> appear in the advanced filter panel of the list.',
                            'During an import, unmapped columns can be <strong>created automatically</strong> as custom fields.',
                        ],
                    ],
                    [
                        'id'      => 'imports',
                        'icon'    => 'upload',
                        'title'   => 'Imports',
                        'summary' => 'Loading contacts or clients from CSV/XLSX files.',
                        'items'   => [
                            'Formats: <strong>CSV and XLSX</strong>; the first row must contain column headers.',
                            'Flow: <strong>upload → preview → map columns → process</strong>.',
                            'For contacts the <strong>full_name</strong> column is required; rows whose email already exists are skipped.',
                            'Every row and every error is logged; the history shows imported / skipped / failed counts per batch.',
                            'Download ready-made <strong>templates</strong> with correct headers from the Exports page.',
                        ],
                    ],
                    [
                        'id'      => 'exports',
                        'icon'    => 'download',
                        'title'   => 'Exports',
                        'summary' => 'Downloading contacts or clients as CSV or XLSX.',
                        'items'   => [
                            'Choose the entity (<strong>contacts / clients</strong>) and the format (<strong>CSV / XLSX</strong>).',
                            'Select fields by group: basic info, related data (tags, linked clients, sector) and custom fields.',
                            'The <strong>history</strong> lists past exports with date, entity and row count.',
                            '<strong>Import templates</strong> are downloaded from this page as well.',
                        ],
                    ],
                    [
                        'id'      => 'search-filters',
                        'icon'    => 'search',
                        'title'   => 'Search and filters',
                        'summary' => 'Global search, list filters and advanced filters.',
                        'items'   => [
                            '<strong>Global search</strong> (topbar): instant results across contacts and clients.',
                            'The <strong>Filters</strong> button opens the panel; active filters appear as chips and can be removed one by one.',
                            'Advanced filters: linked client, sector, country, creation/update dates and filterable custom fields.',
                            'Filters are kept in the <strong>URL</strong>, so a filtered view can be bookmarked or shared.',
                            '<strong>"Reset all"</strong> clears every active filter.',
                        ],
                    ],
                    [
                        'id'      => 'users-roles',
                        'icon'    => 'users',
                        'title'   => 'Users and permissions',
                        'summary' => 'Roles, per-user permissions and account management.',
                        'items'   => [
                            'Two roles: <strong>Administrator</strong> (full access) and <strong>User</strong> (configurable permissions).',
                            'Per-user permissions: create/edit/delete contacts and clients, import, export, manage tags, sectors and custom fields.',
                            'A permission without an explicit setting is <strong>allowed by default</strong>; deny it explicitly to restrict.',
                            '<strong>Deactivating</strong> a user revokes access but keeps history; a deactivated user can then be permanently deleted.',
                            'Managed in <strong>Users</strong> (administrators only).',
                        ],
                    ],
                ],
            ],
        ];
    }
}
