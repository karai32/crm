<?php

return array (
  'id' => 'clients',
  'title' => 'Clientes',
  'description' => 'Gestión de organizaciones, sus datos y la información relacionada.',
  'icon' => 'ph-buildings',
  'sections' => 
  array (
    0 => 
    array (
      'id' => 'client-meaning',
      'title' => 'Qué considera el sistema un cliente',
      'paragraphs' => 
      array (
        0 => 'En ContactCore, los clientes son las empresas y organizaciones que contratan los servicios de nuestra organización. Les prestamos servicios, gestionamos sus sitios web o colaboramos con ellas de alguna otra forma. Por tanto, la sección «Clientes» no describe a las personas que han enviado una solicitud, sino a las empresas para las que se recopilan esas solicitudes.',
        1 => 'Las personas que envían formularios desde los sitios web de nuestros clientes se guardan en otra sección: «Contactos». Cada contacto representa a un cliente potencial o actual de la empresa a la que prestamos servicio. Cuando llega una solicitud, el contacto se relaciona con la empresa cliente correspondiente, de modo que siempre se puede saber desde qué proyecto o sitio web procede.',
        2 => 'Es importante mantener esta distinción al introducir datos manualmente, importarlos y configurar integraciones: una organización se crea como cliente y la persona que envía una solicitud se crea como contacto relacionado con ese cliente. Si se mezclan estas entidades, los informes, los filtros y las listas de contactos relacionados dejan de reflejar la estructura real de la base de datos.',
      ),
    ),
    1 => 
    array (
      'id' => 'default-fields',
      'title' => 'Campos de la ficha del cliente',
      'paragraphs' => 
      array (
        0 => 'El único campo obligatorio de la ficha estándar es el nombre comercial. Es el nombre habitual de la empresa, por el que resulta más cómodo buscarla en el CRM. La razón social se indica por separado y puede ser diferente del nombre comercial. El campo «NIF / CIF» está destinado al número fiscal o de registro de la organización.',
        1 => 'El sector indica la principal actividad del cliente. Las etiquetas permiten una clasificación más libre: pueden señalar el tipo de servicio, un estado interno, un proyecto o cualquier otra característica. A diferencia del sector, una etiqueta no tiene por qué describir la actividad y una empresa puede tener varias etiquetas.',
        2 => 'La parte de dirección de la ficha incluye la dirección, el código postal, la ciudad, la provincia o región y el país. El campo del sitio web debe contener la dirección del sitio principal del cliente. Las notas permiten guardar información de trabajo importante para la que no existe un campo estructurado específico. Si los campos estándar no son suficientes, la ficha puede ampliarse con campos personalizados creados expresamente para los clientes.',
        3 => 'Antes de crear una ficha conviene comprobar si la empresa ya existe en la base de datos. El nombre comercial es obligatorio, pero el sistema no impide crear varios clientes con el mismo nombre, por lo que los duplicados deben controlarse mediante el procedimiento de trabajo.',
      ),
    ),
    2 => 
    array (
      'id' => 'active-status',
      'title' => 'Qué significa «Cliente activo»',
      'paragraphs' => 
      array (
        0 => 'Un cliente activo es una empresa con la que nuestra organización colabora en este momento. Esta opción está activada de forma predeterminada al crear un cliente. Mientras la colaboración siga vigente y el cliente reciba nuestros servicios, debe mantenerse como activo.',
        1 => 'Si la colaboración finaliza o se interrumpe temporalmente, la opción puede desactivarse. La ficha no se elimina: se conservan la información de la empresa y las relaciones con los contactos recibidos anteriormente. Así es posible separar los clientes actuales de los antiguos sin perder el historial ni los datos acumulados.',
        2 => 'El estado se utiliza en la lista y en los filtros de clientes. Cuando cambia, el sistema también registra la fecha de modificación. El estado de actividad no debe utilizarse para valorar la calidad del cliente ni de una solicitud concreta: describe exclusivamente la situación actual de la colaboración entre nuestra organización y la empresa.',
      ),
    ),
    3 =>
    array (
      'id' => 'api-status',
      'title' => 'Qué significa la conexión con Web / API',
      'paragraphs' =>
      array (
        0 => 'La opción «Conectado a Web / API» indica que el sitio web del cliente está integrado con ContactCore. Una vez realizada la conexión, las solicitudes enviadas mediante los formularios del sitio se transmiten a la plataforma a través de la API y se crean aquí como contactos. Cada contacto creado debe quedar relacionado con el cliente desde cuyo sitio web llegó la solicitud.',
        1 => 'Esta opción es una marca del estado de la integración. Activarla en la ficha no conecta el sitio web, no crea una clave de API ni configura el envío de formularios. Solo debe activarse después de configurar realmente la integración y comprobar que las solicitudes llegan correctamente. Cuando cambia el estado, el sistema guarda la fecha de la modificación.',
        2 => 'Para relacionar correctamente los contactos recibidos, el nombre o identificador del cliente debe utilizarse de forma coherente en la integración. La API puede crear las entidades que falten, por lo que un error o una escritura diferente del nombre puede generar una ficha independiente en vez de relacionar el contacto con un cliente existente. Después de poner en marcha una integración conviene revisar manualmente las primeras solicitudes recibidas.',
      ),
    ),
    4 =>
    array (
      'id' => 'related-contacts',
      'title' => 'Contactos relacionados',
      'paragraphs' =>
      array (
        0 => 'En la parte inferior de la ficha del cliente se muestran los contactos relacionados. Se trata principalmente de las personas que han enviado solicitudes desde el sitio web de la empresa y que se han añadido manualmente, mediante una importación o a través de la API. Desde la lista se puede abrir la ficha de un contacto concreto y consultar sus datos.',
        1 => 'Un cliente puede tener cualquier número de contactos. Una misma persona puede relacionarse con varios clientes si, por ejemplo, ha enviado solicitudes desde distintos proyectos. Esta relación no crea una copia del contacto: en la base de datos se conserva una única ficha de la persona, accesible desde las fichas de todas las empresas relacionadas.',
        2 => 'Eliminar una relación y eliminar el contacto son acciones diferentes. Si una persona deja de estar relacionada con una empresa, la relación puede modificarse desde la ficha del contacto. Al eliminar un cliente no se eliminan automáticamente sus contactos, pero desaparece la relación con la empresa eliminada.',
      ),
    ),
    5 =>
    array (
      'id' => 'client-list',
      'title' => 'Trabajo con la lista de clientes',
      'paragraphs' =>
      array (
        0 => 'En la lista general de clientes se pueden buscar empresas por su nombre comercial o razón social, ordenar los registros y aplicar filtros. Hay filtros por sector, etiquetas, actividad, conexión con Web/API, sitio web, ubicación, fecha de creación y campos personalizados. Los filtros activos se conservan en la dirección de la página, por lo que una selección preparada puede abrirse de nuevo o compartirse con otro usuario.',
        1 => 'Las acciones masivas permiten asignar o retirar etiquetas a varios clientes a la vez. La eliminación de un cliente es definitiva para su ficha, por lo que antes hay que asegurarse de que la empresa ya no es necesaria en la base de datos. Si la colaboración simplemente ha terminado, normalmente es preferible desactivar el estado «Cliente activo» en lugar de eliminar el registro.',
        2 => 'Las fichas de clientes deben mantenerse actualizadas: hay que emplear una escritura uniforme para los nombres, cambiar a tiempo los estados de actividad y conexión, indicar el sitio web correcto y comprobar las relaciones con los contactos recibidos. La calidad de estos datos determina la precisión con la que ContactCore puede distribuir las solicitudes entre los distintos proyectos de clientes.',
      ),
    ),
  ),
);
