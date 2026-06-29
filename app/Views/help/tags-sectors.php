<?php
$isEs    = $locale === 'es';
$backUrl = htmlspecialchars(Auth::url('/help?lang=' . $locale), ENT_QUOTES, 'UTF-8');

$toc = $isEs ? [
    ['id' => 'what',    'label' => 'Que son los tags'],
    ['id' => 'colors',  'label' => 'Colores'],
    ['id' => 'apply',   'label' => 'Como aplicar tags'],
    ['id' => 'sectors', 'label' => 'Sectores'],
    ['id' => 'manage',  'label' => 'Gestion'],
] : [
    ['id' => 'what',    'label' => 'What are tags'],
    ['id' => 'colors',  'label' => 'Colors'],
    ['id' => 'apply',   'label' => 'How to apply tags'],
    ['id' => 'sectors', 'label' => 'Sectors'],
    ['id' => 'manage',  'label' => 'Management'],
];
?>

<!-- Back + lang -->
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
               href="<?= htmlspecialchars(Auth::url('/help/tags-sectors?lang=' . $code), ENT_QUOTES, 'UTF-8') ?>">
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
               class="help-toc-item help-toc-item--amber"
               data-section="<?= $item['id'] ?>">
                <span class="help-toc-dot"></span>
                <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
            </a>
            <?php endforeach; ?>
        </nav>
    </aside>

    <div class="help-content">

        <!-- ══════════════════════════════════ WHAT ══ -->
        <section class="help-card" id="what">
            <div class="help-card-head">
                <div class="help-card-icon help-card-icon--amber">
                    <i class="ph ph-tag"></i>
                </div>
                <div class="help-card-titles">
                    <h2><?= $isEs ? 'Que son los tags' : 'What are tags' ?></h2>
                    <p class="help-card-summary">
                        <?= $isEs
                            ? 'Etiquetas libres y flexibles para clasificar contactos y clientes segun cualquier criterio.'
                            : 'Free, flexible labels to classify contacts and clients by any criteria.' ?>
                    </p>
                </div>
            </div>
            <div class="help-card-body">
                <?php if ($isEs): ?>
                <p class="help-p">
                    Un tag es una etiqueta de texto con color que puedes asignar a contactos y clientes
                    para clasificarlos, filtrarlos y actuar sobre ellos en grupo. A diferencia del sector
                    — que define la industria de un cliente y solo admite uno —, los tags son
                    <strong>multiples y libres</strong>: un mismo registro puede tener tantos tags como
                    necesites, y puedes crearlos con cualquier nombre.
                </p>
                <p class="help-p">
                    Los tags se comparten entre contactos y clientes: un tag llamado <em>Priority</em>
                    puede aplicarse tanto a un contacto como a un cliente. Son el mismo tag en el sistema,
                    aunque la asignacion sea independiente para cada entidad.
                </p>

                <p class="help-sub-label"><?= $isEs ? 'Patrones de uso habituales' : 'Common usage patterns' ?></p>
                <ul class="help-steps">
                    <li class="help-step help-step--amber">
                        <strong><?= $isEs ? 'Estado del embudo:' : 'Funnel status:' ?></strong>
                        <?= $isEs
                            ? 'Lead, Prospect, Client, Churned — para trazar el ciclo de vida de cada contacto.'
                            : 'Lead, Prospect, Client, Churned — to track the lifecycle of each contact.' ?>
                    </li>
                    <li class="help-step help-step--amber">
                        <strong><?= $isEs ? 'Temperatura:' : 'Temperature:' ?></strong>
                        <?= $isEs
                            ? 'Hot, Warm, Cold — para priorizar el seguimiento comercial.'
                            : 'Hot, Warm, Cold — to prioritise sales follow-up.' ?>
                    </li>
                    <li class="help-step help-step--amber">
                        <strong><?= $isEs ? 'Origen:' : 'Source:' ?></strong>
                        <?= $isEs
                            ? 'Inbound, Referral, Event, Ads — para medir la procedencia de los contactos.'
                            : 'Inbound, Referral, Event, Ads — to measure where contacts come from.' ?>
                    </li>
                    <li class="help-step help-step--amber">
                        <strong><?= $isEs ? 'Tipo de cuenta:' : 'Account type:' ?></strong>
                        <?= $isEs
                            ? 'Enterprise, SME, Agency, Partner — para segmentar la cartera de clientes.'
                            : 'Enterprise, SME, Agency, Partner — to segment the client portfolio.' ?>
                    </li>
                </ul>
                <?php else: ?>
                <p class="help-p">
                    A tag is a coloured text label you can assign to contacts and clients to classify,
                    filter, and act on them as a group. Unlike the sector — which defines a client's
                    industry and only allows one — tags are <strong>multiple and free-form</strong>:
                    a single record can have as many tags as you need, and you can create them with
                    any name.
                </p>
                <p class="help-p">
                    Tags are shared between contacts and clients: a tag called <em>Priority</em> can
                    be applied to both a contact and a client. It is the same tag in the system, even
                    though the assignment is independent for each entity.
                </p>

                <p class="help-sub-label">Common usage patterns</p>
                <ul class="help-steps">
                    <li class="help-step help-step--amber">
                        <strong>Funnel status:</strong> Lead, Prospect, Client, Churned — to track
                        the lifecycle of each contact.
                    </li>
                    <li class="help-step help-step--amber">
                        <strong>Temperature:</strong> Hot, Warm, Cold — to prioritise sales follow-up.
                    </li>
                    <li class="help-step help-step--amber">
                        <strong>Source:</strong> Inbound, Referral, Event, Ads — to measure where
                        contacts come from.
                    </li>
                    <li class="help-step help-step--amber">
                        <strong>Account type:</strong> Enterprise, SME, Agency, Partner — to segment
                        the client portfolio.
                    </li>
                </ul>
                <?php endif; ?>
            </div>
        </section>

        <!-- ══════════════════════════════════ COLORS ══ -->
        <section class="help-card" id="colors">
            <div class="help-card-head">
                <div class="help-card-icon help-card-icon--amber">
                    <i class="ph ph-palette"></i>
                </div>
                <div class="help-card-titles">
                    <h2><?= $isEs ? 'Colores' : 'Colors' ?></h2>
                    <p class="help-card-summary">
                        <?= $isEs
                            ? 'Cada tag puede tener un color que lo identifica visualmente en listados y fichas.'
                            : 'Each tag can have a color that identifies it visually in lists and records.' ?>
                    </p>
                </div>
            </div>
            <div class="help-card-body">
                <?php if ($isEs): ?>
                <p class="help-p">
                    Al crear o editar un tag puedes asignarle un <strong>color hexadecimal</strong>.
                    El color se muestra como fondo de la pastilla del tag en todos los listados y fichas
                    donde aparezca — en el listado de contactos, en la ficha individual y en el panel
                    de filtros. Si no asignas color, el tag aparece con el fondo neutro por defecto.
                </p>
                <p class="help-p">
                    Usar colores consistentes te ayuda a leer el estado de un registro de un vistazo
                    sin necesidad de leer el texto. Por ejemplo: verde para <em>Client</em>, naranja
                    para <em>Prospect</em>, rojo para <em>Churned</em> y azul para <em>Lead</em>.
                </p>

                <div class="help-callout help-callout--amber">
                    <i class="ph ph-lightbulb"></i>
                    <p>
                        <?= $isEs
                            ? 'Establece una convencion de colores desde el principio y comunicalasela al equipo. Cambiar los colores mas adelante no afecta a los datos, pero si a la memoria visual de las personas que ya los conocen.'
                            : 'Establish a colour convention from the start and communicate it to the team. Changing colours later doesn\'t affect data, but it does affect the visual memory of people who already know them.' ?>
                    </p>
                </div>
                <?php else: ?>
                <p class="help-p">
                    When creating or editing a tag you can assign a <strong>hex color</strong>. The
                    color appears as the background of the tag pill in all lists and records where it
                    shows up — in the contacts list, the individual record, and the filter panel. If
                    no color is assigned, the tag appears with the default neutral background.
                </p>
                <p class="help-p">
                    Using consistent colors helps you read a record's status at a glance without needing
                    to read the text. For example: green for <em>Client</em>, orange for <em>Prospect</em>,
                    red for <em>Churned</em>, and blue for <em>Lead</em>.
                </p>

                <div class="help-callout help-callout--amber">
                    <i class="ph ph-lightbulb"></i>
                    <p>
                        Establish a color convention from the start and communicate it to the team.
                        Changing colors later doesn't affect data, but it does affect the visual
                        memory of people who already know them.
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- ══════════════════════════════════ APPLY ══ -->
        <section class="help-card" id="apply">
            <div class="help-card-head">
                <div class="help-card-icon help-card-icon--amber">
                    <i class="ph ph-cursor-click"></i>
                </div>
                <div class="help-card-titles">
                    <h2><?= $isEs ? 'Como aplicar tags' : 'How to apply tags' ?></h2>
                    <p class="help-card-summary">
                        <?= $isEs
                            ? 'Desde la ficha individual o en bloque sobre multiples registros a la vez.'
                            : 'From the individual record or in bulk across multiple records at once.' ?>
                    </p>
                </div>
            </div>
            <div class="help-card-body">
                <?php if ($isEs): ?>
                <p class="help-sub-label"><?= $isEs ? 'Desde la ficha individual' : 'From the individual record' ?></p>
                <p class="help-p">
                    Abre la ficha de un contacto o cliente y localiza el campo <strong>Tags</strong>.
                    Escribe el nombre de un tag existente para buscarlo y seleccionarlo, o escribe un
                    nombre nuevo y pulsa Enter para crearlo al vuelo. El tag se anade inmediatamente
                    al guardar el formulario. Para quitarlo, haz clic en la <strong>x</strong> que
                    aparece junto a su nombre en el campo.
                </p>

                <p class="help-sub-label"><?= $isEs ? 'En bloque desde el listado' : 'In bulk from the list' ?></p>
                <p class="help-p">
                    Selecciona varios contactos o clientes usando las casillas de verificacion del
                    listado. Al seleccionar al menos uno aparece la <strong>barra de acciones en
                    bloque</strong> en la parte inferior de la pantalla. Desde ahi puedes:
                </p>
                <ul class="help-steps">
                    <li class="help-step help-step--amber">
                        <strong><?= $isEs ? 'Anadir tags:' : 'Add tags:' ?></strong>
                        <?= $isEs
                            ? 'El tag se anade a todos los seleccionados. Los que ya lo tenian no se duplican.'
                            : 'The tag is added to all selected records. Those that already had it are not duplicated.' ?>
                    </li>
                    <li class="help-step help-step--amber">
                        <strong><?= $isEs ? 'Quitar tags:' : 'Remove tags:' ?></strong>
                        <?= $isEs
                            ? 'El tag se elimina de todos los seleccionados. Los que no lo tenian no se ven afectados.'
                            : 'The tag is removed from all selected records. Those that didn\'t have it are unaffected.' ?>
                    </li>
                </ul>

                <div class="help-callout help-callout--amber">
                    <i class="ph ph-lightbulb"></i>
                    <p>
                        <?= $isEs
                            ? 'Combina filtros y acciones en bloque: filtra por el tag <em>Lead</em>, selecciona todos, y cambia a <em>Prospect</em> anadiendo el nuevo tag y quitando el anterior. Actualizar el pipeline entero te lleva segundos.'
                            : 'Combine filters and bulk actions: filter by the <em>Lead</em> tag, select all, and move to <em>Prospect</em> by adding the new tag and removing the old one. Updating the entire pipeline takes seconds.' ?>
                    </p>
                </div>
                <?php else: ?>
                <p class="help-sub-label">From the individual record</p>
                <p class="help-p">
                    Open a contact or client record and find the <strong>Tags</strong> field. Type the
                    name of an existing tag to search and select it, or type a new name and press Enter
                    to create it on the fly. The tag is added immediately when you save the form. To
                    remove it, click the <strong>×</strong> next to its name in the field.
                </p>

                <p class="help-sub-label">In bulk from the list</p>
                <p class="help-p">
                    Select multiple contacts or clients using the checkboxes in the list. As soon as
                    you select at least one, the <strong>bulk actions bar</strong> appears at the bottom
                    of the screen. From there you can:
                </p>
                <ul class="help-steps">
                    <li class="help-step help-step--amber">
                        <strong>Add tags:</strong> the tag is added to all selected records. Those that
                        already had it are not duplicated.
                    </li>
                    <li class="help-step help-step--amber">
                        <strong>Remove tags:</strong> the tag is removed from all selected records.
                        Those that didn't have it are unaffected.
                    </li>
                </ul>

                <div class="help-callout help-callout--amber">
                    <i class="ph ph-lightbulb"></i>
                    <p>
                        Combine filters and bulk actions: filter by the <em>Lead</em> tag, select all,
                        and move to <em>Prospect</em> by adding the new tag and removing the old one.
                        Updating the entire pipeline takes seconds.
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- ══════════════════════════════════ SECTORS ══ -->
        <section class="help-card" id="sectors">
            <div class="help-card-head">
                <div class="help-card-icon help-card-icon--amber">
                    <i class="ph ph-crosshair"></i>
                </div>
                <div class="help-card-titles">
                    <h2><?= $isEs ? 'Sectores' : 'Sectors' ?></h2>
                    <p class="help-card-summary">
                        <?= $isEs
                            ? 'Lista de referencia de industrias que se asigna a clientes para agruparlos por actividad.'
                            : 'A reference list of industries assigned to clients to group them by activity.' ?>
                    </p>
                </div>
            </div>
            <div class="help-card-body">
                <?php if ($isEs): ?>
                <p class="help-p">
                    Los sectores son una <strong>lista cerrada de categorias industriales</strong> que
                    se asigna a los clientes: Tecnologia, Salud, Construccion, Retail, Finanzas, etc.
                    A diferencia de los tags, el sector es exclusivo — un cliente solo puede tener uno
                    — y los sectores no se crean en el momento de asignarlos: deben existir previamente
                    en el sistema.
                </p>
                <p class="help-p">
                    Esto hace que los sectores sean mas rigidos pero mas fiables para analisis y
                    segmentacion. Puedes filtrar toda la cartera de clientes por sector, exportar el
                    resultado y tener la certeza de que no hay variaciones tipograficas ni duplicados
                    por nombres distintos para la misma industria.
                </p>

                <p class="help-sub-label"><?= $isEs ? 'Diferencia con los tags' : 'Difference from tags' ?></p>
                <ul class="help-steps">
                    <li class="help-step help-step--amber">
                        <?= $isEs
                            ? '<strong>Tags:</strong> multiples por registro, libres, se crean al vuelo, aplicables a contactos y clientes.'
                            : '<strong>Tags:</strong> multiple per record, free-form, created on the fly, applicable to contacts and clients.' ?>
                    </li>
                    <li class="help-step help-step--amber">
                        <?= $isEs
                            ? '<strong>Sector:</strong> uno por cliente, lista cerrada, debe existir antes de asignarlo, solo para clientes.'
                            : '<strong>Sector:</strong> one per client, closed list, must exist before assigning, only for clients.' ?>
                    </li>
                </ul>
                <?php else: ?>
                <p class="help-p">
                    Sectors are a <strong>closed list of industry categories</strong> assigned to clients:
                    Technology, Healthcare, Construction, Retail, Finance, etc. Unlike tags, the sector
                    is exclusive — a client can only have one — and sectors are not created at the moment
                    of assignment: they must already exist in the system.
                </p>
                <p class="help-p">
                    This makes sectors more rigid but more reliable for analysis and segmentation. You
                    can filter your entire client portfolio by sector, export the result, and be confident
                    there are no typo variations or duplicates from different names for the same industry.
                </p>

                <p class="help-sub-label">Difference from tags</p>
                <ul class="help-steps">
                    <li class="help-step help-step--amber">
                        <strong>Tags:</strong> multiple per record, free-form, created on the fly,
                        applicable to contacts and clients.
                    </li>
                    <li class="help-step help-step--amber">
                        <strong>Sector:</strong> one per client, closed list, must exist before
                        assigning, only for clients.
                    </li>
                </ul>
                <?php endif; ?>
            </div>
        </section>

        <!-- ══════════════════════════════════ MANAGE ══ -->
        <section class="help-card" id="manage">
            <div class="help-card-head">
                <div class="help-card-icon help-card-icon--amber">
                    <i class="ph ph-sliders-horizontal"></i>
                </div>
                <div class="help-card-titles">
                    <h2><?= $isEs ? 'Gestion de tags y sectores' : 'Managing tags and sectors' ?></h2>
                    <p class="help-card-summary">
                        <?= $isEs
                            ? 'Crear, renombrar y eliminar desde Settings. Solo disponible para administradores.'
                            : 'Create, rename, and delete from Settings. Available to administrators only.' ?>
                    </p>
                </div>
            </div>
            <div class="help-card-body">
                <?php if ($isEs): ?>
                <p class="help-sub-label"><?= $isEs ? 'Tags' : 'Tags' ?></p>
                <p class="help-p">
                    Los tags se gestionan desde <em>Settings → Tags</em>. Desde esa pantalla puedes
                    crear nuevos tags con nombre y color, editar los existentes o eliminarlos. Al
                    <strong>eliminar un tag</strong>, este se borra de todos los contactos y clientes
                    que lo tenian asignado de forma automatica — es una accion irreversible, por lo
                    que el sistema pide confirmacion antes de proceder.
                </p>
                <p class="help-p">
                    Los usuarios sin rol de administrador pueden <em>asignar</em> tags existentes a
                    contactos y clientes, pero no pueden crear, renombrar ni eliminar tags del sistema.
                    Si necesitas un tag nuevo y no eres administrador, pidele a uno que lo cree.
                </p>

                <p class="help-sub-label"><?= $isEs ? 'Sectores' : 'Sectors' ?></p>
                <p class="help-p">
                    Los sectores se gestionan desde <em>Settings → Sectors</em>. Al
                    <strong>eliminar un sector</strong> que aun tiene clientes asignados, el sistema
                    lo <strong>desactiva</strong> en lugar de borrarlo permanentemente, para no dejar
                    ningun cliente sin referencia. Un sector desactivado sigue siendo visible en las
                    fichas de los clientes que lo tienen, pero no aparece como opcion al crear o editar
                    nuevos clientes. Puedes reactivarlo en cualquier momento desde el listado de sectores.
                </p>
                <p class="help-p">
                    Si el sector no tiene clientes asignados, la eliminacion es definitiva. El slug
                    del sector se genera automaticamente a partir del nombre al crearlo.
                </p>

                <div class="help-callout help-callout--amber">
                    <i class="ph ph-warning"></i>
                    <p>
                        <?= $isEs
                            ? '<strong>Antes de importar clientes masivamente</strong>, asegurate de que todos los sectores que aparecen en el archivo ya existen en el sistema. Si un cliente del archivo referencia un sector desconocido, el campo se omite y el cliente se importa sin sector.'
                            : '<strong>Before bulk-importing clients</strong>, make sure all sectors referenced in the file already exist in the system. If a client in the file references an unknown sector, the field is skipped and the client is imported without a sector.' ?>
                    </p>
                </div>
                <?php else: ?>
                <p class="help-sub-label">Tags</p>
                <p class="help-p">
                    Tags are managed from <em>Settings → Tags</em>. From that screen you can create
                    new tags with a name and color, edit existing ones, or delete them. When you
                    <strong>delete a tag</strong>, it is automatically removed from all contacts and
                    clients that had it assigned — this is irreversible, so the system asks for
                    confirmation before proceeding.
                </p>
                <p class="help-p">
                    Users without the administrator role can <em>assign</em> existing tags to contacts
                    and clients, but cannot create, rename, or delete system tags. If you need a new
                    tag and you're not an administrator, ask one to create it for you.
                </p>

                <p class="help-sub-label">Sectors</p>
                <p class="help-p">
                    Sectors are managed from <em>Settings → Sectors</em>. When you <strong>delete a
                    sector</strong> that still has clients assigned to it, the system <strong>deactivates</strong>
                    it rather than permanently deleting it, so no client is left without a reference.
                    A deactivated sector remains visible on the records of clients that have it, but
                    does not appear as an option when creating or editing new clients. You can reactivate
                    it at any time from the sectors list.
                </p>
                <p class="help-p">
                    If the sector has no clients assigned, the deletion is permanent. The sector slug
                    is auto-generated from the name when it is created.
                </p>

                <div class="help-callout help-callout--amber">
                    <i class="ph ph-warning"></i>
                    <p>
                        <strong>Before bulk-importing clients</strong>, make sure all sectors referenced
                        in the file already exist in the system. If a client in the file references an
                        unknown sector, the field is skipped and the client is imported without a sector.
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </section>

    </div><!-- /.help-content -->
</div><!-- /.help-layout -->