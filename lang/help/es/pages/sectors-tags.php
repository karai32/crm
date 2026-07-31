<?php

return array (
  'id' => 'sectors-tags',
  'title' => 'Sectores y etiquetas',
  'description' => 'Clasificación de registros mediante sectores y etiquetas flexibles.',
  'icon' => 'ph-tag',
  'sections' => 
  array (
    0 => 
    array (
      'id' => 'classification-principles',
      'title' => 'Principios de clasificación',
      'paragraphs' => 
      array (
        0 => 'Los sectores y las etiquetas ayudan a dar una estructura común a la base de datos y permiten localizar rápidamente los clientes y contactos necesarios mediante filtros. Estas herramientas se complementan, pero resuelven tareas diferentes: el sector describe la actividad principal del cliente, mientras que las etiquetas indican características adicionales que pueden cambiar con el tiempo.',
        1 => 'Conviene elegir un sector cuando se necesita responder a la pregunta «¿a qué actividad pertenece este cliente?». Cada cliente solo puede tener un sector. Una etiqueta es adecuada para una clasificación más flexible: puede indicar la prioridad, el origen, la participación en una campaña, el interés por un servicio o cualquier otra característica de trabajo. Un registro puede tener varias etiquetas.',
        2 => 'Para que la clasificación siga siendo útil, es recomendable acordar de antemano una nomenclatura común. Por ejemplo, no se deberían crear a la vez las etiquetas «Importante», «Prioritario» y «VIP» si significan lo mismo. Tampoco conviene repetir como etiqueta la actividad del cliente si ya está indicada en su sector. Cuanto más coherente sea el uso de sectores y etiquetas, más precisos serán los filtros y más fácil resultará analizar la base de datos.',
      ),
    ),
    1 => 
    array (
      'id' => 'sectors',
      'title' => 'Sectores',
      'paragraphs' => 
      array (
        0 => 'Un sector es la principal actividad o área de negocio del cliente, por ejemplo «Turismo», «Inmobiliaria» o «Tecnología». Los sectores solo se asignan a clientes; los contactos no tienen un sector propio. Sin embargo, la lista de contactos puede filtrarse por el sector del cliente con el que están relacionados.',
        1 => 'Cada cliente solo puede tener un sector. Por eso, conviene que la lista de sectores sea suficientemente amplia y estable, sin convertirla en un catálogo de servicios, proyectos o estados temporales. Para esas precisiones es preferible utilizar etiquetas o campos personalizados.',
        2 => 'Para crear un sector basta con indicar su nombre. Más adelante se puede elegir un icono y modificar su estado de actividad. El icono solo sirve para identificar visualmente el sector. Los cambios de nombre o icono se aplican a todos los clientes que ya tienen asignado ese sector.',
        3 => 'Si un sector ya está siendo utilizado por algún cliente, el sistema no lo elimina definitivamente, sino que lo desactiva. Se conservan las relaciones con los clientes existentes, pero el sector inactivo ya no puede asignarse a clientes nuevos. Un sector que todavía no se utiliza puede eliminarse por completo.',
      ),
    ),
    2 => 
    array (
      'id' => 'tags',
      'title' => 'Etiquetas',
      'paragraphs' => 
      array (
        0 => 'Las etiquetas son marcadores flexibles que pueden asignarse tanto a clientes como a contactos. Cada registro puede tener varias etiquetas. Por ejemplo, un cliente puede etiquetarse según el tipo de colaboración o la prioridad, mientras que un contacto puede clasificarse por el origen de la solicitud, el servicio de interés, la campaña de marketing o la fase de tramitación.',
        1 => 'Cada etiqueta tiene un nombre y, opcionalmente, un color. El color permite distinguirlas con mayor rapidez en las listas y fichas, pero no modifica el comportamiento del sistema. Las etiquetas se pueden asignar y retirar desde la ficha de un registro o de forma masiva para varios clientes o contactos seleccionados. Las dos secciones permiten filtrar por etiquetas.',
        2 => 'Las etiquetas también pueden asignarse automáticamente al importar datos o enviarlos a través de la API. Las integraciones deben utilizar siempre los mismos nombres acordados: las diferencias de escritura pueden generar etiquetas innecesarias y dificultar la búsqueda.',
        3 => 'Las etiquetas no tienen estado de actividad. Al cambiar el nombre o el color, la modificación se refleja inmediatamente en todos los registros relacionados. Si se elimina una etiqueta, se retira definitivamente de todos los clientes y contactos, aunque estos registros no se eliminan. Cuando una etiqueta deja de ser necesaria solo para algunos registros, debe retirarse de ellos en vez de eliminarse por completo del sistema.',
      ),
    ),
  ),
);
