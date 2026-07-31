<?php

return [
    'title' => 'Installation',
    'description' => 'Installing ContactCore, preparing the database and configuration, first launch, and platform verification.',
    'icon' => 'ph-arrow-elbow-down-right',
    'sections' => [
        ['id' => 'installation-overview', 'title' => 'Before installation', 'paragraphs' => [
            'This guide continues the Server section. Before starting, prepare a domain, an HTTPS server with Nginx, PHP-FPM 8.4 or 8.5, MySQL, Composer, and all listed PHP extensions. The deploy user must be able to deploy code, while www-data must be able to run the application and write runtime data to storage.',
            'Install in sequence: deploy the code and dependencies, create the database, complete the configuration, set permissions, create the first administrator, verify external services and cron, and then perform the final checks. The commands below use /var/www/contactcore, the crm database, the deploy user, and the www-data group; change every related path and name consistently if your structure differs.',
        ]],
        ['id' => 'installation-deploy', 'title' => 'Deploying the project, Composer, and npm', 'paragraphs' => [
            'Deploy the entire project, not only public_html, for example to /var/www/contactcore. /var/www/contactcore/public_html remains the public directory. Source code can be obtained from a private repository or uploaded as an archive; a .git directory is not required on the production server.',
            'Install PHP libraries from composer.lock with composer install. This places Illuminate Database, Guzzle, PHPMailer, OpenSpout, and their dependencies in vendor. On the server, install without development packages and with the optimized autoloader. Do not run composer update during a normal deployment because it changes the locked library versions.',
            'There is no need to run npm install. The project has no package.json, bundler, or Node.js dependencies: CSS and JavaScript are already present in public_html/assets as ready-to-serve files. Node.js and npm do not need to be installed.',
        ], 'examples' => [[
            'title' => 'Installing Composer and project dependencies',
            'code' => <<<'SHELL'
sudo apt install -y composer
sudo mkdir -p /var/www/contactcore
sudo chown -R deploy:www-data /var/www/contactcore

# Copy the project contents to /var/www/contactcore, then run:
cd /var/www/contactcore
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
composer check-platform-reqs --no-dev
SHELL,
        ]]],
        ['id' => 'installation-database', 'title' => 'Creating and configuring the database', 'paragraphs' => [
            'Create a separate database and MySQL user exclusively for ContactCore. Use utf8mb4. The application needs permission to read and modify data, create indexes, and work with tables. Do not use the root account in config/database.php or expose port 3306 externally unless specifically required.',
            'database/schema.sql is intended for the initial installation. It specifies the crm database and DROP TABLE commands at the beginning. The database name in config/database.php must therefore match the schema, or the first commands in the file must be changed before import. Never run this file again over a production database: it deletes existing data. Updates use sequential SQL files from database/migrations; there is currently no automatic runner or version-tracking table.',
        ], 'examples' => [
            ['title' => 'Application database and user', 'code' => <<<'SQL'
sudo mysql

CREATE DATABASE crm
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE USER 'contactcore'@'localhost'
  IDENTIFIED BY 'REPLACE_WITH_A_LONG_RANDOM_PASSWORD';

GRANT SELECT, INSERT, UPDATE, DELETE ON crm.*
  TO 'contactcore'@'localhost';
FLUSH PRIVILEGES;
EXIT;
SQL],
            ['title' => 'Applying the schema for the first time', 'code' => <<<'SHELL'
cd /var/www/contactcore
sudo mysql < database/schema.sql
mysql -u contactcore -p crm -e "SHOW TABLES;"
SHELL],
        ]],
        ['id' => 'installation-config', 'title' => 'Configuration files and secrets', 'paragraphs' => [
            'Copy the four .example.php files to active files without .example. The real files are already excluded from Git and must not enter the repository, publicly distributed archives, or logs. Read access is required for the PHP-FPM user and the user that runs cron.',
            'database.php defines the host, database name, user, password, and charset. In app.php, base_url must contain the external HTTPS address without a trailing slash; it is used for links in the weekly report. mail.php defines the sender and SMTP. smtp_secure = ssl means SMTPS, usually on port 465; any other value used by the application enables STARTTLS, usually on port 587.',
            'gemini.php stores the Google Gemini key. It is required only for AI-based company lookup from business email; the rest of the CRM works without this function. SMTP is currently used mainly for weekly reports. Two-factor login code is present, but its mandatory invocation is temporarily disabled.',
        ], 'examples' => [
            ['title' => 'Creating active configuration files', 'code' => <<<'SHELL'
cd /var/www/contactcore
cp config/app.example.php config/app.php
cp config/database.example.php config/database.php
cp config/mail.example.php config/mail.php
cp config/gemini.example.php config/gemini.php

sudo chown root:www-data config/*.php
sudo chmod 640 config/*.php
SHELL],
            ['title' => 'Minimal config/app.php', 'code' => <<<'PHP'
<?php

return [
    'base_url' => 'https://crm.example.com',
];
PHP],
            ['title' => 'config/database.php', 'code' => <<<'PHP'
<?php

return [
    'host'     => 'localhost',
    'database' => 'crm',
    'user'     => 'contactcore',
    'password' => 'REPLACE_WITH_DATABASE_PASSWORD',
    'charset'  => 'utf8mb4',
];
PHP],
            ['title' => 'config/mail.php', 'code' => <<<'PHP'
<?php

return [
    'from_email'    => 'no-reply@example.com',
    'from_name'     => 'ContactCore',
    'smtp_host'     => 'smtp.example.com',
    'smtp_port'     => 465,
    'smtp_username' => 'no-reply@example.com',
    'smtp_password' => 'REPLACE_WITH_SMTP_PASSWORD',
    'smtp_secure'   => 'ssl',
];
PHP],
            ['title' => 'config/gemini.php', 'code' => <<<'PHP'
<?php

return [
    'api_key' => 'REPLACE_WITH_GEMINI_API_KEY',
];
PHP],
        ]],
        ['id' => 'installation-storage', 'title' => 'Directories, ownership, and permissions', 'paragraphs' => [
            'PHP must be able to write to storage. It contains sessions, remember-me tokens, login throttling, uploaded import files, and app.log. The PHP-FPM user also needs access to the system temporary directory where PHP initially receives uploaded files. storage is outside public_html and must not be accessible by URL. The rest of the application code should remain read-only for the web user.',
            'Do not grant 777 permissions to the entire project. In a typical installation, deploy owns the code, www-data is the group, and group write access is enabled only for storage. The setgid bit on storage keeps the www-data group on newly created subdirectories and files.',
        ], 'examples' => [[
            'title' => 'Secure application permissions',
            'code' => <<<'SHELL'
cd /var/www/contactcore
sudo chown -R deploy:www-data .
sudo find . -type d -exec chmod 750 {} \;
sudo find . -type f -exec chmod 640 {} \;

sudo mkdir -p storage/sessions storage/remember storage/imports
sudo chown -R deploy:www-data storage
sudo find storage -type d -exec chmod 2770 {} \;
sudo find storage -type f -exec chmod 660 {} \;

sudo chown root:www-data config/*.php
sudo chmod 640 config/*.php
SHELL,
        ]]],
        ['id' => 'installation-first-admin', 'title' => 'Creating the first administrator', 'paragraphs' => [
            'The schema creates the admin and user roles but does not create an account. Before the first login, generate a password hash with PHP and insert the administrator directly into the database. Do not store the plain password in an SQL file or use the placeholder shown below.',
            'After signing in, create all other users through the Users section. Send the initial password to the administrator through a secure channel and replace it if anyone else had access to it.',
        ], 'examples' => [[
            'title' => 'Hashing the password and adding an administrator',
            'code' => <<<'SHELL'
php8.5 -r "echo password_hash('REPLACE_WITH_STRONG_PASSWORD', PASSWORD_DEFAULT), PHP_EOL;"

mysql -u contactcore -p crm

INSERT INTO users (role_id, name, email, password_hash, is_active)
SELECT id, 'Administrator', 'admin@example.com',
       'PASTE_GENERATED_HASH_HERE', 1
FROM roles
WHERE name = 'admin';
SHELL,
        ]]],
        ['id' => 'installation-cron', 'title' => 'Cron for weekly reports', 'paragraphs' => [
            'bin/weekly-report.php collects data for the previous seven days and sends a report to every active user with the admin role. It uses config/database.php, config/mail.php, and config/app.php. Run it as www-data or another user that can read the configuration, connect to the database, and write to storage.',
            'First run the command manually and confirm that it ends with the number of emails sent and produces no errors in storage/app.log. Then create a system cron task. The example below runs every Monday at 08:00 in the server time zone. If the server uses another time zone, change the schedule or configure it with timedatectl.',
            'Cron uses PHP CLI, so vendor dependencies and pdo_mysql must also be available there. When changing the PHP version, update the executable path in cron. Do not invoke the script over HTTP or place bin inside public_html.',
        ], 'examples' => [
            ['title' => 'Testing the report manually', 'code' => <<<'SHELL'
cd /var/www/contactcore
sudo -u www-data /usr/bin/php8.5 bin/weekly-report.php
tail -n 50 storage/app.log
SHELL],
            ['title' => '/etc/cron.d/contactcore-weekly-report', 'code' => <<<'CRON'
SHELL=/bin/sh
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin

0 8 * * 1 www-data cd /var/www/contactcore && /usr/bin/php8.5 bin/weekly-report.php >> storage/weekly-report-cron.log 2>&1
CRON],
            ['title' => 'Installing and monitoring the cron task', 'code' => <<<'SHELL'
sudo chown root:root /etc/cron.d/contactcore-weekly-report
sudo chmod 644 /etc/cron.d/contactcore-weekly-report
sudo systemctl restart cron
sudo journalctl -u cron --since today --no-pager
SHELL],
        ]],
        ['id' => 'installation-integrations', 'title' => 'SMTP, Gemini, and email DNS checks', 'paragraphs' => [
            'For SMTP, allow outbound connections to smtp_host and the selected port. from_email must be authorized by the provider, and SPF, DKIM, and DMARC should be configured for the domain. PHPMailer errors are written to storage/app.log. Test delivery by running the weekly report manually or using the report button in administrator settings.',
            'For the AI tool, the server must allow Guzzle to make HTTPS requests to generativelanguage.googleapis.com and have working root certificates. cURL is recommended; without it, Guzzle can use PHP streams. Store the Gemini key only in config/gemini.php. Never add it to JavaScript, a URL, Git, or an error log.',
            'Email validation uses the system DNS resolver and checkdnsrr to query the domain’s MX record. If DNS is blocked by the server, container, or firewall, valid addresses may receive an incorrect result. An MX check does not require an SMTP connection to the mailbox.',
        ], 'examples' => [[
            'title' => 'Testing external connections',
            'code' => <<<'SHELL'
curl -I https://generativelanguage.googleapis.com/
php8.5 -r "var_dump(checkdnsrr('gmail.com', 'MX'));"
openssl s_client -connect smtp.example.com:465 -servername smtp.example.com </dev/null
SHELL,
        ]]],
        ['id' => 'installation-verification', 'title' => 'Post-installation verification', 'paragraphs' => [
            'Before handing the system to users, check the configuration from the command line, open the login page, and sign in as the first administrator. Create a test client and contact, upload a small CSV and XLSX, run an export, and verify session persistence and logout.',
            'Test the API separately over HTTPS: a request without a key must return 401, while a request with a test key and the required scope must return successful JSON. Test a large import within the configured limits, manual report delivery, Gemini company lookup, and email MX checking. Remove test records and revoke the test API key afterward.',
            'If every page except the home page returns 404, try_files is usually the cause. A 502 means Nginx cannot connect to PHP-FPM or the socket is incorrect. Diagnose a 500 through storage/app.log, the PHP-FPM log, and /var/log/nginx/contactcore.error.log. Upload failures are often caused by mismatched Nginx and PHP limits or storage permissions.',
        ], 'examples' => [[
            'title' => 'Technical verification',
            'code' => <<<'SHELL'
cd /var/www/contactcore
composer check-platform-reqs --no-dev
php8.5 -l public_html/index.php
sudo nginx -t
sudo php-fpm8.5 -t
sudo systemctl --no-pager --full status nginx php8.5-fpm mysql cron
curl -I https://crm.example.com/login
tail -n 100 storage/app.log
SHELL,
        ]]],
        ['id' => 'installation-maintenance', 'title' => 'Backups, updates, and monitoring', 'paragraphs' => [
            'A backup must include the MySQL database, active config files, and required storage data—especially original import files if they must be retained. Encrypt backups, move them to another server or object storage, and test restoration regularly. Sessions and temporary tokens normally do not need to be restored.',
            'Before an update, back up the database and configuration. Deploy the new code, run composer install against the existing composer.lock, check platform requirements again, and restart PHP-FPM. Never apply database/schema.sql as an update. Run unapplied SQL files from database/migrations in order according to the release instructions; there is currently no automatic migration runner.',
            'Monitor free space, TLS certificate expiry, Nginx/PHP-FPM/MySQL/cron health, growth of storage/app.log, and the weekly-report log. Configure log rotation and alerts for 5xx responses. Update the OS and Composer dependencies in a test environment first, not directly on the production CRM.',
        ], 'examples' => [
            ['title' => 'Manual database backup', 'code' => <<<'SHELL'
sudo install -d -m 700 /var/backups/contactcore
mysqldump --single-transaction --quick --routines --triggers \
  -u contactcore -p crm | gzip \
  > /var/backups/contactcore/crm-$(date +%F-%H%M).sql.gz
SHELL],
            ['title' => 'Typical code update', 'code' => <<<'SHELL'
cd /var/www/contactcore
# Deploy the tested new code version first.
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
composer check-platform-reqs --no-dev
sudo systemctl restart php8.5-fpm
sudo nginx -t && sudo systemctl reload nginx
SHELL],
            ['title' => '/etc/logrotate.d/contactcore', 'code' => <<<'CODE'
/var/www/contactcore/storage/*.log {
    weekly
    rotate 12
    compress
    delaycompress
    missingok
    notifempty
    copytruncate
    create 0660 deploy www-data
}
CODE],
        ]],
    ],
];
