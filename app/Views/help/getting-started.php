<?php
$isEs    = $locale === 'es';
$backUrl = htmlspecialchars(Auth::url('/help?lang=' . $locale), ENT_QUOTES, 'UTF-8');

$toc = $isEs ? [
    ['id' => 'interface', 'label' => 'La interfaz'],
    ['id' => 'dashboard', 'label' => 'Dashboard'],
    ['id' => 'search',    'label' => 'Busqueda global'],
    ['id' => 'account',   'label' => 'Tu cuenta'],
    ['id' => 'start',     'label' => 'Por donde empezar'],
] : [
    ['id' => 'interface', 'label' => 'The interface'],
    ['id' => 'dashboard', 'label' => 'Dashboard'],
    ['id' => 'search',    'label' => 'Global search'],
    ['id' => 'account',   'label' => 'Your account'],
    ['id' => 'start',     'label' => 'Where to begin'],
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
               href="<?= htmlspecialchars(Auth::url('/help/getting-started?lang=' . $code), ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Layout: TOC + content -->
<div class="help-layout help-layout-mt">

    <!-- Sticky TOC -->
    <aside class="help-toc">
        <span class="help-toc-label"><?= $isEs ? 'Contenido' : 'Contents' ?></span>
        <nav class="help-toc-nav">
            <?php foreach ($toc as $item): ?>
            <a href="#<?= $item['id'] ?>"
               class="help-toc-item help-toc-item--violet"
               data-section="<?= $item['id'] ?>">
                <span class="help-toc-dot"></span>
                <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
            </a>
            <?php endforeach; ?>
        </nav>
    </aside>

    <!-- Main content -->
    <div class="help-content">

        <!-- ══════════════════════════════════════ INTERFACE ══ -->
        <section class="help-card" id="interface">
            <div class="help-card-head">
                <div class="help-card-icon help-card-icon--violet">
                    <i class="ph ph-squares-four"></i>
                </div>
                <div class="help-card-titles">
                    <h2><?= $isEs ? 'La interfaz' : 'The interface' ?></h2>
                    <p class="help-card-summary">
                        <?= $isEs
                            ? 'Menu lateral, barra superior y area de contenido central: las tres zonas de ContactCore.'
                            : 'Sidebar, topbar, and central content area: the three zones of ContactCore.' ?>
                    </p>
                </div>
            </div>
            <div class="help-card-body">
                <?php if ($isEs): ?>
                <p class="help-p">
                    ContactCore se divide en tres zonas: el <strong>menu lateral</strong> a la izquierda,
                    la <strong>barra superior</strong> y el <strong>area de contenido</strong> central donde
                    aparecen listados, fichas y formularios.
                </p>
                <p class="help-p">
                    El menu lateral agrupa todos los modulos del sistema: <strong>Contacts</strong>,
                    <strong>Clients</strong>, <strong>Sectors</strong>, <strong>Tags</strong> y, para administradores,
                    la seccion de configuracion con <em>Custom Fields</em>, <em>Imports</em>, <em>Exports</em>,
                    <em>Users</em> y <em>API Credentials</em>. El logo en la parte superior del menu es
                    un enlace rapido al Dashboard.
                </p>
                <p class="help-p">
                    En dispositivos moviles o pantallas estrechas el menu lateral se oculta automaticamente.
                    El icono <strong>☰</strong> de la barra superior permite abrirlo y cerrarlo sin perder
                    el contexto de la pantalla actual.
                </p>
                <?php else: ?>
                <p class="help-p">
                    ContactCore is divided into three zones: the <strong>sidebar</strong> on the left,
                    the <strong>topbar</strong>, and the central <strong>content area</strong> where lists,
                    records, and forms appear.
                </p>
                <p class="help-p">
                    The sidebar groups all system modules: <strong>Contacts</strong>, <strong>Clients</strong>,
                    <strong>Sectors</strong>, <strong>Tags</strong>, and — for administrators — the settings
                    section with <em>Custom Fields</em>, <em>Imports</em>, <em>Exports</em>, <em>Users</em>,
                    and <em>API Credentials</em>. The logo at the top of the sidebar is a quick link back
                    to the Dashboard.
                </p>
                <p class="help-p">
                    On mobile or narrow screens the sidebar hides automatically. The <strong>☰</strong> icon
                    in the topbar opens and closes it without losing your place in the current screen.
                </p>
                <?php endif; ?>
            </div>
        </section>

        <!-- ══════════════════════════════════════ DASHBOARD ══ -->
        <section class="help-card" id="dashboard">
            <div class="help-card-head">
                <div class="help-card-icon help-card-icon--violet">
                    <i class="ph ph-house-line"></i>
                </div>
                <div class="help-card-titles">
                    <h2>Dashboard</h2>
                    <p class="help-card-summary">
                        <?= $isEs
                            ? 'La pantalla de inicio con los totales y accesos directos al sistema.'
                            : 'The home screen with totals and shortcuts to the system.' ?>
                    </p>
                </div>
            </div>
            <div class="help-card-body">
                <?php if ($isEs): ?>
                <p class="help-p">
                    El Dashboard es la primera pantalla que aparece al iniciar sesion. Muestra cuatro
                    tarjetas con los totales actuales de la base de datos: <strong>Contacts</strong>,
                    <strong>Clients</strong>, <strong>Sectors</strong> y <strong>Tags</strong>.
                    Cada tarjeta es un enlace directo al listado correspondiente.
                </p>
                <p class="help-p">
                    Usa el Dashboard como punto de partida para verificar de un vistazo el estado general
                    antes de empezar a trabajar o despues de una importacion masiva de datos.
                    Tambien puedes volver a el en cualquier momento haciendo clic en el logo de la barra lateral.
                </p>
                <?php else: ?>
                <p class="help-p">
                    The Dashboard is the first screen that appears after login. It shows four cards with
                    the current database totals: <strong>Contacts</strong>, <strong>Clients</strong>,
                    <strong>Sectors</strong>, and <strong>Tags</strong>. Each card is a direct link to
                    the corresponding list.
                </p>
                <p class="help-p">
                    Use the Dashboard as a starting point to get a quick overview before you start working,
                    or after a bulk data import. You can always return to it by clicking the logo in the sidebar.
                </p>
                <?php endif; ?>
            </div>
        </section>

        <!-- ════════════════════════════════════════ SEARCH ══ -->
        <section class="help-card" id="search">
            <div class="help-card-head">
                <div class="help-card-icon help-card-icon--violet">
                    <i class="ph ph-magnifying-glass"></i>
                </div>
                <div class="help-card-titles">
                    <h2><?= $isEs ? 'Busqueda global' : 'Global search' ?></h2>
                    <p class="help-card-summary">
                        <?= $isEs
                            ? 'Localiza contactos y clientes al instante desde cualquier pantalla del sistema.'
                            : 'Find contacts and clients instantly from any screen in the system.' ?>
                    </p>
                </div>
            </div>
            <div class="help-card-body">
                <?php if ($isEs): ?>
                <p class="help-p">
                    La barra de busqueda de la zona superior encuentra contactos y clientes en tiempo real.
                    Basta con empezar a teclear un nombre, email o empresa: el desplegable muestra
                    resultados instantaneos separados por categoria — <em>Contacts</em> y <em>Clients</em> —
                    para que puedas distinguirlos de un vistazo.
                </p>
                <p class="help-p">
                    Haz clic en cualquier resultado para ir directamente a esa ficha. La busqueda funciona
                    desde cualquier pantalla del sistema, por lo que no necesitas volver al listado para
                    encontrar un registro concreto.
                </p>

                <p class="help-sub-label"><?= $isEs ? 'Busqueda avanzada con filtros' : 'Advanced search with filters' ?></p>
                <p class="help-p">
                    Para segmentaciones mas precisas, usa el panel de <strong>Filters</strong> dentro de
                    los listados de Contacts y Clients. Los filtros permiten combinar multiples criterios:
                    tag, cliente vinculado, sector, pais, rango de fechas de creacion o modificacion, y
                    campos personalizados marcados como filtrables.
                </p>
                <p class="help-p">
                    Los filtros activos aparecen como chips junto al boton Filters. Haz clic en la
                    <strong>x</strong> de un chip para quitarlo sin afectar al resto. El enlace
                    <strong>Reset all</strong> limpia todos de una vez. Ademas, los filtros se conservan
                    en la URL, por lo que puedes guardar o compartir un segmento concreto como marcador.
                </p>
                <?php else: ?>
                <p class="help-p">
                    The search bar in the topbar finds contacts and clients in real time. Just start typing
                    a name, email, or company: a dropdown shows instant results separated by category —
                    <em>Contacts</em> and <em>Clients</em> — so you can tell them apart at a glance.
                </p>
                <p class="help-p">
                    Click any result to go directly to that record. The search works from any screen in
                    the system, so you never need to navigate back to a list to find a specific entry.
                </p>

                <p class="help-sub-label">Advanced search with filters</p>
                <p class="help-p">
                    For more precise segmentation, use the <strong>Filters</strong> panel inside the
                    Contacts and Clients lists. Filters let you combine multiple criteria: tag, linked
                    client, sector, country, creation or update date range, and custom fields marked
                    as filterable.
                </p>
                <p class="help-p">
                    Active filters appear as chips next to the Filters button. Click the <strong>×</strong>
                    on a chip to remove it without affecting the rest. The <strong>Reset all</strong> link
                    clears everything at once. Filters are preserved in the URL, so you can bookmark or
                    share a specific segment.
                </p>
                <?php endif; ?>
            </div>
        </section>

        <!-- ══════════════════════════════════════ ACCOUNT ══ -->
        <section class="help-card" id="account">
            <div class="help-card-head">
                <div class="help-card-icon help-card-icon--violet">
                    <i class="ph ph-user"></i>
                </div>
                <div class="help-card-titles">
                    <h2><?= $isEs ? 'Tu cuenta' : 'Your account' ?></h2>
                    <p class="help-card-summary">
                        <?= $isEs
                            ? 'Perfil, sesion persistente con "Recordarme" y verificacion en dos pasos.'
                            : 'Profile, persistent session with "Remember me", and two-factor authentication.' ?>
                    </p>
                </div>
            </div>
            <div class="help-card-body">
                <?php if ($isEs): ?>
                <p class="help-p">
                    El avatar con tus iniciales en la esquina superior derecha abre el menu de perfil,
                    donde puedes ver tu nombre y rol, acceder a tu ficha de usuario o cerrar sesion.
                    En el menu lateral tambien hay un boton de cierre de sesion en la parte inferior.
                </p>

                <p class="help-sub-label">Recordarme</p>
                <p class="help-p">
                    Al marcar la opcion <strong>Recordarme</strong> en el formulario de login, tu sesion
                    permanece activa durante <strong>30 dias</strong> aunque cierres el navegador o
                    apagues el ordenador. El sistema genera un token unico que se renueva automaticamente
                    en cada acceso, por lo que no necesitas volver a introducir tus credenciales mientras
                    uses el CRM con regularidad.
                </p>
                <p class="help-p">
                    Si no marcas esta opcion, la sesion expira en cuanto cierras el navegador o tras
                    un largo periodo de inactividad.
                </p>

                <p class="help-sub-label">Verificacion en dos pasos (2FA)</p>
                <p class="help-p">
                    Si esta opcion esta activa en tu cuenta, tras introducir el email y la contrasena
                    correctos el sistema envia un <strong>codigo de un solo uso</strong> a tu direccion
                    de correo. Introducelo en la pantalla de verificacion para completar el acceso.
                    Cada codigo es valido durante unos minutos y solo puede usarse una vez.
                </p>
                <p class="help-p">
                    Si el codigo no llega en unos segundos, revisa la carpeta de <strong>spam o correo
                    no deseado</strong>. Si sigue sin aparecer, usa el boton <em>Resend code</em> para
                    solicitar uno nuevo.
                </p>

                <p class="help-sub-label">Idioma</p>
                <p class="help-p">
                    La pagina de ayuda esta disponible en espanol e ingles. Cambia el idioma con el
                    selector de pills que aparece en la esquina superior derecha de esta pagina.
                    Tu eleccion no afecta al idioma de la interfaz principal del CRM.
                </p>
                <?php else: ?>
                <p class="help-p">
                    The avatar with your initials in the top-right corner opens the profile menu, where
                    you can see your name and role, access your user record, or log out. The sidebar
                    also has a logout button at the bottom.
                </p>

                <p class="help-sub-label">Remember me</p>
                <p class="help-p">
                    Checking <strong>Remember me</strong> on the login form keeps your session active
                    for <strong>30 days</strong> even if you close the browser or shut down your computer.
                    The system generates a unique token that renews automatically on each visit, so you
                    don't need to re-enter your credentials as long as you use the CRM regularly.
                </p>
                <p class="help-p">
                    If you don't check this option, the session expires as soon as you close the browser
                    or after a long period of inactivity.
                </p>

                <p class="help-sub-label">Two-factor authentication (2FA)</p>
                <p class="help-p">
                    If this option is enabled on your account, after entering the correct email and password
                    the system sends a <strong>one-time code</strong> to your email address. Enter it on
                    the verification screen to complete sign-in. Each code is valid for a few minutes
                    and can only be used once.
                </p>
                <p class="help-p">
                    If the code doesn't arrive within a few seconds, check your <strong>spam or junk
                    mail folder</strong>. If it still doesn't appear, use the <em>Resend code</em> button
                    to request a new one.
                </p>

                <p class="help-sub-label">Language</p>
                <p class="help-p">
                    The help page is available in Spanish and English. Switch languages using the pill
                    selector in the top-right corner of this page. Your choice does not affect the
                    language of the main CRM interface.
                </p>
                <?php endif; ?>
            </div>
        </section>

        <!-- ════════════════════════════════════════ START ══ -->
        <section class="help-card" id="start">
            <div class="help-card-head">
                <div class="help-card-icon help-card-icon--violet">
                    <i class="ph ph-airplane-tilt"></i>
                </div>
                <div class="help-card-titles">
                    <h2><?= $isEs ? 'Por donde empezar' : 'Where to begin' ?></h2>
                    <p class="help-card-summary">
                        <?= $isEs
                            ? 'Flujo recomendado para tener datos reales y explorar todas las funciones.'
                            : 'Recommended flow to get real data in the system and explore all features.' ?>
                    </p>
                </div>
            </div>
            <div class="help-card-body">
                <?php if ($isEs): ?>
                <p class="help-p">
                    Si acabas de acceder al CRM por primera vez, sigue este flujo para familiarizarte
                    con el sistema de forma practica:
                </p>
                <ol class="help-steps">
                    <li class="help-step help-step--violet">
                        Ve a <strong>Contacts</strong> y crea 2–3 contactos con nombre y email.
                    </li>
                    <li class="help-step help-step--violet">
                        Ve a <strong>Clients</strong> y crea un cliente — por ejemplo, la empresa
                        donde trabajan esos contactos.
                    </li>
                    <li class="help-step help-step--violet">
                        Abre la ficha de cada contacto y vinculalo al cliente que acabas de crear
                        desde el panel lateral derecho.
                    </li>
                    <li class="help-step help-step--violet">
                        Aplica un tag como <em>Lead</em> a los contactos: puedes hacerlo desde
                        la ficha individual o seleccionando varios en el listado y usando la barra
                        de acciones en bloque que aparece en la parte inferior.
                    </li>
                    <li class="help-step help-step--violet">
                        Vuelve al listado de <strong>Contacts</strong>, abre el panel de
                        <strong>Filters</strong> y filtra por el tag <em>Lead</em> para ver el segmento.
                    </li>
                    <li class="help-step help-step--violet">
                        Explora <strong>Exports</strong> para descargar los datos en CSV o XLSX
                        eligiendo exactamente que campos incluir.
                    </li>
                </ol>

                <div class="help-callout help-callout--violet">
                    <i class="ph ph-lightbulb"></i>
                    <p>
                        <strong>¿Ya tienes datos en una hoja de calculo?</strong> Ve directamente a
                        <em>Imports</em> para cargarlos de golpe. Descarga antes la plantilla CSV desde
                        <em>Exports → Import templates</em> para asegurarte de usar el formato correcto
                        y evitar errores de columnas.
                    </p>
                </div>
                <?php else: ?>
                <p class="help-p">
                    If you've just logged in for the first time, follow this flow to get hands-on
                    experience with the system:
                </p>
                <ol class="help-steps">
                    <li class="help-step help-step--violet">
                        Go to <strong>Contacts</strong> and create 2–3 contacts with a name and email.
                    </li>
                    <li class="help-step help-step--violet">
                        Go to <strong>Clients</strong> and create a client — for example, the company
                        where those contacts work.
                    </li>
                    <li class="help-step help-step--violet">
                        Open each contact record and link it to the client you just created from the
                        right sidebar panel.
                    </li>
                    <li class="help-step help-step--violet">
                        Apply a tag like <em>Lead</em> to the contacts: you can do it from the
                        individual record or by selecting multiple contacts in the list and using
                        the bulk actions bar that appears at the bottom.
                    </li>
                    <li class="help-step help-step--violet">
                        Go back to the <strong>Contacts</strong> list, open the <strong>Filters</strong>
                        panel, and filter by the <em>Lead</em> tag to see the segment.
                    </li>
                    <li class="help-step help-step--violet">
                        Explore <strong>Exports</strong> to download the data as CSV or XLSX, choosing
                        exactly which fields to include.
                    </li>
                </ol>

                <div class="help-callout help-callout--violet">
                    <i class="ph ph-lightbulb"></i>
                    <p>
                        <strong>Already have data in a spreadsheet?</strong> Go straight to <em>Imports</em>
                        to load it in bulk. Download the CSV template first from
                        <em>Exports → Import templates</em> to make sure you use the correct column
                        format and avoid import errors.
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </section>

    </div><!-- /.help-content -->
</div><!-- /.help-layout -->