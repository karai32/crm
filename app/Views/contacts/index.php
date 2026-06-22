<?php
$activeFilters = array_filter($filters, function ($value) {
    return is_array($value) ? !empty($value) : ($value !== '' && $value !== 0 && $value !== null);
});

$paginateParams = array_merge($activeFilters, ['sort' => $sort, 'dir' => $dir]);
$sortUrl = function (string $col) use ($sort, $dir, $activeFilters): string {
    $nd = ($sort === $col && $dir === 'asc') ? 'desc' : 'asc';
    return Auth::url('/contacts?' . http_build_query(array_merge($activeFilters, ['sort' => $col, 'dir' => $nd, 'page' => 1])));
};
$thSort = function (string $col, string $label, string $xClass = '') use ($sort, $dir, $sortUrl): string {
    $active = $sort === $col;
    $icon   = $active ? ($dir === 'asc' ? '↑' : '↓') : '↕';
    $cls    = trim('th-sort' . ($active ? ' th-sort--' . $dir : '') . ($xClass !== '' ? ' ' . $xClass : ''));
    $href   = htmlspecialchars($sortUrl($col), ENT_QUOTES, 'UTF-8');
    return '<th class="' . $cls . '"><a href="' . $href . '">'
        . htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
        . ' <span class="sort-icon" aria-hidden="true">' . $icon . '</span></a></th>';
};

$canCreateContacts  = Auth::can('contacts.create');
$canEditContacts    = Auth::can('contacts.edit');
$canDeleteContacts  = Auth::can('contacts.delete');
$hasBulkActions     = $canEditContacts || $canDeleteContacts;

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
    'full_name'    => 'Name',
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

if (!empty($filters['company'])) {
    $typeLabel = $filters['company'] === 'company' ? 'Company' : 'Person';
    $f = $filters; unset($f['company'], $f['page']);
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
        <?php if ($canCreateContacts): ?>
        <a class="btn btn-primary" href="<?= htmlspecialchars(Auth::url('/contacts/create'), ENT_QUOTES, 'UTF-8') ?>">
            <i class="ph ph-plus"></i>
            Create contact
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Filter bar (compact row) -->
<div class="filter-bar">
    <button type="button" class="filter-bar-btn" id="filterBarBtn">
        <i class="ph ph-funnel"></i>
        Filters
        <?php if ($chips): ?>
        <span class="filter-bar-count"><?= count($chips) ?></span>
        <?php endif; ?>
    </button>

    <?php if ($hasBulkActions): ?>
    <button type="button" class="filter-bar-btn actions-bar-btn" id="actionsBarBtn">
        <i class="ph ph-list-dashes"></i>
        Actions
        <span class="filter-bar-count" id="actionsBarCount" style="display:none">0</span>
    </button>
    <?php endif; ?>

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
        <input type="hidden" name="sort" value="<?= htmlspecialchars($sort, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="dir"  value="<?= htmlspecialchars($dir, ENT_QUOTES, 'UTF-8') ?>">

        <div class="filter-grid">
            <div class="field">
                <label for="full_name">Name</label>
                <input id="full_name" type="text" name="full_name"
                       value="<?= htmlspecialchars($filters['full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
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
                <label for="company">Type</label>
                <select id="company" name="company">
                    <option value="">All</option>
                    <option value="company" <?= (($filters['company'] ?? '') === 'company') ? 'selected' : '' ?>>Company</option>
                    <option value="person"  <?= (($filters['company'] ?? '') === 'person')  ? 'selected' : '' ?>>Person</option>
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

<!-- Actions panel -->
<?php if ($hasBulkActions): ?>
<div class="actions-panel" id="actionsPanel">
    <form id="contactsBulkForm" method="post" action="<?= htmlspecialchars(Auth::url('/contacts/bulk-action'), ENT_QUOTES, 'UTF-8') ?>">
        <?= Csrf::field() ?>
        <div class="actions-panel-body">

            <?php if ($canEditContacts): ?>
            <div class="actions-panel-section">
                <div class="actions-section-label">Tags</div>
                <div class="actions-section-row">
                    <div class="actions-section-picker">
                        <div class="token-picker token-picker--filter"
                             data-endpoint="<?= htmlspecialchars(Auth::url('/ajax/tags/search'), ENT_QUOTES, 'UTF-8') ?>"
                             data-name="tag_ids[]"
                             data-with-color="1"
                             data-placeholder="Choose tags…"
                             data-selected="[]">
                        </div>
                    </div>
                    <div class="actions-section-btns">
                        <button type="submit" name="bulk_action" value="add_tags" class="btn btn-sm btn-primary">Add tags</button>
                        <button type="submit" name="bulk_action" value="remove_tags" class="btn btn-sm btn-outlined">Remove tags</button>
                    </div>
                </div>
            </div>

            <div class="actions-panel-sep"></div>

            <div class="actions-panel-section">
                <div class="actions-section-label">Link to client</div>
                <div class="actions-section-row">
                    <div class="actions-section-picker">
                        <div class="token-picker token-picker--filter"
                             data-endpoint="<?= htmlspecialchars(Auth::url('/ajax/clients/search'), ENT_QUOTES, 'UTF-8') ?>"
                             data-name="link_client_ids[]"
                             data-placeholder="Search clients…"
                             data-selected="[]">
                        </div>
                    </div>
                    <div class="actions-section-btns">
                        <button type="submit" name="bulk_action" value="link_client" class="btn btn-sm btn-green">Link</button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($canEditContacts && $canDeleteContacts): ?>
            <div class="actions-panel-sep"></div>
            <?php endif; ?>

            <?php if ($canDeleteContacts): ?>
            <div class="actions-panel-section actions-panel-section--danger">
                <div class="actions-section-label">Delete</div>
                <button type="submit" name="bulk_action" value="delete" class="btn btn-sm btn-danger" id="bulkDeleteBtn"
                        onclick="return confirm('Delete selected contacts? This cannot be undone.')">
                    Delete <span id="deleteCountLabel">0</span> selected
                </button>
            </div>
            <?php endif; ?>

        </div>
        <div class="actions-panel-footer">
            <span class="actions-selected-hint" id="actionsSelectedHint">0 selected</span>
            <button type="button" class="actions-deselect-btn" id="actionsDeselectBtn">Deselect all</button>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- Table -->
<?php if (empty($contacts)): ?>
    <div class="contacts-table-card is-empty">
        No contacts found.
    </div>
<?php else: ?>
<div class="contacts-table-card">

    <table class="data-table contacts-table">
        <thead>
            <tr>
                <th class="col-select">
                    <?php if ($hasBulkActions): ?>
                    <input type="checkbox" id="contactsSelectAll" aria-label="Select all">
                    <?php endif; ?>
                </th>
                <?= $thSort('id', 'ID', 'col-id') ?>
                <?= $thSort('full_name', 'Name') ?>
                <?= $thSort('email', 'Email') ?>
                <?= $thSort('clients', 'Clients') ?>
                <th>Tags</th>
                <th class="col-actions">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($contacts as $contact): ?>
            <tr>
                <td class="col-select">
                    <?php if ($hasBulkActions): ?>
                    <input type="checkbox" name="contact_ids[]" value="<?= (int) $contact['id'] ?>"
                           form="contactsBulkForm" aria-label="Select contact">
                    <?php endif; ?>
                </td>
                <td class="col-id"><?= (int) $contact['id'] ?></td>
                <td class="col-name"><a class="col-row-link" href="<?= htmlspecialchars(Auth::url('/contacts/show?id=' . $contact['id']), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($contact['full_name'], ENT_QUOTES, 'UTF-8') ?></a></td>
                <td class="col-email"><?= htmlspecialchars($contact['email'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td class="col-clients">
                    <?php
                        $cList  = $contactClients[(int) $contact['id']] ?? [];
                        $cCount = count($cList);
                    ?>
                    <?php if ($cCount > 0): ?>
                        <a class="col-client-link" href="<?= htmlspecialchars(Auth::url('/clients/show?id=' . (int) $cList[0]['id']), ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($cList[0]['commercial_name'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                        <?php if ($cCount > 1):
                            $moreUrl = Auth::url('/clients?' . http_build_query(['contact_name' => $contact['full_name']]));
                        ?>
                        <a class="col-clients-more" href="<?= htmlspecialchars($moreUrl, ENT_QUOTES, 'UTF-8') ?>">+<?= $cCount - 1 ?></a>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
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
                <td class="col-actions">
                    <div class="action-links">
                        <a class="action-btn action-view" title="View"
                           href="<?= htmlspecialchars(Auth::url('/contacts/show?id=' . $contact['id']), ENT_QUOTES, 'UTF-8') ?>">
                            <i class="ph ph-eye"></i>
                        </a>
                        <?php if ($canEditContacts): ?>
                        <a class="action-btn action-edit" title="Edit"
                           href="<?= htmlspecialchars(Auth::url('/contacts/edit?id=' . $contact['id']), ENT_QUOTES, 'UTF-8') ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/></svg>
                        </a>
                        <?php endif; ?>
                        <?php if ($canDeleteContacts): ?>
                        <a class="action-btn action-delete" title="Delete"
                           href="<?= htmlspecialchars(Auth::url('/contacts/delete?id=' . $contact['id']), ENT_QUOTES, 'UTF-8') ?>"
                           onclick="return confirm('Delete this contact?')">
                            <i class="ph ph-trash"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</div>

<div class="contacts-pagination">
    <span>Showing <?= $from ?> to <?= $to ?> of <?= (int) $total ?> entries</span>

    <div class="pagination-pages">
        <?php if ($page > 1): ?>
            <a class="page-btn"
               href="<?= htmlspecialchars(Auth::url('/contacts?' . http_build_query(array_merge($paginateParams, ['page' => $page - 1]))), ENT_QUOTES, 'UTF-8') ?>">
                &#8249;
            </a>
        <?php else: ?>
            <span class="page-btn disabled">&#8249;</span>
        <?php endif; ?>

        <?php foreach (paginationRange($page, $totalPages) as $p): ?>
            <?php if ($p === '...'): ?>
                <span class="page-ellipsis">...</span>
            <?php elseif ($p === $page): ?>
                <span class="page-btn active"><?= $p ?></span>
            <?php else: ?>
                <a class="page-btn"
                   href="<?= htmlspecialchars(Auth::url('/contacts?' . http_build_query(array_merge($paginateParams, ['page' => $p]))), ENT_QUOTES, 'UTF-8') ?>">
                    <?= $p ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>

        <?php if ($page < $totalPages): ?>
            <a class="page-btn"
               href="<?= htmlspecialchars(Auth::url('/contacts?' . http_build_query(array_merge($paginateParams, ['page' => $page + 1]))), ENT_QUOTES, 'UTF-8') ?>">
                &#8250;
            </a>
        <?php else: ?>
            <span class="page-btn disabled">&#8250;</span>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<script>
(function () {
    var filterBarBtn  = document.getElementById('filterBarBtn');
    var filterPanel   = document.getElementById('filterPanel');
    var actionsBarBtn = document.getElementById('actionsBarBtn');
    var actionsPanel  = document.getElementById('actionsPanel');
    var actionsCount  = document.getElementById('actionsBarCount');
    var deleteCountEl = document.getElementById('deleteCountLabel');
    var hintEl        = document.getElementById('actionsSelectedHint');
    var deselectBtn   = document.getElementById('actionsDeselectBtn');
    var selectAll     = document.getElementById('contactsSelectAll');
    var moreBtn       = document.getElementById('filterToggleBtn');
    var moreExtra     = document.getElementById('filterExtra');

    /* Filter panel toggle */
    if (filterBarBtn && filterPanel) {
        filterBarBtn.addEventListener('click', function () {
            var open = filterPanel.classList.toggle('open');
            filterBarBtn.classList.toggle('active', open);
            if (open && actionsPanel) {
                actionsPanel.classList.remove('open');
                if (actionsBarBtn) actionsBarBtn.classList.remove('active');
            }
        });
    }

    /* More/Less filters */
    if (moreBtn && moreExtra) {
        moreBtn.addEventListener('click', function () {
            var open = moreExtra.classList.toggle('open');
            moreBtn.classList.toggle('open', open);
            moreBtn.querySelector('.filter-toggle-label').textContent = open ? 'Less filters' : 'More filters';
        });
    }

    /* Actions panel toggle */
    if (actionsBarBtn && actionsPanel) {
        actionsBarBtn.addEventListener('click', function () {
            if (getCheckedCount() === 0) { return; }
            var open = actionsPanel.classList.toggle('open');
            actionsBarBtn.classList.toggle('active', open);
            if (open && filterPanel) {
                filterPanel.classList.remove('open');
                if (filterBarBtn) filterBarBtn.classList.remove('active');
            }
        });
    }

    function getCheckedCount() {
        return document.querySelectorAll('input[name="contact_ids[]"]:checked').length;
    }

    function updateActions() {
        var n     = getCheckedCount();
        var total = document.querySelectorAll('input[name="contact_ids[]"]').length;

        if (actionsCount)  { actionsCount.textContent = n; actionsCount.style.display = n > 0 ? '' : 'none'; }
        if (deleteCountEl) { deleteCountEl.textContent = n; }
        if (hintEl)        { hintEl.textContent = n + ' selected'; }
        if (actionsBarBtn) { actionsBarBtn.classList.toggle('actions-bar-btn--has-items', n > 0); }

        if (selectAll) {
            selectAll.indeterminate = n > 0 && n < total;
            selectAll.checked = total > 0 && n === total;
        }

        if (n === 0 && actionsPanel) {
            actionsPanel.classList.remove('open');
            if (actionsBarBtn) actionsBarBtn.classList.remove('active');
        }
    }

    /* Checkboxes use form="contactsBulkForm" so listen on document */
    document.addEventListener('change', function (e) {
        if (e.target.name === 'contact_ids[]') { updateActions(); }
    });

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            document.querySelectorAll('input[name="contact_ids[]"]').forEach(function (cb) {
                cb.checked = selectAll.checked;
            });
            updateActions();
        });
    }

    if (deselectBtn) {
        deselectBtn.addEventListener('click', function () {
            document.querySelectorAll('input[name="contact_ids[]"]').forEach(function (cb) { cb.checked = false; });
            if (selectAll) { selectAll.checked = false; selectAll.indeterminate = false; }
            updateActions();
        });
    }
})();
</script>
