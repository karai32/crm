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

$from = ($page - 1) * $perPage + 1;
$to   = min($page * $perPage, $total);
if ($total === 0) { $from = 0; $to = 0; }
$canCreateClients = Auth::can('clients.create');
$canEditClients = Auth::can('clients.edit');
$canDeleteClients = Auth::can('clients.delete');
$selectedFilterTagIds = $filters['tag_ids'] ?? [];
$preselectedFilterTagsJson = json_encode(array_values(array_map(function ($tag) {
    return ['id' => (int) $tag['id'], 'name' => $tag['name'], 'color' => $tag['color'] ?? null];
}, array_filter($filterTags, function ($tag) use ($selectedFilterTagIds) {
    return in_array((int) $tag['id'], $selectedFilterTagIds ?? [], true);
}))));
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
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:15px;height:15px">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Create client
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Filters -->
<?php
$extendedKeys = ['address', 'postal_code', 'website', 'notes', 'created_from', 'created_to', 'updated_from', 'updated_to'];
$hasExtended = (bool) array_filter($extendedKeys, fn ($key) => !empty($filters[$key]));
?>
<div class="clients-filters">
    <form method="get" action="<?= htmlspecialchars(Auth::url('/clients'), ENT_QUOTES, 'UTF-8') ?>">
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

        <div class="clients-filter-footer">
            <button type="button" id="clientFilterToggleBtn"
                    class="filter-toggle-btn <?= $hasExtended ? 'open' : '' ?>">
                <span class="filter-toggle-label"><?= $hasExtended ? 'Less filters' : 'More filters' ?></span>
                <svg class="filter-toggle-chevron" viewBox="0 0 10 6" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 1l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
            <div class="clients-filter-actions">
                <button class="btn btn-primary" type="submit">Filter</button>
                <a class="btn btn-outlined" href="<?= htmlspecialchars(Auth::url('/clients'), ENT_QUOTES, 'UTF-8') ?>">Reset</a>
            </div>
        </div>
    </form>
</div>
<script>
(function () {
    var btn = document.getElementById('clientFilterToggleBtn');
    var extra = document.getElementById('clientFilterExtra');
    if (!btn || !extra) { return; }
    btn.addEventListener('click', function () {
        var open = extra.classList.toggle('open');
        btn.classList.toggle('open', open);
        btn.querySelector('.filter-toggle-label').textContent = open ? 'Less filters' : 'More filters';
    });
})();
</script>

<!-- Table card -->
<div class="clients-table-card">

    <?php if (empty($clients)): ?>
        <p style="padding:24px 16px;color:var(--color-text-muted);font-size:14px;">No clients found.</p>
    <?php else: ?>
    <?php if ($canEditClients): ?>
    <form method="post" action="<?= htmlspecialchars(Auth::url('/clients/bulk-tags'), ENT_QUOTES, 'UTF-8') ?>">
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

    <table class="clients-table">
        <thead>
            <tr>
                <th class="col-select"></th>
                <th class="col-id">#</th>
                <th>Commercial name</th>
                <th>Legal name</th>
                <th>Sector</th>
                <th>Tags</th>
                <th>City</th>
                <th>Country</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($clients as $client): ?>
                <tr>
                    <td class="col-select">
                        <?php if ($canEditClients): ?>
                        <input type="checkbox" name="client_ids[]" value="<?= (int) $client['id'] ?>" aria-label="Select client">
                        <?php endif; ?>
                    </td>
                    <td class="col-id"><?= (int) $client['id'] ?></td>
                    <td class="col-name">
                        <a href="<?= htmlspecialchars(Auth::url('/clients/show?id=' . $client['id']), ENT_QUOTES, 'UTF-8') ?>"
                           style="color:inherit;text-decoration:none;">
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

    <?php if ($canEditClients): ?>
    </form>
    <?php endif; ?>

    <!-- Pagination -->
    <div class="clients-pagination">
        <span>Showing <?= $from ?>-<?= $to ?> of <?= (int) $total ?></span>

        <div class="pagination-pages">
            <?php if ($page > 1): ?>
                <a class="page-btn" href="<?= htmlspecialchars(Auth::url('/clients?' . http_build_query(array_merge($activeFilters, ['page' => $page - 1]))), ENT_QUOTES, 'UTF-8') ?>">&#8249;</a>
            <?php else: ?>
                <span class="page-btn disabled">&#8249;</span>
            <?php endif; ?>

            <?php foreach (clientPaginationRange($page, $totalPages) as $p): ?>
                <?php if ($p === '...'): ?>
                    <span class="page-ellipsis">...</span>
                <?php else: ?>
                    <a class="page-btn <?= $p === $page ? 'active' : '' ?>"
                       href="<?= htmlspecialchars(Auth::url('/clients?' . http_build_query(array_merge($activeFilters, ['page' => $p]))), ENT_QUOTES, 'UTF-8') ?>"><?= $p ?></a>
                <?php endif; ?>
            <?php endforeach; ?>

            <?php if ($page < $totalPages): ?>
                <a class="page-btn" href="<?= htmlspecialchars(Auth::url('/clients?' . http_build_query(array_merge($activeFilters, ['page' => $page + 1]))), ENT_QUOTES, 'UTF-8') ?>">&#8250;</a>
            <?php else: ?>
                <span class="page-btn disabled">&#8250;</span>
            <?php endif; ?>
        </div>
    </div>

    <?php endif; ?>
</div>
