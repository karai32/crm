<?php
$activeFilters = array_filter($filters, function ($value) {
    return is_array($value) ? !empty($value) : ($value !== '' && $value !== 0 && $value !== null);
});

$exportUrl     = Auth::url('/exports/contacts'      . (!empty($activeFilters) ? '?' . http_build_query($activeFilters) : ''));
$exportXlsxUrl = Auth::url('/exports/contacts-xlsx' . (!empty($activeFilters) ? '?' . http_build_query($activeFilters) : ''));
$canExport          = Auth::can('exports.use');
$canCreateContacts  = Auth::can('contacts.create');
$canEditContacts    = Auth::can('contacts.edit');
$canDeleteContacts  = Auth::can('contacts.delete');

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
        'lead'     => 'status-lead',
        'client'   => 'status-client',
        'partner'  => 'status-partner',
        'prospect' => 'status-prospect',
        'supplier' => 'status-supplier',
        'pending'  => 'status-pending',
        'hot'      => 'status-hot',
        'cold'     => 'status-cold',
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
        if ($prev !== null && $p - $prev > 1) { $result[] = '...'; }
        $result[] = $p;
        $prev = $p;
    }
    return $result;
}

/* --- Build filter chips ---------------------------------------- */
$selectedTagObjects = array_values(array_filter($filterTags, function ($tag) use ($selectedFilterTagIds) {
    return in_array((int) $tag['id'], array_map('intval', $selectedFilterTagIds ?? []), true);
}));

$chips = [];
$base  = Auth::url('/contacts');

$textCols = [
    'first_name'   => 'First name',
    'last_name'    => 'Last name',
    'email'        => 'Email',
    'phone'        => 'Phone',
    'country'      => 'Country',
    'province'     => 'Province',
    'created_from' => 'Created ≥',
    'created_to'   => 'Created ≤',
    'updated_from' => 'Updated ≥',
    'updated_to'   => 'Updated ≤',
];

foreach ($textCols as $key => $label) {
    if (!empty($filters[$key])) {
        $f = $filters; unset($f[$key], $f['page']);
        $chips[] = ['text' => $label . ': ' . $filters[$key], 'href' => $base . '?' . http_build_query($f)];
    }
}

if (isset($filters['is_company']) && $filters['is_company'] !== '') {
    $typeLabel = $filters['is_company'] === '1' ? 'Company' : 'Person';
    $f = $filters; unset($f['is_company'], $f['page']);
    $chips[] = ['text' => 'Type: ' . $typeLabel, 'href' => $base . '?' . http_build_query($f)];
}

if (!empty($filters['client_id'])) {
    $cName = '';
    foreach ($filterClients as $c) {
        if ((int) $c['id'] === (int) $filters['client_id']) { $cName = $c['commercial_name']; break; }
    }
    $f = $filters; unset($f['client_id'], $f['page']);
    $chips[] = ['text' => 'Client: ' . $cName, 'href' => $base . '?' . http_build_query($f)];
}

if (!empty($filters['sector_id'])) {
    $sName = '';
    foreach ($filterSectors as $s) {
        if ((int) $s['id'] === (int) $filters['sector_id']) { $sName = $s['name']; break; }
    }
    $f = $filters; unset($f['sector_id'], $f['page']);
    $chips[] = ['text' => 'Sector: ' . $sName, 'href' => $base . '?' . http_build_query($f)];
}

foreach ($selectedTagObjects as $tag) {
    $f = $filters;
    $f['tag_ids'] = array_values(array_filter($f['tag_ids'] ?? [], fn ($id) => (int) $id !== (int) $tag['id']));
    if (empty($f['tag_ids'])) unset($f['tag_ids']);
    unset($f['page']);
    $chips[] = ['text' => 'Tag: ' . $tag['name'], 'href' => $base . '?' . http_build_query($f)];
}

if (!empty($filters['custom_fields'])) {
    foreach ($filters['custom_fields'] as $fieldId => $value) {
        if ($value !== '') {
            $fieldName = '';
            foreach ($customFilterFields as $cf) {
                if ((int) $cf['id'] === (int) $fieldId) { $fieldName = $cf['name']; break; }
            }
            $f = $filters;
            unset($f['custom_fields'][(int) $fieldId]);
            if (empty($f['custom_fields'])) unset($f['custom_fields']);
            unset($f['page']);
            $chips[] = ['text' => ($fieldName ?: 'Field') . ': ' . $value, 'href' => $base . '?' . http_build_query($f)];
        }
    }
}

$extendedKeys = ['client_id', 'sector_id', 'country', 'province', 'created_from', 'created_to', 'updated_from', 'updated_to'];
$hasExtended  = !empty($filters['custom_fields']) || (bool) array_filter($extendedKeys, fn ($k) => !empty($filters[$k]));
?>

<!-- Header -->
<div class="page-header contacts-header">
    <div>
        <h1>Contacts</h1>
        <span class="count-label"><?= (int) $total ?> contacts found</span>
    </div>
    <div class="page-actions">
        <?php if ($canExport): ?>
        <a class="btn btn-outlined" href="<?= htmlspecialchars(Auth::url('/exports?entity=contacts'), ENT_QUOTES, 'UTF-8') ?>">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:13px;height:13px">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/>
            </svg>
            Export
        </a>
        <?php endif; ?>
        <?php if ($canCreateContacts): ?>
        <a class="btn btn-green" href="<?= htmlspecialchars(Auth::url('/contacts/create'), ENT_QUOTES, 'UTF-8') ?>">Create contact</a>
        <?php endif; ?>
    </div>
</div>

<!-- Filter bar (compact row) -->
<div class="filter-bar">
    <button type="button" class="filter-bar-btn" id="filterBarBtn">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z"/>
        </svg>
        Filters
        <?php if ($chips): ?>
        <span class="filter-bar-count"><?= count($chips) ?></span>
        <?php endif; ?>
    </button>

    <?php if ($chips): ?>
    <div class="filter-bar-chips">
        <?php foreach ($chips as $chip): ?>
        <a class="filter-chip" href="<?= htmlspecialchars($chip['href'], ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars($chip['text'], ENT_QUOTES, 'UTF-8') ?>
            <span class="filter-chip-x" aria-hidden="true">×</span>
        </a>
        <?php endforeach; ?>
    </div>
    <a class="filter-bar-reset" href="<?= htmlspecialchars(Auth::url('/contacts'), ENT_QUOTES, 'UTF-8') ?>">Reset all</a>
    <?php endif; ?>
</div>

<!-- Collapsible filter panel -->
<div class="filter-panel" id="filterPanel">
    <form method="get" action="<?= htmlspecialchars(Auth::url('/contacts'), ENT_QUOTES, 'UTF-8') ?>">

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

        <div class="filter-footer">
            <button type="button" id="filterToggleBtn"
                    class="filter-toggle-btn <?= $hasExtended ? 'open' : '' ?>">
                <span class="filter-toggle-label"><?= $hasExtended ? 'Less filters' : 'More filters' ?></span>
                <svg class="filter-toggle-chevron" viewBox="0 0 10 6" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 1l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
            <div class="filter-actions">
                <button class="btn btn-primary" type="submit">Filter</button>
                <a class="btn btn-outlined" href="<?= htmlspecialchars(Auth::url('/contacts'), ENT_QUOTES, 'UTF-8') ?>">Reset</a>
            </div>
        </div>

    </form>
</div>

<!-- Table -->
<?php if (empty($contacts)): ?>
    <div class="contacts-table-card" style="padding:28px 20px; color:var(--color-neutral); font-size:14px;">
        No contacts found.
    </div>
<?php else: ?>
<div class="contacts-table-card">

    <?php if ($canEditContacts): ?>
    <form id="contactsBulkForm" method="post" action="<?= htmlspecialchars(Auth::url('/contacts/bulk-tags'), ENT_QUOTES, 'UTF-8') ?>">
        <?= Csrf::field() ?>
    <?php endif; ?>

    <table class="contacts-table">
        <thead>
            <tr>
                <th class="col-select">
                    <?php if ($canEditContacts): ?>
                    <input type="checkbox" id="contactsSelectAll" aria-label="Select all">
                    <?php endif; ?>
                </th>
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
    <!-- Selection bar: visible only when rows are checked -->
    <div class="selection-bar" id="selectionBar" role="toolbar" aria-label="Bulk actions">
        <span class="selection-count" id="selectionCount">0 selected</span>
        <div class="selection-bar-tags">
            <div class="token-picker token-picker--filter"
                 data-endpoint="<?= htmlspecialchars(Auth::url('/ajax/tags/search'), ENT_QUOTES, 'UTF-8') ?>"
                 data-name="tag_ids[]"
                 data-with-color="1"
                 data-placeholder="Choose tags..."
                 data-selected="[]">
            </div>
        </div>
        <select class="selection-bar-action" name="bulk_action">
            <option value="add">Add tags</option>
            <option value="remove">Remove tags</option>
        </select>
        <button type="submit" class="btn-apply">Apply</button>
        <button type="button" class="btn-deselect" id="selectionClearBtn">Deselect all</button>
    </div>
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

<script>
(function () {
    /* Filter panel toggle */
    var filterBarBtn = document.getElementById('filterBarBtn');
    var filterPanel  = document.getElementById('filterPanel');
    if (filterBarBtn && filterPanel) {
        filterBarBtn.addEventListener('click', function () {
            var open = filterPanel.classList.toggle('open');
            filterBarBtn.classList.toggle('active', open);
        });
    }

    /* More/Less filters inside the panel */
    var moreBtn   = document.getElementById('filterToggleBtn');
    var moreExtra = document.getElementById('filterExtra');
    if (moreBtn && moreExtra) {
        moreBtn.addEventListener('click', function () {
            var open = moreExtra.classList.toggle('open');
            moreBtn.classList.toggle('open', open);
            moreBtn.querySelector('.filter-toggle-label').textContent = open ? 'Less filters' : 'More filters';
        });
    }

    /* Selection bar */
    var bulkForm  = document.getElementById('contactsBulkForm');
    var selBar    = document.getElementById('selectionBar');
    var countEl   = document.getElementById('selectionCount');
    var clearBtn  = document.getElementById('selectionClearBtn');
    var selectAll = document.getElementById('contactsSelectAll');

    if (!bulkForm || !selBar) { return; }

    function updateBar() {
        var checked = bulkForm.querySelectorAll('input[name="contact_ids[]"]:checked');
        var n = checked.length;
        selBar.classList.toggle('visible', n > 0);
        if (countEl) { countEl.textContent = n + ' selected'; }
        if (selectAll) {
            var total = bulkForm.querySelectorAll('input[name="contact_ids[]"]').length;
            selectAll.indeterminate = n > 0 && n < total;
            selectAll.checked = n > 0 && n === total;
        }
    }

    bulkForm.addEventListener('change', function (e) {
        if (e.target.name === 'contact_ids[]') { updateBar(); }
    });

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            bulkForm.querySelectorAll('input[name="contact_ids[]"]').forEach(function (cb) {
                cb.checked = selectAll.checked;
            });
            updateBar();
        });
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            bulkForm.querySelectorAll('input[name="contact_ids[]"]').forEach(function (cb) { cb.checked = false; });
            updateBar();
        });
    }
})();
</script>
