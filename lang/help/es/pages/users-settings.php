<?php

return array (
  'id' => 'users-settings',
  'title' => 'Usuarios y configuración',
  'description' => 'Cuentas de usuario, preferencias de la interfaz y administración del sistema.',
  'icon' => 'ph-users-three',
  'sections' =>
  array (
    0 =>
    array (
      'id' => 'user-accounts',
      'title' => 'Cuentas y roles',
      'paragraphs' =>
      array (
        0 => 'La cuenta determina con qué nombre y con qué funciones trabaja una persona en ContactCore. Para cada usuario se indican el nombre, un correo electrónico único, la contraseña, el rol y el estado de actividad. La lista también muestra la fecha del último acceso, lo que ayuda a comprobar si la cuenta se utiliza.',
        1 => 'El sistema dispone de dos roles: administrador y usuario. El administrador tiene acceso completo y gestiona las cuentas y las secciones administrativas. Para el rol «Usuario», el acceso se configura de forma individual mediante permisos. Solo los administradores pueden crear y editar usuarios.',
      ),
    ),
    1 =>
    array (
      'id' => 'user-permissions',
      'title' => 'Permisos de usuario',
      'paragraphs' =>
      array (
        0 => 'Los permisos solo se aplican a los usuarios normales. Los administradores siempre tienen acceso completo, independientemente del estado de las casillas. Al crear un usuario normal, todos los permisos aparecen seleccionados inicialmente; después, el administrador puede dejar únicamente las acciones que necesite ese empleado.',
        1 => 'Para contactos y clientes se configuran por separado los permisos de creación, edición y eliminación. Esto permite, por ejemplo, conservar para un usuario la consulta de la base de datos y la creación de registros nuevos, pero impedirle modificar o eliminar definitivamente los existentes. El permiso de edición también es necesario para las acciones masivas que cambian las relaciones o etiquetas de los registros.',
        2 => 'Otros permisos independientes controlan la exportación de datos, la importación, los sectores, las etiquetas y los campos personalizados. Si un permiso específico está desactivado, la sección o acción correspondiente se oculta en la interfaz y cualquier intento de abrir directamente una dirección protegida termina con una denegación de acceso.',
        3 => 'La gestión de usuarios, las integraciones mediante API y las herramientas de IA son funciones administrativas y no se conceden a los usuarios normales mediante una casilla independiente. Antes de otorgar permisos de exportación, importación o eliminación, hay que tener en cuenta que estas operaciones permiten obtener grandes volúmenes de datos o realizar cambios masivos.',
      ),
    ),
    2 =>
    array (
      'id' => 'account-status',
      'title' => 'Actividad y eliminación de cuentas',
      'paragraphs' =>
      array (
        0 => 'Una cuenta activa puede utilizarse para iniciar sesión. Si un empleado deja de trabajar con ContactCore de forma temporal o definitiva, es preferible desactivar primero su cuenta: ya no podrá autenticarse en el siguiente intento de acceso, pero se conservarán el registro del usuario y el historial relacionado.',
        1 => 'Un administrador no puede desactivar ni eliminar su propia cuenta actual. La eliminación definitiva solo está disponible para usuarios que ya estén inactivos y se utiliza cuando ya no es necesario conservar la cuenta. Esta acción debe realizarse con precaución; en la mayoría de las situaciones de trabajo basta con desactivar la cuenta.',
        2 => 'Al editar un usuario se pueden modificar el nombre, el correo electrónico, el rol, los permisos y el estado. La contraseña nueva solo se establece si se rellena el campo correspondiente; si se deja vacío, la contraseña actual no cambia.',
      ),
    ),
    3 =>
    array (
      'id' => 'personal-settings',
      'title' => 'Configuración personal',
      'paragraphs' =>
      array (
        0 => 'Todos los usuarios que han iniciado sesión pueden acceder a la sección «Configuración». Allí se puede elegir el número de filas que se muestran en cada página de las tablas: 20, 50, 100 o 200. Esta preferencia se aplica a las listas de todo el sistema y se guarda por separado para la cuenta actual.',
        1 => 'En la misma página se elige el idioma de la interfaz: español, inglés o ruso. El cambio de idioma afecta a los nombres de las secciones, los botones y los mensajes del sistema, pero no traduce los datos que los propios usuarios han introducido en las fichas.',
        2 => 'Los administradores también disponen de una comprobación de correos por lotes. Procesa en grupos pequeños los contactos cuyo tipo de correo todavía no se ha determinado, clasifica las direcciones y comprueba los registros MX de sus dominios. Esta función se explica con detalle en la sección «Contactos».',
      ),
    ),
    4 =>
    array (
      'id' => 'weekly-report',
      'title' => 'Informe semanal para administradores',
      'paragraphs' =>
      array (
        0 => 'El informe semanal está destinado a los administradores activos. Si se ha configurado en el servidor la ejecución semanal del script correspondiente, el informe se envía automáticamente al correo de cada administrador activo e incluye los datos de los últimos siete días. Las direcciones de los destinatarios se obtienen de sus cuentas.',
        1 => 'El informe incluye el número de contactos y clientes nuevos, los diez clientes con más contactos nuevos, los clientes conectados a la plataforma y los clientes desactivados. El mensaje también contiene enlaces a las selecciones correspondientes en ContactCore.',
        2 => 'Un administrador puede enviar el informe manualmente desde la página «Configuración». En ese caso, el resumen abarca desde el comienzo de la semana actual hasta el momento del envío y solo llega al correo del administrador actual. El envío manual resulta útil para comprobar la configuración del correo o recibir un resumen actualizado antes de la ejecución programada.',
        3 => 'El envío automático requiere configurar el programador de tareas en el servidor, y ambas modalidades del informe necesitan una configuración funcional del correo saliente. Si el mensaje no puede enviarse, el sistema muestra un error y los detalles técnicos se registran en el log de la aplicación.',
      ),
    ),
  ),
);
