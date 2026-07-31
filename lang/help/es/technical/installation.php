<?php

return array (
  'title' => 'Instalación',
  'description' => 'Instalación de ContactCore, preparación de la base de datos y la configuración, primera ejecución y comprobación de la plataforma.',
  'icon' => 'ph-arrow-elbow-down-right',
  'sections' =>
  array (
    0 =>
    array (
      'id' => 'installation-overview',
      'title' => 'Antes de la instalación',
      'paragraphs' =>
      array (
        0 => 'Esta guía continúa la sección «Servidor». Antes de empezar deben estar preparados el dominio, un servidor HTTPS con Nginx, PHP-FPM 8.4 o 8.5, MySQL, Composer y todas las extensiones de PHP indicadas. El usuario deploy debe poder desplegar el código, mientras que www-data debe poder ejecutar la aplicación y escribir los datos de trabajo en storage.',
        1 => 'La instalación se realiza por etapas: desplegar el código y las dependencias, crear la base de datos, completar la configuración, ajustar los permisos, crear el primer administrador, comprobar los servicios externos y cron y, por último, realizar la verificación final. Los comandos siguientes utilizan el directorio /var/www/contactcore, la base de datos crm, el usuario deploy y el grupo www-data; si se emplea otra estructura, todas las rutas y nombres deben modificarse de forma coherente.',
      ),
    ),
    1 =>
    array (
      'id' => 'installation-deploy',
      'title' => 'Despliegue del proyecto, Composer y npm',
      'paragraphs' =>
      array (
        0 => 'Despliegue el proyecto completo, no solo public_html, por ejemplo en /var/www/contactcore. El directorio público seguirá siendo /var/www/contactcore/public_html. El código fuente puede obtenerse desde un repositorio privado o cargarse como archivo comprimido; el directorio .git no es obligatorio en el servidor de producción.',
        1 => 'Las bibliotecas de PHP se instalan desde composer.lock mediante composer install. Este comando añade Illuminate Database, Guzzle, PHPMailer, OpenSpout y sus dependencias a vendor. En el servidor debe realizarse una instalación sin paquetes de desarrollo y con el autoloader optimizado. No ejecute composer update durante un despliegue normal: ese comando cambia las versiones fijadas de las bibliotecas.',
        2 => 'No es necesario ejecutar npm install. El proyecto no tiene package.json, sistema de compilación ni dependencias de Node.js: el CSS y JavaScript ya se encuentran en public_html/assets y se sirven como archivos preparados. No es necesario instalar Node.js ni npm.',
      ),
      'examples' =>
      array (
        0 =>
        array (
          'title' => 'Instalación de Composer y de las dependencias del proyecto',
          'code' => 'sudo apt install -y composer
sudo mkdir -p /var/www/contactcore
sudo chown -R deploy:www-data /var/www/contactcore

# Copie el contenido del proyecto en /var/www/contactcore y, a continuación:
cd /var/www/contactcore
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
composer check-platform-reqs --no-dev',
        ),
      ),
    ),
    2 =>
    array (
      'id' => 'installation-database',
      'title' => 'Creación y configuración de la base de datos',
      'paragraphs' =>
      array (
        0 => 'Cree una base de datos y un usuario de MySQL independientes, destinados exclusivamente a ContactCore. Utilice utf8mb4. La aplicación necesita permisos para leer y modificar datos, crear índices y trabajar con tablas. No utilice la cuenta root en config/database.php ni exponga el puerto 3306 al exterior salvo que exista una necesidad específica.',
        1 => 'El archivo database/schema.sql está destinado a la primera instalación. Al principio especifica la base de datos crm e incluye comandos DROP TABLE. Por tanto, el nombre de la base de datos en config/database.php debe coincidir con el de la estructura, o bien deben modificarse los primeros comandos del archivo antes de importarlo. Nunca vuelva a ejecutar este archivo sobre una base de datos en producción: eliminaría los datos existentes. Las actualizaciones utilizan los archivos SQL sucesivos de database/migrations; todavía no existe un runner automático ni una tabla que registre las versiones.',
      ),
      'examples' =>
      array (
        0 =>
        array (
          'title' => 'Base de datos y usuario de la aplicación',
          'code' => 'sudo mysql

CREATE DATABASE crm
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE USER \'contactcore\'@\'localhost\'
  IDENTIFIED BY \'REPLACE_WITH_A_LONG_RANDOM_PASSWORD\';

GRANT SELECT, INSERT, UPDATE, DELETE ON crm.*
  TO \'contactcore\'@\'localhost\';
FLUSH PRIVILEGES;
EXIT;',
        ),
        1 =>
        array (
          'title' => 'Aplicación inicial de la estructura',
          'code' => 'cd /var/www/contactcore
sudo mysql < database/schema.sql
mysql -u contactcore -p crm -e "SHOW TABLES;"',
        ),
      ),
    ),
    3 =>
    array (
      'id' => 'installation-config',
      'title' => 'Archivos de configuración y secretos',
      'paragraphs' =>
      array (
        0 => 'Copie los cuatro archivos con el sufijo .example.php a sus archivos de trabajo sin .example. Los archivos reales ya están excluidos de Git y no deben incluirse en el repositorio, en archivos destinados a distribución pública ni en los registros. Tanto el usuario de PHP-FPM como el usuario que ejecute cron necesitan acceso de lectura.',
        1 => 'database.php define el host, el nombre de la base de datos, el usuario, la contraseña y charset. En app.php, el parámetro base_url debe contener la dirección HTTPS externa sin una barra final; se utiliza en los enlaces del informe semanal. mail.php define el remitente y SMTP. El valor smtp_secure = ssl significa SMTPS, normalmente en el puerto 465; cualquier otro valor que utilice la aplicación activa STARTTLS, normalmente en el puerto 587.',
        2 => 'gemini.php contiene la clave de Google Gemini. Solo es necesaria para la búsqueda mediante IA de la empresa asociada a un correo corporativo; el resto del CRM puede funcionar sin utilizar esta función. En este momento, SMTP se utiliza principalmente para los informes semanales. La aplicación incluye el código de acceso con autenticación en dos pasos, pero su uso obligatorio está desactivado temporalmente.',
      ),
      'examples' =>
      array (
        0 =>
        array (
          'title' => 'Creación de las configuraciones de trabajo',
          'code' => 'cd /var/www/contactcore
cp config/app.example.php config/app.php
cp config/database.example.php config/database.php
cp config/mail.example.php config/mail.php
cp config/gemini.example.php config/gemini.php

sudo chown root:www-data config/*.php
sudo chmod 640 config/*.php',
        ),
        1 =>
        array (
          'title' => 'Configuración mínima de config/app.php',
          'code' => '<?php

return [
    \'base_url\' => \'https://crm.example.com\',
];',
        ),
        2 =>
        array (
          'title' => 'config/database.php',
          'code' => '<?php

return [
    \'host\'     => \'localhost\',
    \'database\' => \'crm\',
    \'user\'     => \'contactcore\',
    \'password\' => \'REPLACE_WITH_DATABASE_PASSWORD\',
    \'charset\'  => \'utf8mb4\',
];',
        ),
        3 =>
        array (
          'title' => 'config/mail.php',
          'code' => '<?php

return [
    \'from_email\'    => \'no-reply@example.com\',
    \'from_name\'     => \'ContactCore\',
    \'smtp_host\'     => \'smtp.example.com\',
    \'smtp_port\'     => 465,
    \'smtp_username\' => \'no-reply@example.com\',
    \'smtp_password\' => \'REPLACE_WITH_SMTP_PASSWORD\',
    \'smtp_secure\'   => \'ssl\',
];',
        ),
        4 =>
        array (
          'title' => 'config/gemini.php',
          'code' => '<?php

return [
    \'api_key\' => \'REPLACE_WITH_GEMINI_API_KEY\',
];',
        ),
      ),
    ),
    4 =>
    array (
      'id' => 'installation-storage',
      'title' => 'Directorios, propietario y permisos de acceso',
      'paragraphs' =>
      array (
        0 => 'PHP debe tener permiso de escritura en storage. Allí se crean las sesiones, los tokens de «Recordarme», el limitador de intentos de acceso, los archivos de importación cargados y app.log. Además, el usuario de PHP-FPM debe tener acceso al directorio temporal del sistema en el que PHP recibe inicialmente los archivos cargados. El directorio storage se encuentra fuera de public_html y no debe ser accesible mediante una URL. El resto del código de la aplicación debe permanecer como solo lectura para el usuario web.',
        1 => 'No asigne permisos 777 a todo el proyecto. En una instalación típica, el propietario del código puede ser el usuario deploy y el grupo, www-data, permitiendo la escritura al grupo únicamente en storage. El bit setgid del directorio storage conserva el grupo www-data en los nuevos subdirectorios y archivos.',
      ),
      'examples' =>
      array (
        0 =>
        array (
          'title' => 'Permisos seguros para la aplicación',
          'code' => 'cd /var/www/contactcore
sudo chown -R deploy:www-data .
sudo find . -type d -exec chmod 750 {} \;
sudo find . -type f -exec chmod 640 {} \;

sudo mkdir -p storage/sessions storage/remember storage/imports
sudo chown -R deploy:www-data storage
sudo find storage -type d -exec chmod 2770 {} \;
sudo find storage -type f -exec chmod 660 {} \;

sudo chown root:www-data config/*.php
sudo chmod 640 config/*.php',
        ),
      ),
    ),
    5 =>
    array (
      'id' => 'installation-first-admin',
      'title' => 'Creación del primer administrador',
      'paragraphs' =>
      array (
        0 => 'La estructura crea los roles admin y user, pero no crea ninguna cuenta. Antes del primer acceso, genere el hash de la contraseña mediante PHP y añada el administrador directamente a la base de datos. No escriba la contraseña sin cifrar en un archivo SQL ni utilice el valor de sustitución que aparece a continuación.',
        1 => 'Después de iniciar sesión, los demás usuarios deben crearse desde la sección «Usuarios». Entregue la primera contraseña al administrador mediante un canal seguro y sustitúyala si alguna otra persona ha podido acceder a ella.',
      ),
      'examples' =>
      array (
        0 =>
        array (
          'title' => 'Generación del hash y creación del administrador',
          'code' => 'php8.5 -r "echo password_hash(\'REPLACE_WITH_STRONG_PASSWORD\', PASSWORD_DEFAULT), PHP_EOL;"

mysql -u contactcore -p crm

INSERT INTO users (role_id, name, email, password_hash, is_active)
SELECT id, \'Administrator\', \'admin@example.com\',
       \'PASTE_GENERATED_HASH_HERE\', 1
FROM roles
WHERE name = \'admin\';',
        ),
      ),
    ),
    6 =>
    array (
      'id' => 'installation-cron',
      'title' => 'Cron para los informes semanales',
      'paragraphs' =>
      array (
        0 => 'El script bin/weekly-report.php recopila los datos de los últimos siete días y envía un informe a todos los usuarios activos con el rol admin. Utiliza config/database.php, config/mail.php y config/app.php. Debe ejecutarse como www-data o como otro usuario que pueda leer la configuración, conectarse a la base de datos y escribir en storage.',
        1 => 'Primero es obligatorio ejecutar manualmente el comando y comprobar que termina con una línea que indica el número de mensajes enviados y que no aparecen errores en storage/app.log. Después puede crearse la tarea cron del sistema. El ejemplo siguiente ejecuta el informe cada lunes a las 08:00 según la zona horaria del servidor. Si el servidor utiliza otra zona, modifique el horario o configúrela mediante timedatectl.',
        2 => 'Cron utiliza PHP CLI, por lo que también necesita las dependencias de vendor y la extensión pdo_mysql. Si cambia la versión de PHP, actualice la ruta del ejecutable en cron. No invoque el script mediante HTTP ni coloque bin dentro de public_html.',
      ),
      'examples' =>
      array (
        0 =>
        array (
          'title' => 'Comprobación manual del informe',
          'code' => 'cd /var/www/contactcore
sudo -u www-data /usr/bin/php8.5 bin/weekly-report.php
tail -n 50 storage/app.log',
        ),
        1 =>
        array (
          'title' => '/etc/cron.d/contactcore-weekly-report',
          'code' => 'SHELL=/bin/sh
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin

0 8 * * 1 www-data cd /var/www/contactcore && /usr/bin/php8.5 bin/weekly-report.php >> storage/weekly-report-cron.log 2>&1',
        ),
        2 =>
        array (
          'title' => 'Instalación y comprobación de la tarea cron',
          'code' => 'sudo chown root:root /etc/cron.d/contactcore-weekly-report
sudo chmod 644 /etc/cron.d/contactcore-weekly-report
sudo systemctl restart cron
sudo journalctl -u cron --since today --no-pager',
        ),
      ),
    ),
    7 =>
    array (
      'id' => 'installation-integrations',
      'title' => 'SMTP, Gemini y comprobación DNS del correo',
      'paragraphs' =>
      array (
        0 => 'Para SMTP, permita la conexión saliente a smtp_host y al puerto seleccionado. La dirección from_email debe estar autorizada por el proveedor y es recomendable configurar SPF, DKIM y DMARC para el dominio. Los errores de PHPMailer se registran en storage/app.log. El envío puede comprobarse ejecutando manualmente el informe semanal o utilizando el botón de envío del informe en la configuración del administrador.',
        1 => 'Para utilizar la herramienta de IA, el servidor debe permitir que Guzzle realice peticiones HTTPS a generativelanguage.googleapis.com y disponer de certificados raíz válidos. Se recomienda la extensión cURL; sin ella, Guzzle puede utilizar streams de PHP. La clave de Gemini solo se guarda en config/gemini.php. No la incluya en JavaScript, direcciones URL, Git ni registros de errores.',
        2 => 'La comprobación de las direcciones de correo utiliza el resolvedor DNS del sistema y la función checkdnsrr para consultar el registro MX del dominio. Si el servidor, el contenedor o el firewall bloquean DNS, las direcciones válidas pueden obtener un resultado incorrecto. La comprobación MX no necesita abrir una conexión SMTP con el buzón.',
      ),
      'examples' =>
      array (
        0 =>
        array (
          'title' => 'Comprobación de las conexiones externas',
          'code' => 'curl -I https://generativelanguage.googleapis.com/
php8.5 -r "var_dump(checkdnsrr(\'gmail.com\', \'MX\'));"
openssl s_client -connect smtp.example.com:465 -servername smtp.example.com </dev/null',
        ),
      ),
    ),
    8 =>
    array (
      'id' => 'installation-verification',
      'title' => 'Comprobación posterior a la instalación',
      'paragraphs' =>
      array (
        0 => 'Antes de entregar el sistema a los usuarios, compruebe la configuración desde la línea de comandos, abra la página de acceso e inicie sesión con el primer administrador. Cree un cliente y un contacto de prueba, cargue archivos CSV y XLSX pequeños, realice una exportación y compruebe la persistencia de la sesión y el cierre de sesión.',
        1 => 'Compruebe por separado la API mediante HTTPS: una petición sin clave debe devolver 401 y otra con una clave de prueba y el scope necesario debe devolver un JSON correcto. Pruebe una importación grande dentro de los límites configurados, el envío manual del informe, la búsqueda de empresas mediante Gemini y la comprobación MX del correo. Después de las pruebas, elimine los registros de prueba y revoque la clave de API utilizada.',
        2 => 'Si el servidor devuelve 404 en todas las páginas salvo la principal, el problema suele estar en try_files. Un error 502 indica que Nginx no puede conectarse a PHP-FPM o que se ha especificado un socket incorrecto. Los errores 500 se diagnostican mediante storage/app.log, el registro de PHP-FPM y /var/log/nginx/contactcore.error.log. Los errores al cargar archivos suelen estar relacionados con límites distintos en Nginx y PHP o con los permisos de storage.',
      ),
      'examples' =>
      array (
        0 =>
        array (
          'title' => 'Comprobación técnica',
          'code' => 'cd /var/www/contactcore
composer check-platform-reqs --no-dev
php8.5 -l public_html/index.php
sudo nginx -t
sudo php-fpm8.5 -t
sudo systemctl --no-pager --full status nginx php8.5-fpm mysql cron
curl -I https://crm.example.com/login
tail -n 100 storage/app.log',
        ),
      ),
    ),
    9 =>
    array (
      'id' => 'installation-maintenance',
      'title' => 'Copias de seguridad, actualizaciones y supervisión',
      'paragraphs' =>
      array (
        0 => 'La copia de seguridad debe incluir la base de datos MySQL, los archivos de configuración en uso y los datos necesarios de storage, especialmente los archivos de importación originales si deben conservarse. Las copias deben cifrarse, trasladarse a otro servidor o almacenamiento de objetos y someterse periódicamente a pruebas de restauración. Normalmente no es necesario restaurar las sesiones ni los tokens temporales.',
        1 => 'Antes de actualizar, haga una copia de seguridad de la base de datos y la configuración. Despliegue el código nuevo, ejecute composer install con el composer.lock existente, vuelva a comprobar los requisitos de la plataforma y reinicie PHP-FPM. No utilice database/schema.sql como actualización. Ejecute en orden los archivos SQL todavía no aplicados de database/migrations según las instrucciones de la versión; aún no existe un migration runner automático.',
        2 => 'Supervise el espacio libre, la caducidad del certificado TLS, el estado de Nginx/PHP-FPM/MySQL/cron, el crecimiento de storage/app.log y el registro del informe semanal. Configure la rotación de logs y avisos para las respuestas 5xx. Actualice periódicamente el sistema operativo y las dependencias de Composer, primero en un entorno de prueba y no directamente en el CRM de producción.',
      ),
      'examples' =>
      array (
        0 =>
        array (
          'title' => 'Copia de seguridad manual de la base de datos',
          'code' => 'sudo install -d -m 700 /var/backups/contactcore
mysqldump --single-transaction --quick --routines --triggers \\
  -u contactcore -p crm | gzip \\
  > /var/backups/contactcore/crm-$(date +%F-%H%M).sql.gz',
        ),
        1 =>
        array (
          'title' => 'Actualización habitual del código',
          'code' => 'cd /var/www/contactcore
# Despliegue primero una versión nueva del código que ya se haya comprobado.
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
composer check-platform-reqs --no-dev
sudo systemctl restart php8.5-fpm
sudo nginx -t && sudo systemctl reload nginx',
        ),
        2 =>
        array (
          'title' => '/etc/logrotate.d/contactcore',
          'code' => '/var/www/contactcore/storage/*.log {
    weekly
    rotate 12
    compress
    delaycompress
    missingok
    notifempty
    copytruncate
    create 0660 deploy www-data
}',
        ),
      ),
    ),
  ),
);
