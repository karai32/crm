<?php
$isEs    = $locale === 'es';
$backUrl = htmlspecialchars(Auth::url('/help?lang=' . $locale), ENT_QUOTES, 'UTF-8');

$toc = $isEs ? [
    ['id' => 'what',     'label' => 'Que es un cliente'],
    ['id' => 'record',   'label' => 'La ficha'],
    ['id' => 'sector',   'label' => 'Sector'],
    ['id' => 'contacts', 'label' => 'Contactos vinculados'],
    ['id' => 'tags',     'label' => 'Tags y campos personalizados'],
] : [
    ['id' => 'what',     'label' => 'What is a client'],
    ['id' => 'record',   'label' => 'The record'],
    ['id' => 'sector',   'label' => 'Sector'],
    ['id' => 'contacts', 'label' => 'Linked contacts'],
    ['id' => 'tags',     'label' => 'Tags and custom fields'],
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
               href="<?= htmlspecialchars(Auth::url('/help/clients?lang=' . $code), ENT_QUOTES, 'UTF-8') ?>">
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
               class="help-toc-item help-toc-item--green"
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
                <div class="help-card-icon help-card-icon--green">
                    <i class="ph ph-buildings"></i>
                </div>
                <div class="help-card-titles">
                    <h2><?= $isEs ? 'Que es un cliente' : 'What is a client' ?></h2>
                    <p class="help-card-summary">
                        <?= $isEs
                            ? 'Los clientes son empresas u organizaciones a las que se vinculan los contactos.'
                            : 'Clients are companies or organisations that contacts are linked to.' ?>
                    </p>
                </div>
            </div>
            <div class="help-card-body">
                <?php if ($isEs): ?>
                <p class="help-p">
                    Un cliente representa una empresa, marca, organizacion o entidad juridica. No es una
                    persona concreta, sino el contenedor que agrupa a las personas — los contactos —
                    que trabajan en o para esa organizacion.
                </p>
                <p class="help-p">
                    La relacion entre clientes y contactos es de <strong>muchos a muchos</strong>: un
                    cliente puede tener multiples contactos asociados, y un mismo contacto puede pertenecer
                    a varios clientes. Esto refleja situaciones reales como grupos empresariales, autonomos
                    que trabajan con varias empresas, o personas que ocupan cargos en distintas organizaciones.
                </p>
                <p class="help-p">
                    El listado de clientes funciona igual que el de contactos: puedes buscar, filtrar por
                    sector, tag o fechas, exportar el resultado y actuar sobre varios registros a la vez.
                    El boton <strong>Create client</strong> de la esquina superior derecha abre el formulario
                    de alta.
                </p>
                <?php else: ?>
                <p class="help-p">
                    A client represents a company, brand, organisation, or legal entity. It is not a
                    specific person, but the container that groups the people — contacts — who work in
                    or for that organisation.
                </p>
                <p class="help-p">
                    The relationship between clients and contacts is <strong>many-to-many</strong>: a
                    client can have multiple associated contacts, and the same contact can belong to
                    several clients. This reflects real-world situations like corporate groups,
                    freelancers working with multiple companies, or people holding roles in different
                    organisations.
                </p>
                <p class="help-p">
                    The clients list works just like the contacts list: you can search, filter by sector,
                    tag, or dates, export results, and act on multiple records at once. The
                    <strong>Create client</strong> button in the top-right corner opens the creation form.
                </p>
                <?php endif; ?>
            </div>
        </section>

        <!-- ══════════════════════════════════ RECORD ══ -->
        <section class="help-card" id="record">
            <div class="help-card-head">
                <div class="help-card-icon help-card-icon--green">
                    <i class="ph ph-address-book"></i>
                </div>
                <div class="help-card-titles">
                    <h2><?= $isEs ? 'La ficha del cliente' : 'The client record' ?></h2>
                    <p class="help-card-summary">
                        <?= $isEs
                            ? 'Nombre comercial, datos fiscales, ubicacion, web y notas.'
                            : 'Commercial name, fiscal data, location, website, and notes.' ?>
                    </p>
                </div>
            </div>
            <div class="help-card-body">
                <?php if ($isEs): ?>
                <p class="help-p">
                    El unico campo obligatorio es el <strong>nombre comercial</strong> — el nombre con
                    el que se conoce a la empresa en el dia a dia. El resto son opcionales y pueden
                    rellenarse segun lo que necesites registrar.
                </p>

                <p class="help-sub-label">Identidad y datos fiscales</p>
                <p class="help-p">
                    Los campos <strong>Legal name</strong> y <strong>CIF / NIF</strong> recogen la
                    razon social y el numero de identificacion fiscal. Son utiles si el nombre comercial
                    difiere del nombre legal, o si necesitas los datos para facturacion o integraciones
                    con otros sistemas.
                </p>

                <p class="help-sub-label">Ubicacion</p>
                <p class="help-p">
                    Los campos de <strong>Address</strong>, <strong>Postal code</strong>,
                    <strong>City</strong>, <strong>Province</strong> y <strong>Country</strong> forman
                    la direccion completa del cliente. Puedes rellenar todos o solo los que necesites.
                    El campo <em>Country</em> permite filtrar clientes por pais en el listado.
                </p>

                <p class="help-sub-label">Web y notas</p>
                <p class="help-p">
                    El campo <strong>Website</strong> guarda la URL del sitio web del cliente. El campo
                    <strong>Notes</strong> es un area de texto libre donde puedes anotar cualquier
                    informacion relevante: historial de la relacion, condiciones especiales, personas
                    de contacto adicionales o cualquier dato que no encaje en los campos estructurados.
                </p>
                <?php else: ?>
                <p class="help-p">
                    The only required field is the <strong>commercial name</strong> — the name by which
                    the company is known day to day. All other fields are optional and can be filled in
                    based on what you need to record.
                </p>

                <p class="help-sub-label">Identity and fiscal data</p>
                <p class="help-p">
                    The <strong>Legal name</strong> and <strong>CIF / NIF</strong> fields hold the
                    registered company name and tax identification number. These are useful when the
                    commercial name differs from the legal name, or when you need the data for invoicing
                    or integrations with other systems.
                </p>

                <p class="help-sub-label">Location</p>
                <p class="help-p">
                    The <strong>Address</strong>, <strong>Postal code</strong>, <strong>City</strong>,
                    <strong>Province</strong>, and <strong>Country</strong> fields form the full address
                    of the client. You can fill in all of them or just the ones you need. The
                    <em>Country</em> field allows filtering clients by country in the list.
                </p>

                <p class="help-sub-label">Website and notes</p>
                <p class="help-p">
                    The <strong>Website</strong> field stores the client's site URL. The <strong>Notes</strong>
                    field is a free-text area where you can record any relevant information: relationship
                    history, special conditions, additional points of contact, or anything that doesn't
                    fit in the structured fields.
                </p>
                <?php endif; ?>
            </div>
        </section>

        <!-- ══════════════════════════════════ SECTOR ══ -->
        <section class="help-card" id="sector">
            <div class="help-card-head">
                <div class="help-card-icon help-card-icon--green">
                    <i class="ph ph-crosshair"></i>
                </div>
                <div class="help-card-titles">
                    <h2>Sector</h2>
                    <p class="help-card-summary">
                        <?= $isEs
                            ? 'Agrupa clientes por industria para facilitar el analisis y el filtrado.'
                            : 'Group clients by industry to make analysis and filtering easier.' ?>
                    </p>
                </div>
            </div>
            <div class="help-card-body">
                <?php if ($isEs): ?>
                <p class="help-p">
                    El campo <strong>Sector</strong> vincula el cliente con una categoria industrial:
                    Tecnologia, Salud, Construccion, Retail, etc. A diferencia de los tags, un cliente
                    solo puede tener <strong>un sector asignado</strong>, ya que representa su actividad
                    principal.
                </p>
                <p class="help-p">
                    El sector es especialmente util para filtrar y segmentar la cartera de clientes.
                    Desde el panel de <strong>Filters</strong> en el listado puedes seleccionar uno o
                    varios sectores para ver solo los clientes de esa industria, y despues exportarlos
                    o aplicarles tags en bloque.
                </p>

                <p class="help-sub-label">Gestion de sectores</p>
                <p class="help-p">
                    Los sectores se crean y gestionan desde <em>Settings → Sectors</em>. Solo los
                    administradores pueden crear, renombrar o eliminar sectores. Si eliminas un sector
                    que tiene clientes asignados, el sector se <strong>desactiva</strong> en lugar de
                    borrarse para no dejar clientes sin referencia; puedes reactivarlo en cualquier
                    momento.
                </p>

                <div class="help-callout help-callout--green">
                    <i class="ph ph-lightbulb"></i>
                    <p>
                        Crea los sectores antes de empezar a importar clientes. Si importas un cliente
                        con un sector que no existe en el sistema, el campo se ignorara y el cliente
                        quedara sin sector asignado.
                    </p>
                </div>
                <?php else: ?>
                <p class="help-p">
                    The <strong>Sector</strong> field links the client to an industry category:
                    Technology, Healthcare, Construction, Retail, etc. Unlike tags, a client can only
                    have <strong>one sector assigned</strong>, since it represents their primary activity.
                </p>
                <p class="help-p">
                    The sector is especially useful for filtering and segmenting your client portfolio.
                    From the <strong>Filters</strong> panel in the list you can select one or more sectors
                    to see only clients in that industry, then export them or apply tags in bulk.
                </p>

                <p class="help-sub-label">Managing sectors</p>
                <p class="help-p">
                    Sectors are created and managed from <em>Settings → Sectors</em>. Only administrators
                    can create, rename, or delete sectors. If you delete a sector that has clients assigned
                    to it, the sector is <strong>deactivated</strong> rather than deleted, so no client
                    is left without a reference; you can reactivate it at any time.
                </p>

                <div class="help-callout help-callout--green">
                    <i class="ph ph-lightbulb"></i>
                    <p>
                        Create your sectors before you start importing clients. If you import a client
                        with a sector that doesn't exist in the system, the field will be ignored and
                        the client will be left without an assigned sector.
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- ══════════════════════════════════ CONTACTS ══ -->
        <section class="help-card" id="contacts">
            <div class="help-card-head">
                <div class="help-card-icon help-card-icon--green">
                    <i class="ph ph-user"></i>
                </div>
                <div class="help-card-titles">
                    <h2><?= $isEs ? 'Contactos vinculados' : 'Linked contacts' ?></h2>
                    <p class="help-card-summary">
                        <?= $isEs
                            ? 'Ve y gestiona todas las personas asociadas a un cliente desde su ficha.'
                            : 'View and manage all people associated with a client from their record.' ?>
                    </p>
                </div>
            </div>
            <div class="help-card-body">
                <?php if ($isEs): ?>
                <p class="help-p">
                    La ficha de un cliente muestra la lista de todos los contactos vinculados a esa
                    empresa. Desde ahi puedes ver el nombre, email y telefono de cada persona, e ir
                    directamente a su ficha individual con un clic.
                </p>
                <p class="help-p">
                    Para <strong>vincular un contacto</strong> a un cliente puedes hacerlo desde dos
                    lugares: desde la ficha del contacto, en el panel lateral <em>Linked Clients</em>,
                    o desde la ficha del cliente, usando el buscador de contactos que aparece al pie
                    de la lista de contactos vinculados.
                </p>
                <p class="help-p">
                    Para <strong>desvincular</strong> un contacto de un cliente, abre la ficha del
                    contacto, localiza el cliente en el panel <em>Linked Clients</em> y usa el boton
                    de desvinculacion. La accion elimina la relacion pero no borra ni el contacto ni
                    el cliente.
                </p>

                <div class="help-callout help-callout--green">
                    <i class="ph ph-info"></i>
                    <p>
                        Si trabajas con grupos empresariales, crea un cliente por cada empresa del grupo
                        y vincula los mismos contactos clave a todas ellas. Asi mantienes una sola ficha
                        por persona y puedes ver desde que empresas se relaciona con tu organizacion.
                    </p>
                </div>
                <?php else: ?>
                <p class="help-p">
                    A client record shows the list of all contacts linked to that company. From there
                    you can see each person's name, email, and phone, and go directly to their individual
                    record with a click.
                </p>
                <p class="help-p">
                    To <strong>link a contact</strong> to a client you can do it from two places: from
                    the contact record in the <em>Linked Clients</em> sidebar panel, or from the client
                    record using the contact search that appears below the linked contacts list.
                </p>
                <p class="help-p">
                    To <strong>unlink</strong> a contact from a client, open the contact record, find
                    the client in the <em>Linked Clients</em> panel, and use the unlink button. The
                    action removes the relationship but does not delete either the contact or the client.
                </p>

                <div class="help-callout help-callout--green">
                    <i class="ph ph-info"></i>
                    <p>
                        If you work with corporate groups, create one client per company in the group
                        and link the same key contacts to all of them. This keeps a single record per
                        person while letting you see which companies they are connected to.
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- ══════════════════════════════════ TAGS ══ -->
        <section class="help-card" id="tags">
            <div class="help-card-head">
                <div class="help-card-icon help-card-icon--green">
                    <i class="ph ph-tag"></i>
                </div>
                <div class="help-card-titles">
                    <h2><?= $isEs ? 'Tags y campos personalizados' : 'Tags and custom fields' ?></h2>
                    <p class="help-card-summary">
                        <?= $isEs
                            ? 'Clasifica y amplia la ficha del cliente sin tocar codigo.'
                            : 'Classify and extend the client record without touching code.' ?>
                    </p>
                </div>
            </div>
            <div class="help-card-body">
                <?php if ($isEs): ?>
                <p class="help-sub-label">Tags en clientes</p>
                <p class="help-p">
                    Los clientes admiten los mismos tags que los contactos. Puedes usarlos para clasificar
                    el estado de la relacion comercial (<em>Active</em>, <em>Prospect</em>, <em>Churned</em>),
                    el tipo de cuenta (<em>Enterprise</em>, <em>SME</em>, <em>Agency</em>) o cualquier
                    otro criterio relevante para tu proceso.
                </p>
                <p class="help-p">
                    Los tags de clientes funcionan de forma independiente a los de contactos: puedes
                    tener un contacto con el tag <em>Lead</em> vinculado a un cliente con el tag
                    <em>Active</em> sin ninguna relacion entre ambas etiquetas. Las acciones en bloque
                    — anadir o quitar tags a varios clientes a la vez — tambien estan disponibles en el
                    listado de clientes.
                </p>

                <p class="help-sub-label">Campos personalizados</p>
                <p class="help-p">
                    Los campos personalizados permiten anadir a la ficha del cliente cualquier dato
                    adicional que necesites: numero de contrato, volumen de facturacion, fecha de
                    renovacion, gestor asignado, etc. Se configuran en <em>Settings → Custom Fields</em>
                    asignandolos a la entidad <strong>Clients</strong>.
                </p>
                <p class="help-p">
                    Los campos de clientes son completamente independientes de los de contactos: puedes
                    tener campos distintos para cada entidad. Si activas la opcion <strong>Filterable</strong>
                    al crear un campo, aparecera en el panel de filtros avanzados del listado de clientes,
                    lo que te permite segmentar por ese dato sin exportar primero.
                </p>

                <div class="help-callout help-callout--green">
                    <i class="ph ph-lightbulb"></i>
                    <p>
                        Ejemplo practico: crea un campo <em>Fecha de renovacion</em> (tipo <strong>date</strong>)
                        marcado como Filterable. Cada mes puedes filtrar los clientes cuya fecha de
                        renovacion cae en los proximos 30 dias y exportar la lista para el equipo comercial.
                    </p>
                </div>
                <?php else: ?>
                <p class="help-sub-label">Tags on clients</p>
                <p class="help-p">
                    Clients support the same tags as contacts. You can use them to classify the commercial
                    relationship status (<em>Active</em>, <em>Prospect</em>, <em>Churned</em>), account
                    type (<em>Enterprise</em>, <em>SME</em>, <em>Agency</em>), or any other criterion
                    relevant to your process.
                </p>
                <p class="help-p">
                    Client tags work independently from contact tags: you can have a contact tagged
                    <em>Lead</em> linked to a client tagged <em>Active</em> with no relationship between
                    the two labels. Bulk actions — adding or removing tags from multiple clients at once
                    — are also available in the clients list.
                </p>

                <p class="help-sub-label">Custom fields</p>
                <p class="help-p">
                    Custom fields let you add any additional data to the client record: contract number,
                    billing volume, renewal date, assigned account manager, etc. They are configured in
                    <em>Settings → Custom Fields</em>, assigned to the <strong>Clients</strong> entity.
                </p>
                <p class="help-p">
                    Client fields are completely independent from contact fields: you can have different
                    fields for each entity. If you enable the <strong>Filterable</strong> option when
                    creating a field, it will appear in the advanced filter panel on the clients list,
                    letting you segment by that data without exporting first.
                </p>

                <div class="help-callout help-callout--green">
                    <i class="ph ph-lightbulb"></i>
                    <p>
                        Practical example: create a <em>Renewal date</em> field (type <strong>date</strong>)
                        marked as Filterable. Each month you can filter clients whose renewal date falls
                        within the next 30 days and export the list for your sales team.
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
