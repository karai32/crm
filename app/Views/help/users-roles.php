<?php
$isEs    = $locale === 'es';
$backUrl = htmlspecialchars(Auth::url('/help?lang=' . $locale), ENT_QUOTES, 'UTF-8');

$toc = $isEs ? [
    ['id' => 'roles',       'label' => 'Roles del sistema'],
    ['id' => 'permissions', 'label' => 'Permisos granulares'],
    ['id' => 'manage',      'label' => 'Gestionar usuarios'],
    ['id' => 'deactivate',  'label' => 'Desactivar usuarios'],
    ['id' => 'api-creds',   'label' => 'Credenciales de API'],
] : [
    ['id' => 'roles',       'label' => 'System roles'],
    ['id' => 'permissions', 'label' => 'Granular permissions'],
    ['id' => 'manage',      'label' => 'Managing users'],
    ['id' => 'deactivate',  'label' => 'Deactivating users'],
    ['id' => 'api-creds',   'label' => 'API credentials'],
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
               href="<?= htmlspecialchars(Auth::url('/help/users-roles?lang=' . $code), ENT_QUOTES, 'UTF-8') ?>">
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
               class="help-toc-item help-toc-item--slate"
               data-section="<?= $item['id'] ?>">
                <span class="help-toc-dot"></span>
                <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
            </a>
            <?php endforeach; ?>
        </nav>
    </aside>

    <div class="help-content">

        <!-- ══════════════════════════════════ ROLES ══ -->
        <section class="help-card" id="roles">
            <div class="help-card-head">
                <div class="help-card-icon help-card-icon--slate">
                    <i class="ph ph-users"></i>
                </div>
                <div class="help-card-titles">
                    <h2><?= $isEs ? 'Roles del sistema' : 'System roles' ?></h2>
                    <p class="help-card-summary">
                        <?= $isEs
                            ? 'Dos roles: Administrator con acceso total y User con permisos configurables.'
                            : 'Two roles: Administrator with full access and User with configurable permissions.' ?>
                    </p>
                </div>
            </div>
            <div class="help-card-body">
                <?php if ($isEs): ?>
                <p class="help-p">
                    El sistema tiene dos roles de usuario. El rol se asigna al crear la cuenta y
                    puede cambiarse despues desde la pantalla de edicion del usuario.
                </p>
                <ul class="help-steps">
                    <li class="help-step help-step--slate">
                        <div>
                            <strong>Administrator</strong><br>
                            Acceso completo a todas las funciones del sistema: contactos, clientes,
                            sectores, tags, campos personalizados, importaciones, exportaciones,
                            gestion de usuarios y credenciales de API. No puede ser restringido
                            con permisos granulares: siempre tiene acceso total.
                        </div>
                    </li>
                    <li class="help-step help-step--slate">
                        <div>
                            <strong>User</strong><br>
                            Acceso a los modulos principales (contactos y clientes) con los permisos
                            que el administrador le haya otorgado. Las secciones de configuracion
                            (usuarios, campos personalizados, credenciales de API) no son visibles
                            para este rol. Las secciones de importacion y exportacion solo son
                            accesibles si el administrador activa explicitamente esos permisos.
                        </div>
                    </li>
                </ul>
                <div class="help-callout help-callout--slate">
                    <i class="ph ph-info"></i>
                    <p>
                        Debe existir siempre al menos un usuario con rol Administrator en el sistema.
                        No es posible rebajar a User el unico administrador activo.
                    </p>
                </div>
                <?php else: ?>
                <p class="help-p">
                    The system has two user roles. The role is assigned when the account is created
                    and can be changed later from the user edit screen.
                </p>
                <ul class="help-steps">
                    <li class="help-step help-step--slate">
                        <div>
                            <strong>Administrator</strong><br>
                            Full access to all system features: contacts, clients, sectors, tags,
                            custom fields, imports, exports, user management, and API credentials.
                            Cannot be restricted with granular permissions — always has full access.
                        </div>
                    </li>
                    <li class="help-step help-step--slate">
                        <div>
                            <strong>User</strong><br>
                            Access to the main modules (contacts and clients) with the permissions
                            an administrator has granted. Settings sections (users, custom fields,
                            API credentials) are not visible to this role. Import and export sections
                            are only accessible if an administrator explicitly enables those permissions.
                        </div>
                    </li>
                </ul>
                <div class="help-callout help-callout--slate">
                    <i class="ph ph-info"></i>
                    <p>
                        There must always be at least one Administrator in the system. It is not
                        possible to downgrade the only active administrator to a User role.
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- ══════════════════════════════════ PERMISSIONS ══ -->
        <section class="help-card" id="permissions">
            <div class="help-card-head">
                <div class="help-card-icon help-card-icon--slate">
                    <i class="ph ph-lock-key"></i>
                </div>
                <div class="help-card-titles">
                    <h2><?= $isEs ? 'Permisos granulares' : 'Granular permissions' ?></h2>
                    <p class="help-card-summary">
                        <?= $isEs
                            ? 'Controla exactamente que puede hacer cada usuario con rol User.'
                            : 'Control exactly what each User-role account can do.' ?>
                    </p>
                </div>
            </div>
            <div class="help-card-body">
                <?php if ($isEs): ?>
                <p class="help-p">
                    Para los usuarios con rol <strong>User</strong>, el administrador puede activar
                    o desactivar cada permiso de forma independiente. Esto permite crear perfiles
                    muy especificos: un comercial que solo puede ver y editar contactos, un
                    supervisor que puede exportar pero no eliminar, o un externo que solo consulta.
                </p>

                <p class="help-sub-label"><?= $isEs ? 'Permisos disponibles' : 'Available permissions' ?></p>
                <ul class="help-steps">
                    <li class="help-step help-step--slate">
                        <div><strong><?= $isEs ? 'Crear contactos' : 'Create contacts' ?></strong> — <?= $isEs ? 'puede usar el formulario de nuevo contacto.' : 'can use the new contact form.' ?></div>
                    </li>
                    <li class="help-step help-step--slate">
                        <div><strong><?= $isEs ? 'Editar contactos' : 'Edit contacts' ?></strong> — <?= $isEs ? 'puede modificar la ficha de un contacto existente.' : 'can modify an existing contact\'s record.' ?></div>
                    </li>
                    <li class="help-step help-step--slate">
                        <div><strong><?= $isEs ? 'Eliminar contactos' : 'Delete contacts' ?></strong> — <?= $isEs ? 'puede borrar contactos individualmente o en bloque.' : 'can delete contacts individually or in bulk.' ?></div>
                    </li>
                    <li class="help-step help-step--slate">
                        <div><strong><?= $isEs ? 'Crear clientes' : 'Create clients' ?></strong> — <?= $isEs ? 'puede crear nuevos clientes.' : 'can create new clients.' ?></div>
                    </li>
                    <li class="help-step help-step--slate">
                        <div><strong><?= $isEs ? 'Editar clientes' : 'Edit clients' ?></strong> — <?= $isEs ? 'puede modificar la ficha de un cliente.' : 'can modify a client\'s record.' ?></div>
                    </li>
                    <li class="help-step help-step--slate">
                        <div><strong><?= $isEs ? 'Eliminar clientes' : 'Delete clients' ?></strong> — <?= $isEs ? 'puede borrar clientes.' : 'can delete clients.' ?></div>
                    </li>
                    <li class="help-step help-step--slate">
                        <div><strong><?= $isEs ? 'Exportar datos' : 'Export data' ?></strong> — <?= $isEs ? 'tiene acceso a la seccion Exports.' : 'has access to the Exports section.' ?></div>
                    </li>
                    <li class="help-step help-step--slate">
                        <div><strong><?= $isEs ? 'Importar datos' : 'Import data' ?></strong> — <?= $isEs ? 'tiene acceso a la seccion Imports.' : 'has access to the Imports section.' ?></div>
                    </li>
                </ul>
                <div class="help-callout help-callout--slate">
                    <i class="ph ph-lightbulb"></i>
                    <p>
                        <?= $isEs
                            ? 'Un usuario sin ningun permiso activado puede acceder y ver el listado de contactos y clientes, pero no puede crear, editar, eliminar, importar ni exportar.'
                            : 'A user with no permissions enabled can access and view the contacts and clients lists, but cannot create, edit, delete, import, or export.' ?>
                    </p>
                </div>
                <?php else: ?>
                <p class="help-p">
                    For users with the <strong>User</strong> role, the administrator can enable or
                    disable each permission independently. This allows creating very specific
                    profiles: a sales rep who can only view and edit contacts, a supervisor who can
                    export but not delete, or an external viewer who only browses.
                </p>

                <p class="help-sub-label">Available permissions</p>
                <ul class="help-steps">
                    <li class="help-step help-step--slate">
                        <div><strong>Create contacts</strong> — can use the new contact form.</div>
                    </li>
                    <li class="help-step help-step--slate">
                        <div><strong>Edit contacts</strong> — can modify an existing contact's record.</div>
                    </li>
                    <li class="help-step help-step--slate">
                        <div><strong>Delete contacts</strong> — can delete contacts individually or in bulk.</div>
                    </li>
                    <li class="help-step help-step--slate">
                        <div><strong>Create clients</strong> — can create new clients.</div>
                    </li>
                    <li class="help-step help-step--slate">
                        <div><strong>Edit clients</strong> — can modify a client's record.</div>
                    </li>
                    <li class="help-step help-step--slate">
                        <div><strong>Delete clients</strong> — can delete clients.</div>
                    </li>
                    <li class="help-step help-step--slate">
                        <div><strong>Export data</strong> — has access to the Exports section.</div>
                    </li>
                    <li class="help-step help-step--slate">
                        <div><strong>Import data</strong> — has access to the Imports section.</div>
                    </li>
                </ul>
                <div class="help-callout help-callout--slate">
                    <i class="ph ph-lightbulb"></i>
                    <p>
                        A user with no permissions enabled can access and view the contacts and
                        clients lists, but cannot create, edit, delete, import, or export.
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- ══════════════════════════════════ MANAGE ══ -->
        <section class="help-card" id="manage">
            <div class="help-card-head">
                <div class="help-card-icon help-card-icon--slate">
                    <i class="ph ph-user-gear"></i>
                </div>
                <div class="help-card-titles">
                    <h2><?= $isEs ? 'Gestionar usuarios' : 'Managing users' ?></h2>
                    <p class="help-card-summary">
                        <?= $isEs
                            ? 'Crear, editar y configurar cuentas desde Settings → Users.'
                            : 'Create, edit, and configure accounts from Settings → Users.' ?>
                    </p>
                </div>
            </div>
            <div class="help-card-body">
                <?php if ($isEs): ?>
                <p class="help-p">
                    La seccion <strong>Settings → Users</strong> lista todos los usuarios del
                    sistema con su rol, estado (activo / inactivo) y fecha de creacion. Solo los
                    administradores pueden acceder a esta seccion.
                </p>
                <ol class="help-steps">
                    <li class="help-step help-step--slate">
                        <div>
                            <strong><?= $isEs ? 'Crear un usuario' : 'Create a user' ?></strong><br>
                            <?= $isEs
                                ? 'Haz clic en <em>New user</em>, rellena nombre, email y contrasena, elige el rol y activa los permisos que correspondan. El usuario recibe sus credenciales y puede iniciar sesion inmediatamente.'
                                : 'Click <em>New user</em>, fill in name, email, and password, choose the role, and enable the appropriate permissions. The user receives their credentials and can log in immediately.' ?>
                        </div>
                    </li>
                    <li class="help-step help-step--slate">
                        <div>
                            <strong><?= $isEs ? 'Editar un usuario' : 'Edit a user' ?></strong><br>
                            <?= $isEs
                                ? 'Haz clic en el nombre del usuario para abrir su ficha. Puedes cambiar el nombre, email, rol, permisos y la contrasena. La contrasena solo se actualiza si rellenas el campo; dejarlo vacio mantiene la contrasena actual.'
                                : 'Click the user\'s name to open their record. You can change name, email, role, permissions, and password. The password only updates if you fill in the field; leaving it blank keeps the current password.' ?>
                        </div>
                    </li>
                    <li class="help-step help-step--slate">
                        <div>
                            <strong><?= $isEs ? 'Cambiar la contrasena propia' : 'Change your own password' ?></strong><br>
                            <?= $isEs
                                ? 'Cada usuario puede cambiar su propia contrasena desde el menu de perfil (avatar superior derecho) → <em>Mi cuenta</em>, sin necesidad de pasar por Settings → Users.'
                                : 'Every user can change their own password from the profile menu (top-right avatar) → <em>My account</em>, without going through Settings → Users.' ?>
                        </div>
                    </li>
                </ol>
                <?php else: ?>
                <p class="help-p">
                    The <strong>Settings → Users</strong> section lists all system users with their
                    role, status (active / inactive), and creation date. Only administrators can
                    access this section.
                </p>
                <ol class="help-steps">
                    <li class="help-step help-step--slate">
                        <div>
                            <strong>Create a user</strong><br>
                            Click <em>New user</em>, fill in name, email, and password, choose the
                            role, and enable the appropriate permissions. The user receives their
                            credentials and can log in immediately.
                        </div>
                    </li>
                    <li class="help-step help-step--slate">
                        <div>
                            <strong>Edit a user</strong><br>
                            Click the user's name to open their record. You can change name, email,
                            role, permissions, and password. The password only updates if you fill
                            in the field; leaving it blank keeps the current password.
                        </div>
                    </li>
                    <li class="help-step help-step--slate">
                        <div>
                            <strong>Change your own password</strong><br>
                            Every user can change their own password from the profile menu
                            (top-right avatar) → <em>My account</em>, without going through
                            Settings → Users.
                        </div>
                    </li>
                </ol>
                <?php endif; ?>
            </div>
        </section>

        <!-- ══════════════════════════════════ DEACTIVATE ══ -->
        <section class="help-card" id="deactivate">
            <div class="help-card-head">
                <div class="help-card-icon help-card-icon--slate">
                    <i class="ph ph-user-minus"></i>
                </div>
                <div class="help-card-titles">
                    <h2><?= $isEs ? 'Desactivar usuarios' : 'Deactivating users' ?></h2>
                    <p class="help-card-summary">
                        <?= $isEs
                            ? 'Revoca el acceso sin borrar la cuenta ni el historial de actividad.'
                            : 'Revoke access without deleting the account or activity history.' ?>
                    </p>
                </div>
            </div>
            <div class="help-card-body">
                <?php if ($isEs): ?>
                <p class="help-p">
                    En lugar de eliminar una cuenta de usuario, el sistema permite
                    <strong>desactivarla</strong>. Un usuario inactivo no puede iniciar sesion,
                    pero su nombre sigue apareciendo en los registros que creo o modifico,
                    preservando el historial de actividad del equipo.
                </p>
                <p class="help-p">
                    Para desactivar un usuario, abre su ficha desde <em>Settings → Users</em> y
                    cambia el estado a <strong>Inactivo</strong>. El cambio es inmediato: si el
                    usuario tenia una sesion abierta en ese momento, sera expulsado en la siguiente
                    peticion al servidor.
                </p>
                <p class="help-p">
                    Reactivar un usuario es igual de sencillo: cambia el estado de vuelta a
                    <strong>Activo</strong> y podra volver a iniciar sesion con sus mismas
                    credenciales y permisos.
                </p>
                <div class="help-callout help-callout--slate">
                    <i class="ph ph-warning"></i>
                    <p>
                        No es posible eliminar permanentemente un usuario que haya creado o
                        modificado registros en el sistema. Usa la desactivacion en su lugar.
                    </p>
                </div>
                <?php else: ?>
                <p class="help-p">
                    Rather than deleting a user account, the system lets you <strong>deactivate</strong>
                    it. An inactive user cannot log in, but their name still appears on records they
                    created or modified, preserving the team's activity history.
                </p>
                <p class="help-p">
                    To deactivate a user, open their record from <em>Settings → Users</em> and
                    change the status to <strong>Inactive</strong>. The change is immediate: if the
                    user had an active session at that moment, they will be logged out on their
                    next request to the server.
                </p>
                <p class="help-p">
                    Reactivating a user is equally simple: change the status back to
                    <strong>Active</strong> and they can log in again with the same credentials
                    and permissions.
                </p>
                <div class="help-callout help-callout--slate">
                    <i class="ph ph-warning"></i>
                    <p>
                        It is not possible to permanently delete a user who has created or modified
                        records in the system. Use deactivation instead.
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- ══════════════════════════════════ API CREDS ══ -->
        <section class="help-card" id="api-creds">
            <div class="help-card-head">
                <div class="help-card-icon help-card-icon--slate">
                    <i class="ph ph-key"></i>
                </div>
                <div class="help-card-titles">
                    <h2><?= $isEs ? 'Credenciales de API' : 'API credentials' ?></h2>
                    <p class="help-card-summary">
                        <?= $isEs
                            ? 'Genera claves de API con scopes especificos para integraciones externas.'
                            : 'Generate API keys with specific scopes for external integrations.' ?>
                    </p>
                </div>
            </div>
            <div class="help-card-body">
                <?php if ($isEs): ?>
                <p class="help-p">
                    Las credenciales de API se gestionan desde <strong>Settings → API Credentials</strong>.
                    Cada credencial tiene un <strong>nombre descriptivo</strong>, una
                    <strong>clave secreta</strong> generada automaticamente y un conjunto de
                    <strong>scopes</strong> que determinan a que recursos puede acceder.
                </p>
                <p class="help-p">
                    Los scopes disponibles siguen el patron <code>entidad:accion</code>, por ejemplo
                    <code>contacts:read</code>, <code>contacts:write</code>, <code>clients:read</code>,
                    <code>clients:write</code>, <code>tags:read</code>, <code>sectors:read</code>.
                    Asigna solo los scopes que la integracion necesita para seguir el principio de
                    minimo privilegio.
                </p>
                <p class="help-p">
                    La clave solo se muestra <strong>una vez</strong> en el momento de la creacion.
                    Si se pierde, hay que revocar la credencial y generar una nueva. Las credenciales
                    revocadas quedan registradas en el historial pero no pueden reactivarse.
                </p>
                <div class="help-callout help-callout--slate">
                    <i class="ph ph-info"></i>
                    <p>
                        Para mas detalles sobre como usar la API, consulta la seccion
                        <a href="<?= htmlspecialchars(Auth::url('/help/api?lang=' . $locale), ENT_QUOTES, 'UTF-8') ?>">API Reference</a>
                        de este centro de ayuda.
                    </p>
                </div>
                <?php else: ?>
                <p class="help-p">
                    API credentials are managed from <strong>Settings → API Credentials</strong>.
                    Each credential has a <strong>descriptive name</strong>, an automatically
                    generated <strong>secret key</strong>, and a set of <strong>scopes</strong>
                    that determine which resources it can access.
                </p>
                <p class="help-p">
                    Available scopes follow the <code>entity:action</code> pattern, for example
                    <code>contacts:read</code>, <code>contacts:write</code>, <code>clients:read</code>,
                    <code>clients:write</code>, <code>tags:read</code>, <code>sectors:read</code>.
                    Assign only the scopes the integration needs to follow the principle of least
                    privilege.
                </p>
                <p class="help-p">
                    The key is only shown <strong>once</strong> at the moment of creation. If it is
                    lost, you must revoke the credential and generate a new one. Revoked credentials
                    remain in the history but cannot be reactivated.
                </p>
                <div class="help-callout help-callout--slate">
                    <i class="ph ph-info"></i>
                    <p>
                        For full details on using the API, see the
                        <a href="<?= htmlspecialchars(Auth::url('/help/api?lang=' . $locale), ENT_QUOTES, 'UTF-8') ?>">API Reference</a>
                        section of this help centre.
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </section>

    </div>
</div>