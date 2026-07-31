<?php

return array (
  'id' => 'ai-tools',
  'title' => 'Herramientas de IA',
  'description' => 'Detección automática de empresas y revisión de los resultados de la IA.',
  'icon' => 'ph-sparkle',
  'sections' =>
  array (
    0 =>
    array (
      'id' => 'ai-purpose',
      'title' => 'Para qué se utiliza la IA',
      'paragraphs' =>
      array (
        0 => 'Las herramientas de IA de ContactCore utilizan Google Gemini. En la versión actual tienen una única tarea concreta: buscar el nombre de la empresa de aquellos contactos que tienen un correo electrónico corporativo y cuyo campo «Empresa» todavía está vacío.',
        1 => 'Para realizar la búsqueda, Gemini recibe el dominio del correo, por ejemplo example.com, e intenta determinar el nombre oficial de la organización a partir del sitio web asociado. No es un asistente de IA general ni una herramienta para crear clientes, redactar textos o procesar otros datos.',
      ),
    ),
    1 =>
    array (
      'id' => 'ai-queue',
      'title' => 'Qué contactos aparecen en la lista',
      'paragraphs' =>
      array (
        0 => 'La tabla solo muestra los contactos cuyo correo se ha clasificado como corporativo, cuyo campo «Empresa» está vacío y para los que todavía no se ha tomado una decisión sobre la empresa. Encima de la tabla aparecen el número de contactos en esta situación y la cantidad de dominios de correo únicos.',
        1 => 'Cada fila muestra el nombre del contacto, su correo y el dominio. Al pulsar el dominio se abre el sitio web probable en una pestaña nueva para poder comprobar manualmente el resultado. El número situado junto al dominio indica cuántos contactos de la cola utilizan ese mismo dominio.',
      ),
    ),
    2 =>
    array (
      'id' => 'ai-actions',
      'title' => 'Botones y comprobación del resultado',
      'paragraphs' =>
      array (
        0 => 'El botón con la estrella envía el dominio del contacto a Gemini. El nombre encontrado aparece en el campo de texto de la fila, pero todavía no se guarda en la ficha. Hay que revisar la respuesta y, si es necesario, corregirla manualmente.',
        1 => 'El botón con la marca de verificación guarda el contenido del campo de texto como empresa del contacto. Después de guardarlo, la fila desaparece de la cola. Este botón solo debe pulsarse después de comprobar el nombre.',
        2 => 'El botón con la cruz marca la empresa como no encontrada o como no necesaria de procesar. El campo «Empresa» permanece vacío, pero el contacto se retira de la cola y deja de proponerse para nuevas búsquedas.',
        3 => 'El botón «Auto» situado encima de la tabla inicia una búsqueda de Gemini, por orden, para las filas visibles de la página. Solo rellena las opciones encontradas y no las guarda automáticamente: cada resultado debe confirmarse con la marca de verificación o rechazarse con la cruz. El botón «Detener» interrumpe las siguientes solicitudes automáticas.',
      ),
    ),
  ),
);
