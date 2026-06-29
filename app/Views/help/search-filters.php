<?php
$isEs    = $locale === 'es';
$backUrl = htmlspecialchars(Auth::url('/help?lang=' . $locale), ENT_QUOTES, 'UTF-8');

$toc = $isEs ? [
    ['id' => 'global',   'label' => 'Busqueda global'],
    ['id' => 'filters',  'label' => 'Panel de filtros'],
    ['id' => 'chips',    'label' => 'Chips activos'],
    ['id' => 'advanced', 'label' => 'Filtros avanzados'],
    ['id' => 'tips',     'label' => 'Combinaciones utiles'],
] : [
    ['id' => 'global',   'label' => 'Global search'],
    ['id' => 'filters',  'label' => 'Filter panel'],
    ['id' => 'chips',    'label' => 'Active chips'],
    ['id' => 'advanced', 'label' => 'Advanced filters'],
    ['id' => 'tips',     'label' => 'Useful combinations'],
];
?>

<div class="help-back-row">
    <a href="<?= $backUrl ?>" class="help-topic-back">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
        </svg>
        <?= $isEs ? 'Centro de ayuda' : 'Help Center' ?>
    </a>
    <div class="help-lang-switch">
        <span><?= $isEs ? 'Idioma' : 'Language' ?></span>
        <div class="help-lang-pills">
            <?php foreach ($availableLocales as $code => $label): ?>
            <a class="help-lang-pill <?= $locale === $code ? 'active' : '' ?>"
               href="<?= htmlspecialchars(Auth::url('/help/search-filters?lang=' . $code), ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="help-layout help-layout-mt">

    <aside class="help-toc">
        <span class="help-toc-label"><?= $isEs ? 'Contenido' : 'Contents' ?></span>
        <nav class="help-toc-nav">
            <?php foreach ($toc as $item): ?>
            <a href="#<?= $item['id'] ?>"
               class="help-toc-item help-toc-item--cyan"
               data-section="<?= $item['id'] ?>">
                <span class="help-toc-dot"></span>
                <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
            </a>
            <?php endforeach; ?>
        </nav>
    </aside>

    <div class="help-content">

        <!-- ══════════════════════════════════ GLOBAL ══ -->
        <section class="help-card" id="global">
            <div class="help-card-head">
                <div class="help-card-icon help-card-icon--cyan">
                    <i class="ph ph-magnifying-glass"></i>
                </div>
                <div class="help-card-titles">
                    <h2><?= $isEs ? 'Busqueda global' : 'Global search' ?></h2>
                    <p class="help-card-summary">
                        <?= $isEs
                            ? 'La barra superior encuentra contactos y clientes en tiempo real mientras escribes.'
                            : 'The top bar finds contacts and clients in real time as you type.' ?>
                    </p>
                </div>
            </div>
            <div class="help-card-body">
                <?php if ($isEs): ?>
                <p class="help-p">
                    La <strong>barra de busqueda global</strong> esta siempre visible en la parte
                    superior de la aplicacion. Busca simultaneamente en <strong>contactos</strong> y
                    <strong>clientes</strong> por nombre, email y empresa, y muestra los resultados
                    en un desplegable instantaneo mientras escribes, sin necesidad de pulsar Enter.
                </p>
                <p class="help-p">
                    Cada resultado muestra el tipo de registro (contacto o cliente), el nombre completo
                    y un dato secundario (email para contactos, nombre comercial para clientes). Al
                    hacer clic en un resultado el navegador abre directamente la ficha de ese registro.
                </p>
                <div class="help-callout help-callout--cyan">
                    <i class="ph ph-lightbulb"></i>
                    <p>
                        La busqueda global es la forma mas rapida de llegar a un registro concreto.
                        Usala cuando sabes el nombre o email exacto. Para explorar segmentos, usa
                        los filtros del listado.
                    </p>
                </div>
                <?php else: ?>
                <p class="help-p">
                    The <strong>global search bar</strong> is always visible at the top of the
                    application. It searches <strong>contacts</strong> and <strong>clients</strong>
                    simultaneously by name, email, and company, and shows results in an instant
                    dropdown as you type — no Enter key needed.
                </p>
                <p class="help-p">
                    Each result shows the record type (contact or client), the full name, and a
                    secondary detail (email for contacts, commercial name for clients). Clicking a
                    result opens that record's page directly.
                </p>
                <div class="help-callout help-callout--cyan">
                    <i class="ph ph-lightbulb"></i>
                    <p>
                        Global search is the fastest way to reach a specific record. Use it when you
                        know the exact name or email. To explore segments, use the list filters.
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- ══════════════════════════════════ FILTERS ══ -->
        <section class="help-card" id="filters">
            <div class="help-card-head">
                <div class="help-card-icon help-card-icon--cyan">
                    <i class="ph ph-funnel"></i>
                </div>
                <div class="help-card-titles">
                    <h2><?= $isEs ? 'Panel de filtros' : 'Filter panel' ?></h2>
                    <p class="help-card-summary">
                        <?= $isEs
                            ? 'Segmenta el listado por cualquier campo con el boton Filters.'
                            : 'Segment the list by any field using the Filters button.' ?>
                    </p>
                </div>
            </div>
            <div class="help-card-body">
                <?php if ($isEs): ?>
                <p class="help-p">
                    Tanto en el listado de contactos como en el de clientes hay un boton
                    <strong>Filters</strong> que abre el panel de filtros. El panel se despliega
                    encima del listado y permite combinar varios criterios de busqueda al mismo tiempo.
                    Cada vez que cambias un valor el listado se actualiza en tiempo real.
                </p>
                <p class="help-p">
                    Los filtros disponibles para <strong>contactos</strong> son:
                </p>
                <ul class="help-steps">
                    <li class="help-step help-step--cyan">
                        <div><strong>Nombre / Email / Empresa</strong> — texto libre, busqueda parcial.</div>
                    </li>
                    <li class="help-step help-step--cyan">
                        <div><strong>Tags</strong> — seleccion multiple; muestra contactos que tengan <em>todos</em> los tags seleccionados.</div>
                    </li>
                    <li class="help-step help-step--cyan">
                        <div><strong>Cliente vinculado</strong> — filtra los contactos asociados a un cliente concreto.</div>
                    </li>
                    <li class="help-step help-step--cyan">
                        <div><strong>Sector</strong> — muestra contactos cuyo cliente vinculado pertenece a ese sector.</div>
                    </li>
                    <li class="help-step help-step--cyan">
                        <div><strong>Pais</strong> — campo libre.</div>
                    </li>
                    <li class="help-step help-step--cyan">
                        <div><strong>Fecha de creacion / modificacion</strong> — rango desde–hasta.</div>
                    </li>
                    <li class="help-step help-step--cyan">
                        <div><strong>Campos personalizados filtrables</strong> — cualquier campo con "Filterable" activado aparece aqui automaticamente.</div>
                    </li>
                </ul>
                <p class="help-p">
                    Los filtros de <strong>clientes</strong> son similares: nombre comercial, razon
                    social, sector, tags, pais y campos personalizados de cliente.
                </p>
                <?php else: ?>
                <p class="help-p">
                    Both the contacts list and the clients list have a <strong>Filters</strong> button
                    that opens the filter panel. The panel expands above the list and lets you combine
                    multiple search criteria at once. The list updates in real time as you change
                    any value.
                </p>
                <p class="help-p">
                    Available filters for <strong>contacts</strong>:
                </p>
                <ul class="help-steps">
                    <li class="help-step help-step--cyan">
                        <div><strong>Name / Email / Company</strong> — free text, partial match.</div>
                    </li>
                    <li class="help-step help-step--cyan">
                        <div><strong>Tags</strong> — multi-select; shows contacts that have <em>all</em> selected tags.</div>
                    </li>
                    <li class="help-step help-step--cyan">
                        <div><strong>Linked client</strong> — filters contacts associated with a specific client.</div>
                    </li>
                    <li class="help-step help-step--cyan">
                        <div><strong>Sector</strong> — shows contacts whose linked client belongs to that sector.</div>
                    </li>
                    <li class="help-step help-step--cyan">
                        <div><strong>Country</strong> — free text field.</div>
                    </li>
                    <li class="help-step help-step--cyan">
                        <div><strong>Created / updated date</strong> — from–to range.</div>
                    </li>
                    <li class="help-step help-step--cyan">
                        <div><strong>Filterable custom fields</strong> — any field with "Filterable" enabled appears here automatically.</div>
                    </li>
                </ul>
                <p class="help-p">
                    <strong>Client</strong> filters are similar: commercial name, legal name, sector,
                    tags, country, and client custom fields.
                </p>
                <?php endif; ?>
            </div>
        </section>

        <!-- ══════════════════════════════════ CHIPS ══ -->
        <section class="help-card" id="chips">
            <div class="help-card-head">
                <div class="help-card-icon help-card-icon--cyan">
                    <i class="ph ph-x-circle"></i>
                </div>
                <div class="help-card-titles">
                    <h2><?= $isEs ? 'Chips activos' : 'Active chips' ?></h2>
                    <p class="help-card-summary">
                        <?= $isEs
                            ? 'Los filtros activos aparecen como chips junto al boton Filters.'
                            : 'Active filters appear as chips next to the Filters button.' ?>
                    </p>
                </div>
            </div>
            <div class="help-card-body">
                <?php if ($isEs): ?>
                <p class="help-p">
                    Cuando hay filtros activos, cada uno aparece como un <strong>chip</strong>
                    (pastilla de texto) junto al boton Filters en la barra superior del listado.
                    Los chips te permiten ver de un vistazo que segmentacion esta aplicada sin
                    necesidad de abrir el panel.
                </p>
                <p class="help-p">
                    Cada chip tiene una <strong>X</strong> en el lado derecho. Al hacer clic en
                    ella, ese filtro concreto se elimina sin tocar el resto. Si quieres limpiar
                    todos los filtros a la vez, usa el enlace <strong>Reset all</strong> que
                    aparece al final de la fila de chips.
                </p>
                <p class="help-p">
                    Los filtros activos tambien se reflejan en la <strong>URL de la pagina</strong>
                    como parametros GET. Esto significa que puedes copiar la URL y compartirla con
                    un colega, o guardarla como marcador en el navegador, y al abrirla se cargara
                    exactamente el mismo segmento.
                </p>
                <div class="help-callout help-callout--cyan">
                    <i class="ph ph-info"></i>
                    <p>
                        El numero de registros mostrado en el listado siempre refleja los filtros
                        activos. Si el numero parece bajo, comprueba si hay chips activos antes de
                        asumir que faltan datos.
                    </p>
                </div>
                <?php else: ?>
                <p class="help-p">
                    When filters are active, each one appears as a <strong>chip</strong> next to
                    the Filters button in the list toolbar. Chips let you see at a glance what
                    segmentation is applied without opening the panel.
                </p>
                <p class="help-p">
                    Each chip has an <strong>×</strong> on the right side. Clicking it removes
                    that specific filter without affecting the others. To clear all filters at
                    once, use the <strong>Reset all</strong> link that appears at the end of the
                    chip row.
                </p>
                <p class="help-p">
                    Active filters are also reflected in the <strong>page URL</strong> as GET
                    parameters. This means you can copy the URL and share it with a colleague, or
                    save it as a browser bookmark, and opening it will load exactly the same segment.
                </p>
                <div class="help-callout help-callout--cyan">
                    <i class="ph ph-info"></i>
                    <p>
                        The record count shown in the list always reflects active filters. If the
                        number looks unexpectedly low, check for active chips before assuming data
                        is missing.
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- ══════════════════════════════════ ADVANCED ══ -->
        <section class="help-card" id="advanced">
            <div class="help-card-head">
                <div class="help-card-icon help-card-icon--cyan">
                    <i class="ph ph-sliders-horizontal"></i>
                </div>
                <div class="help-card-titles">
                    <h2><?= $isEs ? 'Filtros avanzados' : 'Advanced filters' ?></h2>
                    <p class="help-card-summary">
                        <?= $isEs
                            ? 'Rangos de fechas, campos personalizados y combinaciones multiples.'
                            : 'Date ranges, custom fields, and multi-criteria combinations.' ?>
                    </p>
                </div>
            </div>
            <div class="help-card-body">
                <?php if ($isEs): ?>
                <p class="help-p">
                    Los filtros avanzados son los que aparecen en la parte inferior del panel y
                    permiten criterios mas especificos que los campos de texto rapido. Incluyen
                    <strong>rangos de fechas</strong> (creado desde / creado hasta / modificado
                    desde / modificado hasta) y todos los <strong>campos personalizados
                    filtrables</strong> que hayas configurado.
                </p>
                <p class="help-p">
                    Los campos personalizados de tipo <code>select</code> aparecen como desplegables
                    con las opciones que definiste. Los de tipo <code>text</code> o <code>number</code>
                    aparecen como campos de texto libre. Los de tipo <code>date</code> ofrecen
                    un selector de fechas. Los de tipo <code>checkbox</code> ofrecen tres estados:
                    sin filtrar, marcado, no marcado.
                </p>
                <div class="help-callout help-callout--cyan">
                    <i class="ph ph-lightbulb"></i>
                    <p>
                        Si un campo personalizado no aparece en el panel de filtros, comprueba que
                        tiene la opcion <strong>"Filterable"</strong> activada en
                        <em>Settings → Custom Fields</em>.
                    </p>
                </div>
                <?php else: ?>
                <p class="help-p">
                    Advanced filters appear in the lower part of the panel and allow more specific
                    criteria than the quick text fields. They include <strong>date ranges</strong>
                    (created from / created to / updated from / updated to) and all
                    <strong>filterable custom fields</strong> you have configured.
                </p>
                <p class="help-p">
                    Custom fields of type <code>select</code> appear as dropdowns with the options
                    you defined. Fields of type <code>text</code> or <code>number</code> appear as
                    free-text inputs. Fields of type <code>date</code> offer a date picker. Fields
                    of type <code>checkbox</code> offer three states: unfiltered, checked,
                    unchecked.
                </p>
                <div class="help-callout help-callout--cyan">
                    <i class="ph ph-lightbulb"></i>
                    <p>
                        If a custom field doesn't appear in the filter panel, check that it has
                        the <strong>"Filterable"</strong> option enabled in
                        <em>Settings → Custom Fields</em>.
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- ══════════════════════════════════ TIPS ══ -->
        <section class="help-card" id="tips">
            <div class="help-card-head">
                <div class="help-card-icon help-card-icon--cyan">
                    <i class="ph ph-star"></i>
                </div>
                <div class="help-card-titles">
                    <h2><?= $isEs ? 'Combinaciones utiles' : 'Useful combinations' ?></h2>
                    <p class="help-card-summary">
                        <?= $isEs
                            ? 'Ejemplos de segmentos habituales que puedes construir en segundos.'
                            : 'Examples of common segments you can build in seconds.' ?>
                    </p>
                </div>
            </div>
            <div class="help-card-body">
                <?php if ($isEs): ?>
                <p class="help-p">
                    Combinar varios filtros al mismo tiempo es donde el sistema muestra su potencia.
                    Aqui hay algunos ejemplos de segmentos habituales:
                </p>

                <p class="help-sub-label"><?= $isEs ? 'Contactos' : 'Contacts' ?></p>
                <ul class="help-steps">
                    <li class="help-step help-step--cyan">
                        <div>
                            <strong><?= $isEs ? 'Leads calientes de este mes' : 'Hot leads this month' ?></strong><br>
                            <?= $isEs
                                ? 'Tags: <em>Lead + Hot</em> · Creado desde: primer dia del mes.'
                                : 'Tags: <em>Lead + Hot</em> · Created from: first day of the month.' ?>
                        </div>
                    </li>
                    <li class="help-step help-step--cyan">
                        <div>
                            <strong><?= $isEs ? 'Contactos de un cliente concreto' : 'Contacts from a specific client' ?></strong><br>
                            <?= $isEs
                                ? 'Cliente vinculado: <em>[nombre del cliente]</em>.'
                                : 'Linked client: <em>[client name]</em>.' ?>
                        </div>
                    </li>
                    <li class="help-step help-step--cyan">
                        <div>
                            <strong><?= $isEs ? 'Prospectos sin actividad reciente' : 'Prospects with no recent activity' ?></strong><br>
                            <?= $isEs
                                ? 'Tags: <em>Prospect</em> · Modificado hasta: hace 30 dias.'
                                : 'Tags: <em>Prospect</em> · Updated to: 30 days ago.' ?>
                        </div>
                    </li>
                </ul>

                <p class="help-sub-label"><?= $isEs ? 'Clientes' : 'Clients' ?></p>
                <ul class="help-steps">
                    <li class="help-step help-step--cyan">
                        <div>
                            <strong><?= $isEs ? 'Clientes del sector Tecnologia en Espana' : 'Tech sector clients in Spain' ?></strong><br>
                            <?= $isEs
                                ? 'Sector: <em>Tecnologia</em> · Pais: <em>Spain</em>.'
                                : 'Sector: <em>Technology</em> · Country: <em>Spain</em>.' ?>
                        </div>
                    </li>
                    <li class="help-step help-step--cyan">
                        <div>
                            <strong><?= $isEs ? 'Clientes con renovacion proxima' : 'Clients with upcoming renewal' ?></strong><br>
                            <?= $isEs
                                ? 'Si tienes un campo personalizado "Fecha de renovacion" (date, filterable): filtrar por rango del proximo mes.'
                                : 'If you have a "Renewal date" custom field (date, filterable): filter by next month\'s range.' ?>
                        </div>
                    </li>
                </ul>

                <div class="help-callout help-callout--cyan">
                    <i class="ph ph-info"></i>
                    <p>
                        <?= $isEs
                            ? 'Recuerda que la URL guarda el estado de los filtros. Una vez construido un segmento util, guarda la URL como marcador para reutilizarlo en el futuro sin configurarlo de nuevo.'
                            : 'Remember that the URL saves filter state. Once you\'ve built a useful segment, save the URL as a bookmark to reuse it later without reconfiguring.' ?>
                    </p>
                </div>
                <?php else: ?>
                <p class="help-p">
                    Combining multiple filters at once is where the system shows its power. Here are
                    some examples of common segments:
                </p>

                <p class="help-sub-label">Contacts</p>
                <ul class="help-steps">
                    <li class="help-step help-step--cyan">
                        <div>
                            <strong>Hot leads this month</strong><br>
                            Tags: <em>Lead + Hot</em> · Created from: first day of the month.
                        </div>
                    </li>
                    <li class="help-step help-step--cyan">
                        <div>
                            <strong>Contacts from a specific client</strong><br>
                            Linked client: <em>[client name]</em>.
                        </div>
                    </li>
                    <li class="help-step help-step--cyan">
                        <div>
                            <strong>Prospects with no recent activity</strong><br>
                            Tags: <em>Prospect</em> · Updated to: 30 days ago.
                        </div>
                    </li>
                </ul>

                <p class="help-sub-label">Clients</p>
                <ul class="help-steps">
                    <li class="help-step help-step--cyan">
                        <div>
                            <strong>Tech sector clients in Spain</strong><br>
                            Sector: <em>Technology</em> · Country: <em>Spain</em>.
                        </div>
                    </li>
                    <li class="help-step help-step--cyan">
                        <div>
                            <strong>Clients with upcoming renewal</strong><br>
                            If you have a "Renewal date" custom field (date, filterable): filter by
                            next month's range.
                        </div>
                    </li>
                </ul>

                <div class="help-callout help-callout--cyan">
                    <i class="ph ph-info"></i>
                    <p>
                        The URL saves filter state. Once you've built a useful segment, save the URL
                        as a bookmark to reuse it later without reconfiguring.
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </section>

    </div>
</div>