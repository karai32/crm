<?php

return array (
  'title' => 'Сервер',
  'description' => 'Подготовка операционной системы, PHP-FPM, Nginx, сети и HTTPS для работы ContactCore.',
  'icon' => 'ph-arrow-elbow-down-right',
  'sections' => 
  array (
    0 => 
    array (
      'id' => 'server-stack',
      'title' => 'Состав сервера и обязательные версии',
      'paragraphs' => 
      array (
        0 => 'Ниже описана установка на чистый сервер Ubuntu 24.04 LTS с Nginx, PHP-FPM и MySQL. Другой современный Linux также подходит, если в нём установлен PHP нужной версии, однако названия пакетов и пути к конфигурации могут отличаться. Для рабочего окружения рекомендуется 64-разрядная система, два ядра процессора, 2 ГБ оперативной памяти и отдельное дисковое пространство для базы, журналов и загружаемых файлов. Для небольшой базы достаточно 20 ГБ, но фактический объём следует рассчитывать по количеству контактов, импортов и сроку хранения резервных копий.',
        1 => 'Приложению требуется PHP 8.4 или новее. В точке входа Composer-зависимости намеренно не подключаются на более старой версии PHP. PHP должен быть установлен одновременно для FPM, который обслуживает сайт, и для CLI, который запускает служебные команды и еженедельный отчёт. Обе среды должны использовать одну основную версию PHP и одинаковый набор расширений.',
        2 => 'База данных должна поддерживать InnoDB, utf8mb4, внешние ключи, JSON-поля, FULLTEXT-индексы и реально применять CHECK-ограничения. Требуется MySQL 8.0.16 или новее либо актуальная поддерживаемая версия MariaDB с включённой проверкой CHECK. Веб-сервер должен передавать все неизвестные маршруты в public_html/index.php, принимать методы GET, POST, PATCH и DELETE, обслуживать HTTPS и разрешать загрузку CSV/XLSX.',
      ),
    ),
    1 => 
    array (
      'id' => 'server-base-system',
      'title' => 'Подготовка операционной системы, DNS и сети',
      'paragraphs' => 
      array (
        0 => 'До установки приложения создайте отдельную учётную запись для развёртывания, обновите систему и настройте вход по SSH-ключу. Корень сайта должен указывать только на каталог public_html, поэтому config, vendor, storage, database и исходный код приложения не должны отдаваться веб-сервером напрямую.',
        1 => 'Создайте DNS-запись A для домена CRM, указывающую на IPv4-адрес сервера; при использовании IPv6 добавьте AAAA. До выпуска сертификата проверьте, что домен уже открывается с этого сервера. Во входящем направлении нужны только SSH, HTTP и HTTPS. MySQL не следует публиковать в интернете, если база находится на том же сервере.',
        2 => 'Серверу нужны исходящие соединения: DNS по UDP/TCP 53 для проверки MX-записей почты, HTTPS по TCP 443 для Gemini и обновления Composer, а также порт SMTP-провайдера — обычно 465 для SMTPS или 587 для STARTTLS. Клиентским браузерам необходим доступ к cdn.jsdelivr.net, откуда сейчас загружаются Phosphor Icons.',
      ),
      'examples' => 
      array (
        0 => 
        array (
          'title' => 'Базовая подготовка Ubuntu/Debian',
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
      'title' => 'PHP и системные расширения',
      'paragraphs' => 
      array (
        0 => 'Обязательны PHP-FPM, PHP CLI и расширения pdo_mysql, mbstring, fileinfo, dom, simplexml, xml, xmlreader, xmlwriter, zip, zlib, gd, iconv, ctype, filter и hash. Для внешних HTTP-запросов рекомендуется расширение curl. OpenSSL нужен для защищённого SMTP и HTTPS. Расширения ctype, filter, hash, iconv, fileinfo, zlib и OpenSSL обычно входят в базовые пакеты PHP, но их наличие всё равно следует проверить.',
        1 => 'Guzzle выполняет запросы к Gemini через cURL, а при отсутствии расширения может использовать PHP streams. PDO MySQL нужен для базы данных, fileinfo — для проверки типа импортируемого файла, а DOM, libxml, XMLReader и ZIP требуются OpenSpout для потокового чтения и создания XLSX. Функции checkdnsrr, set_time_limit, random_bytes, password_hash, flock и работа с файлами не должны быть отключены директивой disable_functions.',
      ),
      'examples' => 
      array (
        0 => 
        array (
          'title' => 'Установка PHP 8.4 и расширений',
          'code' => 'sudo apt install -y \\
  php8.4-fpm php8.4-cli php8.4-mysql php8.4-curl \\
  php8.4-mbstring php8.4-xml php8.4-zip php8.4-gd

php8.4 --version
php8.4 -m | grep -E \'curl|dom|fileinfo|gd|mbstring|PDO|pdo_mysql|SimpleXML|xmlreader|xmlwriter|zip\'
sudo systemctl enable --now php8.4-fpm nginx mysql cron',
        ),
      ),
    ),
    3 => 
    array (
      'id' => 'server-php-config',
      'title' => 'Настройки PHP-FPM и лимиты',
      'paragraphs' => 
      array (
        0 => 'OpenSpout обрабатывает XLSX потоково, поэтому импорт не загружает всю книгу в память. Для обычной установки разумная отправная точка — memory_limit 512M, upload_max_filesize 25M и post_max_size 32M. post_max_size должен быть больше upload_max_filesize, а лимит Nginx — не меньше обоих. Если предполагаются особенно большие файлы, лимиты загрузки следует увеличивать согласованно и контролировать временное дисковое пространство.',
        1 => 'Обработка импорта выполняется синхронно и внутри приложения снимает PHP-лимит времени, поэтому fastcgi_read_timeout Nginx должен быть достаточно большим. В рабочем окружении выключите display_errors, включите log_errors, установите часовой пояс и безопасные параметры сессионных cookie. Изменения нужны в конфигурации FPM; для cron проверьте также отдельный php.ini CLI.',
      ),
      'examples' => 
      array (
        0 => 
        array (
          'title' => '/etc/php/8.4/fpm/conf.d/99-contactcore.ini',
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
          'title' => 'Применение и проверка настроек',
          'code' => 'sudo cp /etc/php/8.4/fpm/conf.d/99-contactcore.ini \\
  /etc/php/8.4/cli/conf.d/99-contactcore.ini
sudo php-fpm8.4 -t
sudo systemctl restart php8.4-fpm
php8.4 --ini
php8.4 -r "echo ini_get(\'memory_limit\'), PHP_EOL;"',
        ),
      ),
    ),
    4 => 
    array (
      'id' => 'server-nginx',
      'title' => 'Настройка Nginx и маршрутизации',
      'paragraphs' => 
      array (
        0 => 'Document root должен быть равен public_html. Правило try_files направляет виртуальные маршруты вроде /contacts, /help/technical/server и /api/v1/contacts в index.php, но оставляет CSS, JavaScript, шаблоны импорта и favicon обычными статическими файлами. Без этого правила будут работать только запросы к самому index.php.',
        1 => 'Конфигурация должна передавать PHP только реально существующему index.php, разрешать все HTTP-методы API и не добавлять Basic Auth поверх /api, если внешние формы обращаются к нему напрямую. Для API-аутентификации приложение использует собственные ключи. Заголовки безопасности приложение добавляет само, но HSTS лучше включить на уровне Nginx после настройки HTTPS.',
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

    location ~ \\.php$ {
        try_files $uri =404;
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_read_timeout 300s;
    }

    location ~ /\\.(?!well-known) {
        deny all;
    }
}',
        ),
        1 => 
        array (
          'title' => 'Включение сайта',
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
      'title' => 'HTTPS и сертификат',
      'paragraphs' => 
      array (
        0 => 'Рабочую CRM следует открывать только по HTTPS: через неё передаются пароли, контакты и ключи API. После того как DNS указывает на сервер и HTTP-конфигурация отвечает, выпустите сертификат. Certbot может сам добавить HTTPS-сервер и перенаправление с HTTP.',
        1 => 'После успешного выпуска включите HSTS только для домена, который уже стабильно работает по HTTPS. Не включайте preload и includeSubDomains без понимания последствий. Убедитесь, что config/app.php также содержит адрес с https://.',
      ),
      'examples' => 
      array (
        0 => 
        array (
          'title' => 'Сертификат Let’s Encrypt',
          'code' => 'sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d crm.example.com --redirect
sudo certbot renew --dry-run',
        ),
      ),
    ),
  ),
);
