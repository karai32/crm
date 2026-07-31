<?php

return array (
  'title' => 'Установка',
  'description' => 'Установка ContactCore, подготовка базы и конфигурации, первый запуск и проверка платформы.',
  'icon' => 'ph-arrow-elbow-down-right',
  'sections' => 
  array (
    0 => 
    array (
      'id' => 'installation-overview',
      'title' => 'Перед установкой',
      'paragraphs' => 
      array (
        0 => 'Эта инструкция продолжает раздел «Сервер». Перед началом должны быть готовы домен, HTTPS-сервер с Nginx, PHP-FPM 8.4 или 8.5, MySQL, Composer и все перечисленные PHP-расширения. Пользователь deploy должен иметь возможность размещать код, а www-data — запускать приложение и записывать рабочие данные в storage.',
        1 => 'Установка выполняется последовательно: разместить код и зависимости, создать базу, заполнить конфигурацию, настроить права, создать первого администратора, проверить внешние сервисы и cron, затем пройти итоговую проверку. Команды ниже используют каталог /var/www/contactcore, базу crm, пользователя deploy и группу www-data; при другой структуре все пути и имена нужно менять согласованно.',
      ),
    ),
    1 => 
    array (
      'id' => 'installation-deploy',
      'title' => 'Размещение проекта, Composer и npm',
      'paragraphs' => 
      array (
        0 => 'Разместите весь проект, а не только public_html, например в /var/www/contactcore. Публичным каталогом останется /var/www/contactcore/public_html. Исходный код можно получить из закрытого репозитория или загрузить архивом; каталог .git на рабочем сервере не обязателен.',
        1 => 'PHP-библиотеки устанавливаются из composer.lock командой composer install. Она добавляет Illuminate Database, Guzzle, PHPMailer, OpenSpout и их зависимости в vendor. На сервере следует использовать установку без пакетов разработки и с оптимизированным автозагрузчиком. Не выполняйте composer update во время обычного развёртывания: эта команда меняет зафиксированные версии библиотек.',
        2 => 'npm install выполнять не нужно. В проекте нет package.json, сборщика и Node.js-зависимостей: CSS и JavaScript уже находятся в public_html/assets и отдаются как готовые файлы. Node.js и npm можно вообще не устанавливать.',
      ),
      'examples' => 
      array (
        0 => 
        array (
          'title' => 'Установка Composer и зависимостей проекта',
          'code' => 'sudo apt install -y composer
sudo mkdir -p /var/www/contactcore
sudo chown -R deploy:www-data /var/www/contactcore

# Скопируйте содержимое проекта в /var/www/contactcore, затем:
cd /var/www/contactcore
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
composer check-platform-reqs --no-dev',
        ),
      ),
    ),
    2 => 
    array (
      'id' => 'installation-database',
      'title' => 'Создание и настройка базы данных',
      'paragraphs' => 
      array (
        0 => 'Создайте отдельную базу и отдельного пользователя MySQL только для ContactCore. Используйте utf8mb4. Приложению нужны права чтения и изменения данных, создания индексов и работы с таблицами. Не используйте учётную запись root в config/database.php и не открывайте порт 3306 наружу без отдельной необходимости.',
  1 => 'Файл database/schema.sql предназначен для первой установки. В его начале указаны база crm и команды DROP TABLE. Поэтому имя базы в config/database.php должно совпадать с именем в схеме, либо первые команды файла нужно изменить до импорта. Никогда не запускайте этот файл повторно над рабочей базой: он удалит существующие данные. Изменения схемы существующей базы администратор подготавливает и выполняет вручную через SQL-клиент после резервного копирования. В проекте нет системы миграций, последовательных файлов обновления и таблицы учёта версий.',
      ),
      'examples' => 
      array (
        0 => 
        array (
          'title' => 'База и пользователь приложения',
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
          'title' => 'Первичное применение схемы',
          'code' => 'cd /var/www/contactcore
sudo mysql < database/schema.sql
mysql -u contactcore -p crm -e "SHOW TABLES;"',
        ),
      ),
    ),
    3 => 
    array (
      'id' => 'installation-config',
      'title' => 'Файлы конфигурации и секреты',
      'paragraphs' => 
      array (
        0 => 'Скопируйте четыре файла с суффиксом .example.php в рабочие файлы без .example. Реальные файлы уже исключены из Git и не должны попадать в репозиторий, архивы для публичной раздачи или журналы. Доступ на чтение нужен пользователю PHP-FPM и пользователю, от которого запускается cron.',
        1 => 'В database.php задаются хост, имя базы, пользователь, пароль и charset. В app.php параметр base_url должен содержать внешний HTTPS-адрес без завершающего слеша; он используется для ссылок в еженедельном отчёте. В mail.php задаются отправитель и SMTP. Значение smtp_secure = ssl означает SMTPS, обычно порт 465; любое другое используемое приложением значение включает STARTTLS, обычно порт 587.',
        2 => 'В gemini.php хранится ключ Google Gemini. Он необходим только для ИИ-поиска компании по корпоративной почте; остальная CRM может работать без вызова этой функции. SMTP сейчас нужен прежде всего для еженедельных отчётов. Код двухфакторного входа в приложении присутствует, но его обязательный вызов временно отключён.',
      ),
      'examples' => 
      array (
        0 => 
        array (
          'title' => 'Создание рабочих конфигураций',
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
          'title' => 'Минимальный config/app.php',
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
      'title' => 'Каталоги, владелец и права доступа',
      'paragraphs' => 
      array (
        0 => 'PHP должен иметь право записи в storage. Там создаются сессии, токены «Запомнить меня», ограничитель попыток входа, загруженные файлы импорта и app.log. Кроме того, пользователь PHP-FPM должен иметь доступ к системному временному каталогу, куда PHP сначала принимает загружаемые файлы. Каталог storage находится вне public_html и не должен быть доступен по URL. Остальной код приложения следует оставить только для чтения веб-пользователю.',
        1 => 'Не выдавайте всему проекту права 777. Для типовой установки владельцем кода может быть пользователь deploy, группой — www-data, а запись разрешается только группе в storage. setgid на каталоге storage сохраняет группу www-data у новых подкаталогов и файлов.',
      ),
      'examples' => 
      array (
        0 => 
        array (
          'title' => 'Безопасные права для приложения',
          'code' => 'cd /var/www/contactcore
sudo chown -R deploy:www-data .
sudo find . -type d -exec chmod 750 {} \\;
sudo find . -type f -exec chmod 640 {} \\;

sudo mkdir -p storage/sessions storage/remember storage/imports
sudo chown -R deploy:www-data storage
sudo find storage -type d -exec chmod 2770 {} \\;
sudo find storage -type f -exec chmod 660 {} \\;

sudo chown root:www-data config/*.php
sudo chmod 640 config/*.php',
        ),
      ),
    ),
    5 => 
    array (
      'id' => 'installation-first-admin',
      'title' => 'Создание первого администратора',
      'paragraphs' => 
      array (
        0 => 'Схема создаёт роли admin и user, но не создаёт учётную запись. До первого входа сформируйте хеш пароля через PHP и добавьте администратора непосредственно в базу. Не записывайте открытый пароль в SQL-файл и не используйте показанное ниже значение-заглушку.',
        1 => 'После входа остальных пользователей следует создавать через раздел «Пользователи». Первый пароль передайте администратору по защищённому каналу и замените, если он был доступен кому-либо ещё.',
      ),
      'examples' => 
      array (
        0 => 
        array (
          'title' => 'Хеширование пароля и добавление администратора',
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
      'title' => 'Cron для еженедельных отчётов',
      'paragraphs' => 
      array (
        0 => 'Скрипт bin/weekly-report.php собирает данные за последние семь дней и отправляет отчёт всем активным пользователям с ролью admin. Он использует config/database.php, config/mail.php и config/app.php. Запуск должен выполняться от пользователя www-data либо другого пользователя, который может читать конфигурацию, подключаться к базе и писать в storage.',
        1 => 'Сначала обязательно запустите команду вручную и убедитесь, что она заканчивается строкой с количеством отправленных писем и без ошибок в storage/app.log. Затем создайте системную cron-задачу. Пример ниже запускает отчёт каждый понедельник в 08:00 по часовому поясу сервера. Если сервер использует другой часовой пояс, измените расписание или настройте его через timedatectl.',
        2 => 'Cron использует PHP CLI, поэтому для него также должны быть установлены vendor-зависимости и расширение pdo_mysql. При смене версии PHP обновите путь к исполняемому файлу в cron. Не вызывайте скрипт через HTTP и не размещайте bin в public_html.',
      ),
      'examples' => 
      array (
        0 => 
        array (
          'title' => 'Ручная проверка отчёта',
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
          'title' => 'Установка и контроль cron-задачи',
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
      'title' => 'SMTP, Gemini и DNS-проверка почты',
      'paragraphs' => 
      array (
        0 => 'Для SMTP разрешите исходящее соединение к smtp_host и выбранному порту. Адрес from_email должен быть разрешён провайдером, а у домена желательно настроить SPF, DKIM и DMARC. Ошибки PHPMailer записываются в storage/app.log. Проверить отправку можно ручным запуском еженедельного отчёта или кнопкой отправки отчёта в настройках администратора.',
        1 => 'Для ИИ-инструмента сервер должен разрешать Guzzle выполнять HTTPS-запросы к generativelanguage.googleapis.com и иметь рабочие корневые сертификаты. Расширение cURL рекомендуется; без него Guzzle может использовать PHP streams. Ключ Gemini хранится только в config/gemini.php. Не добавляйте его в JavaScript, URL, Git или журнал ошибок.',
        2 => 'Проверка адресов электронной почты использует системный DNS-резолвер и функцию checkdnsrr для MX-записи домена. Если сервер, контейнер или firewall блокирует DNS, валидные адреса могут получать ошибочный результат. Проверка MX не требует открывать SMTP-соединение к почтовому ящику.',
      ),
      'examples' => 
      array (
        0 => 
        array (
          'title' => 'Проверка внешних соединений',
          'code' => 'curl -I https://generativelanguage.googleapis.com/
php8.5 -r "var_dump(checkdnsrr(\'gmail.com\', \'MX\'));"
openssl s_client -connect smtp.example.com:465 -servername smtp.example.com </dev/null',
        ),
      ),
    ),
    8 => 
    array (
      'id' => 'installation-verification',
      'title' => 'Проверка после установки',
      'paragraphs' => 
      array (
        0 => 'До передачи системы пользователям проверьте конфигурацию из командной строки, затем откройте страницу входа и войдите первым администратором. Создайте тестового клиента и контакт, загрузите небольшой CSV и XLSX, выполните экспорт, проверьте сохранение сессии и выход из системы.',
        1 => 'Отдельно проверьте API через HTTPS: запрос без ключа должен вернуть 401, а запрос с тестовым ключом и нужным scope — успешный JSON. Проверьте большой импорт в пределах настроенных лимитов, ручную отправку отчёта, поиск компании через Gemini и MX-проверку почты. После испытаний удалите тестовые записи и отзовите тестовый API-ключ.',
        2 => 'Если сервер отвечает 404 на все страницы, кроме главной, проблема обычно в try_files. Ошибка 502 означает, что Nginx не может подключиться к PHP-FPM или указан неверный сокет. Ошибка 500 диагностируется по storage/app.log, журналу PHP-FPM и /var/log/nginx/contactcore.error.log. Ошибка загрузки файла часто связана с несовпадающими лимитами Nginx и PHP либо с правами storage.',
      ),
      'examples' => 
      array (
        0 => 
        array (
          'title' => 'Техническая проверка',
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
      'title' => 'Резервные копии, обновления и наблюдение',
      'paragraphs' => 
      array (
        0 => 'Резервная копия должна включать базу MySQL, рабочие файлы config и необходимые данные storage, прежде всего исходные файлы импортов, если их требуется хранить. Копии следует шифровать, выносить на другой сервер или объектное хранилище и регулярно проверять восстановление. Сессии и временные токены обычно восстанавливать не требуется.',
  1 => 'Перед обновлением сделайте резервную копию базы и конфигурации. Разверните новый код, выполните composer install по существующему composer.lock, повторно проверьте platform requirements и перезапустите PHP-FPM. Не применяйте database/schema.sql как обновление. Если новая версия требует изменения схемы или данных, заранее подготовьте и проверьте нужные SQL-команды на копии базы, затем выполните их вручную на рабочей базе. Проект не запускает обновления БД автоматически.',
        2 => 'Контролируйте свободное место, срок действия TLS-сертификата, состояние Nginx/PHP-FPM/MySQL/cron, рост storage/app.log и журнал еженедельного отчёта. Настройте ротацию журналов и оповещения об ответах 5xx. Периодически обновляйте ОС и Composer-зависимости сначала в тестовом окружении, а не непосредственно на рабочей CRM.',
      ),
      'examples' => 
      array (
        0 => 
        array (
          'title' => 'Ручная резервная копия базы',
          'code' => 'sudo install -d -m 700 /var/backups/contactcore
mysqldump --single-transaction --quick --routines --triggers \\
  -u contactcore -p crm | gzip \\
  > /var/backups/contactcore/crm-$(date +%F-%H%M).sql.gz',
        ),
        1 => 
        array (
          'title' => 'Типовое обновление кода',
          'code' => 'cd /var/www/contactcore
# Сначала разместите проверенную новую версию кода.
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
