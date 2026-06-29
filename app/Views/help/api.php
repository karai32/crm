<?php
$proto   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$apiBase = $proto . '://' . ($_SERVER['HTTP_HOST'] ?? 'yoursite.com');

$toc = [
    ['id' => 'auth',     'label' => 'Authentication', 'accent' => 'slate',
     'icon' => 'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z'],
    ['id' => 'contacts', 'label' => 'Contacts',       'accent' => 'blue',
     'icon' => 'M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z'],
    ['id' => 'clients',  'label' => 'Clients',        'accent' => 'green',
     'icon' => 'M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z'],
    ['id' => 'sectors',  'label' => 'Sectors',        'accent' => 'amber',
     'icon' => 'M9 6.75V15m6-6v8.25m.503 3.498 4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 0 0-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c-.317-.159-.69-.159-1.006 0l-4.994 2.497c-.317.158-.69.158-1.006 0Z'],
    ['id' => 'tags',     'label' => 'Tags',           'accent' => 'indigo',
     'icon' => 'M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L9.568 3ZM6.75 8.25a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3Z'],
];

function helpApiCardHead(array $item): void { ?>
    <div class="help-card-head">
        <div class="help-card-icon help-card-icon--<?= $item['accent'] ?>">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="<?= htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8') ?>"/>
            </svg>
        </div>
        <div class="help-card-titles">
            <h2><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></h2>
<?php }

function helpApiCodeBlock(string $label, string $id, string $code): void { ?>
    <div class="api-code-wrap">
        <div class="api-code-header">
            <span class="api-code-label"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
            <button class="api-code-copy" onclick="
                navigator.clipboard.writeText(document.getElementById('<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>').textContent).then(function(){
                    var btn = event.target; btn.textContent='Copied!';
                    setTimeout(function(){ btn.textContent='Copy'; }, 2000);
                });
            ">Copy</button>
        </div>
        <pre class="api-code" id="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>"><?= $code ?></pre>
    </div>
<?php }
?>

<!-- Back button + title -->
<div class="help-api-back-row">
    <a href="<?= htmlspecialchars(Auth::url('/help?lang=' . $locale), ENT_QUOTES, 'UTF-8') ?>" class="help-topic-back">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
        </svg>
        <?= $locale === 'es' ? 'Centro de ayuda' : 'Help Center' ?>
    </a>
    <span class="help-api-base-url">
        Base URL:
        <code class="help-api-inline-code"><?= htmlspecialchars($apiBase, ENT_QUOTES, 'UTF-8') ?></code>
    </span>
</div>

<!-- Two-column layout: TOC + content -->
<div class="help-layout help-layout-mt">

    <!-- Sticky TOC -->
    <aside class="help-toc">
        <span class="help-toc-label">Contents</span>
        <nav class="help-toc-nav">
            <?php foreach ($toc as $item): ?>
            <a href="#<?= $item['id'] ?>"
               class="help-toc-item help-toc-item--<?= $item['accent'] ?>"
               data-section="<?= $item['id'] ?>">
                <span class="help-toc-dot"></span>
                <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
            </a>
            <?php endforeach; ?>
        </nav>
    </aside>

    <!-- Main content -->
    <div class="help-content">

        <!-- ══════════════════════════════════════ AUTHENTICATION ══ -->
        <section class="help-card" id="auth">
            <?php helpApiCardHead($toc[0]); ?>
                    How to authenticate API requests
                </div>
            </div>

            <div class="help-card-body">
                <p class="api-desc-text">
                    Every request uses HTTP Basic Authentication with an active Client ID and Secret.
                    The Secret is shown only once at creation time, and only its SHA-256 hash is stored in the database.
                    Send requests exclusively over HTTPS. Manage integrations at
                    <a href="<?= htmlspecialchars(Auth::url('/api-keys'), ENT_QUOTES, 'UTF-8') ?>" class="api-link-primary">Settings - API Credentials</a>.
                </p>

                <p class="api-label">Request header</p>
                <?php helpApiCodeBlock('HTTP', 'ex-auth', '<span class="ch">Authorization</span>: <span class="cs">Basic base64(client_id:secret)</span>'); ?>

                <p class="api-label api-label-mt">Formidable Forms</p>
                <p class="help-api-body-text-muted">
                    Enter the raw value below in the Basic Auth field. Formidable builds the Authorization header automatically.
                </p>
                <?php helpApiCodeBlock('Basic Auth value', 'ex-formidable-auth', '<span class="cs">crm_your_client_id:your_secret</span>'); ?>

                <p class="api-label api-label-mt">PowerShell example</p>
                <?php helpApiCodeBlock('PowerShell', 'ex-ps', <<<"CODE"
<span class="ck">$clientId</span> = <span class="cs">"crm_your_client_id"</span>
<span class="ck">$secret</span> = <span class="cs">"your_secret"</span>
<span class="ck">$token</span> = [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes(<span class="cs">"$clientId`:$secret"</span>))
<span class="ck">$headers</span> = @{ <span class="cs">"Authorization"</span> = <span class="cs">"Basic $token"</span> }

<span class="cc"># GET request</span>
Invoke-RestMethod -Uri <span class="cs">"<?= htmlspecialchars($apiBase, ENT_QUOTES, 'UTF-8') ?>/api/v1/contacts"</span> -Headers <span class="ck">$headers</span>

<span class="cc"># POST request with JSON body</span>
Invoke-RestMethod -Method Post `
    -Uri <span class="cs">"<?= htmlspecialchars($apiBase, ENT_QUOTES, 'UTF-8') ?>/api/v1/contacts"</span> `
    -Headers <span class="ck">$headers</span> `
    -ContentType <span class="cs">"application/json"</span> `
    -Body <span class="cs">'[{"full_name":"Ivan Petrov","email":"ivan@example.com","tags":"VIP","clients":"Acme Corp"}]'</span>
CODE); ?>

                <p class="api-label api-label-mt">Response envelope</p>
                <p class="help-api-body-text-muted">All responses return JSON with a consistent envelope.</p>
                <div class="api-resp-shape">
                    <?php helpApiCodeBlock('Success', 'ex-resp-ok', <<<"CODE"
{
  <span class="ck">"success"</span>: <span class="cn">true</span>,
  <span class="ck">"data"</span>: { ... }
}
CODE); ?>
                    <?php helpApiCodeBlock('Error', 'ex-resp-err', <<<"CODE"
{
  <span class="ck">"success"</span>: <span class="cn">false</span>,
  <span class="ck">"error"</span>: {
    <span class="ck">"code"</span>: <span class="cs">"not_found"</span>,
    <span class="ck">"message"</span>: <span class="cs">"Contact not found"</span>
  }
}
CODE); ?>
                </div>

                <p class="api-label api-label-mt">Scopes</p>
                <p class="help-api-body-text-muted">
                    Each integration carries a set of scopes. <strong class="help-api-strong">write</strong> implies <strong class="help-api-strong">read</strong> for the same resource.
                </p>
                <div class="api-scopes-row">
                    <?php foreach (['contacts:read','contacts:write','clients:read','clients:write','sectors:read','sectors:write','tags:read','tags:write'] as $s): ?>
                    <span class="api-scope"><?= $s ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════ CONTACTS ══ -->
        <section class="help-card" id="contacts">
            <?php helpApiCardHead($toc[1]); ?>
                    Create, list, update and delete contacts
                </div>
            </div>

            <div class="help-card-body">
                <p class="api-desc-text">
                    Contacts represent individual people. Only the full name is required.
                    When an email is provided it must be valid and unique. Optional tags and clients are
                    <strong class="help-api-strong">created automatically</strong> when they don't exist.
                    Custom fields are saved silently — unknown field slugs are ignored.
                </p>
                <p class="api-desc-text">
                    <code class="help-api-inline-code">tags</code> and
                    <code class="help-api-inline-code">clients</code> each accept a single name, a comma-separated
                    string of names (<code class="help-api-inline-code">"VIP, Enterprise"</code>), or a JSON array —
                    the same field works for one or many.
                </p>

                <p class="api-label">Endpoints</p>
                <div class="api-endpoint-list">
                    <div class="api-endpoint">
                        <span class="api-method api-method--post">POST</span>
                        <span class="api-endpoint-path">/api/v1/contacts</span>
                        <span class="api-scope">contacts:write</span>
                        <span class="api-endpoint-desc">Batch create (up to 100) · 207</span>
                    </div>
                    <div class="api-endpoint">
                        <span class="api-method api-method--get">GET</span>
                        <span class="api-endpoint-path">/api/v1/contacts</span>
                        <span class="api-scope">contacts:read</span>
                        <span class="api-endpoint-desc">Paginated list with filters</span>
                    </div>
                    <div class="api-endpoint">
                        <span class="api-method api-method--get">GET</span>
                        <span class="api-endpoint-path">/api/v1/contacts/<em>{id}</em></span>
                        <span class="api-scope">contacts:read</span>
                        <span class="api-endpoint-desc">Full detail with tags &amp; fields</span>
                    </div>
                    <div class="api-endpoint">
                        <span class="api-method api-method--patch">PATCH</span>
                        <span class="api-endpoint-path">/api/v1/contacts/<em>{id}</em></span>
                        <span class="api-scope">contacts:write</span>
                        <span class="api-endpoint-desc">Partial update</span>
                    </div>
                    <div class="api-endpoint">
                        <span class="api-method api-method--delete">DELETE</span>
                        <span class="api-endpoint-path">/api/v1/contacts/<em>{id}</em></span>
                        <span class="api-scope">contacts:write</span>
                        <span class="api-endpoint-desc">Permanent delete</span>
                    </div>
                </div>

                <p class="api-label">Fields (POST &amp; PATCH)</p>
                <table class="api-fields">
                    <thead>
                        <tr><th>Field</th><th>Type</th><th>POST</th><th>Description</th></tr>
                    </thead>
                    <tbody>
                        <tr><td><code>full_name</code></td><td><span class="api-type">string</span></td><td><span class="api-badge-req">required</span></td><td>Full name</td></tr>
                        <tr><td><code>email</code></td><td><span class="api-type">string|null</span></td><td><span class="api-badge-opt">optional</span></td><td>Unique email address when provided</td></tr>
                        <tr><td><code>tags</code></td><td><span class="api-type">string|string[]</span></td><td><span class="api-badge-opt">optional</span></td><td>One or more tag names — single name, comma-separated string, or array. Created if missing.</td></tr>
                        <tr><td><code>clients</code></td><td><span class="api-type">string|string[]</span></td><td><span class="api-badge-opt">optional</span></td><td>One or more client commercial names — single name, comma-separated string, or array. Created if missing.</td></tr>
                        <tr><td><code>phone</code></td><td><span class="api-type">string</span></td><td><span class="api-badge-opt">optional</span></td><td>Phone number</td></tr>
                        <tr><td><code>company</code></td><td><span class="api-type">string</span></td><td><span class="api-badge-opt">optional</span></td><td>Company name, or empty string if not a company</td></tr>
                        <tr><td><code>custom_fields</code></td><td><span class="api-type">object</span></td><td><span class="api-badge-opt">optional</span></td><td>Key/value pairs by field slug. Unknown slugs are ignored. Flat <code class="help-api-inline-code">"custom_fields.&lt;slug&gt;"</code> keys work the same way, on both POST and PATCH.</td></tr>
                    </tbody>
                </table>
                <p class="api-patch-note">
                    PATCH: all fields are optional — only keys present in the body are updated. Pass
                    <code class="help-api-inline-code">"email": null</code>
                    or an empty string to clear the email.
                    <code class="help-api-inline-code">tags</code> and
                    <code class="help-api-inline-code">clients</code> accept a single name, a comma-separated string,
                    or an array, and <strong class="help-api-strong">replace</strong> the current list.
                </p>

                <p class="api-label api-label-mt">GET filters</p>
                <p class="help-api-body-text-muted">All parameters are optional and combine with AND logic.</p>
                <div class="api-filter-params api-filter-params-mb">
                    <?php foreach (['page','per_page (max 100)','full_name','email','phone','company','client_id','tag_id','created_from (YYYY-MM-DD)','created_to (YYYY-MM-DD)'] as $p): ?>
                    <code class="help-api-inline-code"><?= htmlspecialchars($p, ENT_QUOTES, 'UTF-8') ?></code>
                    <?php endforeach; ?>
                </div>

                <p class="api-label api-label-mt">Example — batch create</p>
                <?php helpApiCodeBlock('POST /api/v1/contacts', 'ex-contacts-post', <<<"CODE"
[
  {
    <span class="ck">"full_name"</span>: <span class="cs">"Ivan Petrov"</span>,
    <span class="ck">"email"</span>:     <span class="cs">"ivan@example.com"</span>,
    <span class="ck">"phone"</span>:     <span class="cs">"+7999000001"</span>,
    <span class="ck">"tags"</span>:      <span class="cs">"VIP"</span>,
    <span class="ck">"clients"</span>:   <span class="cs">"Acme Corp"</span>,
    <span class="ck">"custom_fields"</span>: { <span class="ck">"birthday"</span>: <span class="cs">"1990-01-01"</span> }
  }
]
CODE); ?>

                <p class="api-label api-label-mt">Example — response (207)</p>
                <?php helpApiCodeBlock('Response', 'ex-contacts-207', <<<"CODE"
{
  <span class="ck">"success"</span>: <span class="cn">true</span>,
  <span class="ck">"data"</span>: {
    <span class="ck">"processed"</span>: <span class="cn">1</span>, <span class="ck">"created"</span>: <span class="cn">1</span>, <span class="ck">"failed"</span>: <span class="cn">0</span>,
    <span class="ck">"results"</span>: [
      { <span class="ck">"index"</span>: <span class="cn">0</span>, <span class="ck">"success"</span>: <span class="cn">true</span>, <span class="ck">"data"</span>: { <span class="ck">"contact_id"</span>: <span class="cn">42</span>, <span class="ck">"tag_created"</span>: <span class="cn">false</span>, <span class="ck">"client_created"</span>: <span class="cn">true</span> } }
    ]
  }
}
CODE); ?>

                <p class="api-label api-label-mt">Example — PATCH</p>
                <?php helpApiCodeBlock('PATCH /api/v1/contacts/42', 'ex-contacts-patch', <<<"CODE"
{
  <span class="ck">"full_name"</span>: <span class="cs">"John Smith"</span>,
  <span class="ck">"tags"</span>:      [<span class="cs">"VIP"</span>, <span class="cs">"Priority"</span>],
  <span class="ck">"clients"</span>:    [<span class="cs">"Acme Corp"</span>],
  <span class="ck">"custom_fields"</span>: { <span class="ck">"birthday"</span>: <span class="cs">"1985-03-15"</span> }
}
CODE); ?>

                <p class="api-label api-label-mt">Example — multiple tags as a comma-separated string</p>
                <?php helpApiCodeBlock('POST /api/v1/contacts', 'ex-contacts-tags-string', <<<"CODE"
{ <span class="ck">"full_name"</span>: <span class="cs">"Ivan Petrov"</span>, <span class="ck">"tags"</span>: <span class="cs">"VIP, Enterprise, Priority"</span> }
<span class="cc">// equivalent to "tags": ["VIP", "Enterprise", "Priority"]</span>
CODE); ?>
            </div>
        </section>

        <!-- ════════════════════════════════════════════ CLIENTS ══ -->
        <section class="help-card" id="clients">
            <?php helpApiCardHead($toc[2]); ?>
                    Create, list, update and delete clients
                </div>
            </div>

            <div class="help-card-body">
                <p class="help-api-clients-desc">
                    Clients are organisations or companies. Only <code class="help-api-inline-code">commercial_name</code> is required.
                    The <code class="help-api-inline-code">sector</code> field is resolved by name — the sector must already exist in the system.
                    Tags are find-or-created just like for contacts.
                </p>

                <p class="api-label">Endpoints</p>
                <div class="api-endpoint-list">
                    <div class="api-endpoint">
                        <span class="api-method api-method--post">POST</span>
                        <span class="api-endpoint-path">/api/v1/clients</span>
                        <span class="api-scope">clients:write</span>
                        <span class="api-endpoint-desc">Batch create (up to 100) · 207</span>
                    </div>
                    <div class="api-endpoint">
                        <span class="api-method api-method--get">GET</span>
                        <span class="api-endpoint-path">/api/v1/clients</span>
                        <span class="api-scope">clients:read</span>
                        <span class="api-endpoint-desc">Paginated list with filters</span>
                    </div>
                    <div class="api-endpoint">
                        <span class="api-method api-method--get">GET</span>
                        <span class="api-endpoint-path">/api/v1/clients/<em>{id}</em></span>
                        <span class="api-scope">clients:read</span>
                        <span class="api-endpoint-desc">Full detail with contacts &amp; tags</span>
                    </div>
                    <div class="api-endpoint">
                        <span class="api-method api-method--patch">PATCH</span>
                        <span class="api-endpoint-path">/api/v1/clients/<em>{id}</em></span>
                        <span class="api-scope">clients:write</span>
                        <span class="api-endpoint-desc">Partial update</span>
                    </div>
                    <div class="api-endpoint">
                        <span class="api-method api-method--delete">DELETE</span>
                        <span class="api-endpoint-path">/api/v1/clients/<em>{id}</em></span>
                        <span class="api-scope">clients:write</span>
                        <span class="api-endpoint-desc">Permanent delete</span>
                    </div>
                </div>

                <p class="api-label">Fields (POST &amp; PATCH)</p>
                <table class="api-fields">
                    <thead>
                        <tr><th>Field</th><th>Type</th><th>POST</th><th>Description</th></tr>
                    </thead>
                    <tbody>
                        <tr><td><code>commercial_name</code></td><td><span class="api-type">string</span></td><td><span class="api-badge-req">required</span></td><td>Commercial / trading name</td></tr>
                        <tr><td><code>legal_name</code></td><td><span class="api-type">string</span></td><td><span class="api-badge-opt">optional</span></td><td>Legal entity name</td></tr>
                        <tr><td><code>cif</code></td><td><span class="api-type">string</span></td><td><span class="api-badge-opt">optional</span></td><td>Tax identification number</td></tr>
                        <tr><td><code>sector</code></td><td><span class="api-type">string</span></td><td><span class="api-badge-opt">optional</span></td><td>Sector name — must exist; returns error if not found</td></tr>
                        <tr><td><code>address</code></td><td><span class="api-type">string</span></td><td><span class="api-badge-opt">optional</span></td><td>Street address</td></tr>
                        <tr><td><code>postal_code</code></td><td><span class="api-type">string</span></td><td><span class="api-badge-opt">optional</span></td><td>Postal / ZIP code</td></tr>
                        <tr><td><code>city</code></td><td><span class="api-type">string</span></td><td><span class="api-badge-opt">optional</span></td><td>City</td></tr>
                        <tr><td><code>province</code></td><td><span class="api-type">string</span></td><td><span class="api-badge-opt">optional</span></td><td>Province / state / region</td></tr>
                        <tr><td><code>country</code></td><td><span class="api-type">string</span></td><td><span class="api-badge-opt">optional</span></td><td>Country</td></tr>
                        <tr><td><code>website</code></td><td><span class="api-type">string</span></td><td><span class="api-badge-opt">optional</span></td><td>Website URL</td></tr>
                        <tr><td><code>notes</code></td><td><span class="api-type">string</span></td><td><span class="api-badge-opt">optional</span></td><td>Free-text notes</td></tr>
                        <tr><td><code>tags</code></td><td><span class="api-type">string|string[]</span></td><td><span class="api-badge-opt">optional</span></td><td>One or more tag names — single name, comma-separated string, or array. Created if missing.</td></tr>
                        <tr><td><code>custom_fields</code></td><td><span class="api-type">object</span></td><td><span class="api-badge-opt">optional</span></td><td>Key/value pairs by field slug. Flat <code class="help-api-inline-code">"custom_fields.&lt;slug&gt;"</code> keys work the same way, on both POST and PATCH.</td></tr>
                    </tbody>
                </table>

                <p class="api-label api-label-mt">GET filters</p>
                <div class="api-filter-params api-filter-params-mb">
                    <?php foreach (['page','per_page (max 100)','commercial_name','legal_name','city','country','sector_id','tag_id','created_from','created_to'] as $p): ?>
                    <code class="help-api-inline-code"><?= htmlspecialchars($p, ENT_QUOTES, 'UTF-8') ?></code>
                    <?php endforeach; ?>
                </div>

                <p class="api-label">Example — batch create</p>
                <?php helpApiCodeBlock('POST /api/v1/clients', 'ex-clients-post', <<<"CODE"
[
  {
    <span class="ck">"commercial_name"</span>: <span class="cs">"Acme Corp"</span>,
    <span class="ck">"legal_name"</span>:      <span class="cs">"Acme Corporation S.L."</span>,
    <span class="ck">"sector"</span>:          <span class="cs">"Technology"</span>,
    <span class="ck">"city"</span>:            <span class="cs">"Madrid"</span>,
    <span class="ck">"country"</span>:         <span class="cs">"Spain"</span>,
    <span class="ck">"website"</span>:         <span class="cs">"https://acme.example.com"</span>,
    <span class="ck">"tags"</span>:            [<span class="cs">"Enterprise"</span>, <span class="cs">"Priority"</span>]
  }
]
CODE); ?>

                <p class="api-label api-label-mt">Example — GET single client</p>
                <?php helpApiCodeBlock('Response', 'ex-clients-get', <<<"CODE"
{
  <span class="ck">"success"</span>: <span class="cn">true</span>,
  <span class="ck">"data"</span>: {
    <span class="ck">"id"</span>: <span class="cn">5</span>,
    <span class="ck">"commercial_name"</span>: <span class="cs">"Acme Corp"</span>,
    <span class="ck">"sector"</span>:          <span class="cs">"Technology"</span>,
    <span class="ck">"city"</span>:            <span class="cs">"Madrid"</span>,
    <span class="ck">"tags"</span>:            [{ <span class="ck">"id"</span>: <span class="cn">1</span>, <span class="ck">"name"</span>: <span class="cs">"Enterprise"</span> }],
    <span class="ck">"contacts"</span>:        [{ <span class="ck">"id"</span>: <span class="cn">42</span>, <span class="ck">"full_name"</span>: <span class="cs">"Ivan Petrov"</span>, <span class="ck">"email"</span>: <span class="cs">"ivan@example.com"</span> }],
    <span class="ck">"custom_fields"</span>:   { <span class="ck">"contract_number"</span>: <span class="cs">"A-001"</span> }
  }
}
CODE); ?>
            </div>
        </section>

        <!-- ════════════════════════════════════════════ SECTORS ══ -->
        <section class="help-card" id="sectors">
            <?php helpApiCardHead($toc[3]); ?>
                    Manage industry sectors (reference list)
                </div>
            </div>

            <div class="help-card-body">
                <p class="api-desc-text">
                    Sectors are a curated reference list used to classify clients. They are <strong class="help-api-strong">not created automatically</strong> —
                    use the API (or the web UI) to manage them explicitly.
                    Deleting a sector that still has clients will <strong class="help-api-strong">deactivate</strong> it instead of deleting it,
                    and the response will indicate which action was taken.
                </p>

                <p class="api-label">Endpoints</p>
                <div class="api-endpoint-list">
                    <div class="api-endpoint">
                        <span class="api-method api-method--post">POST</span>
                        <span class="api-endpoint-path">/api/v1/sectors</span>
                        <span class="api-scope">sectors:write</span>
                        <span class="api-endpoint-desc">Batch create (up to 100) · 207</span>
                    </div>
                    <div class="api-endpoint">
                        <span class="api-method api-method--get">GET</span>
                        <span class="api-endpoint-path">/api/v1/sectors</span>
                        <span class="api-scope">sectors:read</span>
                        <span class="api-endpoint-desc">Full list (no pagination)</span>
                    </div>
                    <div class="api-endpoint">
                        <span class="api-method api-method--get">GET</span>
                        <span class="api-endpoint-path">/api/v1/sectors/<em>{id}</em></span>
                        <span class="api-scope">sectors:read</span>
                        <span class="api-endpoint-desc">Single sector with clients_count</span>
                    </div>
                    <div class="api-endpoint">
                        <span class="api-method api-method--patch">PATCH</span>
                        <span class="api-endpoint-path">/api/v1/sectors/<em>{id}</em></span>
                        <span class="api-scope">sectors:write</span>
                        <span class="api-endpoint-desc">Update name or is_active</span>
                    </div>
                    <div class="api-endpoint">
                        <span class="api-method api-method--delete">DELETE</span>
                        <span class="api-endpoint-path">/api/v1/sectors/<em>{id}</em></span>
                        <span class="api-scope">sectors:write</span>
                        <span class="api-endpoint-desc">Delete or deactivate if has clients</span>
                    </div>
                </div>

                <p class="api-label">Fields</p>
                <table class="api-fields">
                    <thead>
                        <tr><th>Field</th><th>Type</th><th>POST</th><th>Description</th></tr>
                    </thead>
                    <tbody>
                        <tr><td><code>name</code></td><td><span class="api-type">string</span></td><td><span class="api-badge-req">required</span></td><td>Sector name. Slug is auto-generated.</td></tr>
                        <tr><td><code>is_active</code></td><td><span class="api-type">boolean</span></td><td><span class="api-badge-opt">PATCH only</span></td><td>Activate or deactivate the sector</td></tr>
                    </tbody>
                </table>

                <p class="api-label api-label-mt">GET filters</p>
                <div class="api-filter-params api-filter-params-mb">
                    <?php foreach (['name (partial match)','is_active (0 or 1)'] as $p): ?>
                    <code class="help-api-inline-code"><?= htmlspecialchars($p, ENT_QUOTES, 'UTF-8') ?></code>
                    <?php endforeach; ?>
                </div>

                <p class="api-label">Example — create &amp; delete</p>
                <?php helpApiCodeBlock('POST /api/v1/sectors', 'ex-sectors-post', <<<"CODE"
[{ <span class="ck">"name"</span>: <span class="cs">"Technology"</span> }, { <span class="ck">"name"</span>: <span class="cs">"Healthcare"</span> }]
CODE); ?>

                <p class="api-sectors-delete-note">DELETE response — the <code class="help-api-inline-code">action</code> field tells you what happened:</p>
                <?php helpApiCodeBlock('Response', 'ex-sectors-del', <<<"CODE"
{ <span class="ck">"success"</span>: <span class="cn">true</span>, <span class="ck">"data"</span>: { <span class="ck">"id"</span>: <span class="cn">3</span>, <span class="ck">"action"</span>: <span class="cs">"deactivated"</span> } }
<span class="cc">// or: "action": "deleted"  — when no clients were linked</span>
CODE); ?>
            </div>
        </section>

        <!-- ══════════════════════════════════════════════ TAGS ══ -->
        <section class="help-card" id="tags">
            <?php helpApiCardHead($toc[4]); ?>
                    Manage tags used on contacts and clients
                </div>
            </div>

            <div class="help-card-body">
                <p class="api-desc-text">
                    Tags can be applied to both contacts and clients. They are <strong class="help-api-strong">created automatically</strong>
                    when referenced by name during contact or client creation, but you can also manage them directly here.
                    Deleting a tag removes it from all linked contacts and clients (ON DELETE CASCADE).
                </p>

                <p class="api-label">Endpoints</p>
                <div class="api-endpoint-list">
                    <div class="api-endpoint">
                        <span class="api-method api-method--post">POST</span>
                        <span class="api-endpoint-path">/api/v1/tags</span>
                        <span class="api-scope">tags:write</span>
                        <span class="api-endpoint-desc">Batch create (up to 100) · 207</span>
                    </div>
                    <div class="api-endpoint">
                        <span class="api-method api-method--get">GET</span>
                        <span class="api-endpoint-path">/api/v1/tags</span>
                        <span class="api-scope">tags:read</span>
                        <span class="api-endpoint-desc">Full list (no pagination)</span>
                    </div>
                    <div class="api-endpoint">
                        <span class="api-method api-method--get">GET</span>
                        <span class="api-endpoint-path">/api/v1/tags/<em>{id}</em></span>
                        <span class="api-scope">tags:read</span>
                        <span class="api-endpoint-desc">Single tag</span>
                    </div>
                    <div class="api-endpoint">
                        <span class="api-method api-method--patch">PATCH</span>
                        <span class="api-endpoint-path">/api/v1/tags/<em>{id}</em></span>
                        <span class="api-scope">tags:write</span>
                        <span class="api-endpoint-desc">Update name or color</span>
                    </div>
                    <div class="api-endpoint">
                        <span class="api-method api-method--delete">DELETE</span>
                        <span class="api-endpoint-path">/api/v1/tags/<em>{id}</em></span>
                        <span class="api-scope">tags:write</span>
                        <span class="api-endpoint-desc">Permanent delete (cascades)</span>
                    </div>
                </div>

                <p class="api-label">Fields</p>
                <table class="api-fields">
                    <thead>
                        <tr><th>Field</th><th>Type</th><th>POST</th><th>Description</th></tr>
                    </thead>
                    <tbody>
                        <tr><td><code>name</code></td><td><span class="api-type">string</span></td><td><span class="api-badge-req">required</span></td><td>Tag label. Slug is auto-generated.</td></tr>
                        <tr><td><code>color</code></td><td><span class="api-type">string</span></td><td><span class="api-badge-opt">optional</span></td><td>Hex colour, e.g. <code>#3b82f6</code>. Pass <code>null</code> to clear.</td></tr>
                    </tbody>
                </table>

                <p class="api-label api-label-mt">GET filters</p>
                <div class="api-filter-params api-filter-params-mb">
                    <code class="help-api-inline-code">name (partial match)</code>
                </div>

                <p class="api-label">Example — create with color &amp; PATCH</p>
                <?php helpApiCodeBlock('POST /api/v1/tags', 'ex-tags-post', <<<"CODE"
[
  { <span class="ck">"name"</span>: <span class="cs">"VIP"</span>,      <span class="ck">"color"</span>: <span class="cs">"#f59e0b"</span> },
  { <span class="ck">"name"</span>: <span class="cs">"Enterprise"</span>, <span class="ck">"color"</span>: <span class="cs">"#3b82f6"</span> }
]
CODE); ?>

                <?php helpApiCodeBlock('PATCH /api/v1/tags/7', 'ex-tags-patch', <<<"CODE"
{ <span class="ck">"name"</span>: <span class="cs">"Premium"</span>, <span class="ck">"color"</span>: <span class="cs">"#8b5cf6"</span> }
CODE); ?>

                <?php helpApiCodeBlock('Response', 'ex-tags-resp', <<<"CODE"
{
  <span class="ck">"success"</span>: <span class="cn">true</span>,
  <span class="ck">"data"</span>: { <span class="ck">"id"</span>: <span class="cn">7</span>, <span class="ck">"name"</span>: <span class="cs">"Premium"</span>, <span class="ck">"slug"</span>: <span class="cs">"premium"</span>, <span class="ck">"color"</span>: <span class="cs">"#8b5cf6"</span> }
}
CODE); ?>
            </div>
        </section>

    </div><!-- /.help-content -->
</div><!-- /.help-layout -->