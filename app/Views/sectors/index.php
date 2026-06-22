<?php
$sortUrl = function (string $col) use ($sort, $dir): string {
    $nd = ($sort === $col && $dir === 'asc') ? 'desc' : 'asc';
    return Auth::url('/sectors?' . http_build_query(['sort' => $col, 'dir' => $nd]));
};
$thSort = function (string $col, string $label) use ($sort, $dir, $sortUrl): string {
    $active = $sort === $col;
    $icon   = $active ? ($dir === 'asc' ? '↑' : '↓') : '↕';
    $cls    = 'th-sort' . ($active ? ' th-sort--' . $dir : '');
    $href   = htmlspecialchars($sortUrl($col), ENT_QUOTES, 'UTF-8');
    return '<th class="' . $cls . '"><a href="' . $href . '">'
        . htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
        . ' <span class="sort-icon" aria-hidden="true">' . $icon . '</span></a></th>';
};
?>
<!-- Header -->
<div class="page-header settings-header">
    <div>
        <h1>Sectors</h1>
        <span class="count-label"><?= count($sectors) ?> sectors</span>
    </div>
    <div class="page-actions">
        <a class="btn btn-primary" href="<?= htmlspecialchars(Auth::url('/sectors/create'), ENT_QUOTES, 'UTF-8') ?>">
            <i class="ph ph-plus"></i>
            Create sector
        </a>
    </div>
</div>

<div class="settings-search-card">
    <input type="text"
           class="settings-live-search"
           placeholder="Search sectors..."
           autocomplete="off"
           data-settings-search="sectors"
           data-search-endpoint="<?= htmlspecialchars(Auth::url('/ajax/sectors/search'), ENT_QUOTES, 'UTF-8') ?>"
           data-search-target="sectorsSearchResults"
           data-edit-url="<?= htmlspecialchars(Auth::url('/sectors/edit?id='), ENT_QUOTES, 'UTF-8') ?>"
           data-delete-url="<?= htmlspecialchars(Auth::url('/sectors/delete?id='), ENT_QUOTES, 'UTF-8') ?>">
</div>

<div class="settings-table-card">
    <?php if (empty($sectors)): ?>
        <p class="table-empty-state">No sectors found.</p>
    <?php else: ?>
    <table class="data-table settings-table">
        <thead>
            <tr>
                <?= $thSort('name', 'Name') ?>
                <?= $thSort('slug', 'Slug') ?>
                <?= $thSort('is_active', 'Status') ?>
                <th class="col-actions">Actions</th>
            </tr>
        </thead>
        <tbody id="sectorsSearchResults">
            <?php foreach ($sectors as $sector): ?>
                <tr>
                    <td class="col-name"><a class="col-row-link" href="<?= htmlspecialchars(Auth::url('/sectors/edit?id=' . $sector['id']), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($sector['name'], ENT_QUOTES, 'UTF-8') ?></a></td>
                    <td class="col-slug"><?= htmlspecialchars($sector['slug'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <?php if ((int) $sector['is_active'] === 1): ?>
                            <span class="badge-active">Active</span>
                        <?php else: ?>
                            <span class="badge-inactive">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td class="col-actions">
                        <div class="action-links">
                            <a class="action-btn action-edit" title="Edit" href="<?= htmlspecialchars(Auth::url('/sectors/edit?id=' . $sector['id']), ENT_QUOTES, 'UTF-8') ?>">
                                <i class="ph ph-pencil"></i>
                            </a>
                            <a class="action-btn action-delete" title="Delete"
                               href="<?= htmlspecialchars(Auth::url('/sectors/delete?id=' . $sector['id']), ENT_QUOTES, 'UTF-8') ?>"
                               onclick="return confirm('Delete this sector? If it is used by clients, it will be deactivated.')">
                                <i class="ph ph-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

