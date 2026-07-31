<?php

return array (
  'id' => 'contacts',
  'title' => 'Contactos',
  'description' => 'Gestión de personas, sus datos de contacto y sus relaciones con clientes.',
  'icon' => 'ph-address-book',
  'sections' => 
  array (
    0 => 
    array (
      'id' => 'contact-meaning',
      'title' => 'Qué considera el sistema un contacto',
      'paragraphs' => 
      array (
        0 => 'En ContactCore, un contacto es una persona concreta. En el modelo principal de la plataforma, los contactos se crean cuando las personas envían solicitudes mediante los formularios de los sitios web de nuestros clientes. La empresa cuyo sitio web recibe la solicitud se guarda como cliente, mientras que la persona que envía el formulario se guarda como contacto relacionado con él.',
        1 => 'El contacto no tiene por qué ser cliente de nuestra propia organización. Normalmente es un cliente potencial o actual de la empresa a la que prestamos servicio. Esta separación permite recopilar en una única base de datos las solicitudes procedentes de distintos sitios web de clientes, sin mezclar las propias empresas con las personas que se ponen en contacto con ellas.',
        2 => 'Un contacto puede estar relacionado con varios clientes. Esto resulta útil cuando una misma persona ha enviado solicitudes desde diferentes sitios web o está vinculada a varios proyectos. En ese caso, es preferible utilizar una única ficha de contacto con varias relaciones en lugar de crear una copia distinta de la persona para cada cliente.',
      ),
    ),
    1 => 
    array (
      'id' => 'contact-fields',
      'title' => 'Campos de la ficha del contacto',
      'paragraphs' => 
      array (
        0 => 'El nombre completo es el único campo estándar obligatorio. El correo electrónico y el teléfono se rellenan cuando estos datos están presentes en la solicitud. El correo es especialmente importante: se utiliza para buscar el contacto, comprobar la dirección y detectar registros repetidos durante la importación o el envío de datos a través de la API.',
        1 => 'El campo «Empresa» indica la organización en la que trabaja la persona. No es lo mismo que un cliente relacionado. Por ejemplo, una solicitud puede llegar al sitio web de nuestro cliente «Acme Agency», mientras que la persona que la envía trabaja en «Example Group». En ese caso, «Acme Agency» se indica entre los clientes relacionados y «Example Group» en el campo «Empresa». Si no se conoce el lugar de trabajo, el campo puede dejarse vacío.',
        2 => 'Las etiquetas permiten indicar el estado o el tipo de contacto, mientras que los campos personalizados sirven para guardar información adicional del formulario: el origen, el servicio de interés, el presupuesto, el consentimiento para recibir comunicaciones u otros datos. Los campos personalizados de los contactos se configuran por separado de los campos de clientes.',
      ),
    ),
    2 => 
    array (
      'id' => 'contact-sources',
      'title' => 'Cómo llegan los contactos al sistema',
      'paragraphs' => 
      array (
        0 => 'Un contacto puede crearse manualmente, importarse desde un archivo CSV o XLSX o recibirse a través de la API. El principal flujo automático comienza con el envío de un formulario desde el sitio web de un cliente. La integración transmite los datos de la solicitud a ContactCore, crea el contacto y lo relaciona con la empresa cliente a la que pertenece el sitio.',
        1 => 'Al transmitir solicitudes es importante indicar siempre el mismo cliente de forma coherente. Si la integración utiliza una escritura diferente del nombre, la API puede crear una nueva ficha de empresa en lugar de relacionar el contacto con la existente. Después de conectar un sitio web conviene revisar las primeras solicitudes: comprobar que el nombre, el correo y el teléfono sean correctos, que se haya asignado el cliente adecuado y que se transmitan los campos adicionales del formulario.',
        2 => 'El correo electrónico suele utilizarse como identificador práctico de la persona. Durante una importación se omiten las filas cuyo correo ya existe, mientras que la API devuelve un error de duplicado. Si la persona ya está en la base de datos, pero ahora está relacionada con otro cliente, es preferible añadir una nueva relación a su ficha existente. Al crear contactos manualmente, el sistema no bloquea las direcciones repetidas, por lo que conviene utilizar la búsqueda antes de guardar.',
      ),
    ),
    3 =>
    array (
      'id' => 'email-validation',
      'title' => 'Cómo se comprueba el correo electrónico',
      'paragraphs' =>
      array (
        0 => 'Al crear un contacto manualmente o mediante la API, el sistema analiza la dirección de correo indicada. Primero comprueba su formato: la presencia de un nombre, el símbolo @ y una parte de dominio válida. A continuación, compara el dominio con una lista de servicios de correo personal habituales y, durante una comprobación normal, busca su registro MX, el registro técnico que indica que un dominio puede recibir correo.',
        1 => 'Como resultado, la dirección recibe dos indicadores independientes. El primero determina el tipo de correo: corporativo o personal. Las direcciones de Gmail, Outlook, Yahoo, Yandex, Mail.ru y otros servicios públicos se consideran personales; una dirección en el dominio propio de una organización se considera corporativa. El segundo indicador señala si la comprobación técnica ha detectado algún problema con la dirección.',
        2 => 'Si el formato es incorrecto o el dominio no tiene un registro MX, el correo se marca como no válido. Esta indicación aparece en la lista y en la ficha del contacto. La ausencia de una advertencia solo significa que no se han detectado problemas técnicos evidentes. El sistema no confirma que exista el buzón concreto, la identidad de su propietario ni que un mensaje pueda entregarse realmente.',
        3 => 'La clasificación como correo corporativo tampoco constituye una confirmación legal del lugar de trabajo de la persona. Se basa en el dominio: cualquier dirección cuyo dominio no figure entre los servicios personales conocidos se considera corporativa. El resultado debe entenderse como una clasificación automática útil que puede corregirse manualmente cuando sea necesario.',
      ),
    ),
    4 =>
    array (
      'id' => 'email-correction',
      'title' => 'Corrección manual del estado del correo',
      'paragraphs' =>
      array (
        0 => 'Al editar un contacto, el estado del correo puede cambiarse manualmente a «Corporativo», «Personal» o «No válido». La selección manual tiene prioridad sobre la detección automática. Es útil si el sistema clasifica incorrectamente un servicio de correo poco conocido, si un dominio corporativo se utiliza para correo personal o si el resultado de la comprobación DNS no coincide con la situación real.',
        1 => 'Si se modifica la propia dirección, hay que revisar de nuevo el estado seleccionado. Una clasificación manual guardada anteriormente puede no ser adecuada para el nuevo dominio. Los contactos sin correo electrónico no tienen asignado ningún estado.',
        2 => 'En los ajustes existe una comprobación por lotes para las direcciones cuyo tipo de correo todavía no se ha determinado. Se ejecuta en grupos pequeños para no sobrecargar el servidor, clasifica la dirección y comprueba el registro MX del dominio. Después, cualquier resultado dudoso o claramente incorrecto puede corregirse desde la ficha del contacto.',
      ),
    ),
    5 =>
    array (
      'id' => 'contact-list',
      'title' => 'Relaciones, búsqueda y trabajo con la lista',
      'paragraphs' =>
      array (
        0 => 'La ficha del contacto muestra los clientes relacionados, las etiquetas y los campos personalizados. La relación con un cliente indica en qué proyecto de cliente apareció o se utiliza el contacto. El campo «Empresa» sigue siendo una característica independiente de la propia persona.',
        1 => 'La lista de contactos puede filtrarse por nombre, correo electrónico, teléfono, cliente relacionado, sector del cliente, etiquetas, fechas, tipo y estado del correo, así como por campos personalizados. La búsqueda global de la barra superior permite localizar rápidamente a una persona por su nombre, dirección o teléfono desde cualquier sección del CRM.',
        2 => 'Las acciones masivas se utilizan para asignar y retirar etiquetas, relacionar los contactos seleccionados con un cliente y eliminar registros. La eliminación de un contacto es definitiva y también elimina sus relaciones con clientes. Si solo es necesario retirar el contacto de un proyecto, hay que modificar las relaciones desde su ficha en lugar de eliminar a la persona.',
      ),
    ),
  ),
);
