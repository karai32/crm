<?php

return [
    'title' => 'Авторизация и безопасность',
    'description' => 'Модель безопасности ContactCore: аутентификация пользователей и интеграций, управление сессиями, проверка полномочий, CSRF, браузерные ограничения, хранение секретов и известные риски.',
    'icon' => 'ph-arrow-elbow-down-right',
    'sections' => [
        [
            'id' => 'security-boundaries',
            'title' => 'Границы безопасности',
            'paragraphs' => [
                'В ContactCore существуют две независимые модели аутентификации. Веб-интерфейс использует PHP-сессию и, по выбору пользователя, постоянный remember-token. Публичный /api/v1 использует пару client_id + secret через HTTP Basic Auth и проверяет scopes. Внутренние /ajax/* относятся к веб-интерфейсу: они используют ту же сессию, а изменяющие POST-запросы — общий CSRF-токен.',
                'Защита строится слоями. HTTPS и настройки cookie защищают транспорт и учётные данные; Auth определяет личность и разрешения; Router применяет политику доступа до вызова контроллера; CSRF защищает cookie-аутентифицированные POST-запросы; репозитории используют параметризованный SQL; представления обязаны кодировать вывод; заголовки браузера ограничивают выполнение и встраивание контента. Ни один слой не заменяет остальные.',
                'Document root должен указывать только на public_html. Каталоги config, storage, database, vendor и app не должны быть доступны по HTTP. В них находятся пароли базы и SMTP, ключ Gemini, сессии, remember-токены, журнал приложения и исходные файлы импортов. Ошибка в корне виртуального хоста полностью обходит многие прикладные меры.',
            ],
            'examples' => [
                [
                    'title' => 'Три входных контура',
                    'code' => <<<'CODE'
Browser pages
  HTTPS → session cookie → Auth → permission → controller → HTML

Internal AJAX
  HTTPS → session cookie → Auth → permission → controller → JSON
  POST additionally requires CSRF token

Public API
  HTTPS → Basic client_id:secret → scope → ApiService → JSON
  no browser session and no CSRF
CODE,
                ],
            ],
        ],
        [
            'id' => 'web-login',
            'title' => 'Вход через веб-интерфейс',
            'paragraphs' => [
                'GET /login создаёт или продолжает PHP-сессию и показывает форму с CSRF-токеном. POST /login нормализует email функцией trim(), сначала проверяет LoginThrottle, затем AuthService ищет пользователя по email, требует is_active = 1 и вызывает password_verify(). Для отсутствующего пользователя, отключённой учётной записи и неверного пароля возвращается одно и то же сообщение — это уменьшает возможность перебора зарегистрированных адресов.',
                'После успешной проверки очищается счётчик неудач, обновляется users.last_login_at и вызывается Auth::login(). Метод регенерирует идентификатор сессии с удалением старого id, затем сохраняет в $_SESSION только id, name, email и строковое имя роли. Если выбрано «Запомнить меня», после входа дополнительно выпускается постоянный токен. Завершается сценарий перенаправлением на /dashboard.',
                'Слой контроллера отвечает за последовательность, AuthService — за проверку учётной записи, UserRepository — за запрос и last_login_at, Auth — за сессию. Это разделение следует сохранять: пароль не должен проверяться в представлении или произвольном контроллере, а создание $_SESSION[user] не должно дублироваться вне Auth::login().',
            ],
            'examples' => [
                [
                    'title' => 'Успешный путь входа',
                    'code' => <<<'CODE'
GET /login → session + CSRF form
POST /login
  → LoginThrottle::isLocked(email)
  → UserRepository::findByEmail(email)
  → is_active === 1
  → password_verify(password, password_hash)
  → updateLastLogin(user_id)
  → session_regenerate_id(true)
  → $_SESSION['user'] = {id, name, email, role}
  → optional remember token
  → /dashboard
CODE,
                ],
            ],
        ],
        [
            'id' => 'passwords',
            'title' => 'Пароли и учётные записи',
            'paragraphs' => [
                'Пароли не сохраняются в открытом виде. UserController использует password_hash($password, PASSWORD_DEFAULT), а users.password_hash имеет длину 255 символов, достаточную для смены алгоритма PASSWORD_DEFAULT в будущих версиях PHP. Проверка выполняется только через password_verify(); сравнивать хеши строками или пытаться расшифровать их нельзя.',
                'В текущем интерфейсе пароль при создании обязан быть только непустым. Минимальная длина, проверка распространённых или скомпрометированных паролей и серверная максимальная длина не заданы. HTML-поле также не задаёт minlength. Email проверяется браузерным type=email, но сервер ограничивается trim(); уникальность обеспечивает UNIQUE-индекс users.email с регистронезависимой utf8mb4_unicode_ci collation.',
                'password_needs_rehash() при успешном входе не вызывается. Поэтому существующие хеши не обновятся автоматически после изменения алгоритма или параметров PHP. Политика паролей должна быть единой для создания администратора при установке, создания пользователя и смены пароля, а обновление хеша — выполняться после успешного password_verify().',
            ],
            'examples' => [
                [
                    'title' => 'Рекомендуемая точка обновления хеша',
                    'code' => <<<'PHP'
if (!password_verify($password, $user['password_hash'])) {
    return null;
}

if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
    $users->updatePassword(
        (int) $user['id'],
        password_hash($password, PASSWORD_DEFAULT)
    );
}
PHP,
                ],
            ],
        ],
        [
            'id' => 'login-throttle',
            'title' => 'Ограничение попыток входа',
            'paragraphs' => [
                'LoginThrottle считает неудачи по приведённому к нижнему регистру email. Пять неудачных попыток в пределах 15 минут устанавливают блокировку ещё на 15 минут. Успешный вход удаляет запись. Состояние хранится в storage/login_throttle.json, а запись защищена эксклюзивным flock; устаревшие элементы удаляются при последующих изменениях файла.',
                'Файловая реализация подходит только для одного сервера с общим локальным storage. Несколько PHP-узлов будут иметь независимые счётчики, если не используют общее хранилище. Ошибки создания каталога, открытия или записи подавляются и фактически отключают ограничение без сигнала оператору. read() также не блокирует файл на чтение.',
                'Ключом является только email, а не IP или сочетание IP + учётная запись. Это ограничивает перебор конкретного пользователя, но допускает распределённые попытки по множеству адресов и позволяет постороннему намеренно заблокировать известную учётную запись. В файле хранятся сами email. Для масштабируемой схемы нужны централизованный Redis/БД-счётчик, несколько измерений лимита, контролируемая разблокировка, журнал событий и метрики.',
            ],
            'examples' => [
                [
                    'title' => 'Текущие параметры',
                    'code' => <<<'CODE'
key              = lowercase(trim(email))
window           = 15 minutes
maximum failures = 5
lockout          = 15 minutes
storage          = storage/login_throttle.json
success          = remove counter
CODE,
                ],
            ],
        ],
        [
            'id' => 'sessions',
            'title' => 'PHP-сессия',
            'paragraphs' => [
                'Точка входа сохраняет сессии в storage/sessions с каталогом 0700 и устанавливает session.gc_maxlifetime в 30 суток. Это защищает сессии от слишком агрессивной системной очистки shared hosting, но не задаёт приложению явный idle timeout или абсолютный срок. Cookie основной сессии по умолчанию остаётся сессионной, если иное не установлено в php.ini.',
                'При логине и восстановлении через remember-token идентификатор регенерируется с удалением старого, что защищает от session fixation. Logout очищает $_SESSION, удаляет session-cookie с текущими параметрами и вызывает session_destroy(). CSRF-токен находится в той же сессии и исчезает вместе с ней.',
                'Приложение не вызывает session_set_cookie_params() и полагается на конфигурацию PHP для HttpOnly, Secure, SameSite и strict mode. В production обязательны session.cookie_httponly=1, session.cookie_secure=1, session.cookie_samesite=Lax и session.use_strict_mode=1. HTTPS должен быть принудительным до выдачи cookie. Для чувствительной CRM разумно добавить серверный idle timeout, абсолютный срок сессии и повторную аутентификацию перед критическими действиями.',
            ],
            'examples' => [
                [
                    'title' => 'Минимальная production-конфигурация PHP',
                    'code' => <<<'INI'
session.cookie_httponly = 1
session.cookie_secure = 1
session.cookie_samesite = Lax
session.use_strict_mode = 1

; Приложение устанавливает:
session.gc_maxlifetime = 2592000
INI,
                ],
            ],
        ],
        [
            'id' => 'remember-me',
            'title' => 'Механизм «Запомнить меня»',
            'paragraphs' => [
                'Auth::issueRememberToken() генерирует 32 случайных байта, кодирует их в 64 шестнадцатеричных символа и создаёт файл storage/remember/{token}. JSON внутри содержит user_id и expires; срок — 30 суток. Cookie remember_token имеет Path=/, HttpOnly и SameSite=Lax. При восстановлении проверяются формат токена, файл, срок, существование активного пользователя и его текущая роль.',
                'Успешное восстановление одноразово ротирует credential: старый файл удаляется, новый токен выпускается, затем создаётся обычная сессия с новым session id. Logout отзывает только remember-token из текущего браузера. Другие токены того же пользователя, например на другом устройстве, продолжают действовать до истечения срока или ручного удаления файлов.',
                'Cookie remember_token сейчас создаётся без Secure, поэтому этот флаг равен false независимо от настроек session.cookie_secure. При доступном HTTP браузер способен отправить постоянный credential до перенаправления на HTTPS. Флаг Secure необходимо задавать непосредственно в setcookie(). Файлы названы самим bearer-токеном и содержат его серверную привязку в открытом виде: доступ к каталогу storage/remember равносилен возможности захвата сессии. Нужны строгие права, шифрование резервных копий, массовый отзыв по user_id и очистка просроченных файлов.',
            ],
            'examples' => [
                [
                    'title' => 'Жизненный цикл постоянного токена',
                    'code' => <<<'CODE'
login + remember_me
  → random 256-bit token
  → storage/remember/{token}
  → HttpOnly, SameSite=Lax cookie for 30 days

request without session
  → validate token file and expiry
  → reload active user from DB
  → delete old token
  → issue new token
  → regenerate session id and login
CODE,
                ],
            ],
        ],
        [
            'id' => 'authorization-model',
            'title' => 'Роли и разрешения',
            'paragraphs' => [
                'В схеме есть роли admin и user. Администратор получает все зарегистрированные в permissionDefinitions права и только он управляет пользователями, API-ключами, API-журналами и ИИ-инструментами. Эти ограничения задаются политиками маршрутов. Для обычного пользователя индивидуальные решения хранятся в user_permissions по составному ключу user_id + permission_key.',
                'Определены одиннадцать разрешений: contacts.create/edit/delete, clients.create/edit/delete, exports.use, imports.manage, sectors.manage, tags.manage и custom_fields.manage. Отдельных разрешений на чтение контактов и клиентов нет: их списки и карточки доступны любому вошедшему пользователю. Dashboard, справка, пользовательские настройки и поисковые AJAX-справочники также в основном требуют только действующую сессию.',
                'Меню скрывает недоступные пункты для удобства, но не является защитой. Серверное ограничение задаётся рядом с регистрацией маршрута в public_html/index.php и применяется Router до вызова action. Проверять право только в представлении или JavaScript нельзя: URL и POST можно вызвать напрямую.',
            ],
            'examples' => [
                [
                    'title' => 'Политики доступа при регистрации маршрутов',
                    'code' => <<<'PHP'
$router->get('/dashboard', [$dashboardController, 'index'], [
    'auth' => 'user',
]);

$router->post('/contacts/update', [$contactController, 'update'], [
    'permission' => 'contacts.edit',
]);

$router->get('/ai', [$aiController, 'index'], [
    'auth' => 'admin',
]);
PHP,
                ],
            ],
        ],
        [
            'id' => 'authorization-resolution',
            'title' => 'Как вычисляется разрешение',
            'paragraphs' => [
                'Auth::can() работает по fail-closed контракту. Сначала он требует session user и проверяет, что переданный ключ зарегистрирован в permissionDefinitions. Поэтому опечатка или неизвестный ключ дают false даже администратору. Для известного ключа admin получает доступ без обращения к user_permissions.',
                'Для обычного пользователя userPermissions() один раз за HTTP-запрос читает строки пользователя и кэширует ассоциативный массив. Явный is_allowed = 1 разрешает действие, is_allowed = 0 запрещает. Отсутствующая строка также означает запрет. При создании и редактировании пользователя UserController сохраняет явное решение для каждого известного ключа; при добавлении нового permission существующим пользователям его нужно выдать отдельно через настройки или вручную SQL-командой. Актуальная схема задаёт для is_allowed безопасный DEFAULT 0.',
                'Если чтение user_permissions завершается исключением, Auth журналирует ошибку и использует пустой набор разрешений. Обычный пользователь в таком запросе не получает защищённые операции. Это безопаснее доступности: сбой базы не должен временно расширять полномочия.',
            ],
            'examples' => [
                [
                    'title' => 'Текущая таблица решений Auth::can()',
                    'code' => <<<'CODE'
no session                         → false
unknown permission key             → false
known permission, role = admin     → true
explicit is_allowed = 1            → true
explicit is_allowed = 0            → false
permission row is absent           → false
permission query failed            → false
CODE,
                ],
            ],
        ],
        [
            'id' => 'route-enforcement',
            'title' => 'Защита страниц и AJAX',
            'paragraphs' => [
                'Router хранит вместе с обработчиком политику маршрута. auth = user требует действующую сессию, auth = admin — роль администратора, permission — известное разрешение Auth. Для bulk-action permission может быть callable и выбирать право по типу операции. Проверка выполняется до action, поэтому контроллер занимается входными данными и сценарием, а не повторяет авторизацию.',
                'Поле response выбирает форму отказа. Для HTML гость перенаправляется на /login, а недостаток полномочий даёт 403 Access denied. Для AJAX политика response = json возвращает JSON с 401 или 403 и не вызывает action. Политика обязательна для каждого маршрута: сознательно открытые точки вроде login объявляют auth = public, а /api/v1 использует public только на уровне сессии и затем выполняет собственную Basic/scopes-аутентификацию.',
                'Страница /ai и все связанные POST-маршруты gemini-company, company и company/skip выровнены по auth = admin. Пакетная проверка email также административная, поскольку запускается из административного сценария. CSRF остаётся отдельным слоем: он подтверждает происхождение запроса из сессии, но не заменяет проверку роли или разрешения.',
            ],
            'examples' => [
                [
                    'title' => 'Безопасное добавление AJAX-action',
                    'code' => <<<'PHP'
$router->post(
    '/ajax/contacts/update-something',
    [$ajaxController, 'updateSomething'],
    ['permission' => 'contacts.edit', 'response' => 'json']
);

// Action вызывается только после успешной проверки политики.
PHP,
                ],
            ],
        ],
        [
            'id' => 'csrf',
            'title' => 'CSRF-защита',
            'paragraphs' => [
                'Csrf::token() создаёт 32 случайных байта в шестнадцатеричном виде и хранит значение в $_SESSION[_csrf_token]. Csrf::field() кодирует его и добавляет скрытый _csrf_token в обычную форму. AJAX берёт тот же токен из data-атрибута и отправляет как параметр формы. validate() использует hash_equals(), исключая обычное сравнение с утечкой времени.',
                'Перед Router точка входа проверяет каждый POST, кроме URL, содержащих /api/v1/. Неверный или отсутствующий токен завершает запрос статусом 419 до контроллера. Поэтому защита распространяется также на /login, настройки, импорт, экспорт и внутренние AJAX POST. Публичный API исключён осознанно: он не использует cookie-сессию и аутентифицируется собственным Authorization.',
                'Глобальная проверка охватывает только POST. PATCH и DELETE сейчас используются публичным API и защищены Basic Auth, а браузерные изменения оформлены POST. Если в будущем появится сессионный PATCH/DELETE, его необходимо включить в CSRF-проверку. GET /logout изменяет состояние без CSRF и допускает принудительный выход по внешней ссылке; корректный контракт — POST /logout с токеном.',
            ],
            'examples' => [
                [
                    'title' => 'Путь защищённого POST',
                    'code' => <<<'CODE'
HTML:  <?= Csrf::field() ?>
AJAX:  _csrf_token=<value from page>

POST request
  → public_html/index.php
  → not /api/v1/*
  → Csrf::validate()
      false → HTTP 419, controller is not called
      true  → Router → controller → Auth/permission check
CODE,
                ],
            ],
        ],
        [
            'id' => 'two-factor',
            'title' => 'Двухфакторная проверка',
            'paragraphs' => [
                'TwoFactorService реализует шестизначный одноразовый код по email. В сессии хранится не код, а password_hash, копия минимальных данных пользователя, срок 10 минут, время последней отправки и счётчик попыток. Повторная отправка разрешается через 60 секунд и создаёт новый код. После превышения пяти неудачных проверок pending-состояние удаляется.',
                'Маршруты /login/verify и /login/resend-code, представление и отправка через MailerService зарегистрированы. Однако вызов TwoFactorService::start() в AuthController::login() закомментирован. Текущий production-путь после верного пароля сразу вызывает completeLogin(), поэтому 2FA сейчас не является действующей мерой безопасности и не должна заявляться пользователям как включённая.',
                'Простое снятие комментариев недостаточно для зрелой 2FA. Нужно определить обязательность по пользователю или роли, защитить смену email, связать remember-me с успешной второй проверкой, журналировать события, продумать recovery и резервные коды, ограничивать попытки и отправки централизованно и протестировать отказ SMTP. Email-код защищает слабее TOTP или WebAuthn и зависит от безопасности почтового ящика.',
            ],
            'examples' => [
                [
                    'title' => 'Фактическое и подготовленное поведение',
                    'code' => <<<'CODE'
Current:
  password valid → completeLogin() → dashboard

Implemented but disabled:
  password valid → TwoFactorService::start()
                 → email code
                 → /login/verify
                 → completeLogin() → dashboard
CODE,
                ],
            ],
        ],
        [
            'id' => 'api-authentication',
            'title' => 'Аутентификация API',
            'paragraphs' => [
                'API-ключ создаётся администратором. client_id содержит префикс crm_ и 16 случайных байт, secret — 32 случайных байта. Открытый secret показывается только один раз через session flash; в api_keys сохраняется SHA-256. ApiAuthenticator получает Basic credentials из PHP_AUTH_USER/PHP_AUTH_PW либо Authorization, ищет активный client_id, хеширует предоставленный secret и сравнивает через hash_equals().',
                'После аутентификации ApiController требует scope contacts:read/write или clients:read/write. Отзыв ключа устанавливает is_active=0 и revoked_at, включение возвращает ключ с тем же secret. last_used_at обновляется не чаще одного раза в пять минут. API не использует браузерную сессию и освобождён от CSRF, поэтому HTTPS является обязательным: Basic — это кодирование, а не шифрование.',
                'Встроенного rate limiter, срока действия ключа, ограничения по IP/origin и автоматической ротации нет. Журнал API содержит тела запросов и ответов, где могут находиться персональные данные. Подробный жизненный цикл, scopes и формат ошибок описаны в разделе «Внутреннее устройство API»; здесь важно не смешивать API-key с remember-token или session-cookie.',
            ],
            'examples' => [
                [
                    'title' => 'Проверяемый credential',
                    'code' => <<<'CODE'
Authorization: Basic base64(client_id:secret)

DB:
  client_id   = crm_...
  secret_hash = sha256(secret)
  is_active   = 1
  scopes      = ["contacts:read", "contacts:write", ...]

compare: hash_equals(stored_hash, sha256(provided_secret))
CODE,
                ],
            ],
        ],
        [
            'id' => 'data-safety',
            'title' => 'SQL, ввод и безопасный вывод',
            'paragraphs' => [
                'Репозитории используют bindings Query Builder. Там, где динамически выбирается сортировка или экспортируемая колонка, значение должно проходить фиксированный белый список до включения в SQL. Приведение id к int полезно как нормализация, но не заменяет проверку существования сущности и разрешения на неё.',
                'View является тонкой PHP-обёрткой и не имеет автоматического escaping. Безопасность HTML зависит от явного htmlspecialchars($value, ENT_QUOTES, UTF-8) в каждом контексте. Для многострочного текста сначала применяется htmlspecialchars, затем nl2br. Для JSON используется json_encode, а данные в HTML data-атрибутах дополнительно кодируются как атрибут. При добавлении JavaScript нельзя собирать пользовательские значения через innerHTML без очистки.',
                'HTML-кодирование не проверяет смысл URL. Поле website выводится в href после htmlspecialchars, но серверный ClientController не ограничивает схему; данные также могут прийти через импорт или API. Следует разрешать только http и https после parse_url и нормализации. Аналогично MIME-проверка импорта, допустимые имена файлов, числовые границы и предметная валидация должны выполняться на сервере, даже если форма использует type=email или type=url.',
            ],
            'examples' => [
                [
                    'title' => 'Контекстное кодирование',
                    'code' => <<<'PHP'
// HTML text or attribute
htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

// Multiline text
nl2br(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));

// URL: validate scheme first, then encode for the attribute
$scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
if (in_array($scheme, ['http', 'https'], true)) {
    echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
}
PHP,
                ],
            ],
        ],
        [
            'id' => 'browser-transport',
            'title' => 'HTTPS и браузерные заголовки',
            'paragraphs' => [
                'public_html/index.php добавляет X-Frame-Options: SAMEORIGIN, X-Content-Type-Options: nosniff и Referrer-Policy: strict-origin-when-cross-origin. CSP ограничивает default-src значением self, запрещает object, фиксирует base-uri и form-action на self, разрешает кадры только с собственного origin и ограничивает browser connect-src собственным сайтом. img-src допускает self и data:, а шрифты и стили — перечисленные Google и jsDelivr endpoints.',
                'script-src и style-src сейчас содержат unsafe-inline, потому что представления используют inline onclick и style. Это заметно ослабляет CSP против XSS. Зрелый переход — вынести обработчики и стили в статические assets, затем использовать nonce или полностью убрать inline-разрешения. До этого CSP остаётся полезной границей источников, но не должна считаться полной защитой от внедрённого HTML.',
                'HSTS приложение не отправляет; его следует включать на reverse proxy только после стабильного HTTPS. HTTP должен перенаправляться на HTTPS, а прокси — передавать Authorization в PHP. Полезно дополнительно определить Permissions-Policy и политику кеширования для страниц с персональными данными. Заголовки нужно проверять как на HTML, так и на JSON и ошибках прокси, которые могут формироваться до PHP.',
            ],
            'examples' => [
                [
                    'title' => 'Текущая CSP в сокращённом виде',
                    'code' => <<<'CODE'
default-src 'self'
script-src  'self' 'unsafe-inline'
style-src   'self' 'unsafe-inline' fonts.googleapis.com cdn.jsdelivr.net
font-src    'self' fonts.gstatic.com cdn.jsdelivr.net
img-src     'self' data:
connect-src 'self'
object-src  'none'
base-uri    'self'
form-action 'self'
frame-ancestors 'self'
CODE,
                ],
            ],
        ],
        [
            'id' => 'secrets-logs-storage',
            'title' => 'Секреты, журналы и файловое хранилище',
            'paragraphs' => [
                'Рабочие config/database.php, config/mail.php и config/gemini.php исключены из Git и должны иметь права не шире root:www-data 0640. Альтернативный GEMINI_API_KEY читается из окружения. Секреты нельзя помещать в JavaScript, URL, сообщения пользователю, дампы ошибок или публичные резервные копии. Для production предпочтителен внешний secret manager либо защищённое окружение процесса.',
                'storage должен принадлежать только приложению и операторам. В нём находятся session files, plaintext bearer-файлы remember-me, login_throttle.json, app.log и импорты с персональными данными. Нужны раздельные права, контроль заполнения диска, шифрованные резервные копии и политика хранения. Публикация storage через веб-сервер является критическим инцидентом.',
                'logApplicationError() записывает полный текст исключения в системный error_log и storage/app.log, тогда как пользователь получает общий ответ 500. Это правильное разделение интерфейса и диагностики, но сообщения PDO, SMTP или внешних сервисов всё равно способны содержать детали окружения. api_logs дополнительно сохраняет request_body и response_body. Маскирование секретов и персональных данных, ограничение доступа, ротация и срок хранения сейчас не реализованы централизованно.',
            ],
        ],
        [
            'id' => 'account-lifecycle',
            'title' => 'Жизненный цикл доступа и аудит',
            'paragraphs' => [
                'Администратор может деактивировать пользователя, изменить роль, разрешения, email и пароль либо окончательно удалить уже неактивную запись. Нельзя деактивировать или удалить собственную текущую учётную запись через интерфейс. Удаление пользователя каскадно удаляет user_permissions, а связанные журнальные user_id в других таблицах обычно переводятся в NULL.',
                'Активная сессия не сверяет users.is_active и роль с базой на каждом запросе. Роль хранится строкой в session user, поэтому деактивированный пользователь продолжит работать до завершения существующей сессии, а изменённая роль вступит в силу после нового входа. Смена пароля также не уничтожает активные сессии и все remember-токены. При восстановлении через remember-me активность и текущая роль уже проверяются заново.',
                'В schema.sql есть audit_logs, однако текущий код не записывает в него входы, выходы, неудачные попытки, смены ролей и разрешений, создание ключей или административные изменения. last_login_at и api_logs покрывают только часть картины. Для расследования инцидентов необходим неизменяемый аудит с actor, action, target, временем, IP, request id и безопасным описанием изменения без паролей и секретов.',
            ],
            'examples' => [
                [
                    'title' => 'События, которые должны отзывать доступ',
                    'code' => <<<'CODE'
user deactivated
password changed/reset
role changed
administrator requests “sign out everywhere”
suspected account compromise

Required effect:
  - invalidate all server sessions for user_id
  - delete all remember tokens for user_id
  - optionally revoke related API keys by explicit ownership policy
  - write security audit event
CODE,
                ],
            ],
        ],
        [
            'id' => 'security-testing',
            'title' => 'Проверка изменений безопасности',
            'paragraphs' => [
                'Изменение защищённой функции следует проверять как минимум четырьмя личностями: гость, обычный пользователь без права, обычный пользователь с правом и администратор. Для каждого action проверяются прямой URL и реальный HTTP-метод, а не только видимость кнопки. Отдельно проверяются 401/403/419, отсутствие побочного изменения при отказе и отсутствие чувствительных данных в ответе.',
                'Аутентификация требует тестов верного и неверного пароля, отключённого пользователя, регенерации session id, блокировки и истечения throttle, повреждённого remember-файла, истёкшего и ротированного токена, logout и восстановления после смены роли. CSRF проверяется без токена, с чужим токеном и с валидным токеном для каждой браузерной мутации.',
                'Для API нужны отсутствие Authorization, неверный client_id и secret, отозванный ключ, недостаточный scope, успешные read/write, отсутствие CSRF-зависимости, журналирование и гарантированное отсутствие secret в базе и логах. Проверки CSP, cookie flags, HTTPS redirect и HSTS выполняются на развёрнутом сервере, поскольку CLI-тест не видит поведение reverse proxy и браузера.',
            ],
            'examples' => [
                [
                    'title' => 'Минимальная матрица доступа',
                    'code' => <<<'CODE'
                     guest   user:no   user:yes   admin
read protected page  302      200        200       200
edit protected page  302      403        200       200
POST without CSRF    419      419        419       419
admin page           302      403        403       200
API without key      401      401        401       401
API with scope       —        —          —         2xx
CODE,
                ],
            ],
        ],
        [
            'id' => 'security-known-gaps',
            'title' => 'Приоритетный технический долг',
            'paragraphs' => [
                'Разрешения уже переведены на fail-closed, политики веб-маршрутов централизованы в Router, а доступ к странице ИИ и её AJAX выровнен до уровня admin. При добавлении permission по-прежнему нужно вручную задать явные значения для существующих пользователей. Оставшийся высокий приоритет — проверять активность и текущую роль пользователя в живой сессии и реализовать отзыв всех сессий и remember-токенов при деактивации, смене пароля или роли.',
                'Следующий уровень — добавить Secure постоянной cookie, сделать logout POST с CSRF, ввести явные idle и absolute session timeouts, строгую политику паролей и rehash, а также принять решение по настоящему включению или удалению неактивного 2FA-кода. LoginThrottle следует перенести в надёжное централизованное хранилище и дополнить IP/risk-ограничениями без возможности простой блокировки чужой учётной записи.',
                'Далее необходимы серверная проверка URL-схем, отказ от unsafe-inline в CSP, автоматический security audit, маскирование и retention журналов, rate limiting API, срок и ротация API-ключей, сканирование зависимостей и регулярные интеграционные тесты матрицы доступа. Эти пункты описывают текущие границы реализации; документацию нужно обновлять одновременно с фактическим исправлением каждого контракта.',
            ],
            'examples' => [
                [
                    'title' => 'Порядок укрепления',
                    'code' => <<<'CODE'
P0  revoke access on account/password/role changes

P1  Secure remember cookie + POST logout
P1  explicit session timeouts and password policy
P1  decide and complete 2FA
P1  centralized login throttling and security audit

P2  strict URL validation and CSP without unsafe-inline
P2  API rate limits, expiry and key rotation
P2  log redaction/retention and automated security tests
CODE,
                ],
            ],
        ],
    ],
];
