<?php

return array (
  'id' => 'import-export',
  'title' => 'Importación y exportación',
  'description' => 'Carga, comprobación y descarga de datos en formatos CSV y XLSX.',
  'icon' => 'ph-arrows-down-up',
  'sections' =>
  array (
    0 =>
    array (
      'id' => 'data-exchange',
      'title' => 'Para qué sirven la importación y la exportación',
      'paragraphs' =>
      array (
        0 => 'La importación se utiliza cuando es necesario añadir a ContactCore muchos clientes o contactos a la vez desde una hoja de cálculo. En lugar de crear cada ficha manualmente, el sistema lee las filas del archivo, propone asociar sus columnas con los campos del CRM y después procesa los registros uno a uno. Los clientes y los contactos se importan por separado, por lo que antes de cargar el archivo hay que seleccionar la pestaña correcta.',
        1 => 'La exportación realiza la operación inversa: genera una tabla a partir de los datos que ya se encuentran en el sistema. Resulta útil para preparar un informe, compartir una selección con otros compañeros, procesar los datos en otra herramienta o crear una copia de trabajo de respaldo. La exportación no modifica las fichas originales de clientes y contactos.',
        2 => 'Ambas herramientas admiten los formatos CSV y XLSX. CSV es cómodo para intercambiar datos entre sistemas diferentes, mientras que XLSX resulta adecuado para consultar y seguir trabajando con la información en aplicaciones de hojas de cálculo.',
      ),
    ),
    1 =>
    array (
      'id' => 'import-templates',
      'title' => 'Preparación del archivo y plantillas',
      'paragraphs' =>
      array (
        0 => 'Las plantillas preparadas se pueden descargar directamente desde la página «Importaciones». Primero hay que elegir la pestaña «Contactos» o «Clientes» y, a continuación, descargar un ejemplo en formato CSV o XLSX desde el bloque «Plantillas de importación». Las plantillas de clientes y contactos son diferentes porque estos registros tienen distintos campos estándar.',
        1 => 'La plantilla muestra la estructura correcta de la tabla: la primera fila contiene los nombres de las columnas y la siguiente, un registro de ejemplo. No es obligatorio rellenarla por completo ni conservar el orden original de las columnas, pero utilizar los encabezados preparados ayuda al sistema a proponer las asociaciones correctas. Las columnas innecesarias pueden eliminarse y se pueden añadir otras nuevas.',
        2 => 'Cada fila no vacía posterior debe describir un cliente o un contacto. Para importar contactos es obligatorio indicar el nombre completo y, para los clientes, el nombre comercial. Se admiten archivos CSV y XLSX de hasta 20 MB.',
      ),
    ),
    2 =>
    array (
      'id' => 'field-mapping',
      'title' => 'Vista previa y asignación de campos',
      'paragraphs' =>
      array (
        0 => 'Después de cargar el archivo, el sistema muestra las primeras filas y el número total de registros encontrados. Debajo aparece la tabla de asignación: para cada columna del archivo original hay que indicar en qué campo de ContactCore se guardará su valor. Los nombres habituales, como «email», «phone», «sector» o «tags», se reconocen automáticamente, pero aun así es necesario revisar las asignaciones propuestas antes de iniciar el proceso.',
        1 => 'Una columna puede asignarse a un campo estándar, excluirse de la importación o utilizarse para crear un campo personalizado. Una asignación incorrecta no modifica los datos originales, pero los guarda en el lugar equivocado: por ejemplo, el nombre de una ciudad podría terminar en el campo del país. Por eso es especialmente importante revisar las columnas con significados parecidos y comprobar que una de ellas esté asignada al campo obligatorio: el nombre completo del contacto o el nombre comercial del cliente.',
        2 => 'Los nombres de las columnas pueden ser distintos de los de la plantilla si el usuario selecciona manualmente las asignaciones correctas. Por tanto, normalmente no es necesario rehacer todo el archivo original: basta con ordenar los encabezados, eliminar las filas auxiliares y configurar cuidadosamente la asignación.',
      ),
    ),
    3 =>
    array (
      'id' => 'import-custom-fields',
      'title' => 'Creación de campos personalizados durante la importación',
      'paragraphs' =>
      array (
        0 => 'Si no existe un campo estándar adecuado para una columna, se puede elegir «Crear campo personalizado» en la lista de asignaciones. Después hay que indicar su tipo: texto, texto multilínea, número, fecha, correo electrónico, enlace, lista o casilla. El encabezado de la columna se convierte en el nombre del nuevo campo y su identificador técnico se genera automáticamente.',
        1 => 'Un campo creado de este modo pertenece al tipo de registro que se está importando: si se importan contactos, se crea para contactos; si se importan clientes, para clientes. El campo nuevo se crea como opcional y filtrable. Si ya existe un campo con el mismo identificador técnico para el tipo de registro seleccionado, el sistema lo reutiliza y guarda los valores en él en lugar de crear otro.',
        2 => 'Antes de importar conviene revisar los nombres de estas columnas y los tipos seleccionados. Las distintas formas de escribir una misma característica pueden generar varios campos similares. Para una casilla se consideran valores activados 1, yes, true y si; los demás valores se guardan como desactivados. Para un campo de lista es preferible crear previamente las opciones en la configuración o revisarlas y configurarlas después de la importación.',
      ),
    ),
    4 =>
    array (
      'id' => 'import-processing',
      'title' => 'Procesamiento de registros y datos relacionados',
      'paragraphs' =>
      array (
        0 => 'La importación crea registros nuevos y no se utiliza para actualizar fichas existentes de forma masiva. Se omiten los contactos cuyo correo electrónico ya existe en la base de datos o se repite en el mismo archivo. También se omiten los clientes cuyo nombre comercial ya existe. Las filas sin el nombre obligatorio o con una dirección de correo incorrecta se marcan como errores.',
        1 => 'Las etiquetas y los sectores se asocian por su nombre. Si la etiqueta o el sector indicado todavía no existe, el sistema lo crea automáticamente, por lo que los mismos valores deben escribirse siempre de forma uniforme. Dentro de una celda, las etiquetas pueden separarse mediante comas, puntos y coma o barras verticales.',
        2 => 'Al importar contactos, el valor del campo de cliente relaciona el contacto con un cliente existente por su nombre comercial o crea una ficha mínima para un cliente nuevo. Al importar clientes ocurre lo contrario: el valor de la columna del contacto debe indicar un contacto que ya exista; si no se encuentra ninguna persona con ese nombre, la fila termina con un error.',
        3 => 'Al finalizar se muestra el número de filas importadas, omitidas y con errores. Todas las ejecuciones se guardan en el historial de importaciones. Las filas problemáticas se pueden consultar en una lista independiente que muestra el número de fila, los datos originales y el motivo por el que no se añadió el registro.',
      ),
    ),
    5 =>
    array (
      'id' => 'export-data',
      'title' => 'Exportación de datos',
      'paragraphs' =>
      array (
        0 => 'En la página «Exportaciones» se elige primero el tipo de datos: contactos o clientes. A continuación, el usuario marca los campos que deben incluirse en el archivo. Los campos estándar están agrupados por finalidad y también se pueden seleccionar los campos personalizados creados en el sistema.',
        1 => 'Antes de descargar el archivo se puede seleccionar el formato CSV o XLSX. La primera fila del archivo resultante contiene los nombres de los campos elegidos y cada fila posterior corresponde a un registro. El nombre del archivo se genera automáticamente e incluye el tipo de datos y la hora de creación.',
        2 => 'En la parte inferior de la página se conserva el historial de exportaciones: tipo de datos, nombre y formato del archivo, número de filas, autor y hora de ejecución. El historial registra que se realizó la exportación, pero el archivo descargado debe guardarse en un lugar seguro y adecuado, ya que puede contener datos de contacto y otra información de trabajo.',
      ),
    ),
  ),
);
