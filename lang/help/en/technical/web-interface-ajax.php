<?php

return [
    'title' => 'Web interface and AJAX',
    'description' => 'ContactCore server-rendered HTML interface, client-side workflows, AJAX contracts, and rules for developing interactive features.',
    'icon' => 'ph-arrow-elbow-down-right',
    'sections' => [
        [
            'id' => 'web-overview',
            'title' => 'Overall interface model',
            'paragraphs' => [
                'ContactCore uses server rendering with targeted progressive enhancement. PHP produces a complete HTML page that can be opened and submitted without a client framework, while plain JavaScript adds interactivity: drop-down panels, search without reloads, token pickers, bulk actions, value copying, and AI-tool workflows.',
                'This is not a SPA. Navigation between main pages, list filtering, sorting, pagination, and most save operations use ordinary HTTP requests with a full document load. AJAX is used where updating a small part of the interface is noticeably more convenient than a complete transition: autocomplete, global search, value selection, and long step-by-step operations.',
                'The project has no frontend bundler, module loader, or JavaScript framework. CSS and JS live in public_html/assets and are included directly. Dependencies between scripts must therefore remain simple and explicit, and each page-specific file must exit safely if its required DOM element is absent.',
            ],
            'examples' => [[
                'title' => 'Two types of user workflow',
                'code' => <<<'CODE'
Full HTML operation
Browser → Router → Controller → Repository → View → Layout → HTML

Targeted AJAX operation
Browser fetch() → /ajax/* → AjaxController → Repository/Service → JSON
                       ↓
                 update part of the DOM
CODE,
            ]],
        ],
        [
            'id' => 'web-rendering',
            'title' => 'Rendering an HTML page',
            'paragraphs' => [
                'A controller prepares data and calls View::render(). The first argument specifies a view relative to app/Views, the second contains page variables, and the optional third argument changes the layout. View extracts data through extract(..., EXTR_SKIP), buffers the view output, and passes the completed fragment to the layout as content.',
                'The main layout is app/Views/layouts/main.php. It builds the common shell: sidebar, top bar, global search, profile, main area, and shared window.I18N translations. base.css and admin.js are always loaded. The controller supplies additional CSS and JS filenames in styles and scripts; page scripts load after admin.js.',
                'Build HTML URLs with the url() helper, which calls Auth::url() and escapes the result. The application may be installed outside the domain root, and a hard-coded link such as /ajax/tags/search can lose the base path. The title, styles, scripts, and all view data should be declared in one render call.',
            ],
            'examples' => [[
                'title' => 'Typical view call',
                'code' => <<<'PHP'
View::render('projects/index', [
    'title'    => Lang::get('projects.title'),
    'styles'   => ['data.css', 'projects.css'],
    'scripts'  => ['list-page.js', 'projects.js'],
    'projects' => $projects,
    'page'     => $page,
]);
PHP,
            ]],
        ],
        [
            'id' => 'web-views',
            'title' => 'Views and markup reuse',
            'paragraphs' => [
                'Views are grouped by section: app/Views/contacts, clients, users, and so on. index.php files render lists, a shared _form.php directly serves creation and editing, and show.php renders a record. The controller passes the form mode through isEdit. Repeated elements that do not belong to one section live in app/Views/partials, as with custom-field input.',
                'A view must not run SQL or make business decisions. It may prepare text, build URLs, select a CSS class, and make small transformations of already loaded data. If a template needs another record set, the controller retrieves it through a repository before View::render().',
                'User and database values are output through the short e() helper from Illuminate Support. Translations use t() or Lang::get(), the latter when substitutions are needed. An array passed in a data attribute is first serialized with json_encode() and then escaped with e(). Dynamic values must never be concatenated into inline JavaScript.',
            ],
            'examples' => [[
                'title' => 'Passing PHP data safely to a component',
                'code' => <<<'HTML'
<?php $selectedJson = json_encode($selected, JSON_UNESCAPED_UNICODE); ?>

<div class="token-picker"
     data-endpoint="<?= url('/ajax/projects/search') ?>"
     data-name="project_ids[]"
     data-selected="<?= e($selectedJson) ?>">
</div>
HTML,
            ]],
        ],
        [
            'id' => 'web-assets',
            'title' => 'CSS, JavaScript, and DOM contracts',
            'paragraphs' => [
                'base.css contains the shared shell, typography, buttons, fields, and global components. Area-specific files such as contacts.css, settings.css, and api.css cover their sections. data.css is used by import and export pages. Add a new style to an appropriate existing file or to a page-specific file when the component is specialized and large enough to justify separation.',
                'admin.js contains global components: top-bar search, token picker, live catalog search, icon selection, profile menu, and mobile sidebar. list-page.js handles filter panels, row selection, and bulk actions. Other files belong to one page. Most scripts use an IIFE to avoid globals; older inline calls should gradually move to data attributes and addEventListener().',
                'HTML and JavaScript communicate through data-* attributes and stable component classes. PHP provides URLs, the CSRF token, translated messages, and initial state; JS reads dataset. The server must not rely on a component’s visual state: a hidden button, disabled attribute, or CSS class is not an access check or validation.',
            ],
            'examples' => [[
                'title' => 'Self-contained page script',
                'code' => <<<'JS'
(function () {
    var root = document.querySelector('[data-project-widget]');
    if (!root) {
        return;
    }

    var endpoint = root.dataset.endpoint;
    root.addEventListener('click', function (event) {
        var button = event.target.closest('[data-project-action]');
        if (!button) return;
        // Component interaction.
    });
})();
JS,
            ]],
        ],
        [
            'id' => 'web-lists',
            'title' => 'Lists, filters, and bulk actions',
            'paragraphs' => [
                'Client and contact lists remain server-rendered. GET parameters describe filters, sort, dir, page, and per_page; the repository applies them to count and paginate; the view preserves active parameters in sorting links, pagination, and chips. This makes URLs shareable, prevents refresh from resetting the selection, and keeps browser navigation predictable.',
                'Shared thSort(), renderPagination(), and date formatting through formatDate() live in app/Helpers/view_helpers.php. The controller must permit only known sort names, and the repository maps them to an SQL-column allowlist. Filter values use Query Builder bindings; a column name never comes directly from the request.',
                'list-page.js does not submit data itself. It opens panels, counts selected rows, synchronizes select-all, and displays the count. The final bulk operation remains an ordinary POST form with a CSRF token. The server checks permission separately for each bulk_action value.',
            ],
            'examples' => [[
                'title' => 'List state in the URL',
                'code' => <<<'CODE'
/contacts
  ?tag_ids[]=4
  &client_id=12
  &email_status=valid
  &sort=created_at
  &dir=desc
  &page=3
CODE,
            ]],
        ],
        [
            'id' => 'web-forms',
            'title' => 'Forms and modifying operations',
            'paragraphs' => [
                'Creation, editing, deletion, import, export, and settings use POST. Every HTML form contains Csrf::field(). After a successful save, the controller redirects, implementing Post/Redirect/Get and preventing form resubmission on refresh.',
                'HTML validation attributes such as required and type=email improve the interface but are not a trust boundary. The controller or service normalizes and validates input again. On error, the form is rendered with the entered values and a clear message; secrets and passwords must never be returned to HTML.',
                'Deletion uses a POST form even when it appears as a link or icon. One form may use formaction and formnovalidate for several actions. confirm() reduces accidental clicks but does not replace permission, CSRF, or server-side identifier validation.',
            ],
            'examples' => [[
                'title' => 'Minimal safe form',
                'code' => <<<'HTML'
<form method="post" action="<?= url('/projects/store') ?>">
    <?= Csrf::field() ?>
    <input name="name" required>
    <button type="submit">Save</button>
</form>
HTML,
            ]],
        ],
        [
            'id' => 'ajax-surface',
            'title' => 'Current AJAX routes',
            'paragraphs' => [
                'AJAX routes are registered in public_html/index.php and handled by AjaxController. This is an internal browser interface using session authentication. It must not be confused with public /api/v1, which has separate authentication, scopes, controllers, response format, and logging.',
                'Search GET routes make no changes. global-search combines contacts and clients; clients/search and tags/search support page and has_more; clients/field returns unique standard-field values; custom-field/values returns unique text values for a custom field. Sector and icon searches require sectors.manage.',
                'POST routes perform batch email inspection and AI operations: company discovery, saving the result, and skipping a row. Every route passes the global CSRF check. The /ai page, batch email inspection, and all three related AI handlers share auth = admin; a direct POST by a regular user receives JSON 403.',
            ],
            'examples' => [[
                'title' => 'Internal route map',
                'code' => <<<'CODE'
GET  /ajax/global-search
GET  /ajax/clients/search
GET  /ajax/clients/field
GET  /ajax/tags/search
GET  /ajax/sectors/search          sectors.manage
GET  /ajax/icons/search            sectors.manage
GET  /ajax/custom-field/values

POST /ajax/contacts/inspect-email-batch  admin
POST /ajax/contacts/gemini-company      admin
POST /ajax/contacts/company             admin
POST /ajax/contacts/company/skip        admin
CODE,
            ]],
        ],
        [
            'id' => 'ajax-contract',
            'title' => 'Request and response formats',
            'paragraphs' => [
                'A GET search receives q and optionally page through the query string. The basic list contract is an items object; paginated suggestions also return has_more. Every item must contain at least id and name because TokenPicker depends on that format. Components may use additional color, slug, icon, type, meta, or url fields.',
                'Current AJAX POST requests use application/x-www-form-urlencoded through URLSearchParams, so data is available in $_POST. The body must contain _csrf_token. Accept: application/json and X-Requested-With headers indicate client intent, although the server does not currently switch behavior based on them.',
                'AjaxController has no common envelope. A successful response depends on the operation, while an error normally uses {error: string} with an appropriate 401, 403, 404, 422, 500, or 502 status. The client checks response.ok before using JSON. One exception matters: the global CSRF check runs before the controller and returns plain text for 419, so a universal client must not call response.json() unconditionally.',
            ],
            'examples' => [
                ['title' => 'Paginated search contract', 'code' => <<<'JSON'
GET /ajax/clients/search?q=acme&page=2

{
  "items": [
    {"id": 42, "name": "Acme Studio"}
  ],
  "has_more": false
}
JSON],
                ['title' => 'Typical operation error', 'code' => <<<'CODE'
HTTP/1.1 422 Unprocessable Entity
Content-Type: application/json; charset=utf-8

{"error": "Contact does not have a valid email address"}
CODE],
            ],
        ],
        [
            'id' => 'ajax-client',
            'title' => 'Client request and DOM updates',
            'paragraphs' => [
                'Search begins after a debounce so a request is not sent for every keystroke. Loading, empty, error, and success states must be distinguishable. A modifying operation disables its button and restores it in finally. A repeated click must not start a duplicate write.',
                'Response data is inserted into the DOM with textContent and element creation. innerHTML is acceptable for a predefined static template, but not for name, error, or other server data. Links from the response should be generated on the server through Auth::url() or built from a trusted id using a known route.',
                'Current search components do not cancel the previous fetch. On a slow network, an older response may arrive after a newer one and replace current results. New components should use AbortController or a request-version counter. Session loss must also be handled: 401 must not look like an empty result.',
            ],
            'examples' => [[
                'title' => 'Reliable GET search that cancels the previous request',
                'code' => <<<'JS'
var activeRequest = null;

async function search(query) {
    if (activeRequest) activeRequest.abort();
    activeRequest = new AbortController();

    var response = await fetch(
        endpoint + '?q=' + encodeURIComponent(query),
        {
            headers: { Accept: 'application/json' },
            signal: activeRequest.signal,
        }
    );

    if (!response.ok) throw new Error('Search failed');
    return response.json();
}
JS,
            ]],
        ],
        [
            'id' => 'web-token-picker',
            'title' => 'TokenPicker and remote catalogs',
            'paragraphs' => [
                'TokenPicker is a shared admin.js component for selecting one or several values. Its source div describes the component with data-name, data-selected, data-placeholder, data-endpoint, or data-options. JavaScript builds the search field, dropdown, chips, and hidden inputs, so the server receives ordinary form values and does not depend on a JavaScript body format.',
                'data-options enables local mode without AJAX. data-endpoint enables remote search. data-max=1 makes the component single-select, data-with-color=1 displays a tag color, and data-paginate=1 adds page and loads the next page on scroll. data-selected must be a JSON array of objects with id and name.',
                'A remote endpoint returns {items: [{id, name}], has_more}. Selected values are stored as hidden inputs with the name from data-name. For multiple selection, the name must end in [], such as tag_ids[]. When re-rendering a form, the controller must provide selected objects, not only ids, or the chips cannot be labeled.',
            ],
            'examples' => [[
                'title' => 'Remote multiple selection',
                'code' => <<<'HTML'
<div class="token-picker"
     data-endpoint="/ajax/tags/search"
     data-name="tag_ids[]"
     data-selected='[{"id":4,"name":"Hot","color":"#ef4444"}]'
     data-with-color="1"
     data-paginate="1"
     data-placeholder="Search tags">
</div>
HTML,
            ]],
        ],
        [
            'id' => 'ajax-security',
            'title' => 'Authorization, permissions, and CSRF',
            'paragraphs' => [
                'AJAX access is defined by the route policy in public_html/index.php, which Router checks before calling AjaxController. Specify response = json for internal endpoints: a guest receives JSON 401 and a user without permission receives JSON 403. auth = admin limits administrative actions, while permission checks a functional permission. Page, menu-item, or button visibility does not protect a direct HTTP request.',
                'In public_html/index.php, every POST except /api/v1 is checked with Csrf::validate() before dispatch. The token is created per session and passed in _csrf_token. GET must not modify data because it is not covered by CSRF. The public API is excluded from the session check because it uses its own request authentication.',
                'CSP limits connect-src to self, so browser fetch may call ContactCore but not an arbitrary external domain. Gemini is called by server-side PHP through Guzzle. CSP currently permits unsafe-inline for compatibility with inline onclick and style; new components must not increase that dependency.',
            ],
            'examples' => [[
                'title' => 'Protected AJAX route and thin handler',
                'code' => <<<'PHP'
$router->get('/ajax/contacts/edit-search', [$ajaxController, 'editableContactsSearch'], [
    'permission' => 'contacts.edit',
    'response' => 'json',
]);

public function editableContactsSearch(): void
{
    $query = trim($_GET['q'] ?? '');
    $items = $this->contacts->search($query, 20);

    $this->json(['items' => $items]);
}
PHP,
            ]],
        ],
        [
            'id' => 'web-new-feature',
            'title' => 'Adding a new interactive workflow',
            'paragraphs' => [
                'First decide whether AJAX is needed. If an operation ends by navigating to a record or list, a normal POST form is simpler, more accessible, and more reliable. AJAX is justified for suggestions, a local state change, a background step, or an operation where a full reload would destroy the working context.',
                'A new workflow adds a repository or service method, an AjaxController method, a route in public_html/index.php, a data contract in the view, and a handler in the appropriate JS file. If the component belongs to one page, include the file through the controller’s scripts. Do not repeat a business rule in AjaxController: it adapts HTTP and invokes the shared service.',
                'Before completion, test success, an empty result, validation failure, 401, 403, 419, 500, a slow network, a repeated click, and an element removed from the DOM. The interface must remain understandable at mobile widths and operable by keyboard; interactive buttons need type=button, an accessible name, and current aria-expanded or aria-disabled where applicable.',
            ],
            'examples' => [[
                'title' => 'Implementation sequence',
                'code' => <<<'CODE'
1. Define input, result, errors, and permission
2. Implement the reusable operation in a Service/Repository
3. Add AjaxController::method() and the route
4. Pass endpoint, CSRF, and text through data-*
5. Include page JavaScript through scripts
6. Update the DOM safely and show request states
7. Test the HTML workflow, access, CSRF, locales, and base path
CODE,
            ]],
        ],
    ],
];
