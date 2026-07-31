<?php

return [
    'title' => 'Authentication and security',
    'description' => 'ContactCore security model: user and integration authentication, session management, authorization, CSRF, browser restrictions, secret storage, and known risks.',
    'icon' => 'ph-arrow-elbow-down-right',
    'sections' => [
        [
            'id' => 'security-boundaries',
            'title' => 'Security boundaries',
            'paragraphs' => [
                'ContactCore has two independent authentication models. The web interface uses a PHP session and, optionally, a persistent remember token. Public /api/v1 uses a client_id + secret pair through HTTP Basic Auth and checks scopes. Internal /ajax/* belongs to the web interface: it uses the same session, and modifying POST requests require the shared CSRF token.',
                'Protection is layered. HTTPS and cookie settings protect transport and credentials; Auth determines identity and permissions; Router applies access policy before a controller runs; CSRF protects cookie-authenticated POST requests; repositories use parameterized SQL; views must encode output; and browser headers restrict content execution and embedding. No layer replaces the others.',
                'The document root must point only to public_html. config, storage, database, vendor, and app must not be accessible over HTTP. They contain database and SMTP passwords, the Gemini key, sessions, remember tokens, the application log, and source import files. An incorrect virtual-host root bypasses many application safeguards entirely.',
            ],
            'examples' => [[
                'title' => 'Three entry boundaries',
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
            ]],
        ],
        [
            'id' => 'web-login',
            'title' => 'Web-interface login',
            'paragraphs' => [
                'GET /login creates or continues a PHP session and displays a form with a CSRF token. POST /login normalizes email with trim(), checks LoginThrottle first, and then AuthService looks up the user by email, requires is_active = 1, and calls password_verify(). A missing user, disabled account, and incorrect password receive the same message, reducing account enumeration.',
                'After successful verification, the failure counter is cleared, users.last_login_at is updated, and Auth::login() is called. The method regenerates the session identifier while deleting the old id, then stores only id, name, email, and the string role name in $_SESSION. If “Remember me” was selected, a persistent token is also issued after login. The workflow ends with a redirect to /dashboard.',
                'The controller layer manages the sequence; AuthService verifies the account; UserRepository handles lookup and last_login_at; and Auth owns the session. Preserve this separation: passwords must not be verified in a view or arbitrary controller, and creation of $_SESSION[user] must not be duplicated outside Auth::login().',
            ],
            'examples' => [[
                'title' => 'Successful login path',
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
            ]],
        ],
        [
            'id' => 'passwords',
            'title' => 'Passwords and accounts',
            'paragraphs' => [
                'Passwords are never stored as plain text. UserController uses password_hash($password, PASSWORD_DEFAULT), and users.password_hash is 255 characters long, leaving room for future changes to PHP’s PASSWORD_DEFAULT algorithm. Verification uses password_verify() only; hashes must not be compared as strings or decrypted.',
                'The current interface only requires a non-empty password when creating an account. There is no minimum length, common or compromised-password check, or server-side maximum. The HTML field also has no minlength. The browser checks email through type=email, but the server only trims it; a UNIQUE index on users.email with case-insensitive utf8mb4_unicode_ci collation enforces uniqueness.',
                'password_needs_rehash() is not called after a successful login. Existing hashes therefore do not update automatically when PHP’s algorithm or parameters change. One password policy should cover installer-created administrators, user creation, and password changes, and hash migration should run after a successful password_verify().',
            ],
            'examples' => [[
                'title' => 'Recommended hash-update point',
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
            ]],
        ],
        [
            'id' => 'login-throttle',
            'title' => 'Login-attempt throttling',
            'paragraphs' => [
                'LoginThrottle counts failures by lowercased email. Five failed attempts within 15 minutes cause a further 15-minute lock. A successful login removes the record. State is stored in storage/login_throttle.json, and writes use an exclusive flock; expired entries are removed during later file changes.',
                'The file implementation is suitable only for one server with shared local storage. Multiple PHP nodes have independent counters unless they share storage. Directory creation, open, and write failures are suppressed and effectively disable throttling without notifying an operator. read() also does not lock the file for reading.',
                'The key is email only, not IP or IP + account. This limits attempts against one user but allows distributed attempts across many addresses and lets an attacker deliberately lock a known account. The file stores the email addresses themselves. A scalable design needs a centralized Redis/database counter, several limit dimensions, controlled unlocking, event logging, and metrics.',
            ],
            'examples' => [[
                'title' => 'Current parameters',
                'code' => <<<'CODE'
key              = lowercase(trim(email))
window           = 15 minutes
maximum failures = 5
lockout          = 15 minutes
storage          = storage/login_throttle.json
success          = remove counter
CODE,
            ]],
        ],
        [
            'id' => 'sessions',
            'title' => 'PHP session',
            'paragraphs' => [
                'The entry point stores sessions in storage/sessions with directory permissions 0700 and sets session.gc_maxlifetime to 30 days. This protects sessions from overly aggressive cleanup on shared hosting but does not define an explicit application idle timeout or absolute lifetime. The main session cookie remains a session cookie by default unless php.ini says otherwise.',
                'On login and remember-token restoration, the identifier is regenerated and the old one deleted, protecting against session fixation. Logout clears $_SESSION, deletes the session cookie with its current parameters, and calls session_destroy(). The CSRF token is in the same session and disappears with it.',
                'The application does not call session_set_cookie_params() and relies on PHP configuration for HttpOnly, Secure, SameSite, and strict mode. Production requires session.cookie_httponly=1, session.cookie_secure=1, session.cookie_samesite=Lax, and session.use_strict_mode=1. HTTPS must be enforced before issuing cookies. A sensitive CRM should also add a server-side idle timeout, absolute session lifetime, and reauthentication before critical actions.',
            ],
            'examples' => [[
                'title' => 'Minimum production PHP configuration',
                'code' => <<<'INI'
session.cookie_httponly = 1
session.cookie_secure = 1
session.cookie_samesite = Lax
session.use_strict_mode = 1

; Set by the application:
session.gc_maxlifetime = 2592000
INI,
            ]],
        ],
        [
            'id' => 'remember-me',
            'title' => 'Remember-me mechanism',
            'paragraphs' => [
                'Auth::issueRememberToken() generates 32 random bytes, encodes them as 64 hexadecimal characters, and creates storage/remember/{token}. Its JSON contains user_id and expires, with a 30-day lifetime. The remember_token cookie has Path=/, HttpOnly, and SameSite=Lax. Restoration checks token format, file, expiry, an active user, and the current role.',
                'Successful restoration rotates the credential for one-time use: the old file is deleted, a new token issued, and a normal session created with a new session id. Logout revokes only the current browser’s remember token. Other tokens for the same user, such as another device, continue until expiry or manual file deletion.',
                'remember_token is currently created without Secure, so the flag is false regardless of session.cookie_secure. If HTTP is available, a browser may send the persistent credential before redirecting to HTTPS. Secure must be set directly in setcookie(). Files are named with the bearer token and contain its server binding in plain text: access to storage/remember enables session takeover. Strict permissions, encrypted backups, mass revocation by user_id, and expired-file cleanup are required.',
            ],
            'examples' => [[
                'title' => 'Persistent-token lifecycle',
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
            ]],
        ],
        [
            'id' => 'authorization-model',
            'title' => 'Roles and permissions',
            'paragraphs' => [
                'The schema has admin and user roles. An administrator receives every permission registered in permissionDefinitions and is the only role that manages users, API keys, API logs, and AI tools. Route policies define these restrictions. For a regular user, individual decisions are stored in user_permissions under the composite user_id + permission_key.',
                'Eleven permissions are defined: contacts.create/edit/delete, clients.create/edit/delete, exports.use, imports.manage, sectors.manage, tags.manage, and custom_fields.manage. Contacts and clients have no separate read permissions: their lists and records are available to every signed-in user. Dashboard, help, user settings, and search AJAX catalogs also generally require only a valid session.',
                'The menu hides unavailable items for convenience but is not security. Server restrictions are declared beside route registration in public_html/index.php and applied by Router before an action runs. Checking permission only in a view or JavaScript is insufficient because URLs and POST requests can be invoked directly.',
            ],
            'examples' => [[
                'title' => 'Access policies during route registration',
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
            ]],
        ],
        [
            'id' => 'authorization-resolution',
            'title' => 'How permissions are resolved',
            'paragraphs' => [
                'Auth::can() follows a fail-closed contract. It first requires a session user and checks that the supplied key is registered in permissionDefinitions. A typo or unknown key therefore returns false even for an administrator. For a known key, admin receives access without querying user_permissions.',
                'For a regular user, userPermissions() reads their rows once per HTTP request and caches an associative array. Explicit is_allowed = 1 permits an action; is_allowed = 0 denies it. An absent row also means denial. UserController stores an explicit decision for every known key when creating and editing users; a new permission must be granted to existing users separately or through a migration. The schema and 20260729_fail_closed_permissions.sql give is_allowed a safe DEFAULT 0.',
                'If reading user_permissions throws an exception, Auth logs the error and uses an empty permission set. A regular user receives no protected operations in that request. This favors security over availability: a database failure must not temporarily expand privileges.',
            ],
            'examples' => [[
                'title' => 'Current Auth::can() decision table',
                'code' => <<<'CODE'
no session                         → false
unknown permission key             → false
known permission, role = admin     → true
explicit is_allowed = 1            → true
explicit is_allowed = 0            → false
permission row is absent           → false
permission query failed            → false
CODE,
            ]],
        ],
        [
            'id' => 'route-enforcement',
            'title' => 'Protecting pages and AJAX',
            'paragraphs' => [
                'Router stores a route policy with its handler. auth = user requires a valid session, auth = admin requires the administrator role, and permission requires a known Auth permission. For a bulk action, permission may be callable and select the right based on operation type. Checks run before the action, leaving the controller to handle input and workflow instead of repeating authorization.',
                'response selects the denial format. For HTML, a guest redirects to /login and insufficient privilege returns 403 Access denied. For AJAX, response = json returns JSON 401 or 403 without invoking the action. Every route requires a policy: intentionally open endpoints such as login declare auth = public, while /api/v1 is public only at the session level and then performs its own Basic/scopes authentication.',
                'The /ai page and related gemini-company, company, and company/skip POST routes are all auth = admin. Batch email inspection is also administrative because it belongs to an administration workflow. CSRF remains separate: it proves the request originated from the session but does not replace role or permission checks.',
            ],
            'examples' => [[
                'title' => 'Adding an AJAX action safely',
                'code' => <<<'PHP'
$router->post(
    '/ajax/contacts/update-something',
    [$ajaxController, 'updateSomething'],
    ['permission' => 'contacts.edit', 'response' => 'json']
);

// The action runs only after the policy succeeds.
PHP,
            ]],
        ],
        [
            'id' => 'csrf',
            'title' => 'CSRF protection',
            'paragraphs' => [
                'Csrf::token() creates 32 random bytes in hexadecimal and stores the value in $_SESSION[_csrf_token]. Csrf::field() encodes it and adds a hidden _csrf_token to a normal form. AJAX reads the same token from a data attribute and sends it as a form parameter. validate() uses hash_equals(), avoiding an ordinary timing-sensitive comparison.',
                'Before Router, the entry point checks every POST except URLs containing /api/v1/. A missing or invalid token terminates the request with status 419 before the controller. Protection therefore also covers /login, settings, import, export, and internal AJAX POST. The public API is deliberately excluded: it does not use a cookie session and authenticates through Authorization.',
                'The global check covers POST only. PATCH and DELETE currently belong to the Basic-Auth public API, while browser modifications use POST. Any future session-based PATCH/DELETE must be included in CSRF checking. GET /logout changes state without CSRF and permits forced logout through an external link; the correct contract is POST /logout with a token.',
            ],
            'examples' => [[
                'title' => 'Protected POST path',
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
            ]],
        ],
        [
            'id' => 'two-factor',
            'title' => 'Two-factor verification',
            'paragraphs' => [
                'TwoFactorService implements a six-digit, single-use email code. The session stores a password_hash rather than the code, a copy of minimal user data, a 10-minute expiry, last-send time, and attempt count. Resending is allowed after 60 seconds and creates a new code. After more than five failed checks, pending state is deleted.',
                '/login/verify and /login/resend-code routes, the view, and MailerService delivery are registered. However, the call to TwoFactorService::start() in AuthController::login() is commented out. The current production path calls completeLogin() immediately after a valid password, so 2FA is not an active security measure and must not be advertised as enabled.',
                'Simply uncommenting the code is insufficient for mature 2FA. Define whether it is required per user or role, protect email changes, tie remember-me to successful second verification, log events, design recovery and backup codes, centralize attempt and send limits, and test SMTP failure. Email codes protect less than TOTP or WebAuthn and depend on mailbox security.',
            ],
            'examples' => [[
                'title' => 'Current and prepared behavior',
                'code' => <<<'CODE'
Current:
  password valid → completeLogin() → dashboard

Implemented but disabled:
  password valid → TwoFactorService::start()
                 → email code
                 → /login/verify
                 → completeLogin() → dashboard
CODE,
            ]],
        ],
        [
            'id' => 'api-authentication',
            'title' => 'API authentication',
            'paragraphs' => [
                'An administrator creates an API key. client_id contains the crm_ prefix and 16 random bytes; secret contains 32 random bytes. The plain secret is shown only once through session flash; api_keys stores SHA-256. ApiAuthenticator reads Basic credentials from PHP_AUTH_USER/PHP_AUTH_PW or Authorization, finds an active client_id, hashes the supplied secret, and compares it through hash_equals().',
                'After authentication, ApiController requires contacts:read/write or clients:read/write. Revoking a key sets is_active=0 and revoked_at; re-enabling it restores the same secret. last_used_at is updated at most every five minutes. The API uses no browser session and is exempt from CSRF, so HTTPS is mandatory: Basic is encoding, not encryption.',
                'There is no built-in rate limiter, key expiry, IP/origin restriction, or automatic rotation. API logs contain request and response bodies that may include personal data. Detailed lifecycle, scopes, and errors are documented under Internal API architecture; an API key must not be confused with a remember token or session cookie.',
            ],
            'examples' => [[
                'title' => 'Verified credential',
                'code' => <<<'CODE'
Authorization: Basic base64(client_id:secret)

DB:
  client_id   = crm_...
  secret_hash = sha256(secret)
  is_active   = 1
  scopes      = ["contacts:read", "contacts:write", ...]

compare: hash_equals(stored_hash, sha256(provided_secret))
CODE,
            ]],
        ],
        [
            'id' => 'data-safety',
            'title' => 'SQL, input, and safe output',
            'paragraphs' => [
                'Repositories use Query Builder bindings. Where sorting or an exported column is selected dynamically, the value must pass a fixed allowlist before entering SQL. Casting an id to int is useful normalization but does not replace checking that an entity exists or that the user is permitted to access it.',
                'View is a thin PHP wrapper with no automatic escaping. HTML safety depends on explicit htmlspecialchars($value, ENT_QUOTES, UTF-8) in every context. For multiline text, apply htmlspecialchars before nl2br. JSON uses json_encode, and data placed in HTML data attributes is additionally attribute-encoded. JavaScript must not build user values through innerHTML without sanitization.',
                'HTML encoding does not validate URL meaning. website is output in href after htmlspecialchars, but server-side ClientController does not restrict its scheme; data may also enter through import or API. Allow only http and https after parse_url and normalization. MIME checks, permitted filenames, numeric bounds, and domain validation must likewise run server-side even when a form uses type=email or type=url.',
            ],
            'examples' => [[
                'title' => 'Contextual encoding',
                'code' => <<<'PHP'
// HTML text or attribute
htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

// Multiline text
nl2br(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));

// URL: validate the scheme first, then encode for the attribute
$scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
if (in_array($scheme, ['http', 'https'], true)) {
    echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
}
PHP,
            ]],
        ],
        [
            'id' => 'browser-transport',
            'title' => 'HTTPS and browser headers',
            'paragraphs' => [
                'public_html/index.php adds X-Frame-Options: SAMEORIGIN, X-Content-Type-Options: nosniff, and Referrer-Policy: strict-origin-when-cross-origin. CSP limits default-src to self, blocks object, fixes base-uri and form-action to self, permits frames only from the same origin, and limits browser connect-src to the site. img-src allows self and data:, while fonts and styles allow listed Google and jsDelivr endpoints.',
                'script-src and style-src currently include unsafe-inline because views use inline onclick and style. This significantly weakens CSP against XSS. A mature transition moves handlers and styles to static assets and then uses nonces or removes inline allowances entirely. Until then CSP remains a useful source boundary but is not complete protection from injected HTML.',
                'The application does not send HSTS; enable it at the reverse proxy only after HTTPS is stable. HTTP must redirect to HTTPS, and the proxy must forward Authorization to PHP. Also consider Permissions-Policy and cache controls for pages containing personal data. Check headers on HTML, JSON, and proxy errors that may occur before PHP.',
            ],
            'examples' => [[
                'title' => 'Current CSP in abbreviated form',
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
            ]],
        ],
        [
            'id' => 'secrets-logs-storage',
            'title' => 'Secrets, logs, and file storage',
            'paragraphs' => [
                'Active config/database.php, config/mail.php, and config/gemini.php are excluded from Git and should have permissions no broader than root:www-data 0640. GEMINI_API_KEY may instead come from the environment. Secrets must not enter JavaScript, URLs, user messages, error dumps, or public backups. Production should use an external secret manager or protected process environment.',
                'storage must be accessible only to the application and operators. It contains session files, plain bearer files for remember-me, login_throttle.json, app.log, and imports with personal data. Separate permissions, disk-capacity monitoring, encrypted backups, and a retention policy are required. Serving storage through the web server is a critical incident.',
                'logApplicationError() writes full exception text to system error_log and storage/app.log while the user receives a generic 500. This correctly separates UI and diagnostics, but PDO, SMTP, or external-service messages may still reveal environment details. api_logs also stores request_body and response_body. Secret and personal-data masking, access control, rotation, and retention are not centrally implemented.',
            ],
        ],
        [
            'id' => 'account-lifecycle',
            'title' => 'Access lifecycle and audit',
            'paragraphs' => [
                'An administrator can deactivate a user, change role, permissions, email, and password, or permanently delete an inactive record. The interface prevents deactivating or deleting the current account. Deleting a user cascades to user_permissions, while related user_id values in log tables generally become NULL.',
                'An active session does not recheck users.is_active or the database role on every request. The role is stored as a string in session user, so a deactivated user continues until the session ends, and a changed role takes effect after a new login. Changing a password likewise does not destroy active sessions or all remember tokens. Remember-me restoration does recheck activity and current role.',
                'schema.sql contains audit_logs, but current code does not record logins, logouts, failures, role or permission changes, key creation, or administrative changes there. last_login_at and api_logs cover only part of the picture. Incident investigation needs an immutable audit with actor, action, target, time, IP, request id, and a safe change description without passwords or secrets.',
            ],
            'examples' => [[
                'title' => 'Events that should revoke access',
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
            ]],
        ],
        [
            'id' => 'security-testing',
            'title' => 'Testing security changes',
            'paragraphs' => [
                'A protected feature change should be tested with at least four identities: guest, regular user without permission, regular user with permission, and administrator. Test the direct URL and actual HTTP method for every action, not only button visibility. Separately verify 401/403/419, absence of side effects on denial, and absence of sensitive data in the response.',
                'Authentication tests cover correct and incorrect passwords, disabled users, session-id regeneration, throttle locking and expiry, corrupt remember files, expired and rotated tokens, logout, and restoration after a role change. Test CSRF without a token, with another token, and with a valid token for every browser mutation.',
                'API tests cover missing Authorization, invalid client_id and secret, revoked keys, insufficient scopes, successful reads/writes, no CSRF dependency, logging, and absence of secrets from the database and logs. Check CSP, cookie flags, HTTPS redirects, and HSTS on a deployed server because CLI tests cannot observe reverse-proxy and browser behavior.',
            ],
            'examples' => [[
                'title' => 'Minimum access matrix',
                'code' => <<<'CODE'
                     guest   user:no   user:yes   admin
read protected page  302      200        200       200
edit protected page  302      403        200       200
POST without CSRF    419      419        419       419
admin page           302      403        403       200
API without key      401      401        401       401
API with scope       —        —          —         2xx
CODE,
            ]],
        ],
        [
            'id' => 'security-known-gaps',
            'title' => 'Priority technical debt',
            'paragraphs' => [
                'Permissions are already fail-closed, web-route policies are centralized in Router, and access to the AI page and AJAX is aligned to admin. Adding a permission still requires migration of explicit values for existing users. The remaining high priority is to check activity and current role in live sessions and revoke all sessions and remember tokens after account deactivation or password/role changes.',
                'The next level is to add Secure to the persistent cookie, make logout a CSRF-protected POST, introduce explicit idle and absolute session timeouts, strict password policy and rehashing, and decide whether to enable or remove inactive 2FA code. LoginThrottle should move to reliable centralized storage with IP/risk limits that do not make account denial easy.',
                'Further work includes server-side URL-scheme validation, removing unsafe-inline from CSP, automatic security audit, log redaction and retention, API rate limiting, API-key expiry and rotation, dependency scanning, and regular access-matrix integration tests. These items describe current implementation boundaries; update documentation with each actual contract fix.',
            ],
            'examples' => [[
                'title' => 'Hardening order',
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
            ]],
        ],
    ],
];
