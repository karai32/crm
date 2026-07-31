<?php

return array (
  'id' => 'start',
  'title' => 'Inicio',
  'description' => 'Introducción al sistema, la navegación y el flujo de trabajo principal.',
  'icon' => 'ph-house-line',
  'sections' => 
  array (
    0 => 
    array (
      'id' => 'purpose',
      'title' => 'Para qué sirve ContactCore',
      'paragraphs' => 
      array (
        0 => 'ContactCore es un sistema para gestionar de forma centralizada clientes y contactos. Permite guardar en un único lugar la información sobre empresas y personas, relacionarlas entre sí y mantener los datos organizados y comprensibles. En lugar de trabajar con varias hojas de cálculo, archivos dispersos y anotaciones repartidas entre distintos empleados, el equipo dispone de una base de datos común en la que puede encontrar rápidamente la información necesaria y comprender el contexto de la relación con cada cliente.',
        1 => 'El sistema no se limita a almacenar direcciones y teléfonos. Su objetivo principal es mostrar la estructura de los datos: con qué organización está relacionada una persona, a qué sector pertenece una empresa, qué etiquetas están asignadas a un registro, qué información adicional se ha recopilado y cuándo se modificó. De este modo, la ficha de un cliente o contacto se convierte en un verdadero punto de trabajo y no en una simple línea de un directorio.',
        2 => 'ContactCore está pensado para el trabajo cotidiano con la base de datos: añadir nuevos registros, completar la información existente, buscar personas y empresas, preparar selecciones, intercambiar información con otros sistemas y mejorar progresivamente la calidad de los datos.',
      ),
    ),
    1 => 
    array (
      'id' => 'contents',
      'title' => 'Qué incluye el sistema',
      'paragraphs' => 
      array (
        0 => 'ContactCore se articula en torno a dos secciones relacionadas: clientes y contactos. En el sistema, un cliente es una organización o empresa. Su ficha contiene el nombre, los datos legales y de dirección, el sitio web, el sector de actividad, notas y otra información. Un contacto es una persona concreta, con su nombre, correo electrónico, teléfono e información sobre su empresa. Un cliente puede estar relacionado con varios contactos y un contacto, a su vez, con varios clientes.',
        1 => 'Para clasificar los datos se utilizan sectores y etiquetas. El sector describe la principal área de actividad del cliente, por ejemplo, tecnología, educación o turismo. Las etiquetas son más flexibles: permiten indicar un estado, un tipo de relación, la pertenencia a un proyecto o cualquier otra característica importante para el equipo. Los campos personalizados permiten ampliar las fichas estándar sin modificar el código y guardar exactamente los datos que necesita cada negocio.',
        2 => 'El sistema también incluye herramientas de importación y exportación. Permiten cargar bases de datos existentes desde archivos CSV o XLSX, asociar sus columnas con los campos del CRM, controlar los errores y volver a exportar los datos necesarios. Las herramientas de IA ayudan en determinadas tareas de enriquecimiento de datos, mientras que la API REST permite conectar sitios web, formularios y servicios internos.',
        3 => 'Hay secciones específicas para gestionar usuarios, ajustes e integraciones. Desde ellas se configuran las cuentas, las preferencias de la interfaz, las claves de API y otras funciones administrativas. La documentación técnica describe la estructura interna de la plataforma, su configuración y su despliegue.',
      ),
    ),
    2 => 
    array (
      'id' => 'capabilities',
      'title' => 'Qué permite hacer ContactCore',
      'paragraphs' => 
      array (
        0 => 'En el trabajo habitual, ContactCore permite crear la ficha de una empresa, añadir las personas relacionadas con ella y completar gradualmente los registros con toda la información disponible. Los datos pueden editarse a medida que se confirman, vincularse con nuevos clientes, organizarse por sectores y marcarse con etiquetas. Los contactos y los clientes admiten campos personalizados independientes, por lo que la estructura de sus fichas puede evolucionar junto con las necesidades del equipo.',
        1 => 'Las listas de contactos y clientes permiten buscar, ordenar y filtrar. Es posible localizar registros por sus datos principales, entidades relacionadas, fechas y campos personalizados. Las acciones masivas ayudan a procesar varios registros a la vez: por ejemplo, asignarles una etiqueta común, añadir una relación o eliminar los elementos seleccionados. La búsqueda global de la barra superior sirve para acceder rápidamente a una persona u organización concreta desde cualquier sección del CRM.',
        2 => 'Para trasladar grandes volúmenes de información no es necesario crear cada registro manualmente. La importación carga los datos por lotes y conserva el resultado del procesamiento de cada fila, mientras que la exportación genera una selección con el conjunto de campos necesario. A través de la API, las aplicaciones externas también pueden trabajar con las principales entidades. Esto permite utilizar ContactCore tanto como CRM independiente como fuente central de datos para otras herramientas.',
        3 => 'El mayor valor del sistema se obtiene cuando los datos se mantienen de forma coherente. Unas reglas de nomenclatura comunes, relaciones cuidadas, etiquetas claras y fichas completas permiten encontrar la información con mayor rapidez, evitar duplicados y conservar el contexto importante durante el trabajo en equipo.',
      ),
    ),
    3 =>
    array (
      'id' => 'data-model',
      'title' => 'Cómo se relacionan los datos principales',
      'paragraphs' =>
      array (
        0 => 'Al trabajar con el sistema es importante distinguir entre cliente y contacto. El cliente responde a la pregunta «¿con qué organización trabajamos?», mientras que el contacto responde a «¿con qué persona nos comunicamos?». Ambos registros pueden existir por separado, pero son precisamente sus relaciones las que ofrecen la visión más completa. Desde la ficha de un cliente se pueden ver las personas vinculadas a él, y desde la ficha de un contacto se puede acceder a las organizaciones relacionadas.',
        1 => 'El sector se asigna al cliente y describe su área de actividad. Las etiquetas pueden asignarse tanto a clientes como a contactos y sirven para una clasificación más libre. Los campos personalizados también se crean por separado para clientes y contactos: un campo añadido para organizaciones no aparece automáticamente en las fichas de personas. Esta separación ayuda a mantener estructurada la base de datos y evita mezclar información con finalidades distintas.',
        2 => 'Cuando la información llega desde un archivo externo o una integración, termina incorporándose a estas mismas entidades y relaciones. Por ello, antes de importar datos o conectar una API conviene decidir qué datos representan clientes, cuáles representan contactos, qué valores deben convertirse en etiquetas y cuáles es preferible guardar en campos personalizados.',
      ),
    ),
    4 =>
    array (
      'id' => 'using-help',
      'title' => 'Cómo utilizar la ayuda',
      'paragraphs' =>
      array (
        0 => 'El centro de ayuda sigue la misma organización que el CRM. La navegación de la izquierda permite acceder al tema necesario: clientes, contactos, clasificación, campos personalizados, intercambio de datos, herramientas de IA, ajustes o API. En pantallas pequeñas, la lista de secciones se abre mediante un botón situado encima del artículo.',
        1 => 'El campo de búsqueda situado en la parte superior de la página permite encontrar una sección por su nombre o descripción breve. Busca en la estructura de la ayuda, no en el texto completo de los artículos. Dentro de cada sección, el contenido está dividido en apartados temáticos, por lo que puede leerse en orden o consultarse directamente el tema de interés. Al final de la página hay enlaces al tema anterior y al siguiente.',
        2 => 'Las secciones de usuario explican la finalidad de las funciones y la forma habitual de trabajar con ellas. La sección de la API está destinada a configurar integraciones e incluye una descripción de las solicitudes y respuestas. La documentación técnica aborda la arquitectura de la aplicación, la base de datos, la seguridad, la configuración y el despliegue. Para una tarea cotidiana del CRM, lo mejor es empezar por la sección de usuario correspondiente; para una cuestión relacionada con la estructura o el mantenimiento de la plataforma, hay que consultar la documentación técnica.',
        3 => 'El idioma de la ayuda coincide con el idioma seleccionado para la interfaz. El contenido se ampliará a medida que evolucione ContactCore, por lo que el centro de ayuda puede utilizarse como punto de referencia principal para conocer las funciones del sistema y consultar el flujo de trabajo.',
      ),
    ),
  ),
);
