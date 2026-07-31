<?php

return array (
  'title' => 'Server',
  'description' => 'Preparing the operating system, PHP-FPM, Nginx, network, and HTTPS for ContactCore.',
  'icon' => 'ph-arrow-elbow-down-right',
  'sections' => array (
    array (
      'id' => 'server-stack',
      'title' => 'Server stack and required versions',
      'paragraphs' => array (
        'The following instructions describe installation on a clean Ubuntu 24.04 LTS server with Nginx, PHP-FPM, and MySQL. Another modern Linux distribution is also suitable if it provides the required PHP version, although package names and configuration paths may differ. A 64-bit system, two CPU cores, 2 GB of RAM, and separate disk capacity for the database, logs, and uploaded files are recommended for production. 20 GB is sufficient for a small database, but actual capacity should be calculated from the number of contacts and imports and the backup retention period.',
        'The application supports PHP 8.4 and 8.5. The entry point deliberately does not load Composer dependencies on an unsupported PHP version. PHP must be installed both for FPM, which serves the website, and for CLI, which runs maintenance commands and the weekly report. Both environments must use the same major PHP version and the same set of extensions.',
        'The database must support InnoDB, utf8mb4, foreign keys, JSON fields, FULLTEXT indexes, and actual enforcement of CHECK constraints. MySQL 8.0.16 or newer, or a currently supported MariaDB version with CHECK enforcement enabled, is required. The web server must route all unknown paths to public_html/index.php, accept GET, POST, PATCH, and DELETE, serve HTTPS, and allow CSV/XLSX uploads.',
      ),
    ),
    array (
      'id' => 'server-base-system',
      'title' => 'Preparing the operating system, DNS, and network',
      'paragraphs' => array (
        'Before installing the application, create a dedicated deployment account, update the system, and configure SSH-key authentication. The site root must point only to public_html, so config, vendor, storage, database, and the application source code are never served directly by the web server.',
        'Create an A record for the CRM domain pointing to the server’s IPv4 address; add an AAAA record if IPv6 is used. Before issuing the certificate, verify that the domain already opens from this server. Only SSH, HTTP, and HTTPS are required inbound. MySQL should not be exposed to the internet when the database is on the same server.',
        'The server requires outbound connections: DNS over UDP/TCP 53 for email MX checks, HTTPS over TCP 443 for Gemini and Composer updates, and the SMTP provider’s port—usually 465 for SMTPS or 587 for STARTTLS. Client browsers require access to cdn.jsdelivr.net, from which Phosphor Icons are currently loaded.',
      ),
      'examples' => array (
        array (
          'title' => 'Basic Ubuntu/Debian preparation',
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
    array (
      'id' => 'server-php',
      'title' => 'PHP and system extensions',
      'paragraphs' => array (
        'PHP-FPM, PHP CLI, and the pdo_mysql, mbstring, fileinfo, dom, simplexml, xml, xmlreader, xmlwriter, zip, zlib, gd, iconv, ctype, filter, and hash extensions are required. The curl extension is recommended for external HTTP requests. OpenSSL is required for secure SMTP and HTTPS. ctype, filter, hash, iconv, fileinfo, zlib, and OpenSSL are usually included in the base PHP packages, but their presence should still be verified.',
        'Guzzle sends requests to Gemini through cURL and can fall back to PHP streams when the extension is absent. PDO MySQL is required for the database; fileinfo validates imported file types; and DOM, libxml, XMLReader, and ZIP are required by OpenSpout for streaming XLSX reads and writes. checkdnsrr, set_time_limit, random_bytes, password_hash, flock, and file functions must not be disabled by disable_functions.',
      ),
      'examples' => array (
        array (
          'title' => 'Installing PHP 8.5 and extensions',
          'code' => 'sudo apt install -y \\
  php8.5-fpm php8.5-cli php8.5-mysql php8.5-curl \\
  php8.5-mbstring php8.5-xml php8.5-zip php8.5-gd

php8.5 --version
php8.5 -m | grep -E \'curl|dom|fileinfo|gd|mbstring|PDO|pdo_mysql|SimpleXML|xmlreader|xmlwriter|zip\'
sudo systemctl enable --now php8.5-fpm nginx mysql cron',
        ),
      ),
    ),
    array (
      'id' => 'server-php-config',
      'title' => 'PHP-FPM settings and limits',
      'paragraphs' => array (
        'OpenSpout processes XLSX as a stream, so imports do not load the entire workbook into memory. A reasonable starting point for a typical installation is memory_limit 512M, upload_max_filesize 25M, and post_max_size 32M. post_max_size must exceed upload_max_filesize, and the Nginx limit must be no smaller than either. If especially large files are expected, increase upload limits consistently and monitor temporary disk capacity.',
        'Imports run synchronously and remove the PHP time limit inside the application, so Nginx fastcgi_read_timeout must be sufficiently large. In production, disable display_errors, enable log_errors, set the time zone, and configure secure session-cookie parameters. These changes are required in the FPM configuration; also check the separate CLI php.ini used by cron.',
      ),
      'examples' => array (
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
        array (
          'title' => 'Applying and checking the settings',
          'code' => 'sudo cp /etc/php/8.5/fpm/conf.d/99-contactcore.ini \\
  /etc/php/8.5/cli/conf.d/99-contactcore.ini
sudo php-fpm8.5 -t
sudo systemctl restart php8.5-fpm
php8.5 --ini
php8.5 -r "echo ini_get(\'memory_limit\'), PHP_EOL;"',
        ),
      ),
    ),
    array (
      'id' => 'server-nginx',
      'title' => 'Configuring Nginx and routing',
      'paragraphs' => array (
        'The document root must be public_html. The try_files rule routes virtual paths such as /contacts, /help/technical/server, and /api/v1/contacts to index.php while leaving CSS, JavaScript, import templates, and the favicon as ordinary static files. Without this rule, only direct requests to index.php will work.',
        'The configuration must pass only the existing index.php file to PHP, allow every API HTTP method, and avoid adding Basic Auth in front of /api when external forms call it directly. The application uses its own keys for API authentication. It adds security headers itself, but HSTS is best enabled in Nginx after HTTPS has been configured.',
      ),
      'examples' => array (
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

    location ~ \\.php$ {
        try_files $uri =404;
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.5-fpm.sock;
        fastcgi_read_timeout 300s;
    }

    location ~ /\\.(?!well-known) {
        deny all;
    }
}',
        ),
        array (
          'title' => 'Enabling the site',
          'code' => 'sudo ln -s /etc/nginx/sites-available/contactcore \\
  /etc/nginx/sites-enabled/contactcore
sudo rm /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx',
        ),
      ),
    ),
    array (
      'id' => 'server-tls',
      'title' => 'HTTPS and certificates',
      'paragraphs' => array (
        'A production CRM should be accessible only over HTTPS because it transmits passwords, contacts, and API keys. Once DNS points to the server and the HTTP configuration responds, issue a certificate. Certbot can add the HTTPS server and HTTP redirect automatically.',
        'After successful issuance, enable HSTS only for a domain that already works reliably over HTTPS. Do not enable preload or includeSubDomains without understanding the consequences. Make sure config/app.php also contains an https:// address.',
      ),
      'examples' => array (
        array (
          'title' => 'Let’s Encrypt certificate',
          'code' => 'sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d crm.example.com --redirect
sudo certbot renew --dry-run',
        ),
      ),
    ),
  ),
);
