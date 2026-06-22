<?php
$isEs    = $locale === 'es';
$backUrl = htmlspecialchars(Auth::url('/help?lang=' . $locale), ENT_QUOTES, 'UTF-8');

$toc = $isEs ? [
    ['id' => 'what',    'label' => 'Que es un contacto'],
    ['id' => 'record',  'label' => 'La ficha'],
    ['id' => 'tags',    'label' => 'Tags'],
    ['id' => 'clients', 'label' => 'Clientes vinculados'],
    ['id' => 'bulk',    'label' => 'Acciones en bloque'],
] : [
    ['id' => 'what',    'label' => 'What is a contact'],
    ['id' => 'record',  'label' => 'The record'],
    ['id' => 'tags',    'label' => 'Tags'],
    ['id' => 'clients', 'label' => 'Linked clients'],
    ['id' => 'bulk',    'label' => 'Bulk actions'],
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
               href="<?= htmlspecialchars(Auth::url('/help/contacts?lang=' . $code), ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="help-layout help-layout-mt">

    <!-- Sticky TOC -->
    <aside class="help-toc">
        <span class="help-toc-label"><?= $isEs ? 'Contenido' : 'Contents' ?></span>
        <nav class="help-toc-nav">
            <?php foreach ($toc as $item): ?>
            <a href="#<?= $item['id'] ?>"
               class="help-toc-item help-toc-item--blue"
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
                <div class="help-card-icon help-card-icon--blue">
                    <i class="ph ph-user"></i>
                </div>
                <div class="help-card-titles">
                    <h2><?= $isEs ? 'Que es un contacto' : 'What is a contact' ?></h2>
                    <p class="help-card-summary">
                        <?= $isEs
                            ? 'Los contactos son personas — el elemento central alrededor del que gira el CRM.'
                            : 'Contacts are people — the central element around which the CRM revolves.' ?>
                    </p>
                </div>
            </div>
            <div class="help-card-body">
                <?php if ($isEs): ?>
                <p class="help-p">
                    Un contacto representa a una persona: alguien con nombre, datos de contacto y una
                    relacion con clientes u organizaciones. Son el elemento central del CRM: todo lo demas
                    — clientes, tags, campos personalizados, importaciones y exportaciones — gira en torno a ellos.
                </p>
                <p class="help-p">
                    El listado de contactos es el punto de partida habitual del trabajo diario. Desde
                    ahi puedes buscar, filtrar, exportar y actuar sobre varios registros a la vez. El
                    boton <strong>Create contact</strong> de la esquina superior derecha abre el formulario
                    de alta individual.
                </p>
                <?php else: ?>
                <p class="help-p">
                    A contact represents a person: someone with a name, contact details, and a relationship
                    with clients or organisations. They are the central element of the CRM — everything else
                    (clients, tags, custom fields, imports, and exports) revolves around them.
                </p>
                <p class="help-p">
                    The contacts list is the usual starting point for day-to-day work. From there you can
                    search, filter, export, and act on multiple records at once. The
                    <strong>Create contact</strong> button in the top-right corner opens the individual
                    creation form.
                </p>
                <?php endif; ?>
            </div>
        </section>

        <!-- ══════════════════════════════════ RECORD ══ -->
        <section class="help-card" id="record">
            <div class="help-card-head">
                <div class="help-card-icon help-card-icon--blue">
                    <i class="ph ph-address-book"></i>
                </div>
                <div class="help-card-titles">
                    <h2><?= $isEs ? 'La ficha del contacto' : 'The contact record' ?></h2>
                    <p class="help-card-summary">
                        <?= $isEs
                            ? 'Campos base, el flag "Is company" y la unicidad del email.'
                            : 'Base fields, the "Is company" flag, and email uniqueness.' ?>
                    </p>
                </div>
            </div>
            <div class="help-card-body">
                <?php if ($isEs): ?>
                <p class="help-p">
                    Cada ficha de contacto incluye los campos base: <strong>nombre completo</strong>,
                    <strong>email</strong>, <strong>telefono</strong> y <strong>empresa</strong>.
                    El unico campo obligatorio es el nombre completo; el resto son opcionales.
                </p>

                <p class="help-sub-label">El flag "Is company"</p>
                <p class="help-p">
                    El campo <strong>Is company</strong> indica que el contacto representa a una entidad
                    juridica — una empresa, un autonomo o una organizacion — en lugar de a una persona
                    fisica concreta. Activarlo cambia el icono del contacto en el listado y puede resultar
                    util para importar bases de datos mixtas donde algunas filas son empresas y otras personas.
                </p>

                <p class="help-sub-label">Unicidad del email</p>
                <p class="help-p">
                    El email de un contacto debe ser <strong>unico en todo el sistema</strong>. Si
                    intentas crear o importar un contacto con un email que ya existe, el registro se
                    rechaza o se omite. Esto evita duplicados silenciosos y garantiza que cada direccion
                    de correo identifica a una sola persona.
                </p>
                <p class="help-p">
                    Si un contacto no tiene email, puede dejarse el campo vacio sin problema. Varios
                    contactos sin email coexisten sin conflicto.
                </p>
                <?php else: ?>
                <p class="help-p">
                    Each contact record includes the base fields: <strong>full name</strong>,
                    <strong>email</strong>, <strong>phone</strong>, and <strong>company</strong>.
                    The only required field is the full name; the rest are optional.
                </p>

                <p class="help-sub-label">The "Is company" flag</p>
                <p class="help-p">
                    The <strong>Is company</strong> field indicates that the contact represents a legal
                    entity — a company, sole trader, or organisation — rather than an individual person.
                    Enabling it changes the contact's icon in the list and can be useful when importing
                    mixed databases where some rows are companies and others are individuals.
                </p>

                <p class="help-sub-label">Email uniqueness</p>
                <p class="help-p">
                    A contact's email must be <strong>unique across the entire system</strong>. If you
                    try to create or import a contact with an email that already exists, the record is
                    rejected or skipped. This prevents silent duplicates and ensures each email address
                    identifies a single person.
                </p>
                <p class="help-p">
                    If a contact has no email, the field can simply be left blank. Multiple contacts
                    without an email coexist without conflict.
                </p>
                <?php endif; ?>
            </div>
        </section>

        <!-- ══════════════════════════════════ TAGS ══ -->
        <section class="help-card" id="tags">
            <div class="help-card-head">
                <div class="help-card-icon help-card-icon--blue">
                    <i class="ph ph-tag"></i>
                </div>
                <div class="help-card-titles">
                    <h2>Tags</h2>
                    <p class="help-card-summary">
                        <?= $isEs
                            ? 'Etiquetas de colores para clasificar el estado, prioridad u origen de cada contacto.'
                            : 'Coloured labels to classify the status, priority, or source of each contact.' ?>
                    </p>
                </div>
            </div>
            <div class="help-card-body">
                <?php if ($isEs): ?>
                <p class="help-p">
                    Los tags son etiquetas de colores que puedes asignar libremente a los contactos para
                    clasificarlos segun el criterio que necesites. Cada contacto puede tener varios tags
                    al mismo tiempo: por ejemplo <em>Lead</em> y <em>Hot</em>, o <em>Client</em> y
                    <em>Priority</em>. Los tags se muestran como pastillas de color en el listado y en
                    la ficha individual.
                </p>

                <p class="help-sub-label">Usos habituales</p>
                <p class="help-p">
                    Los tags son flexibles y puedes usarlos para lo que necesites. Algunos patrones comunes:
                </p>
                <ul class="help-steps">
                    <li class="help-step help-step--blue">
                        <strong>Estado comercial:</strong> Lead → Prospect → Client para trazar el avance
                        de un contacto a lo largo del embudo de ventas.
                    </li>
                    <li class="help-step help-step--blue">
                        <strong>Prioridad:</strong> Hot, Cold, Warm para marcar el interes o la urgencia
                        de seguimiento.
                    </li>
                    <li class="help-step help-step--blue">
                        <strong>Origen:</strong> Inbound, Referral, Event para saber como llego el
                        contacto al sistema.
                    </li>
                    <li class="help-step help-step--blue">
                        <strong>Accion pendiente:</strong> Follow-up, No-response, Demo-done para
                        gestionar el estado de las comunicaciones.
                    </li>
                </ul>

                <p class="help-sub-label">Aplicar y quitar tags</p>
                <p class="help-p">
                    Desde la ficha individual, el campo de tags permite buscar y seleccionar etiquetas
                    existentes o escribir el nombre de una nueva para crearla al vuelo. Desde el listado,
                    selecciona uno o varios contactos y usa la <strong>barra de acciones en bloque</strong>
                    que aparece en la parte inferior para aplicar o quitar tags en masa.
                </p>
                <p class="help-p">
                    Los tags se crean y gestionan desde <em>Settings → Tags</em>. Solo los administradores
                    pueden crear, renombrar o eliminar tags del sistema.
                </p>
                <?php else: ?>
                <p class="help-p">
                    Tags are colour labels you can freely assign to contacts to classify them however
                    you need. Each contact can have multiple tags at the same time: for example
                    <em>Lead</em> and <em>Hot</em>, or <em>Client</em> and <em>Priority</em>. Tags
                    appear as colour pills in the list and on the individual record.
                </p>

                <p class="help-sub-label">Common uses</p>
                <p class="help-p">
                    Tags are flexible — use them for whatever fits your workflow. Some common patterns:
                </p>
                <ul class="help-steps">
                    <li class="help-step help-step--blue">
                        <strong>Commercial status:</strong> Lead → Prospect → Client to track a contact's
                        progression through the sales funnel.
                    </li>
                    <li class="help-step help-step--blue">
                        <strong>Priority:</strong> Hot, Cold, Warm to mark interest level or follow-up urgency.
                    </li>
                    <li class="help-step help-step--blue">
                        <strong>Source:</strong> Inbound, Referral, Event to record how the contact
                        entered the system.
                    </li>
                    <li class="help-step help-step--blue">
                        <strong>Pending action:</strong> Follow-up, No-response, Demo-done to manage
                        the state of ongoing communications.
                    </li>
                </ul>

                <p class="help-sub-label">Applying and removing tags</p>
                <p class="help-p">
                    From the individual record, the tag field lets you search and select existing labels
                    or type a new name to create one on the fly. From the list, select one or more contacts
                    and use the <strong>bulk actions bar</strong> that appears at the bottom to apply or
                    remove tags in mass.
                </p>
                <p class="help-p">
                    Tags are created and managed from <em>Settings → Tags</em>. Only administrators can
                    create, rename, or delete system tags.
                </p>
                <?php endif; ?>
            </div>
        </section>

        <!-- ══════════════════════════════════ CLIENTS ══ -->
        <section class="help-card" id="clients">
            <div class="help-card-head">
                <div class="help-card-icon help-card-icon--blue">
                    <i class="ph ph-buildings"></i>
                </div>
                <div class="help-card-titles">
                    <h2><?= $isEs ? 'Clientes vinculados' : 'Linked clients' ?></h2>
                    <p class="help-card-summary">
                        <?= $isEs
                            ? 'Un contacto puede pertenecer a varios clientes sin duplicar su ficha.'
                            : 'A contact can belong to multiple clients without duplicating the record.' ?>
                    </p>
                </div>
            </div>
            <div class="help-card-body">
                <?php if ($isEs): ?>
                <p class="help-p">
                    En el panel lateral derecho de la ficha de un contacto aparece la seccion
                    <strong>Linked Clients</strong>. Desde ahi puedes vincular al contacto con uno o
                    varios clientes del sistema. La relacion es de muchos a muchos: un contacto puede
                    pertenecer a multiples clientes, y un cliente puede tener multiples contactos.
                </p>
                <p class="help-p">
                    Esto es util cuando una misma persona trabaja en dos empresas, es autonomo con
                    varios proyectos, o pertenece a un grupo empresarial con diferentes razones sociales.
                    En lugar de duplicar la ficha, simplemente vinculas el mismo contacto a todos los
                    clientes que correspondan.
                </p>
                <p class="help-p">
                    Desde la ficha del cliente puedes ver el listado inverso: todos los contactos
                    vinculados a esa empresa, con la opcion de ir directamente a cada uno de ellos.
                </p>

                <div class="help-callout help-callout--blue">
                    <i class="ph ph-info"></i>
                    <p>
                        Tambien puedes filtrar contactos por cliente desde el panel de <strong>Filters</strong>
                        en el listado. Selecciona un cliente en el filtro <em>Client</em> para ver todos
                        los contactos asociados a esa empresa.
                    </p>
                </div>
                <?php else: ?>
                <p class="help-p">
                    The right sidebar panel on a contact record shows the <strong>Linked Clients</strong>
                    section. From there you can link the contact to one or more clients in the system.
                    The relationship is many-to-many: a contact can belong to multiple clients, and a
                    client can have multiple contacts.
                </p>
                <p class="help-p">
                    This is useful when the same person works at two companies, is a freelancer with
                    multiple projects, or belongs to a corporate group with different legal entities.
                    Instead of duplicating the record, you simply link the same contact to all the
                    relevant clients.
                </p>
                <p class="help-p">
                    From the client record you can see the reverse list: all contacts linked to that
                    company, with a direct link to each of them.
                </p>

                <div class="help-callout help-callout--blue">
                    <i class="ph ph-info"></i>
                    <p>
                        You can also filter contacts by client from the <strong>Filters</strong> panel
                        in the list. Select a client in the <em>Client</em> filter to see all contacts
                        associated with that company.
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- ══════════════════════════════════ BULK ══ -->
        <section class="help-card" id="bulk">
            <div class="help-card-head">
                <div class="help-card-icon help-card-icon--blue">
                    <i class="ph ph-list-dashes"></i>
                </div>
                <div class="help-card-titles">
                    <h2><?= $isEs ? 'Acciones en bloque' : 'Bulk actions' ?></h2>
                    <p class="help-card-summary">
                        <?= $isEs
                            ? 'Selecciona varios contactos y aplica cambios a todos a la vez.'
                            : 'Select multiple contacts and apply changes to all of them at once.' ?>
                    </p>
                </div>
            </div>
            <div class="help-card-body">
                <?php if ($isEs): ?>
                <p class="help-p">
                    En el listado de contactos, cada fila tiene una casilla de verificacion a la izquierda.
                    Marca una o varias para seleccionarlas; la casilla de la cabecera de columna selecciona
                    o deselecciona toda la pagina de golpe. En cuanto seleccionas al menos un contacto,
                    aparece la <strong>barra de acciones en bloque</strong> en la parte inferior de la pantalla.
                </p>

                <p class="help-sub-label">Que puedes hacer en bloque</p>
                <p class="help-p">
                    Desde la barra de acciones puedes <strong>anadir tags</strong> a todos los contactos
                    seleccionados o <strong>quitar tags</strong> de todos ellos. Esta operacion es
                    especialmente util despues de una importacion masiva: selecciona todos los contactos
                    recien importados y aplica el tag <em>Nuevo</em> o <em>Lead</em> en un solo clic.
                </p>
                <p class="help-p">
                    Si tienes el permiso de eliminar contactos, la barra tambien muestra la opcion de
                    <strong>eliminar en bloque</strong>. Esta accion es permanente e irreversible, por
                    lo que el sistema pedira confirmacion antes de proceder.
                </p>

                <div class="help-callout help-callout--blue">
                    <i class="ph ph-lightbulb"></i>
                    <p>
                        Combina filtros y acciones en bloque para operaciones de mantenimiento precisas:
                        filtra por el tag <em>Lead</em> y la fecha de creacion de hace mas de 90 dias,
                        selecciona todos y cambia el tag a <em>Cold</em> para actualizar el estado
                        del pipeline sin tocar ficha por ficha.
                    </p>
                </div>
                <?php else: ?>
                <p class="help-p">
                    In the contacts list, each row has a checkbox on the left. Check one or more to
                    select them; the header checkbox selects or deselects the entire current page at
                    once. As soon as you select at least one contact, the <strong>bulk actions bar</strong>
                    appears at the bottom of the screen.
                </p>

                <p class="help-sub-label">What you can do in bulk</p>
                <p class="help-p">
                    From the actions bar you can <strong>add tags</strong> to all selected contacts or
                    <strong>remove tags</strong> from all of them. This is especially useful after a
                    mass import: select all the newly imported contacts and apply the tag <em>New</em>
                    or <em>Lead</em> in a single click.
                </p>
                <p class="help-p">
                    If you have the delete permission, the bar also shows the option to
                    <strong>bulk delete</strong>. This action is permanent and irreversible, so the
                    system will ask for confirmation before proceeding.
                </p>

                <div class="help-callout help-callout--blue">
                    <i class="ph ph-lightbulb"></i>
                    <p>
                        Combine filters and bulk actions for precise maintenance operations: filter by
                        the <em>Lead</em> tag and a creation date older than 90 days, select all, and
                        change the tag to <em>Cold</em> to update your pipeline status without
                        opening each record individually.
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </section>

    </div><!-- /.help-content -->
</div><!-- /.help-layout -->

<script>
(function () {
    var items = document.querySelectorAll('.help-toc-item');
    if (!items.length || !window.IntersectionObserver) return;
    var active = null;
    function setActive(id) {
        if (active === id) return;
        active = id;
        items.forEach(function (a) { a.classList.toggle('is-active', a.dataset.section === id); });
    }
    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) { if (e.isIntersecting) setActive(e.target.id); });
    }, { rootMargin: '-20% 0px -70% 0px', threshold: 0 });
    document.querySelectorAll('.help-card').forEach(function (card) { observer.observe(card); });
    items.forEach(function (a) {
        a.addEventListener('click', function (e) {
            var t = document.getElementById(a.dataset.section);
            if (!t) return;
            e.preventDefault();
            t.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });
}());
</script>
