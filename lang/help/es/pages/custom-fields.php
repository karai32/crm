<?php

return array (
  'id' => 'custom-fields',
  'title' => 'Campos personalizados',
  'description' => 'Ampliación de las fichas de clientes y contactos con campos propios.',
  'icon' => 'ph-sliders-horizontal',
  'sections' => 
  array (
    0 => 
    array (
      'id' => 'custom-fields-purpose',
      'title' => 'Finalidad de los campos personalizados',
      'paragraphs' => 
      array (
        0 => 'Los campos personalizados permiten guardar información para la que no existe un espacio específico en la ficha estándar de un cliente o contacto. Por ejemplo, se puede añadir a un cliente un número interno de contrato, la tarifa que utiliza o la fecha de la próxima renovación; y a un contacto, el origen de la solicitud, el servicio que le interesa o su consentimiento para un determinado tipo de comunicación.',
        1 => 'Cada campo se crea para clientes o para contactos. Un campo de cliente no aparece en la ficha de un contacto y viceversa. Si se necesita la misma información para ambos tipos de registro, hay que crear dos campos independientes con nombres claros.',
        2 => 'Los campos personalizados complementan la estructura estándar del sistema, pero no la sustituyen. Por ejemplo, un campo adicional de tipo correo electrónico no participa en la comprobación y clasificación de la dirección principal del contacto, y un campo de texto con el nombre de una actividad no sustituye al sector del cliente.',
      ),
    ),
    1 => 
    array (
      'id' => 'custom-field-types',
      'title' => 'Tipos de campo',
      'paragraphs' => 
      array (
        0 => 'El tipo «text» está destinado a valores cortos de una sola línea, como un código, un nombre, un origen o un comentario breve. «textarea» se utiliza para textos extensos de varias líneas, por ejemplo, una descripción adicional o notas internas.',
        1 => 'El tipo «number» guarda un valor numérico y «date», una fecha del calendario. Es preferible utilizarlos en lugar de texto normal cuando se conoce de antemano el formato de los datos: así la introducción es uniforme y se puede utilizar el elemento de filtro correspondiente.',
        2 => 'Los tipos «email» y «url» están destinados, respectivamente, a direcciones de correo electrónico y enlaces web. Ayudan a introducir el valor en el formato esperado, aunque un correo guardado en este tipo de campo sigue siendo un valor adicional y no pasa la comprobación MX específica de la dirección principal de un contacto.',
        3 => 'El tipo «select» crea una lista de opciones predefinidas entre las que el usuario elige una. Cada opción se introduce en una línea distinta al configurar el campo. «checkbox» es un interruptor sencillo con dos estados, activado o desactivado, y resulta adecuado para indicadores como el consentimiento, la existencia de un contrato o la participación en un programa.',
      ),
    ),
    2 => 
    array (
      'id' => 'custom-field-settings',
      'title' => 'Creación y configuración',
      'paragraphs' => 
      array (
        0 => 'Al crear un campo se indican su destino —cliente o contacto—, el nombre, el identificador técnico, el tipo y el orden de visualización. Los usuarios ven el nombre en las fichas y los formularios. El identificador técnico, o slug, se utiliza en el sistema, las importaciones y la API; si no se introduce manualmente, se genera a partir del nombre.',
        1 => 'El slug debe ser único entre los campos de un mismo tipo de registro. Una vez iniciada una importación o conectada una integración, es preferible no cambiarlo, ya que el sistema externo seguirá enviando los datos con el identificador anterior. También conviene definir el tipo y el destino del campo antes de comenzar a llenar la base de datos: modificarlos cuando el campo ya está en uso puede hacer que los valores guardados anteriormente dejen de estar disponibles o no sean adecuados para el nuevo formato.',
        2 => 'El valor predeterminado se introduce automáticamente al crear un registro nuevo cuando el usuario o la integración no proporcionan otro valor. No se aplica retroactivamente a las fichas existentes. La opción «Obligatorio» se guarda en la configuración y aparece en la lista de campos, pero en la versión actual no impide guardar una ficha con el valor vacío.',
      ),
    ),
    3 =>
    array (
      'id' => 'filterable-custom-fields',
      'title' => 'Qué significa «Filtrable»',
      'paragraphs' =>
      array (
        0 => 'Al activar la opción «Filtrable», el campo pasa a estar disponible entre los filtros adicionales de la lista correspondiente. Un campo de cliente aparece en los filtros de la sección «Clientes» y uno de contacto, en los de «Contactos». Desactivar esta opción no oculta el campo en las fichas ni elimina los valores guardados: únicamente impide utilizarlo como criterio de selección en la interfaz.',
        1 => 'El tipo de filtro depende del tipo de campo. Para «text» se puede seleccionar uno de los valores ya utilizados; para «select», una de las opciones predefinidas; y para «checkbox», «Sí» o «No». Para «number» y «date» se muestran los campos de entrada correspondientes.',
        2 => 'En la versión actual hay elementos de filtro específicos para los tipos «text», «select», «checkbox», «number» y «date». Los campos «textarea», «email» y «url» pueden marcarse como filtrables en la configuración, pero todavía no se muestra un filtro específico para ellos en la lista.',
      ),
    ),
    4 =>
    array (
      'id' => 'custom-field-values',
      'title' => 'Uso y conservación de los datos',
      'paragraphs' =>
      array (
        0 => 'Después de crear el campo, este aparece en el formulario y en la ficha del tipo de registro seleccionado. Sus valores se guardan por separado para cada cliente o contacto, se incluyen en las exportaciones y pueden rellenarse durante una importación. El orden de visualización permite colocar los campos más utilizados por encima de los demás.',
        1 => 'Los valores de los campos personalizados también se pueden enviar y recibir a través de la API. Para ello se utiliza el slug del campo, por lo que el campo necesario debe crearse previamente para clientes o contactos. Los formatos de las solicitudes y los ejemplos se explicarán por separado en la sección «API».',
        2 => 'Al eliminar un campo personalizado también se eliminan todos los valores que se habían guardado en él para clientes o contactos. Las fichas se conservan, pero los valores no pueden recuperarse desde la interfaz. Antes de eliminar un campo en uso, conviene asegurarse de que los datos ya no son necesarios o guardarlos previamente mediante una exportación.',
      ),
    ),
  ),
);
