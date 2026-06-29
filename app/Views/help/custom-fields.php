<?php
$isEs    = $locale === 'es';
$backUrl = htmlspecialchars(Auth::url('/help?lang=' . $locale), ENT_QUOTES, 'UTF-8');

$toc = $isEs ? [
    ['id' => 'what',       'label' => 'Que son'],
    ['id' => 'types',      'label' => 'Tipos de campo'],
    ['id' => 'filterable', 'label' => 'Campo filtrable'],
    ['id' => 'import',     'label' => 'En importaciones'],
    ['id' => 'manage',     'label' => 'Gestion'],
] : [
    ['id' => 'what',       'label' => 'What they are'],
    ['id' => 'types',      'label' => 'Field types'],
    ['id' => 'filterable', 'label' => 'Filterable field'],
    ['id' => 'import',     'label' => 'During imports'],
    ['id' => 'manage',     'label' => 'Management'],
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
               href="<?= htmlspecialchars(Auth::url('/help/custom-fields?lang=' . $code), ENT_QUOTES, 'UTF-8') ?>">
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
               class="help-toc-item help-toc-item--teal"
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
                <div class="help-card-icon help-card-icon--teal">
                    <i class="ph ph-sliders-horizontal"></i>
                </div>
                <div class="help-card-titles">
                    <h2><?= $isEs ? 'Que son los campos personalizados' : 'What are custom fields' ?></h2>
                    <p class="help-card-summary">
                        <?= $isEs
                            ? 'Amplian la ficha de contactos o clientes con cualquier dato extra que necesites, sin tocar codigo.'
                            : 'Extend contact or client records with any extra data you need, without touching code.' ?>
                    </p>
                </div>
            </div>
            <div class="help-card-body">
                <?php if ($isEs): ?>
                <p class="help-p">
                    Los campos personalizados permiten adaptar el CRM a tu proceso sin necesidad de
                    modificar el codigo ni la base de datos. Son campos adicionales que aparecen en la
                    ficha de contactos o clientes junto a los campos base, y funcionan exactamente igual
                    que ellos: se rellenan al crear o editar un registro, se exportan y se pueden usar
                    como filtros.
                </p>
                <p class="help-p">
                    Los campos de <strong>contactos</strong> y los de <strong>clientes</strong> son
                    completamente independientes: puedes tener campos distintos para cada entidad. Un
                    campo creado para contactos no aparece en la ficha de clientes y viceversa.
                </p>

                <p class="help-sub-label"><?= $isEs ? 'Ejemplos practicos' : 'Practical examples' ?></p>
                <ul class="help-steps">
                    <li class="help-step help-step--teal">
                        <?= $isEs
                            ? '<strong>Contactos:</strong> Fecha de nacimiento, Presupuesto estimado, Cargo, LinkedIn, Siguiente accion.'
                            : '<strong>Contacts:</strong> Date of birth, Estimated budget, Job title, LinkedIn, Next action.' ?>
                    </li>
                    <li class="help-step help-step--teal">
                        <?= $isEs
                            ? '<strong>Clientes:</strong> Numero de contrato, Fecha de renovacion, Volumen de facturacion, Gestor asignado, NPS.'
                            : '<strong>Clients:</strong> Contract number, Renewal date, Billing volume, Assigned manager, NPS.' ?>
                    </li>
                </ul>
                <?php else: ?>
                <p class="help-p">
                    Custom fields let you adapt the CRM to your process without modifying code or the
                    database. They are additional fields that appear on contact or client records alongside
                    the base fields, and work exactly the same way: filled in when creating or editing a
                    record, exported with the rest of the data, and usable as filters.
                </p>
                <p class="help-p">
                    <strong>Contact</strong> fields and <strong>client</strong> fields are completely
                    independent: you can have different fields for each entity. A field created for
                    contacts does not appear on client records and vice versa.
                </p>

                <p class="help-sub-label">Practical examples</p>
                <ul class="help-steps">
                    <li class="help-step help-step--teal">
                        <strong>Contacts:</strong> Date of birth, Estimated budget, Job title,
                        LinkedIn, Next action.
                    </li>
                    <li class="help-step help-step--teal">
                        <strong>Clients:</strong> Contract number, Renewal date, Billing volume,
                        Assigned manager, NPS.
                    </li>
                </ul>
                <?php endif; ?>
            </div>
        </section>

        <!-- ══════════════════════════════════ TYPES ══ -->
        <section class="help-card" id="types">
            <div class="help-card-head">
                <div class="help-card-icon help-card-icon--teal">
                    <i class="ph ph-list-bullets"></i>
                </div>
                <div class="help-card-titles">
                    <h2><?= $isEs ? 'Tipos de campo' : 'Field types' ?></h2>
                    <p class="help-card-summary">
                        <?= $isEs
                            ? 'Ocho tipos disponibles para cubrir cualquier clase de dato.'
                            : 'Eight available types to cover any kind of data.' ?>
                    </p>
                </div>
            </div>
            <div class="help-card-body">
                <?php if ($isEs): ?>
                <p class="help-p">
                    Al crear un campo debes elegir su tipo, que determina como se muestra en el
                    formulario y como se valida el valor introducido. El tipo no se puede cambiar
                    despues de crear el campo, por lo que conviene elegirlo bien desde el principio.
                </p>
                <ul class="help-steps">
                    <li class="help-step help-step--teal">
                        <strong>text</strong> — Linea de texto corto. Para nombres, referencias,
                        URLs cortas o cualquier cadena de texto libre de una sola linea.
                    </li>
                    <li class="help-step help-step--teal">
                        <strong>textarea</strong> — Texto largo multilínea. Para notas, descripciones
                        o comentarios extendidos.
                    </li>
                    <li class="help-step help-step--teal">
                        <strong>number</strong> — Valor numerico. Para presupuestos, cantidades,
                        puntuaciones o cualquier dato cuantitativo.
                    </li>
                    <li class="help-step help-step--teal">
                        <strong>date</strong> — Fecha con selector de calendario. Para fechas de
                        nacimiento, renovacion, firma de contrato, etc.
                    </li>
                    <li class="help-step help-step--teal">
                        <strong>email</strong> — Direccion de correo electronico con validacion de
                        formato. Util para un segundo email de contacto.
                    </li>
                    <li class="help-step help-step--teal">
                        <strong>url</strong> — Enlace web con validacion de formato. Para perfiles de
                        LinkedIn, fichas de empresa externa, etc.
                    </li>
                    <li class="help-step help-step--teal">
                        <strong>select</strong> — Lista desplegable de opciones cerradas que defines
                        tu. Para estados, categorias o cualquier valor acotado.
                    </li>
                    <li class="help-step help-step--teal">
                        <strong>checkbox</strong> — Casilla de si/no. Para flags booleanos como
                        "Newsletter", "RGPD firmado" o "Contacto verificado".
                    </li>
                </ul>

                <p class="help-sub-label"><?= $isEs ? 'El tipo select' : 'The select type' ?></p>
                <p class="help-p">
                    Al crear un campo de tipo <strong>select</strong> defines la lista de opciones
                    disponibles. Solo los valores de esa lista pueden seleccionarse al rellenar el
                    campo, lo que garantiza coherencia en los datos y facilita el filtrado posterior.
                    Puedes anadir, editar o reordenar las opciones editando el campo desde
                    <em>Settings → Custom Fields</em>.
                </p>
                <?php else: ?>
                <p class="help-p">
                    When creating a field you must choose its type, which determines how it appears
                    in the form and how the entered value is validated. The type cannot be changed
                    after the field is created, so it is worth choosing carefully from the start.
                </p>
                <ul class="help-steps">
                    <li class="help-step help-step--teal">
                        <strong>text</strong> — Short single-line text. For names, references, short
                        URLs, or any free-form single-line string.
                    </li>
                    <li class="help-step help-step--teal">
                        <strong>textarea</strong> — Long multi-line text. For notes, descriptions,
                        or extended comments.
                    </li>
                    <li class="help-step help-step--teal">
                        <strong>number</strong> — Numeric value. For budgets, quantities, scores,
                        or any quantitative data.
                    </li>
                    <li class="help-step help-step--teal">
                        <strong>date</strong> — Date with a calendar picker. For birth dates, renewal
                        dates, contract signing dates, etc.
                    </li>
                    <li class="help-step help-step--teal">
                        <strong>email</strong> — Email address with format validation. Useful for a
                        secondary contact email.
                    </li>
                    <li class="help-step help-step--teal">
                        <strong>url</strong> — Web link with format validation. For LinkedIn profiles,
                        external company pages, etc.
                    </li>
                    <li class="help-step help-step--teal">
                        <strong>select</strong> — Dropdown of closed options that you define. For
                        statuses, categories, or any bounded value set.
                    </li>
                    <li class="help-step help-step--teal">
                        <strong>checkbox</strong> — Yes/no toggle. For boolean flags like "Newsletter",
                        "GDPR signed", or "Verified contact".
                    </li>
                </ul>

                <p class="help-sub-label">The select type</p>
                <p class="help-p">
                    When creating a <strong>select</strong> field you define the list of available
                    options. Only values from that list can be chosen when filling in the field, which
                    ensures data consistency and makes filtering easier later. You can add, edit, or
                    reorder options by editing the field from <em>Settings → Custom Fields</em>.
                </p>
                <?php endif; ?>
            </div>
        </section>

        <!-- ══════════════════════════════════ FILTERABLE ══ -->
        <section class="help-card" id="filterable">
            <div class="help-card-head">
                <div class="help-card-icon help-card-icon--teal">
                    <i class="ph ph-funnel"></i>
                </div>
                <div class="help-card-titles">
                    <h2><?= $isEs ? 'Campo filtrable' : 'Filterable field' ?></h2>
                    <p class="help-card-summary">
                        <?= $isEs
                            ? 'Activa esta opcion para que el campo aparezca en el panel de filtros avanzados del listado.'
                            : 'Enable this option to make the field appear in the advanced filter panel of the list.' ?>
                    </p>
                </div>
            </div>
            <div class="help-card-body">
                <?php if ($isEs): ?>
                <p class="help-p">
                    Cada campo personalizado tiene una opcion <strong>Filterable</strong>. Cuando esta
                    activada, el campo aparece en el panel de filtros avanzados del listado de contactos
                    o clientes, segun la entidad a la que pertenezca. Esto te permite segmentar los
                    registros por ese dato directamente desde la interfaz, sin necesidad de exportar
                    primero.
                </p>
                <p class="help-p">
                    No todos los campos tienen que ser filtrables. Activa esta opcion solo en los campos
                    por los que realmente necesitas filtrar: mantener el panel de filtros limpio y sin
                    opciones innecesarias facilita el trabajo diario.
                </p>

                <div class="help-callout help-callout--teal">
                    <i class="ph ph-lightbulb"></i>
                    <p>
                        <?= $isEs
                            ? 'Ejemplo: un campo <em>Siguiente accion</em> de tipo <strong>select</strong> con opciones "Llamar", "Enviar propuesta", "Esperar respuesta", marcado como Filterable, permite filtrar todos los contactos que estan en la fase "Enviar propuesta" y exportarlos para el equipo comercial.'
                            : 'Example: a <em>Next action</em> field of type <strong>select</strong> with options "Call", "Send proposal", "Await response", marked as Filterable, lets you filter all contacts in the "Send proposal" stage and export them for the sales team.' ?>
                    </p>
                </div>
                <?php else: ?>
                <p class="help-p">
                    Each custom field has a <strong>Filterable</strong> option. When enabled, the field
                    appears in the advanced filter panel of the contacts or clients list, depending on
                    which entity it belongs to. This lets you segment records by that data directly from
                    the interface, without needing to export first.
                </p>
                <p class="help-p">
                    Not every field needs to be filterable. Only enable this option for fields you
                    genuinely need to filter by: keeping the filter panel clean and free of unnecessary
                    options makes day-to-day work easier.
                </p>

                <div class="help-callout help-callout--teal">
                    <i class="ph ph-lightbulb"></i>
                    <p>
                        Example: a <em>Next action</em> field of type <strong>select</strong> with
                        options "Call", "Send proposal", "Await response", marked as Filterable, lets
                        you filter all contacts in the "Send proposal" stage and export them for the
                        sales team.
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- ══════════════════════════════════ IMPORT ══ -->
        <section class="help-card" id="import">
            <div class="help-card-head">
                <div class="help-card-icon help-card-icon--teal">
                    <i class="ph ph-upload-simple"></i>
                </div>
                <div class="help-card-titles">
                    <h2><?= $isEs ? 'Campos personalizados en importaciones' : 'Custom fields during imports' ?></h2>
                    <p class="help-card-summary">
                        <?= $isEs
                            ? 'Las columnas del archivo CSV pueden mapearse a campos existentes o crear campos nuevos al vuelo.'
                            : 'CSV columns can be mapped to existing fields or create new fields on the fly.' ?>
                    </p>
                </div>
            </div>
            <div class="help-card-body">
                <?php if ($isEs): ?>
                <p class="help-p">
                    Durante el proceso de importacion, en el paso de <strong>mapeo de columnas</strong>,
                    cada columna del archivo CSV o XLSX puede asignarse a un campo del sistema — incluidos
                    los campos personalizados existentes. Si el archivo tiene una columna que no corresponde
                    a ningun campo base ni personalizado, tienes dos opciones:
                </p>
                <ul class="help-steps">
                    <li class="help-step help-step--teal">
                        <strong><?= $isEs ? 'Descartar la columna:' : 'Discard the column:' ?></strong>
                        <?= $isEs
                            ? 'El dato de esa columna se ignora y no se importa.'
                            : 'The data in that column is ignored and not imported.' ?>
                    </li>
                    <li class="help-step help-step--teal">
                        <strong><?= $isEs ? 'Crear campo personalizado:' : 'Create custom field:' ?></strong>
                        <?= $isEs
                            ? 'El sistema crea automaticamente un campo de tipo <strong>text</strong> con el nombre de la columna como etiqueta y lo popula con los datos del archivo.'
                            : 'The system automatically creates a <strong>text</strong> type field using the column name as the label and populates it with the file\'s data.' ?>
                    </li>
                </ul>
                <p class="help-p">
                    Los campos creados durante una importacion aparecen despues en <em>Settings →
                    Custom Fields</em> como cualquier otro campo. Puedes editarlos, cambiarles el
                    nombre o activar la opcion Filterable una vez terminada la importacion.
                </p>

                <div class="help-callout help-callout--teal">
                    <i class="ph ph-info"></i>
                    <p>
                        <?= $isEs
                            ? 'Si el archivo tiene una columna llamada <em>linkedin</em> y ya existe un campo personalizado con ese slug, el mapeador lo detecta automaticamente y lo preselecciona. Revisa siempre el mapeo antes de confirmar la importacion.'
                            : 'If the file has a column named <em>linkedin</em> and a custom field with that slug already exists, the mapper detects it automatically and pre-selects it. Always review the mapping before confirming the import.' ?>
                    </p>
                </div>
                <?php else: ?>
                <p class="help-p">
                    During the import process, in the <strong>column mapping</strong> step, each column
                    in the CSV or XLSX file can be assigned to a system field — including existing custom
                    fields. If the file has a column that doesn't match any base or custom field, you
                    have two options:
                </p>
                <ul class="help-steps">
                    <li class="help-step help-step--teal">
                        <strong>Discard the column:</strong> the data in that column is ignored and
                        not imported.
                    </li>
                    <li class="help-step help-step--teal">
                        <strong>Create custom field:</strong> the system automatically creates a
                        <strong>text</strong> type field using the column name as the label and
                        populates it with the file's data.
                    </li>
                </ul>
                <p class="help-p">
                    Fields created during an import appear afterwards in <em>Settings → Custom Fields</em>
                    like any other field. You can edit them, rename them, or enable the Filterable option
                    once the import is complete.
                </p>

                <div class="help-callout help-callout--teal">
                    <i class="ph ph-info"></i>
                    <p>
                        If the file has a column named <em>linkedin</em> and a custom field with that
                        slug already exists, the mapper detects it automatically and pre-selects it.
                        Always review the mapping before confirming the import.
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- ══════════════════════════════════ MANAGE ══ -->
        <section class="help-card" id="manage">
            <div class="help-card-head">
                <div class="help-card-icon help-card-icon--teal">
                    <i class="ph ph-gear"></i>
                </div>
                <div class="help-card-titles">
                    <h2><?= $isEs ? 'Gestion de campos' : 'Managing fields' ?></h2>
                    <p class="help-card-summary">
                        <?= $isEs
                            ? 'Crear, editar y eliminar desde Settings → Custom Fields. Solo administradores.'
                            : 'Create, edit, and delete from Settings → Custom Fields. Administrators only.' ?>
                    </p>
                </div>
            </div>
            <div class="help-card-body">
                <?php if ($isEs): ?>
                <p class="help-p">
                    Los campos personalizados se gestionan desde <em>Settings → Custom Fields</em>,
                    una seccion visible solo para administradores. Desde ahi puedes crear nuevos campos,
                    editar los existentes (nombre, etiqueta y opciones en campos select) y eliminarlos.
                </p>
                <p class="help-p">
                    Al <strong>eliminar un campo personalizado</strong>, se borra de forma permanente
                    junto con todos los valores que los contactos o clientes tenian guardados en el.
                    Esta accion es irreversible: los datos de ese campo desaparecen de todas las fichas.
                    El sistema pide confirmacion antes de proceder.
                </p>
                <p class="help-p">
                    El <strong>slug</strong> de un campo es el identificador interno que se usa en las
                    importaciones via CSV y en la API. Se genera automaticamente a partir del nombre al
                    crear el campo y no cambia si editas la etiqueta. Ten en cuenta el slug si conectas
                    formularios externos o automatizaciones que envien datos a traves de la API.
                </p>

                <div class="help-callout help-callout--teal">
                    <i class="ph ph-warning"></i>
                    <p>
                        <?= $isEs
                            ? '<strong>Eliminar un campo borra todos sus datos.</strong> Si solo quieres ocultarlo temporalmente del formulario, considera dejar el campo vacio en las fichas en lugar de eliminar el campo del sistema.'
                            : '<strong>Deleting a field deletes all its data.</strong> If you only want to temporarily hide it from the form, consider leaving the field empty on records rather than removing the field from the system.' ?>
                    </p>
                </div>
                <?php else: ?>
                <p class="help-p">
                    Custom fields are managed from <em>Settings → Custom Fields</em>, a section visible
                    only to administrators. From there you can create new fields, edit existing ones
                    (name, label, and options for select fields), and delete them.
                </p>
                <p class="help-p">
                    When you <strong>delete a custom field</strong>, it is permanently removed along
                    with all values that contacts or clients had saved in it. This action is irreversible:
                    the data for that field disappears from all records. The system asks for confirmation
                    before proceeding.
                </p>
                <p class="help-p">
                    The field <strong>slug</strong> is the internal identifier used in CSV imports and
                    the API. It is auto-generated from the name when the field is created and does not
                    change if you edit the label. Keep the slug in mind if you connect external forms
                    or automations that send data through the API.
                </p>

                <div class="help-callout help-callout--teal">
                    <i class="ph ph-warning"></i>
                    <p>
                        <strong>Deleting a field deletes all its data.</strong> If you only want to
                        temporarily hide it from the form, consider leaving the field empty on records
                        rather than removing the field from the system.
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </section>

    </div><!-- /.help-content -->
</div><!-- /.help-layout -->