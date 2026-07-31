<?php

return array (
  'title' => 'Servidor',
  'description' => 'Preparación del sistema operativo, PHP-FPM, Nginx, la red y HTTPS para ejecutar ContactCore.',
  'icon' => 'ph-arrow-elbow-down-right',
  'sections' =>
  array (
    0 =>
    array (
      'id' => 'server-stack',
      'title' => 'Componentes del servidor y versiones obligatorias',
      'paragraphs' =>
      array (
        0 => 'A continuación se describe la instalación en un servidor limpio con Ubuntu 24.04 LTS, Nginx, PHP-FPM y MySQL. También puede utilizarse otra distribución Linux moderna si dispone de la versión de PHP necesaria, aunque los nombres de los paquetes y las rutas de configuración pueden variar. Para un entorno de producción se recomienda un sistema de 64 bits, dos núcleos de procesador, 2 GB de memoria RAM y espacio en disco independiente para la base de datos, los registros y los archivos cargados. Para una base de datos pequeña son suficientes 20 GB, pero el volumen real debe calcularse en función del número de contactos e importaciones y del periodo de conservación de las copias de seguridad.',
        1 => 'La aplicación admite PHP 8.4 y 8.5. El punto de entrada omite deliberadamente las dependencias de Composer cuando se utiliza una versión de PHP no compatible. PHP debe instalarse tanto para FPM, que sirve el sitio web, como para CLI, que ejecuta los comandos administrativos y el informe semanal. Ambos entornos deben utilizar la misma versión principal de PHP y el mismo conjunto de extensiones.',
        2 => 'La base de datos debe admitir InnoDB, utf8mb4, claves foráneas, campos JSON, índices FULLTEXT y aplicar realmente las restricciones CHECK. Se requiere MySQL 8.0.16 o posterior, o una versión actual y compatible de MariaDB con la comprobación CHECK activada. El servidor web debe dirigir todas las rutas desconocidas a public_html/index.php, aceptar los métodos GET, POST, PATCH y DELETE, servir el sitio mediante HTTPS y permitir la carga de archivos CSV/XLSX.',
      ),
    ),
    1 =>
    array (
      'id' => 'server-base-system',
      'title' => 'Preparación del sistema operativo, DNS y red',
      'paragraphs' =>
      array (
        0 => 'Antes de instalar la aplicación, cree una cuenta independiente para el despliegue, actualice el sistema y configure el acceso mediante una clave SSH. La raíz del sitio debe apuntar exclusivamente al directorio public_html, de modo que config, vendor, storage, database y el código fuente de la aplicación no sean servidos directamente por el servidor web.',
        1 => 'Cree un registro DNS de tipo A para el dominio del CRM que apunte a la dirección IPv4 del servidor; si utiliza IPv6, añada también un registro AAAA. Antes de emitir el certificado, compruebe que el dominio ya se abre desde ese servidor. Para el tráfico entrante solo son necesarios SSH, HTTP y HTTPS. MySQL no debe exponerse a Internet si la base de datos se encuentra en el mismo servidor.',
        2 => 'El servidor necesita conexiones salientes: DNS mediante UDP/TCP 53 para comprobar los registros MX del correo, HTTPS mediante TCP 443 para Gemini y las actualizaciones de Composer, y el puerto del proveedor SMTP, normalmente 465 para SMTPS o 587 para STARTTLS. Los navegadores de los clientes necesitan acceso a cdn.jsdelivr.net, desde donde se cargan actualmente los Phosphor Icons.',
      ),
      'examples' =>
      array (
        0 =>
        array (
          'title' => 'Preparación básica de Ubuntu/Debian',
          'code' => 'sudo apt update
sudo apt full-upgrade -y
sudo timedatectl set-timezone Europe/Madrid

sudo apt install -y nginx mysql-server cron unzip curl ca-certificates openssl ufw
sudo adduser deploy
sudo usermod -aG www-data deploy
sudo ufw allow OpenSSH
sudo ufw allow \'Nginx Full\'
sudo ufw enable',
        ),
      ),
    ),
    2 =>
    array (
      'id' => 'server-php',
      'title' => 'PHP y extensiones del sistema',
      'paragraphs' =>
      array (
        0 => 'Son obligatorios PHP-FPM, PHP CLI y las extensiones pdo_mysql, mbstring, fileinfo, dom, simplexml, xml, xmlreader, xmlwriter, zip, zlib, gd, iconv, ctype, filter y hash. Para las peticiones HTTP externas se recomienda la extensión curl. OpenSSL es necesario para SMTP seguro y HTTPS. Las extensiones ctype, filter, hash, iconv, fileinfo, zlib y OpenSSL suelen estar incluidas en los paquetes básicos de PHP, pero aun así conviene comprobar su presencia.',
        1 => 'Guzzle realiza las peticiones a Gemini mediante cURL y, si la extensión no está disponible, puede utilizar streams de PHP. PDO MySQL es necesario para la base de datos; fileinfo, para comprobar el tipo de los archivos importados; y DOM, libxml, XMLReader y ZIP son necesarios para que OpenSpout lea y genere archivos XLSX mediante streaming. Las funciones checkdnsrr, set_time_limit, random_bytes, password_hash, flock y las funciones de archivos no deben estar desactivadas mediante la directiva disable_functions.',
      ),
      'examples' =>
      array (
        0 =>
        array (
          'title' => 'Instalación de PHP 8.5 y sus extensiones',
          'code' => 'sudo apt install -y \\
  php8.5-fpm php8.5-cli php8.5-mysql php8.5-curl \\
  php8.5-mbstring php8.5-xml php8.5-zip php8.5-gd

php8.5 --version
php8.5 -m | grep -E \'curl|dom|fileinfo|gd|mbstring|PDO|pdo_mysql|SimpleXML|xmlreader|xmlwriter|zip\'
sudo systemctl enable --now php8.5-fpm nginx mysql cron',
        ),
      ),
    ),
    3 =>
    array (
      'id' => 'server-php-config',
      'title' => 'Configuración de PHP-FPM y límites',
      'paragraphs' =>
      array (
        0 => 'OpenSpout procesa los archivos XLSX mediante streaming, por lo que una importación no carga todo el libro en memoria. Para una instalación habitual, un punto de partida razonable es memory_limit 512M, upload_max_filesize 25M y post_max_size 32M. post_max_size debe ser mayor que upload_max_filesize y el límite de Nginx no debe ser inferior a ninguno de ellos. Si se esperan archivos especialmente grandes, los límites de carga deben aumentarse de forma coordinada y hay que controlar el espacio temporal en disco.',
        1 => 'Las importaciones se procesan de forma síncrona y la aplicación elimina el límite de tiempo de PHP durante el proceso, por lo que fastcgi_read_timeout de Nginx debe ser suficientemente amplio. En producción, desactive display_errors, active log_errors, defina la zona horaria y configure opciones seguras para las cookies de sesión. Estos cambios deben aplicarse a la configuración de FPM; para las tareas cron, compruebe también el php.ini independiente de CLI.',
      ),
      'examples' =>
      array (
        0 =>
        array (
          'title' => '/etc/php/8.5/fpm/conf.d/99-contactcore.ini',
          'code' => 'date.timezone = Europe/Madrid
memory_limit = 512M
upload_max_filesize = 25M
post_max_size = 32M
max_execution_time = 300
max_input_time = 300
max_input_vars = 3000

display_errors = Off
log_errors = On
expose_php = Off

session.cookie_httponly = 1
session.cookie_secure = 1
session.cookie_samesite = Lax
session.use_strict_mode = 1',
        ),
        1 =>
        array (
          'title' => 'Aplicación y comprobación de los ajustes',
          'code' => 'sudo cp /etc/php/8.5/fpm/conf.d/99-contactcore.ini \\
  /etc/php/8.5/cli/conf.d/99-contactcore.ini
sudo php-fpm8.5 -t
sudo systemctl restart php8.5-fpm
php8.5 --ini
php8.5 -r "echo ini_get(\'memory_limit\'), PHP_EOL;"',
        ),
      ),
    ),
    4 =>
    array (
      'id' => 'server-nginx',
      'title' => 'Configuración de Nginx y del enrutamiento',
      'paragraphs' =>
      array (
        0 => 'El document root debe ser public_html. La regla try_files dirige rutas virtuales como /contacts, /help/technical/server y /api/v1/contacts a index.php, pero deja el CSS, JavaScript, las plantillas de importación y el favicon como archivos estáticos normales. Sin esta regla, solo funcionarían las peticiones dirigidas al propio index.php.',
        1 => 'La configuración debe enviar a PHP únicamente el archivo index.php existente, permitir todos los métodos HTTP de la API y no añadir Basic Auth sobre /api si los formularios externos acceden directamente a esta ruta. La aplicación utiliza sus propias claves para autenticar la API. La propia aplicación añade las cabeceras de seguridad, pero es preferible activar HSTS en Nginx después de configurar HTTPS.',
      ),
      'examples' =>
      array (
        0 =>
        array (
          'title' => '/etc/nginx/sites-available/contactcore',
          'code' => 'server {
    listen 80;
    listen [::]:80;
    server_name crm.example.com;

    root /var/www/contactcore/public_html;
    index index.php;
    charset utf-8;
    client_max_body_size 32m;

    access_log /var/log/nginx/contactcore.access.log;
    error_log  /var/log/nginx/contactcore.error.log;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        try_files $uri =404;
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.5-fpm.sock;
        fastcgi_read_timeout 300s;
    }

    location ~ /\.(?!well-known) {
        deny all;
    }
}',
        ),
        1 =>
        array (
          'title' => 'Activación del sitio',
          'code' => 'sudo ln -s /etc/nginx/sites-available/contactcore \\
  /etc/nginx/sites-enabled/contactcore
sudo rm /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx',
        ),
      ),
    ),
    5 =>
    array (
      'id' => 'server-tls',
      'title' => 'HTTPS y certificado',
      'paragraphs' =>
      array (
        0 => 'Un CRM en producción solo debe abrirse mediante HTTPS, ya que transmite contraseñas, contactos y claves de API. Cuando el DNS apunte al servidor y la configuración HTTP responda, emita el certificado. Certbot puede añadir automáticamente el servidor HTTPS y la redirección desde HTTP.',
        1 => 'Después de emitir correctamente el certificado, active HSTS solo para un dominio que ya funcione de forma estable mediante HTTPS. No active preload ni includeSubDomains sin comprender sus consecuencias. Compruebe también que config/app.php contenga una dirección que empiece por https://.',
      ),
      'examples' =>
      array (
        0 =>
        array (
          'title' => 'Certificado de Let’s Encrypt',
          'code' => 'sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d crm.example.com --redirect
sudo certbot renew --dry-run',
        ),
      ),
    ),
  ),
);
