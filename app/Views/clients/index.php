<?php
if (!function_exists('clientPaginationRange')) {
    function clientPaginationRange(int $page, int $totalPages): array
    {
        if ($totalPages <= 7) {
            return range(1, $totalPages);
        }
        $pages = [1];
        if ($page > 3) $pages[] = '...';
        for ($i = max(2, $page - 1); $i <= min($totalPages - 1, $page + 1); $i++) {
            $pages[] = $i;
        }
        if ($page < $totalPages - 2) $pages[] = '...';
        $pages[] = $totalPages;
        return $pages;
    }
}

$activeFilters = array_filter($filters, function ($value) {
    return is_array($value) ? !empty($value) : ($value !== '' && $value !== 0 && $value !== null);
});

$paginateParams = array_merge($activeFilters, ['sort' => $sort, 'dir' => $dir]);
$sortUrl = function (string $col) use ($sort, $dir, $activeFilters): string {
    $nd = ($sort === $col && $dir === 'asc') ? 'desc' : 'asc';
    return Auth::url('/clients?' . http_build_query(array_merge($activeFilters, ['sort' => $col, 'dir' => $nd, 'page' => 1])));
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

$from = ($page - 1) * $perPage + 1;
$to   = min($page * $perPage, $total);
if ($total === 0) { $from = 0; $to = 0; }
$canCreateClients = Auth::can('clients.create');
$canEditClients   = Auth::can('clients.edit');
$canDeleteClients = Auth::can('clients.delete');
$hasBulkActions   = $canEditClients || $canDeleteClients;

$selectedFilterTagIds = $filters['tag_ids'] ?? [];
$preselectedFilterTagsJson = json_encode(array_values(array_map(function ($tag) {
    return ['id' => (int) $tag['id'], 'name' => $tag['name'], 'color' => $tag['color'] ?? null];
}, array_filter($filterTags, function ($tag) use ($selectedFilterTagIds) {
    return in_array((int) $tag['id'], $selectedFilterTagIds ?? [], true);
}))));

/* --- Build filter chips ---------------------------------------- */
$selectedTagObjects = array_values(array_filter($filterTags, function ($tag) use ($selectedFilterTagIds) {
    return in_array((int) $tag['id'], array_map('intval', $selectedFilterTagIds ?? []), true);
}));

$chips = [];
$base  = Auth::url('/clients');

$textCols = [
    'commercial_name' => 'Name',
    'legal_name'      => 'Legal name',
    'cif'             => 'CIF',
    'contact_name'    => 'Contact',
    'city'            => 'City',
    'province'        => 'Province',
    'country'         => 'Country',
    'address'         => 'Address',
    'postal_code'     => 'Postal code',
    'website'         => 'Website',
    'notes'           => 'Notes',
    'created_from'    => 'Created ≥',
    'created_to'      => 'Created ≤',
    'updated_from'    => 'Updated ≥',
    'updated_to'      => 'Updated ≤',
];

foreach ($textCols as $key => $label) {
    if (!empty($filters[$key])) {
        $f = $filters; unset($f[$key], $f['page']);
        $chips[] = ['text' => $label . ': ' . $filters[$key], 'href' => $base . '?' . http_build_query($f)];
    }
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

$hasExtended = (bool) array_filter(
    ['address', 'postal_code', 'website', 'notes', 'created_from', 'created_to', 'updated_from', 'updated_to'],
    fn ($k) => !empty($filters[$k])
);
?>

<!-- Header -->
<div class="page-header clients-header">
    <div>
        <h1>Clients</h1>
        <span class="count-label"><?= (int) $total ?> clients found</span>
    </div>
    <div class="page-actions">
        <?php if ($canCreateClients): ?>
        <a class="btn btn-primary" href="<?= htmlspecialchars(Auth::url('/clients/create'), ENT_QUOTES, 'UTF-8') ?>">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="btn-icon-sm">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Create client
        </a>
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

    <?php if ($hasBulkActions): ?>
    <button type="button" class="filter-bar-btn actions-bar-btn" id="actionsBarBtn">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z"/>
        </svg>
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
    <a class="filter-bar-reset" href="<?= htmlspecialchars(Auth::url('/clients'), ENT_QUOTES, 'UTF-8') ?>">Reset all</a>
    <?php endif; ?>
</div>

<!-- Collapsible filter panel -->
<div class="filter-panel" id="filterPanel">
    <form method="get" action="<?= htmlspecialchars(Auth::url('/clients'), ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="sort" value="<?= htmlspecialchars($sort, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="dir"  value="<?= htmlspecialchars($dir, ENT_QUOTES, 'UTF-8') ?>">

        <div class="filter-grid">
            <div class="field">
                <label for="commercial_name">Commercial name</label>
                <input id="commercial_name" type="text" name="commercial_name"
                       value="<?= htmlspecialchars($filters['commercial_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="field">
                <label for="legal_name">Legal name</label>
                <input id="legal_name" type="text" name="legal_name"
                       value="<?= htmlspecialchars($filters['legal_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="field">
                <label for="cif">CIF</label>
                <input id="cif" type="text" name="cif"
                       value="<?= htmlspecialchars($filters['cif'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="field">
                <label for="contact_name">Contact name</label>
                <input id="contact_name" type="text" name="contact_name"
                       value="<?= htmlspecialchars($filters['contact_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="field">
                <label for="sector_id">Sector</label>
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
                <label>Tags</label>
                <div class="token-picker token-picker--filter"
                     data-endpoint="<?= htmlspecialchars(Auth::url('/ajax/tags/search'), ENT_QUOTES, 'UTF-8') ?>"
                     data-name="tag_ids[]"
                     data-with-color="1"
                     data-placeholder="All tags"
                     data-selected="<?= htmlspecialchars($preselectedFilterTagsJson, ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>
            <div class="field">
                <label for="city">City</label>
                <input id="city" type="text" name="city"
                       value="<?= htmlspecialchars($filters['city'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="field">
                <label for="province">Province</label>
                <input id="province" type="text" name="province"
                       value="<?= htmlspecialchars($filters['province'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="field">
                <label for="country">Country</label>
                <input id="country" type="text" name="country"
                       value="<?= htmlspecialchars($filters['country'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </div>

        <div class="filter-grid filter-grid--extra <?= $hasExtended ? 'open' : '' ?>" id="clientFilterExtra">
            <div class="field">
                <label for="address">Address</label>
                <input id="address" type="text" name="address"
                       value="<?= htmlspecialchars($filters['address'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="field">
                <label for="postal_code">Postal code</label>
                <input id="postal_code" type="text" name="postal_code"
                       value="<?= htmlspecialchars($filters['postal_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="field">
                <label for="website">Website</label>
                <input id="website" type="text" name="website"
                       value="<?= htmlspecialchars($filters['website'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="field">
                <label for="notes">Notes</label>
                <input id="notes" type="text" name="notes"
                       value="<?= htmlspecialchars($filters['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
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
        </div>

        <div class="filter-footer">
            <button type="button" id="clientFilterToggleBtn"
                    class="filter-toggle-btn <?= $hasExtended ? 'open' : '' ?>">
                <span class="filter-toggle-label"><?= $hasExtended ? 'Less filters' : 'More filters' ?></span>
                <svg class="filter-toggle-chevron" viewBox="0 0 10 6" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 1l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
            <div class="filter-actions">
                <button class="btn btn-primary" type="submit">Filter</button>
                <a class="btn btn-outlined" href="<?= htmlspecialchars(Auth::url('/clients'), ENT_QUOTES, 'UTF-8') ?>">Reset</a>
            </div>
        </div>
    </form>
</div>

<!-- Actions panel -->
<?php if ($hasBulkActions): ?>
<div class="actions-panel" id="actionsPanel">
    <form id="clientsBulkForm" method="post" action="<?= htmlspecialchars(Auth::url('/clients/bulk-action'), ENT_QUOTES, 'UTF-8') ?>">
        <?= Csrf::field() ?>
        <div class="actions-panel-body">

            <?php if ($canEditClients): ?>
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
            <?php endif; ?>

            <?php if ($canEditClients && $canDeleteClients): ?>
            <div class="actions-panel-sep"></div>
            <?php endif; ?>

            <?php if ($canDeleteClients): ?>
            <div class="actions-panel-section actions-panel-section--danger">
                <div class="actions-section-label">Delete</div>
                <button type="submit" name="bulk_action" value="delete" class="btn btn-sm btn-danger" id="bulkDeleteBtn"
                        onclick="return confirm('Delete selected clients? This cannot be undone.')">
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

<!-- Table card -->
<div class="clients-table-card">

    <?php if (empty($clients)): ?>
        <p class="table-empty-state">No clients found.</p>
    <?php else: ?>

    <table class="clients-table">
        <thead>
            <tr>
                <th class="col-select">
                    <?php if ($hasBulkActions): ?>
                    <input type="checkbox" id="clientsSelectAll" aria-label="Select all">
                    <?php endif; ?>
                </th>
                <?= $thSort('id', '#', 'col-id') ?>
                <?= $thSort('commercial_name', 'Commercial name') ?>
                <?= $thSort('legal_name', 'Legal name') ?>
                <?= $thSort('sector_name', 'Sector') ?>
                <th>Tags</th>
                <?= $thSort('city', 'City') ?>
                <?= $thSort('country', 'Country') ?>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($clients as $client): ?>
                <tr>
                    <td class="col-select">
                        <?php if ($hasBulkActions): ?>
                        <input type="checkbox" name="client_ids[]" value="<?= (int) $client['id'] ?>"
                               form="clientsBulkForm" aria-label="Select client">
                        <?php endif; ?>
                    </td>
                    <td class="col-id"><?= (int) $client['id'] ?></td>
                    <td class="col-name">
                        <a class="client-name-link" href="<?= htmlspecialchars(Auth::url('/clients/show?id=' . $client['id']), ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($client['commercial_name'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </td>
                    <td class="col-muted"><?= htmlspecialchars($client['legal_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($client['sector_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="col-tags">
                        <?php foreach ($clientTags[(int) $client['id']] ?? [] as $tag): ?>
                            <?php $c = htmlspecialchars($tag['color'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                            <span class="tag-badge" <?= $c ? 'style="background:' . $c . '22;border-color:' . $c . '44;color:' . $c . '"' : '' ?>>
                                <?php if ($c): ?>
                                    <span class="tag-color-dot" style="background:<?= $c ?>"></span>
                                <?php endif; ?>
                                <?= htmlspecialchars($tag['name'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        <?php endforeach; ?>
                    </td>
                    <td class="col-muted"><?= htmlspecialchars($client['city'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="col-muted"><?= htmlspecialchars($client['country'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <div class="action-links">
                            <a class="action-view" href="<?= htmlspecialchars(Auth::url('/clients/show?id=' . $client['id']), ENT_QUOTES, 'UTF-8') ?>">View</a>
                            <?php if ($canEditClients): ?>
                            <a class="action-edit" href="<?= htmlspecialchars(Auth::url('/clients/edit?id=' . $client['id']), ENT_QUOTES, 'UTF-8') ?>">Edit</a>
                            <?php endif; ?>
                            <?php if ($canDeleteClients): ?>
                            <a class="action-delete"
                               href="<?= htmlspecialchars(Auth::url('/clients/delete?id=' . $client['id']), ENT_QUOTES, 'UTF-8') ?>"
                               onclick="return confirm('Delete this client?')">Delete</a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <div class="clients-pagination">
        <span>Showing <?= $from ?>–<?= $to ?> of <?= (int) $total ?></span>

        <div class="pagination-pages">
            <?php if ($page > 1): ?>
                <a class="page-btn" href="<?= htmlspecialchars(Auth::url('/clients?' . http_build_query(array_merge($paginateParams, ['page' => $page - 1]))), ENT_QUOTES, 'UTF-8') ?>">&#8249;</a>
            <?php else: ?>
                <span class="page-btn disabled">&#8249;</span>
            <?php endif; ?>

            <?php foreach (clientPaginationRange($page, $totalPages) as $p): ?>
                <?php if ($p === '...'): ?>
                    <span class="page-ellipsis">...</span>
                <?php else: ?>
                    <a class="page-btn <?= $p === $page ? 'active' : '' ?>"
                       href="<?= htmlspecialchars(Auth::url('/clients?' . http_build_query(array_merge($paginateParams, ['page' => $p]))), ENT_QUOTES, 'UTF-8') ?>"><?= $p ?></a>
                <?php endif; ?>
            <?php endforeach; ?>

            <?php if ($page < $totalPages): ?>
                <a class="page-btn" href="<?= htmlspecialchars(Auth::url('/clients?' . http_build_query(array_merge($paginateParams, ['page' => $page + 1]))), ENT_QUOTES, 'UTF-8') ?>">&#8250;</a>
            <?php else: ?>
                <span class="page-btn disabled">&#8250;</span>
            <?php endif; ?>
        </div>
    </div>

    <?php endif; ?>
</div>

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
    var selectAll     = document.getElementById('clientsSelectAll');
    var moreBtn       = document.getElementById('clientFilterToggleBtn');
    var moreExtra     = document.getElementById('clientFilterExtra');

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
        return document.querySelectorAll('input[name="client_ids[]"]:checked').length;
    }

    function updateActions() {
        var n     = getCheckedCount();
        var total = document.querySelectorAll('input[name="client_ids[]"]').length;

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

    /* Checkboxes use form="clientsBulkForm" so listen on document */
    document.addEventListener('change', function (e) {
        if (e.target.name === 'client_ids[]') { updateActions(); }
    });

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            document.querySelectorAll('input[name="client_ids[]"]').forEach(function (cb) {
                cb.checked = selectAll.checked;
            });
            updateActions();
        });
    }

    if (deselectBtn) {
        deselectBtn.addEventListener('click', function () {
            document.querySelectorAll('input[name="client_ids[]"]').forEach(function (cb) { cb.checked = false; });
            if (selectAll) { selectAll.checked = false; selectAll.indeterminate = false; }
            updateActions();
        });
    }
})();
</script>
