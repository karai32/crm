<?php
$activeFilters = array_filter($filters, function ($value) {
    return is_array($value) ? !empty($value) : ($value !== '' && $value !== 0 && $value !== null);
});

$exportUrl     = Auth::url('/exports/contacts'      . (!empty($activeFilters) ? '?' . http_build_query($activeFilters) : ''));
$exportXlsxUrl = Auth::url('/exports/contacts-xlsx' . (!empty($activeFilters) ? '?' . http_build_query($activeFilters) : ''));
$canExport = Auth::can('exports.use');
$canCreateContacts = Auth::can('contacts.create');
$canEditContacts = Auth::can('contacts.edit');
$canDeleteContacts = Auth::can('contacts.delete');
$selectedFilterTagIds = $filters['tag_ids'] ?? [];
$preselectedFilterTagsJson = json_encode(array_values(array_map(function ($tag) {
    return ['id' => (int) $tag['id'], 'name' => $tag['name'], 'color' => $tag['color'] ?? null];
}, array_filter($filterTags, function ($tag) use ($selectedFilterTagIds) {
    return in_array((int) $tag['id'], $selectedFilterTagIds ?? [], true);
}))));

$from = $total > 0 ? ($page - 1) * $perPage + 1 : 0;
$to   = min($page * $perPage, $total);

function contactStatusClass(string $s): string {
    static $map = [
        'lead'      => 'status-lead',
        'client'    => 'status-client',
        'partner'   => 'status-partner',
        'prospect'  => 'status-prospect',
        'supplier'  => 'status-supplier',
        'pending'   => 'status-pending',
        'hot'       => 'status-hot',
        'cold'      => 'status-cold',
    ];
    return $map[strtolower(trim($s))] ?? 'status-default';
}

function paginationRange(int $current, int $last): array {
    $pages = [];
    for ($i = 1; $i <= $last; $i++) {
        if ($i === 1 || $i === $last || abs($i - $current) <= 2) {
            $pages[] = $i;
        }
    }
    $result = [];
    $prev   = null;
    foreach ($pages as $p) {
        if ($prev !== null && $p - $prev > 1) {
            $result[] = '...';
        }
        $result[] = $p;
        $prev = $p;
    }
    return $result;
}
?>

<!-- Header -->
<div class="page-header contacts-header">
    <div>
        <h1>Contacts</h1>
        <span class="count-label"><?= (int) $total ?> contacts found</span>
    </div>
    <div class="page-actions">
        <?php if ($canExport): ?>
        <a class="btn btn-outlined" href="<?= htmlspecialchars($exportUrl, ENT_QUOTES, 'UTF-8') ?>">Export CSV</a>
        <a class="btn btn-outlined" href="<?= htmlspecialchars($exportXlsxUrl, ENT_QUOTES, 'UTF-8') ?>">Export XLSX</a>
        <?php endif; ?>
        <?php if ($canCreateContacts): ?>
        <a class="btn btn-green"    href="<?= htmlspecialchars(Auth::url('/contacts/create'), ENT_QUOTES, 'UTF-8') ?>">Create contact</a>
        <?php endif; ?>
    </div>
</div>

<!-- Filters -->
<?php
$extendedKeys = ['client_id', 'sector_id', 'country', 'province', 'created_from', 'created_to', 'updated_from', 'updated_to'];
$hasExtended  = !empty($filters['custom_fields']) || (bool) array_filter($extendedKeys, fn ($k) => !empty($filters[$k]));
?>
<div class="contacts-filters">
    <form method="get" action="<?= htmlspecialchars(Auth::url('/contacts'), ENT_QUOTES, 'UTF-8') ?>">

        <!-- Primary filters -->
        <div class="filter-grid">
            <div class="field">
                <label for="first_name">First name</label>
                <input id="first_name" type="text" name="first_name"
                       value="<?= htmlspecialchars($filters['first_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="field">
                <label for="last_name">Last name</label>
                <input id="last_name" type="text" name="last_name"
                       value="<?= htmlspecialchars($filters['last_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="field">
                <label for="email">Email</label>
                <input id="email" type="text" name="email"
                       value="<?= htmlspecialchars($filters['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="field">
                <label for="phone">Phone</label>
                <input id="phone" type="text" name="phone"
                       value="<?= htmlspecialchars($filters['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="field">
                <label for="is_company">Type</label>
                <select id="is_company" name="is_company">
                    <option value="">All</option>
                    <option value="1" <?= (($filters['is_company'] ?? '') === '1') ? 'selected' : '' ?>>Company</option>
                    <option value="0" <?= (($filters['is_company'] ?? '') === '0') ? 'selected' : '' ?>>Person</option>
                </select>
            </div>
            <div class="field">
                <label>Tags</label>
                <div class="token-picker token-picker--filter"
                     data-endpoint="<?= htmlspecialchars(Auth::url('/ajax/tags/search'), ENT_QUOTES, 'UTF-8') ?>"
                     data-name="tag_ids[]"
                     data-with-color="1"
                     data-placeholder="All tags"
                     data-selected="<?= htmlspecialchars($preselectedFilterTagsJson, ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>
        </div>

        <!-- Extended filters -->
        <div class="filter-grid filter-grid--extra <?= $hasExtended ? 'open' : '' ?>" id="filterExtra">
            <div class="field">
                <label for="client_id">Linked client</label>
                <select id="client_id" name="client_id">
                    <option value="">All clients</option>
                    <?php foreach ($filterClients as $client): ?>
                        <option value="<?= (int) $client['id'] ?>"
                            <?= ((int) ($filters['client_id'] ?? 0) === (int) $client['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($client['commercial_name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="sector_id">Client sector</label>
                <select id="sector_id" name="sector_id">
                    <option value="">All sectors</option>
                    <?php foreach ($filterSectors as $sector): ?>
                        <option value="<?= (int) $sector['id'] ?>"
                            <?= ((int) ($filters['sector_id'] ?? 0) === (int) $sector['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($sector['name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="country">Client country</label>
                <input id="country" type="text" name="country"
                       value="<?= htmlspecialchars($filters['country'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="field">
                <label for="province">Client province</label>
                <input id="province" type="text" name="province"
                       value="<?= htmlspecialchars($filters['province'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="field">
                <label for="created_from">Created from</label>
                <input id="created_from" type="date" name="created_from"
                       value="<?= htmlspecialchars($filters['created_from'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="field">
                <label for="created_to">Created to</label>
                <input id="created_to" type="date" name="created_to"
                       value="<?= htmlspecialchars($filters['created_to'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="field">
                <label for="updated_from">Updated from</label>
                <input id="updated_from" type="date" name="updated_from"
                       value="<?= htmlspecialchars($filters['updated_from'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="field">
                <label for="updated_to">Updated to</label>
                <input id="updated_to" type="date" name="updated_to"
                       value="<?= htmlspecialchars($filters['updated_to'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <?php if (!empty($customFilterFields)): ?>
            <div class="filter-grid-sep"></div>
            <?php foreach ($customFilterFields as $field): ?>
            <div class="field">
                <label for="custom_filter_<?= (int) $field['id'] ?>">
                    <?= htmlspecialchars($field['name'], ENT_QUOTES, 'UTF-8') ?>
                </label>
                <input id="custom_filter_<?= (int) $field['id'] ?>"
                       type="text"
                       name="custom_fields[<?= (int) $field['id'] ?>]"
                       value="<?= htmlspecialchars($filters['custom_fields'][(int) $field['id']] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="contacts-filter-footer">
            <button type="button" id="filterToggleBtn"
                    class="filter-toggle-btn <?= $hasExtended ? 'open' : '' ?>">
                <span class="filter-toggle-label"><?= $hasExtended ? 'Less filters' : 'More filters' ?></span>
                <svg class="filter-toggle-chevron" viewBox="0 0 10 6" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 1l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
            <div class="contacts-filter-actions">
                <button class="btn btn-primary" type="submit">Filter</button>
                <a class="btn btn-outlined" href="<?= htmlspecialchars(Auth::url('/contacts'), ENT_QUOTES, 'UTF-8') ?>">Reset</a>
            </div>
        </div>

    </form>
</div>
<script>
(function () {
    var btn   = document.getElementById('filterToggleBtn');
    var extra = document.getElementById('filterExtra');
    if (!btn || !extra) { return; }
    btn.addEventListener('click', function () {
        var open = extra.classList.toggle('open');
        btn.classList.toggle('open', open);
        btn.querySelector('.filter-toggle-label').textContent = open ? 'Less filters' : 'More filters';
    });
})();
</script>

<!-- Table -->
<?php if (empty($contacts)): ?>
    <div class="contacts-table-card" style="padding:28px 20px; color:var(--color-neutral); font-size:14px;">
        No contacts found.
    </div>
<?php else: ?>
<div class="contacts-table-card">
    <?php if ($canEditContacts): ?>
    <form method="post" action="<?= htmlspecialchars(Auth::url('/contacts/bulk-tags'), ENT_QUOTES, 'UTF-8') ?>">
        <?= Csrf::field() ?>
        <div class="bulk-tags-bar">
            <div class="bulk-tags-picker">
                <div class="token-picker token-picker--filter"
                     data-endpoint="<?= htmlspecialchars(Auth::url('/ajax/tags/search'), ENT_QUOTES, 'UTF-8') ?>"
                     data-name="tag_ids[]"
                     data-with-color="1"
                     data-placeholder="Choose tags..."
                     data-selected="[]">
                </div>
            </div>
            <select name="bulk_action" aria-label="Bulk tag action">
                <option value="add">Add tags</option>
                <option value="remove">Remove tags</option>
            </select>
            <button class="btn btn-outlined" type="submit">Apply</button>
        </div>
    <?php endif; ?>

    <table class="contacts-table">
        <thead>
            <tr>
                <th class="col-select"></th>
                <th>ID</th>
                <th>First name</th>
                <th>Last name</th>
                <th>Email</th>
                <th>Tags</th>
                <th>Clients</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($contacts as $contact): ?>
            <tr>
                <td class="col-select">
                    <?php if ($canEditContacts): ?>
                    <input type="checkbox" name="contact_ids[]" value="<?= (int) $contact['id'] ?>" aria-label="Select contact">
                    <?php endif; ?>
                </td>
                <td class="col-id"><?= (int) $contact['id'] ?></td>
                <td class="col-name"><?= htmlspecialchars($contact['first_name'], ENT_QUOTES, 'UTF-8') ?></td>
                <td class="col-name"><?= htmlspecialchars($contact['last_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                <td class="col-email"><?= htmlspecialchars($contact['email'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td class="col-tags">
                    <?php foreach ($contactTags[(int) $contact['id']] ?? [] as $tag): ?>
                        <?php $c = htmlspecialchars($tag['color'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                        <span class="tag-badge" <?= $c ? 'style="background:' . $c . '22;border-color:' . $c . '44;color:' . $c . '"' : '' ?>>
                            <?php if ($c): ?>
                                <span class="tag-color-dot" style="background:<?= $c ?>"></span>
                            <?php endif; ?>
                            <?= htmlspecialchars($tag['name'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    <?php endforeach; ?>
                </td>
                <td class="col-clients">
                    <?php foreach ($contactClients[(int) $contact['id']] ?? [] as $i => $client): ?>
                        <?php if ($i > 0): ?><span class="col-clients-sep">,</span><?php endif; ?>
                        <a class="col-client-link" href="<?= htmlspecialchars(Auth::url('/clients/show?id=' . (int) $client['id']), ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($client['commercial_name'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    <?php endforeach; ?>
                </td>
                <td>
                    <div class="action-links">
                        <a class="action-view"
                           href="<?= htmlspecialchars(Auth::url('/contacts/show?id=' . $contact['id']), ENT_QUOTES, 'UTF-8') ?>">View</a>
                        <?php if ($canEditContacts): ?>
                        <a class="action-edit"
                           href="<?= htmlspecialchars(Auth::url('/contacts/edit?id=' . $contact['id']), ENT_QUOTES, 'UTF-8') ?>">Edit</a>
                        <?php endif; ?>
                        <?php if ($canDeleteContacts): ?>
                        <a class="action-delete"
                           href="<?= htmlspecialchars(Auth::url('/contacts/delete?id=' . $contact['id']), ENT_QUOTES, 'UTF-8') ?>"
                           onclick="return confirm('Delete this contact?')">Delete</a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($canEditContacts): ?>
    </form>
    <?php endif; ?>

    <div class="contacts-pagination">
        <span>Showing <?= $from ?> to <?= $to ?> of <?= (int) $total ?> entries</span>

        <div class="pagination-pages">
            <?php if ($page > 1): ?>
                <a class="page-btn"
                   href="<?= htmlspecialchars(Auth::url('/contacts?' . http_build_query(array_merge($activeFilters, ['page' => $page - 1]))), ENT_QUOTES, 'UTF-8') ?>">
                    Prev
                </a>
            <?php else: ?>
                <span class="page-btn disabled">Prev</span>
            <?php endif; ?>

            <?php foreach (paginationRange($page, $totalPages) as $p): ?>
                <?php if ($p === '...'): ?>
                    <span class="page-ellipsis">...</span>
                <?php elseif ($p === $page): ?>
                    <span class="page-btn active"><?= $p ?></span>
                <?php else: ?>
                    <a class="page-btn"
                       href="<?= htmlspecialchars(Auth::url('/contacts?' . http_build_query(array_merge($activeFilters, ['page' => $p]))), ENT_QUOTES, 'UTF-8') ?>">
                        <?= $p ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>

            <?php if ($page < $totalPages): ?>
                <a class="page-btn"
                   href="<?= htmlspecialchars(Auth::url('/contacts?' . http_build_query(array_merge($activeFilters, ['page' => $page + 1]))), ENT_QUOTES, 'UTF-8') ?>">
                    Next
                </a>
            <?php else: ?>
                <span class="page-btn disabled">Next</span>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>
