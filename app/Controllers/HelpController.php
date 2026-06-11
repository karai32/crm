<?php

class HelpController
{
    public function index(): void
    {
        Auth::requireLogin();

        $locale = $this->localeFromRequest();
        $content = $this->content()[$locale];

        View::render('help/index', [
            'title' => $content['page_title'],
            'styles' => ['help.css'],
            'locale' => $locale,
            'available_locales' => [
                'es' => 'ES',
                'en' => 'EN',
            ],
            'content' => $content,
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
            'es' => [
                'page_title' => 'Centro de ayuda',
                'title' => 'Centro de ayuda',
                'intro' => 'Guias practicas para gestionar contactos, clientes, importaciones, exportaciones y configuracion del CRM.',
                'language_label' => 'Idioma',
                'quick_topics_label' => 'Temas rapidos',
                'sections' => [
                    [
                        'id' => 'getting-started',
                        'title' => 'Primeros pasos',
                        'summary' => 'Usa el menu lateral para navegar entre los modulos principales y la barra superior para buscar contactos o clientes.',
                        'items' => [
                            'Dashboard muestra indicadores principales y actividad reciente.',
                            'Contacts es el lugar principal para crear, editar y clasificar personas.',
                            'Clients guarda empresas u organizaciones y sus contactos asociados.',
                            'El menu de perfil permite acceder a tu cuenta y cerrar sesion.',
                        ],
                    ],
                    [
                        'id' => 'contacts',
                        'title' => 'Contactos',
                        'summary' => 'Los contactos son la entidad principal del CRM y pueden tener tags, clientes relacionados y campos personalizados.',
                        'items' => [
                            'Crea un contacto con nombre, email, telefono y estado de empresa si aplica.',
                            'Relaciona un contacto con uno o varios clientes desde su ficha.',
                            'Usa tags para segmentar listas como Lead, Client, Partner, Hot o Pending.',
                            'Los campos personalizados permiten guardar informacion adicional sin tocar codigo.',
                        ],
                    ],
                    [
                        'id' => 'clients',
                        'title' => 'Clientes',
                        'summary' => 'Los clientes representan empresas, marcas u organizaciones relacionadas con tus contactos.',
                        'items' => [
                            'Registra nombre comercial, razon social, direccion, pais y sector.',
                            'Un cliente puede tener multiples contactos asociados.',
                            'Un mismo contacto puede participar en varias organizaciones.',
                            'Los sectores ayudan a filtrar y analizar la base de datos.',
                        ],
                    ],
                    [
                        'id' => 'tags-sectors',
                        'title' => 'Tags y sectores',
                        'summary' => 'Tags y sectores mantienen la informacion ordenada y facil de filtrar.',
                        'items' => [
                            'Tags clasifican contactos por estado, origen, prioridad o campana.',
                            'Sectors clasifican clientes por industria o actividad.',
                            'Los administradores pueden crear, editar o eliminar tags y sectores.',
                        ],
                    ],
                    [
                        'id' => 'custom-fields',
                        'title' => 'Campos personalizados',
                        'summary' => 'Los campos personalizados adaptan el CRM a cada proceso comercial.',
                        'items' => [
                            'Tipos disponibles: text, textarea, number, date, email, url, select y checkbox.',
                            'Pueden aplicarse a contactos o clientes.',
                            'Tambien pueden crearse durante una importacion si una columna no existe en el sistema.',
                            'Los campos marcados como filtrables aparecen en busquedas avanzadas.',
                        ],
                    ],
                    [
                        'id' => 'imports',
                        'title' => 'Importacion de datos',
                        'summary' => 'Importa contactos desde CSV o XLSX con vista previa y mapeo de columnas.',
                        'items' => [
                            'La primera fila del archivo debe contener los nombres de columnas.',
                            'first_name es obligatorio para crear un contacto.',
                            'Los emails duplicados se omiten para evitar registros repetidos.',
                            'Las columnas no mapeadas pueden convertirse en campos personalizados.',
                            'El historial de importaciones conserva filas importadas, omitidas y errores.',
                        ],
                    ],
                    [
                        'id' => 'exports',
                        'title' => 'Exportacion de datos',
                        'summary' => 'Exporta contactos filtrados a CSV o XLSX y selecciona que campos incluir.',
                        'items' => [
                            'Aplica filtros antes de exportar para limitar el resultado.',
                            'Selecciona campos base, relaciones y campos personalizados.',
                            'La exportacion respeta los filtros activos.',
                        ],
                    ],
                    [
                        'id' => 'search-filters',
                        'title' => 'Busqueda y filtros',
                        'summary' => 'Combina busqueda rapida, filtros basicos y filtros avanzados para segmentar informacion.',
                        'items' => [
                            'La barra superior busca contactos y clientes por nombre y datos principales.',
                            'Los listados permiten filtrar por tags, sector, cliente, fechas y campos personalizados.',
                            'Puedes combinar varios filtros para crear segmentos mas precisos.',
                        ],
                    ],
                    [
                        'id' => 'users-roles',
                        'title' => 'Usuarios y roles',
                        'summary' => 'El sistema contempla administradores y usuarios con permisos mas limitados.',
                        'items' => [
                            'Administrators tienen acceso completo a configuracion, importaciones y usuarios.',
                            'Users pueden trabajar con informacion operativa segun permisos disponibles.',
                            'Mantener usuarios activos y actualizados ayuda a proteger el CRM.',
                        ],
                    ],
                    [
                        'id' => 'two-factor',
                        'title' => 'Autenticacion de dos factores',
                        'summary' => 'La verificacion en dos pasos anade una capa adicional de seguridad al inicio de sesion.',
                        'items' => [
                            'Tras introducir email y password, el CRM envia un codigo al correo del usuario.',
                            'El codigo es temporal y debe introducirse en la pantalla de verificacion.',
                            'Si el codigo caduca, solicita uno nuevo desde la misma pantalla.',
                        ],
                    ],
                ],
            ],
            'en' => [
                'page_title' => 'Help Center',
                'title' => 'Help Center',
                'intro' => 'Practical guides for managing contacts, clients, imports, exports, and CRM settings.',
                'language_label' => 'Language',
                'quick_topics_label' => 'Quick topics',
                'sections' => [
                    [
                        'id' => 'getting-started',
                        'title' => 'Getting started',
                        'summary' => 'Use the sidebar to move between core modules and the topbar to search contacts or clients.',
                        'items' => [
                            'Dashboard shows key metrics and recent activity.',
                            'Contacts is the main place to create, edit, and classify people.',
                            'Clients stores companies or organizations and their linked contacts.',
                            'The profile menu gives access to your account and logout.',
                        ],
                    ],
                    [
                        'id' => 'contacts',
                        'title' => 'Contacts',
                        'summary' => 'Contacts are the main CRM entity and can have tags, linked clients, and custom fields.',
                        'items' => [
                            'Create a contact with name, email, phone, and company indicator when needed.',
                            'Link one contact to one or more clients from the contact record.',
                            'Use tags to segment lists such as Lead, Client, Partner, Hot, or Pending.',
                            'Custom fields store additional information without code changes.',
                        ],
                    ],
                    [
                        'id' => 'clients',
                        'title' => 'Clients',
                        'summary' => 'Clients represent companies, brands, or organizations related to your contacts.',
                        'items' => [
                            'Store commercial name, legal name, address, country, and sector.',
                            'A client can have multiple linked contacts.',
                            'The same contact can participate in several organizations.',
                            'Sectors help filter and analyze the database.',
                        ],
                    ],
                    [
                        'id' => 'tags-sectors',
                        'title' => 'Tags and sectors',
                        'summary' => 'Tags and sectors keep information organized and easy to filter.',
                        'items' => [
                            'Tags classify contacts by status, source, priority, or campaign.',
                            'Sectors classify clients by industry or activity.',
                            'Administrators can create, edit, or delete tags and sectors.',
                        ],
                    ],
                    [
                        'id' => 'custom-fields',
                        'title' => 'Custom fields',
                        'summary' => 'Custom fields adapt the CRM to each commercial process.',
                        'items' => [
                            'Available types: text, textarea, number, date, email, url, select, and checkbox.',
                            'They can apply to contacts or clients.',
                            'They can also be created during import when a column does not exist in the system.',
                            'Fields marked as filterable appear in advanced searches.',
                        ],
                    ],
                    [
                        'id' => 'imports',
                        'title' => 'Importing data',
                        'summary' => 'Import contacts from CSV or XLSX with preview and column mapping.',
                        'items' => [
                            'The first row of the file must contain column names.',
                            'first_name is required to create a contact.',
                            'Duplicate emails are skipped to avoid repeated records.',
                            'Unmapped columns can become custom fields.',
                            'Import history keeps imported, skipped, and error rows.',
                        ],
                    ],
                    [
                        'id' => 'exports',
                        'title' => 'Exporting data',
                        'summary' => 'Export filtered contacts to CSV or XLSX and choose which fields to include.',
                        'items' => [
                            'Apply filters before exporting to limit the result.',
                            'Select base fields, relationships, and custom fields.',
                            'The export respects active filters.',
                        ],
                    ],
                    [
                        'id' => 'search-filters',
                        'title' => 'Search and filters',
                        'summary' => 'Combine quick search, basic filters, and advanced filters to segment information.',
                        'items' => [
                            'The topbar searches contacts and clients by name and key details.',
                            'Lists can filter by tags, sector, client, dates, and custom fields.',
                            'You can combine several filters to create more precise segments.',
                        ],
                    ],
                    [
                        'id' => 'users-roles',
                        'title' => 'Users and roles',
                        'summary' => 'The system supports administrators and users with more limited access.',
                        'items' => [
                            'Administrators have full access to settings, imports, and users.',
                            'Users can work with operational information according to available permissions.',
                            'Keeping active users updated helps protect the CRM.',
                        ],
                    ],
                    [
                        'id' => 'two-factor',
                        'title' => 'Two-factor authentication',
                        'summary' => 'Two-step verification adds another security layer to sign-in.',
                        'items' => [
                            'After email and password, the CRM sends a code to the user email address.',
                            'The code is temporary and must be entered on the verification screen.',
                            'If the code expires, request a new one from the same screen.',
                        ],
                    ],
                ],
            ],
        ];
    }
}
