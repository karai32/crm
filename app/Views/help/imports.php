<?php
$isEs    = $locale === 'es';
$backUrl = htmlspecialchars(Auth::url('/help?lang=' . $locale), ENT_QUOTES, 'UTF-8');

$toc = $isEs ? [
    ['id' => 'what',    'label' => 'Que puedes importar'],
    ['id' => 'format',  'label' => 'Formato del archivo'],
    ['id' => 'process', 'label' => 'El proceso paso a paso'],
    ['id' => 'mapping', 'label' => 'Mapeo de columnas'],
    ['id' => 'history', 'label' => 'Historial y errores'],
] : [
    ['id' => 'what',    'label' => 'What you can import'],
    ['id' => 'format',  'label' => 'File format'],
    ['id' => 'process', 'label' => 'The process step by step'],
    ['id' => 'mapping', 'label' => 'Column mapping'],
    ['id' => 'history', 'label' => 'History and errors'],
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
               href="<?= htmlspecialchars(Auth::url('/help/imports?lang=' . $code), ENT_QUOTES, 'UTF-8') ?>">
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
               class="help-toc-item help-toc-item--indigo"
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
                <div class="help-card-icon help-card-icon--indigo">
                    <i class="ph ph-upload-simple"></i>
                </div>
                <div class="help-card-titles">
                    <h2><?= $isEs ? 'Que puedes importar' : 'What you can import' ?></h2>
                    <p class="help-card-summary">
                        <?= $isEs
                            ? 'Carga contactos en masa desde un archivo CSV o XLSX en cuatro pasos.'
                            : 'Load contacts in bulk from a CSV or XLSX file in four steps.' ?>
                    </p>
                </div>
            </div>
            <div class="help-card-body">
                <?php if ($isEs): ?>
                <p class="help-p">
                    La funcion de importacion permite cargar <strong>contactos</strong> en masa desde
                    un archivo CSV o XLSX. Es el metodo mas rapido para poblar el CRM cuando ya tienes
                    datos existentes en una hoja de calculo, un CRM anterior o una exportacion de otra
                    herramienta.
                </p>
                <p class="help-p">
                    Ademas de los campos base (nombre, email, telefono, empresa), puedes importar
                    <strong>tags</strong>, <strong>clientes vinculados</strong> y cualquier
                    <strong>campo personalizado</strong> que tengas configurado para contactos.
                    Los tags y clientes que no existan en el sistema se crean automaticamente durante
                    la importacion.
                </p>
                <div class="help-callout help-callout--indigo">
                    <i class="ph ph-info"></i>
                    <p>
                        La importacion es solo para contactos. Los clientes se gestionan individualmente
                        desde el listado o a traves de la API.
                    </p>
                </div>
                <?php else: ?>
                <p class="help-p">
                    The import function lets you load <strong>contacts</strong> in bulk from a CSV or
                    XLSX file. It is the fastest way to populate the CRM when you already have data in
                    a spreadsheet, a previous CRM, or an export from another tool.
                </p>
                <p class="help-p">
                    In addition to base fields (name, email, phone, company), you can import
                    <strong>tags</strong>, <strong>linked clients</strong>, and any
                    <strong>custom fields</strong> you have configured for contacts. Tags and clients
                    that don't exist in the system are created automatically during the import.
                </p>
                <div class="help-callout help-callout--indigo">
                    <i class="ph ph-info"></i>
                    <p>
                        The import function is for contacts only. Clients are managed individually
                        from the list or through the API.
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- ══════════════════════════════════ FORMAT ══ -->
        <section class="help-card" id="format">
            <div class="help-card-head">
                <div class="help-card-icon help-card-icon--indigo">
                    <i class="ph ph-file-csv"></i>
                </div>
                <div class="help-card-titles">
                    <h2><?= $isEs ? 'Formato del archivo' : 'File format' ?></h2>
                    <p class="help-card-summary">
                        <?= $isEs
                            ? 'La primera fila debe contener los nombres de columna. Solo full_name es obligatorio.'
                            : 'The first row must contain column names. Only full_name is required.' ?>
                    </p>
                </div>
            </div>
            <div class="help-card-body">
                <?php if ($isEs): ?>
                <p class="help-p">
                    El archivo debe tener la <strong>primera fila con los nombres de columna</strong>
                    (cabecera). Las filas siguientes contienen los datos, una por contacto. Se admiten
                    archivos <strong>CSV</strong> (separador coma o punto y coma) y
                    <strong>XLSX</strong> (Excel). El unico campo obligatorio es <code>full_name</code>;
                    el resto son opcionales.
                </p>

                <p class="help-sub-label"><?= $isEs ? 'Columnas reconocidas automaticamente' : 'Automatically recognised columns' ?></p>
                <ul class="help-steps">
                    <li class="help-step help-step--indigo"><code>full_name</code> — <?= $isEs ? 'Nombre completo (obligatorio).' : 'Full name (required).' ?></li>
                    <li class="help-step help-step--indigo"><code>email</code> — <?= $isEs ? 'Email. Debe ser unico; las filas con email duplicado se omiten.' : 'Email. Must be unique; rows with a duplicate email are skipped.' ?></li>
                    <li class="help-step help-step--indigo"><code>phone</code> — <?= $isEs ? 'Telefono.' : 'Phone number.' ?></li>
                    <li class="help-step help-step--indigo"><code>company</code> — <?= $isEs ? 'Nombre de empresa del contacto.' : 'Contact\'s company name.' ?></li>
                    <li class="help-step help-step--indigo"><code>tags</code> — <?= $isEs ? 'Uno o varios tags separados por coma: <em>Lead, Hot</em>.' : 'One or more tags separated by a comma: <em>Lead, Hot</em>.' ?></li>
                    <li class="help-step help-step--indigo"><code>clients</code> — <?= $isEs ? 'Uno o varios clientes separados por coma. Se crean si no existen.' : 'One or more clients separated by a comma. Created if they don\'t exist.' ?></li>
                </ul>
                <p class="help-p">
                    <?= $isEs
                        ? 'Cualquier otra columna se puede mapear a un campo personalizado existente o descartar durante el paso de mapeo.'
                        : 'Any other column can be mapped to an existing custom field or discarded during the mapping step.' ?>
                </p>

                <div class="help-callout help-callout--indigo">
                    <i class="ph ph-lightbulb"></i>
                    <p>
                        <?= $isEs
                            ? 'Descarga la plantilla CSV desde <em>Exports → Import templates</em> para empezar con el formato correcto y evitar errores de cabecera.'
                            : 'Download the CSV template from <em>Exports → Import templates</em> to start with the correct format and avoid header errors.' ?>
                    </p>
                </div>
                <?php else: ?>
                <p class="help-p">
                    The file must have the <strong>first row with column names</strong> (the header).
                    Following rows contain data, one per contact. Both <strong>CSV</strong> (comma or
                    semicolon separator) and <strong>XLSX</strong> (Excel) files are accepted. The
                    only required field is <code>full_name</code>; the rest are optional.
                </p>

                <p class="help-sub-label">Automatically recognised columns</p>
                <ul class="help-steps">
                    <li class="help-step help-step--indigo"><code>full_name</code> — Full name (required).</li>
                    <li class="help-step help-step--indigo"><code>email</code> — Email. Must be unique; rows with a duplicate email are skipped.</li>
                    <li class="help-step help-step--indigo"><code>phone</code> — Phone number.</li>
                    <li class="help-step help-step--indigo"><code>company</code> — Contact's company name.</li>
                    <li class="help-step help-step--indigo"><code>tags</code> — One or more tags separated by a comma: <em>Lead, Hot</em>.</li>
                    <li class="help-step help-step--indigo"><code>clients</code> — One or more clients separated by a comma. Created if they don't exist.</li>
                </ul>
                <p class="help-p">
                    Any other column can be mapped to an existing custom field or discarded during
                    the mapping step.
                </p>

                <div class="help-callout help-callout--indigo">
                    <i class="ph ph-lightbulb"></i>
                    <p>
                        Download the CSV template from <em>Exports → Import templates</em> to start
                        with the correct format and avoid header errors.
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- ══════════════════════════════════ PROCESS ══ -->
        <section class="help-card" id="process">
            <div class="help-card-head">
                <div class="help-card-icon help-card-icon--indigo">
                    <i class="ph ph-list-numbers"></i>
                </div>
                <div class="help-card-titles">
                    <h2><?= $isEs ? 'El proceso paso a paso' : 'The process step by step' ?></h2>
                    <p class="help-card-summary">
                        <?= $isEs
                            ? 'Subir → Previsualizar → Mapear columnas → Confirmar.'
                            : 'Upload → Preview → Map columns → Confirm.' ?>
                    </p>
                </div>
            </div>
            <div class="help-card-body">
                <?php if ($isEs): ?>
                <ol class="help-steps">
                    <li class="help-step help-step--indigo">
                        <div>
                            <strong><?= $isEs ? 'Subir el archivo' : 'Upload the file' ?></strong><br>
                            <?= $isEs
                                ? 'Ve a <em>Imports</em> y arrastra el archivo CSV o XLSX al area de carga, o haz clic para seleccionarlo. El sistema lo lee y pasa al siguiente paso automaticamente.'
                                : 'Go to <em>Imports</em> and drag the CSV or XLSX file to the upload area, or click to select it. The system reads it and moves to the next step automatically.' ?>
                        </div>
                    </li>
                    <li class="help-step help-step--indigo">
                        <div>
                            <strong><?= $isEs ? 'Previsualizar' : 'Preview' ?></strong><br>
                            <?= $isEs
                                ? 'Se muestran las primeras filas del archivo para que compruebes que los datos se leyeron correctamente: que el separador es el adecuado y que los valores estan en las columnas esperadas.'
                                : 'The first rows of the file are shown so you can verify the data was read correctly: that the separator is right and values are in the expected columns.' ?>
                        </div>
                    </li>
                    <li class="help-step help-step--indigo">
                        <div>
                            <strong><?= $isEs ? 'Mapear columnas' : 'Map columns' ?></strong><br>
                            <?= $isEs
                                ? 'Asigna cada columna del archivo a un campo del sistema. Las columnas con nombres reconocidos (<code>full_name</code>, <code>email</code>, etc.) se preseleccionan automaticamente. Las demas puedes mapearlas a campos personalizados existentes o elegir descartarlas.'
                                : 'Assign each file column to a system field. Columns with recognised names (<code>full_name</code>, <code>email</code>, etc.) are pre-selected automatically. The rest can be mapped to existing custom fields or discarded.' ?>
                        </div>
                    </li>
                    <li class="help-step help-step--indigo">
                        <div>
                            <strong><?= $isEs ? 'Confirmar e importar' : 'Confirm and import' ?></strong><br>
                            <?= $isEs
                                ? 'Revisa el resumen del mapeo y confirma. El sistema procesa todas las filas: crea los contactos validos, omite los duplicados por email y registra los errores linea a linea en el historial.'
                                : 'Review the mapping summary and confirm. The system processes all rows: creates valid contacts, skips email duplicates, and logs errors line by line in the history.' ?>
                        </div>
                    </li>
                </ol>
                <?php else: ?>
                <ol class="help-steps">
                    <li class="help-step help-step--indigo">
                        <div>
                            <strong>Upload the file</strong><br>
                            Go to <em>Imports</em> and drag the CSV or XLSX file to the upload area,
                            or click to select it. The system reads it and moves to the next step automatically.
                        </div>
                    </li>
                    <li class="help-step help-step--indigo">
                        <div>
                            <strong>Preview</strong><br>
                            The first rows of the file are shown so you can verify the data was read
                            correctly: that the separator is right and values are in the expected columns.
                        </div>
                    </li>
                    <li class="help-step help-step--indigo">
                        <div>
                            <strong>Map columns</strong><br>
                            Assign each file column to a system field. Columns with recognised names
                            (<code>full_name</code>, <code>email</code>, etc.) are pre-selected
                            automatically. The rest can be mapped to existing custom fields or discarded.
                        </div>
                    </li>
                    <li class="help-step help-step--indigo">
                        <div>
                            <strong>Confirm and import</strong><br>
                            Review the mapping summary and confirm. The system processes all rows:
                            creates valid contacts, skips email duplicates, and logs errors line by
                            line in the history.
                        </div>
                    </li>
                </ol>
                <?php endif; ?>
            </div>
        </section>

        <!-- ══════════════════════════════════ MAPPING ══ -->
        <section class="help-card" id="mapping">
            <div class="help-card-head">
                <div class="help-card-icon help-card-icon--indigo">
                    <i class="ph ph-arrows-left-right"></i>
                </div>
                <div class="help-card-titles">
                    <h2><?= $isEs ? 'Mapeo de columnas' : 'Column mapping' ?></h2>
                    <p class="help-card-summary">
                        <?= $isEs
                            ? 'Relaciona cada columna del archivo con un campo del sistema o descartala.'
                            : 'Match each file column to a system field or discard it.' ?>
                    </p>
                </div>
            </div>
            <div class="help-card-body">
                <?php if ($isEs): ?>
                <p class="help-p">
                    El mapeador muestra cada columna del archivo con un desplegable donde puedes elegir
                    a que campo del sistema corresponde. Las opciones disponibles son todos los campos
                    base mas los campos personalizados de contactos que esten configurados.
                </p>
                <p class="help-p">
                    Para cada columna sin correspondencia tienes dos opciones adicionales:
                </p>
                <ul class="help-steps">
                    <li class="help-step help-step--indigo">
                        <strong><?= $isEs ? 'Descartar:' : 'Discard:' ?></strong>
                        <?= $isEs ? 'El dato de esa columna se ignora completamente.' : 'The column\'s data is completely ignored.' ?>
                    </li>
                    <li class="help-step help-step--indigo">
                        <strong><?= $isEs ? 'Crear campo personalizado:' : 'Create custom field:' ?></strong>
                        <?= $isEs
                            ? 'El sistema genera un campo de tipo <code>text</code> con el nombre de la columna y lo popula con los datos importados.'
                            : 'The system creates a <code>text</code> type field with the column name and populates it with the imported data.' ?>
                    </li>
                </ul>
                <div class="help-callout help-callout--indigo">
                    <i class="ph ph-warning"></i>
                    <p>
                        <?= $isEs
                            ? 'Revisa siempre el mapeo antes de confirmar. Una vez iniciada la importacion no puede deshacerse: los contactos creados tendran que eliminarse manualmente si el mapeo fue incorrecto.'
                            : 'Always review the mapping before confirming. Once the import starts it cannot be undone: contacts created with incorrect mapping will need to be deleted manually.' ?>
                    </p>
                </div>
                <?php else: ?>
                <p class="help-p">
                    The mapper shows each file column with a dropdown where you can choose which system
                    field it corresponds to. The available options are all base fields plus any contact
                    custom fields you have configured.
                </p>
                <p class="help-p">
                    For each unmatched column you have two additional options:
                </p>
                <ul class="help-steps">
                    <li class="help-step help-step--indigo">
                        <strong>Discard:</strong> the column's data is completely ignored.
                    </li>
                    <li class="help-step help-step--indigo">
                        <strong>Create custom field:</strong> the system creates a <code>text</code>
                        type field with the column name and populates it with the imported data.
                    </li>
                </ul>
                <div class="help-callout help-callout--indigo">
                    <i class="ph ph-warning"></i>
                    <p>
                        Always review the mapping before confirming. Once the import starts it cannot
                        be undone: contacts created with incorrect mapping will need to be deleted
                        manually.
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- ══════════════════════════════════ HISTORY ══ -->
        <section class="help-card" id="history">
            <div class="help-card-head">
                <div class="help-card-icon help-card-icon--indigo">
                    <i class="ph ph-clock-counter-clockwise"></i>
                </div>
                <div class="help-card-titles">
                    <h2><?= $isEs ? 'Historial y errores' : 'History and errors' ?></h2>
                    <p class="help-card-summary">
                        <?= $isEs
                            ? 'Cada importacion queda registrada con el resultado fila a fila.'
                            : 'Each import is logged with the row-by-row result.' ?>
                    </p>
                </div>
            </div>
            <div class="help-card-body">
                <?php if ($isEs): ?>
                <p class="help-p">
                    Al finalizar cada importacion, el sistema muestra un resumen con tres contadores:
                    filas <strong>importadas</strong> correctamente, filas <strong>omitidas</strong>
                    (emails duplicados) y filas con <strong>error</strong>. Este resumen queda guardado
                    en el historial de importaciones, accesible desde la misma pantalla de Imports.
                </p>
                <p class="help-p">
                    El boton <strong>Ver errores</strong> junto a cada importacion en el historial
                    despliega el detalle fila a fila: el numero de linea del archivo original, el dato
                    que causo el problema y el motivo del error. Esto te permite corregir el archivo
                    y reimportar solo las filas fallidas sin volver a procesar las que ya se importaron
                    correctamente.
                </p>

                <p class="help-sub-label"><?= $isEs ? 'Causas de error mas frecuentes' : 'Most common error causes' ?></p>
                <ul class="help-steps">
                    <li class="help-step help-step--indigo">
                        <?= $isEs
                            ? '<strong>Email duplicado:</strong> ya existe un contacto con ese email. La fila se omite, no se cuenta como error.'
                            : '<strong>Duplicate email:</strong> a contact with that email already exists. The row is skipped, not counted as an error.' ?>
                    </li>
                    <li class="help-step help-step--indigo">
                        <?= $isEs
                            ? '<strong>Email invalido:</strong> el formato del email no es correcto (falta @, dominio incompleto, etc.).'
                            : '<strong>Invalid email:</strong> the email format is incorrect (missing @, incomplete domain, etc.).' ?>
                    </li>
                    <li class="help-step help-step--indigo">
                        <?= $isEs
                            ? '<strong>Nombre vacio:</strong> la columna <code>full_name</code> esta vacia en esa fila.'
                            : '<strong>Empty name:</strong> the <code>full_name</code> column is empty for that row.' ?>
                    </li>
                </ul>
                <?php else: ?>
                <p class="help-p">
                    At the end of each import, the system shows a summary with three counters: rows
                    successfully <strong>imported</strong>, rows <strong>skipped</strong> (duplicate
                    emails), and rows with <strong>errors</strong>. This summary is saved in the import
                    history, accessible from the Imports screen.
                </p>
                <p class="help-p">
                    The <strong>View errors</strong> button next to each import in the history expands
                    the row-by-row detail: the line number in the original file, the value that caused
                    the problem, and the reason for the error. This lets you fix the file and re-import
                    only the failed rows without reprocessing the ones that were already imported correctly.
                </p>

                <p class="help-sub-label">Most common error causes</p>
                <ul class="help-steps">
                    <li class="help-step help-step--indigo">
                        <strong>Duplicate email:</strong> a contact with that email already exists.
                        The row is skipped, not counted as an error.
                    </li>
                    <li class="help-step help-step--indigo">
                        <strong>Invalid email:</strong> the email format is incorrect (missing @,
                        incomplete domain, etc.).
                    </li>
                    <li class="help-step help-step--indigo">
                        <strong>Empty name:</strong> the <code>full_name</code> column is empty
                        for that row.
                    </li>
                </ul>
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
