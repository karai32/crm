<?php
$isEs    = $locale === 'es';
$backUrl = htmlspecialchars(Auth::url('/help?lang=' . $locale), ENT_QUOTES, 'UTF-8');

$toc = $isEs ? [
    ['id' => 'what',      'label' => 'Que puedes exportar'],
    ['id' => 'format',    'label' => 'Formato y campos'],
    ['id' => 'process',   'label' => 'Como exportar'],
    ['id' => 'templates', 'label' => 'Plantillas de importacion'],
    ['id' => 'history',   'label' => 'Historial de exportaciones'],
] : [
    ['id' => 'what',      'label' => 'What you can export'],
    ['id' => 'format',    'label' => 'Format and fields'],
    ['id' => 'process',   'label' => 'How to export'],
    ['id' => 'templates', 'label' => 'Import templates'],
    ['id' => 'history',   'label' => 'Export history'],
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
               href="<?= htmlspecialchars(Auth::url('/help/exports?lang=' . $code), ENT_QUOTES, 'UTF-8') ?>">
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
               class="help-toc-item help-toc-item--sky"
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
                <div class="help-card-icon help-card-icon--sky">
                    <i class="ph ph-download-simple"></i>
                </div>
                <div class="help-card-titles">
                    <h2><?= $isEs ? 'Que puedes exportar' : 'What you can export' ?></h2>
                    <p class="help-card-summary">
                        <?= $isEs
                            ? 'Descarga contactos o clientes en CSV o XLSX con los campos que elijas.'
                            : 'Download contacts or clients as CSV or XLSX with the fields you choose.' ?>
                    </p>
                </div>
            </div>
            <div class="help-card-body">
                <?php if ($isEs): ?>
                <p class="help-p">
                    La seccion de exportaciones permite descargar los datos de <strong>contactos</strong>
                    o <strong>clientes</strong> en formato CSV o XLSX. Cada exportacion genera un archivo
                    con una fila por registro y las columnas que selecciones. Es la forma mas directa
                    de hacer una copia de seguridad, analizar los datos en Excel o preparar archivos
                    para otras herramientas.
                </p>
                <p class="help-p">
                    Puedes elegir exportar <strong>todos</strong> los registros o solo los que cumplan
                    los filtros activos en el momento de exportar. Si tienes aplicado un filtro por
                    tag, sector o campo personalizado, la exportacion respetara esa seleccion.
                </p>
                <div class="help-callout help-callout--sky">
                    <i class="ph ph-info"></i>
                    <p>
                        Las exportaciones no tienen limite de filas: si tienes 50.000 contactos y
                        no aplicas filtros, el archivo contendra las 50.000 filas.
                    </p>
                </div>
                <?php else: ?>
                <p class="help-p">
                    The export section lets you download <strong>contact</strong> or <strong>client</strong>
                    data as CSV or XLSX. Each export generates a file with one row per record and the
                    columns you select. It is the most direct way to back up data, analyse it in Excel,
                    or prepare files for other tools.
                </p>
                <p class="help-p">
                    You can choose to export <strong>all</strong> records or only those matching the
                    active filters at the time of export. If you have a filter applied by tag, sector,
                    or custom field, the export will respect that selection.
                </p>
                <div class="help-callout help-callout--sky">
                    <i class="ph ph-info"></i>
                    <p>
                        Exports have no row limit: if you have 50,000 contacts and apply no filters,
                        the file will contain all 50,000 rows.
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- ══════════════════════════════════ FORMAT ══ -->
        <section class="help-card" id="format">
            <div class="help-card-head">
                <div class="help-card-icon help-card-icon--sky">
                    <i class="ph ph-table"></i>
                </div>
                <div class="help-card-titles">
                    <h2><?= $isEs ? 'Formato y campos' : 'Format and fields' ?></h2>
                    <p class="help-card-summary">
                        <?= $isEs
                            ? 'Elige entre CSV y XLSX, y marca exactamente los campos que necesitas.'
                            : 'Choose between CSV and XLSX, and tick exactly the fields you need.' ?>
                    </p>
                </div>
            </div>
            <div class="help-card-body">
                <?php if ($isEs): ?>
                <p class="help-p">
                    Antes de descargar seleccionas el <strong>formato de salida</strong>:
                </p>
                <ul class="help-steps">
                    <li class="help-step help-step--sky">
                        <div>
                            <strong>CSV</strong> — Texto plano separado por comas. Compatible con
                            cualquier programa y con la funcion de reimportacion del propio CRM.
                            Ideal si el archivo va a volver a subirse o procesarse por scripts.
                        </div>
                    </li>
                    <li class="help-step help-step--sky">
                        <div>
                            <strong>XLSX</strong> — Hoja de calculo Excel. Mantiene el formato
                            de fechas y numeros, y permite trabajar directamente en Excel o Google
                            Sheets sin pasos de conversion.
                        </div>
                    </li>
                </ul>
                <p class="help-p">
                    A continuacion marcas los <strong>campos a incluir</strong>. Para contactos
                    puedes elegir cualquier combinacion de campos base
                    (<code>full_name</code>, <code>email</code>, <code>phone</code>, <code>company</code>)
                    mas los tags, clientes vinculados y todos los campos personalizados activos.
                    Para clientes, los campos base son
                    <code>commercial_name</code>, <code>legal_name</code>, <code>sector</code>,
                    <code>renewal_date</code> y los campos personalizados de cliente.
                </p>
                <div class="help-callout help-callout--sky">
                    <i class="ph ph-lightbulb"></i>
                    <p>
                        Los tags y clientes vinculados se exportan como valores separados por coma
                        dentro de una sola celda, igual que el formato que acepta la importacion.
                        Esto hace que un ciclo exportar → editar → reimportar sea limpio y sin perdidas.
                    </p>
                </div>
                <?php else: ?>
                <p class="help-p">
                    Before downloading you select the <strong>output format</strong>:
                </p>
                <ul class="help-steps">
                    <li class="help-step help-step--sky">
                        <div>
                            <strong>CSV</strong> — Plain comma-separated text. Compatible with any
                            program and with the CRM's own re-import function. Ideal when the file
                            will be re-uploaded or processed by scripts.
                        </div>
                    </li>
                    <li class="help-step help-step--sky">
                        <div>
                            <strong>XLSX</strong> — Excel spreadsheet. Preserves date and number
                            formatting, and lets you work directly in Excel or Google Sheets without
                            conversion steps.
                        </div>
                    </li>
                </ul>
                <p class="help-p">
                    Then you tick the <strong>fields to include</strong>. For contacts you can choose
                    any combination of base fields
                    (<code>full_name</code>, <code>email</code>, <code>phone</code>, <code>company</code>)
                    plus tags, linked clients, and all active custom fields. For clients, the base
                    fields are <code>commercial_name</code>, <code>legal_name</code>,
                    <code>sector</code>, <code>renewal_date</code>, and client custom fields.
                </p>
                <div class="help-callout help-callout--sky">
                    <i class="ph ph-lightbulb"></i>
                    <p>
                        Tags and linked clients are exported as comma-separated values inside a single
                        cell, identical to the format the import accepts. This makes an
                        export → edit → re-import cycle clean and lossless.
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- ══════════════════════════════════ PROCESS ══ -->
        <section class="help-card" id="process">
            <div class="help-card-head">
                <div class="help-card-icon help-card-icon--sky">
                    <i class="ph ph-list-numbers"></i>
                </div>
                <div class="help-card-titles">
                    <h2><?= $isEs ? 'Como exportar' : 'How to export' ?></h2>
                    <p class="help-card-summary">
                        <?= $isEs
                            ? 'Tres pasos: elegir entidad → configurar campos y formato → descargar.'
                            : 'Three steps: choose entity → configure fields and format → download.' ?>
                    </p>
                </div>
            </div>
            <div class="help-card-body">
                <?php if ($isEs): ?>
                <ol class="help-steps">
                    <li class="help-step help-step--sky">
                        <div>
                            <strong>Elegir la entidad</strong><br>
                            En la pantalla de Exports, selecciona la pestana <em>Contactos</em> o
                            <em>Clientes</em>. Cada pestana muestra las opciones especificas de esa
                            entidad.
                        </div>
                    </li>
                    <li class="help-step help-step--sky">
                        <div>
                            <strong>Configurar campos y formato</strong><br>
                            Marca los campos que quieres en el archivo y elige el formato (CSV o XLSX).
                            Si quieres exportar solo un subconjunto de registros, asegurate de aplicar
                            los filtros correspondientes en el listado antes de abrir la pantalla de
                            exportacion.
                        </div>
                    </li>
                    <li class="help-step help-step--sky">
                        <div>
                            <strong>Descargar</strong><br>
                            Haz clic en <em>Exportar</em>. El sistema genera el archivo en segundos
                            y lo descarga directamente al navegador. Una entrada queda registrada en
                            el historial de exportaciones con la fecha, el usuario y el numero de
                            registros exportados.
                        </div>
                    </li>
                </ol>
                <?php else: ?>
                <ol class="help-steps">
                    <li class="help-step help-step--sky">
                        <div>
                            <strong>Choose the entity</strong><br>
                            On the Exports screen, select the <em>Contacts</em> or <em>Clients</em>
                            tab. Each tab shows the options specific to that entity.
                        </div>
                    </li>
                    <li class="help-step help-step--sky">
                        <div>
                            <strong>Configure fields and format</strong><br>
                            Tick the fields you want in the file and choose the format (CSV or XLSX).
                            If you want to export only a subset of records, make sure you apply the
                            relevant filters in the list before opening the export screen.
                        </div>
                    </li>
                    <li class="help-step help-step--sky">
                        <div>
                            <strong>Download</strong><br>
                            Click <em>Export</em>. The system generates the file in seconds and
                            downloads it directly to the browser. An entry is logged in the export
                            history with the date, user, and number of records exported.
                        </div>
                    </li>
                </ol>
                <?php endif; ?>
            </div>
        </section>

        <!-- ══════════════════════════════════ TEMPLATES ══ -->
        <section class="help-card" id="templates">
            <div class="help-card-head">
                <div class="help-card-icon help-card-icon--sky">
                    <i class="ph ph-file-arrow-down"></i>
                </div>
                <div class="help-card-titles">
                    <h2><?= $isEs ? 'Plantillas de importacion' : 'Import templates' ?></h2>
                    <p class="help-card-summary">
                        <?= $isEs
                            ? 'Archivos CSV listos para rellenar y subir directamente a Imports.'
                            : 'Ready-to-fill CSV files that upload directly to Imports.' ?>
                    </p>
                </div>
            </div>
            <div class="help-card-body">
                <?php if ($isEs): ?>
                <p class="help-p">
                    La pestana <strong>Import templates</strong> dentro de la pantalla de Exports
                    ofrece archivos CSV preformateados que puedes descargar, rellenar con tus datos
                    y subir directamente a la funcion de importacion sin ajustes adicionales.
                </p>
                <p class="help-p">
                    Hay una plantilla para <strong>contactos</strong> y otra para <strong>clientes</strong>.
                    Cada plantilla incluye:
                </p>
                <ul class="help-steps">
                    <li class="help-step help-step--sky">
                        La <strong>fila de cabecera</strong> con todos los nombres de columna reconocidos
                        por el importador, incluyendo los campos personalizados activos en el momento
                        de la descarga.
                    </li>
                    <li class="help-step help-step--sky">
                        Una <strong>fila de ejemplo</strong> con datos de muestra para que entiendas
                        el formato esperado de cada campo (fechas, tags separados por coma, etc.).
                    </li>
                </ul>
                <div class="help-callout help-callout--sky">
                    <i class="ph ph-info"></i>
                    <p>
                        Descarga la plantilla cada vez que vayas a importar, no guardes una version
                        antigua. Si anadiste campos personalizados desde la ultima descarga, la
                        plantilla vieja no los incluira y tendras que mapear esas columnas manualmente.
                    </p>
                </div>
                <?php else: ?>
                <p class="help-p">
                    The <strong>Import templates</strong> tab within the Exports screen provides
                    pre-formatted CSV files you can download, fill with your data, and upload directly
                    to the import function without any additional adjustments.
                </p>
                <p class="help-p">
                    There is one template for <strong>contacts</strong> and one for <strong>clients</strong>.
                    Each template includes:
                </p>
                <ul class="help-steps">
                    <li class="help-step help-step--sky">
                        The <strong>header row</strong> with all column names recognised by the importer,
                        including custom fields active at the time of download.
                    </li>
                    <li class="help-step help-step--sky">
                        A <strong>sample row</strong> with example data so you can understand the
                        expected format for each field (dates, comma-separated tags, etc.).
                    </li>
                </ul>
                <div class="help-callout help-callout--sky">
                    <i class="ph ph-info"></i>
                    <p>
                        Download the template fresh each time you import — don't keep an old copy.
                        If you added custom fields since the last download, the old template won't
                        include them and you'll need to map those columns manually.
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- ══════════════════════════════════ HISTORY ══ -->
        <section class="help-card" id="history">
            <div class="help-card-head">
                <div class="help-card-icon help-card-icon--sky">
                    <i class="ph ph-clock-counter-clockwise"></i>
                </div>
                <div class="help-card-titles">
                    <h2><?= $isEs ? 'Historial de exportaciones' : 'Export history' ?></h2>
                    <p class="help-card-summary">
                        <?= $isEs
                            ? 'Registro de quien exporto que y cuantos registros.'
                            : 'A log of who exported what and how many records.' ?>
                    </p>
                </div>
            </div>
            <div class="help-card-body">
                <?php if ($isEs): ?>
                <p class="help-p">
                    Cada exportacion completada genera un registro en el historial que puedes consultar
                    desde la misma pantalla de Exports. El registro incluye la <strong>fecha y hora</strong>,
                    el <strong>usuario</strong> que realizo la exportacion, la <strong>entidad</strong>
                    exportada (contactos o clientes), el <strong>formato</strong> elegido (CSV/XLSX) y
                    el <strong>numero de registros</strong> incluidos en el archivo.
                </p>
                <p class="help-p">
                    El historial no almacena el archivo en si, solo los metadatos. Si necesitas
                    recuperar un archivo exportado anteriormente, tendras que volver a generarlo con
                    la misma configuracion. Por esto, si una exportacion es importante, guardala
                    en un lugar seguro inmediatamente despues de descargarla.
                </p>
                <div class="help-callout help-callout--sky">
                    <i class="ph ph-lightbulb"></i>
                    <p>
                        El historial es util para auditorias: si alguien pregunta "quien descargo la
                        base de contactos el martes", encontraras la respuesta exacta aqui con el
                        usuario y la hora.
                    </p>
                </div>
                <?php else: ?>
                <p class="help-p">
                    Each completed export creates an entry in the history that you can view from
                    the Exports screen. The entry includes the <strong>date and time</strong>, the
                    <strong>user</strong> who performed the export, the <strong>entity</strong>
                    exported (contacts or clients), the <strong>format</strong> chosen (CSV/XLSX),
                    and the <strong>number of records</strong> included in the file.
                </p>
                <p class="help-p">
                    The history does not store the file itself, only the metadata. If you need to
                    recover a previously exported file, you will have to regenerate it with the same
                    settings. For this reason, if an export is important, save it to a safe location
                    immediately after downloading.
                </p>
                <div class="help-callout help-callout--sky">
                    <i class="ph ph-lightbulb"></i>
                    <p>
                        The history is useful for audits: if someone asks "who downloaded the contact
                        database on Tuesday", you will find the exact answer here with the user and
                        the timestamp.
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </section>

    </div>
</div>

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
