<?php
$sortUrl = function (string $col) use ($sort, $dir): string {
    $nd = ($sort === $col && $dir === 'asc') ? 'desc' : 'asc';
    return Auth::url('/tags?' . http_build_query(['sort' => $col, 'dir' => $nd]));
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
        <h1>Tags</h1>
        <span class="count-label"><?= count($tags) ?> tags</span>
    </div>
    <div class="page-actions">
        <a class="btn btn-primary" href="<?= htmlspecialchars(Auth::url('/tags/create'), ENT_QUOTES, 'UTF-8') ?>">
            <i class="ph ph-plus"></i>
            Create tag
        </a>
    </div>
</div>

<div class="settings-search-card">
    <input type="text"
           class="settings-live-search"
           placeholder="Search tags..."
           autocomplete="off"
           data-settings-search="tags"
           data-search-endpoint="<?= htmlspecialchars(Auth::url('/ajax/tags/search'), ENT_QUOTES, 'UTF-8') ?>"
           data-search-target="tagsSearchResults"
           data-edit-url="<?= htmlspecialchars(Auth::url('/tags/edit?id='), ENT_QUOTES, 'UTF-8') ?>"
           data-delete-url="<?= htmlspecialchars(Auth::url('/tags/delete?id='), ENT_QUOTES, 'UTF-8') ?>">
</div>

<div class="settings-table-card">
    <?php if (empty($tags)): ?>
        <p class="table-empty-state">No tags found.</p>
    <?php else: ?>
    <table class="data-table settings-table">
        <thead>
            <tr>
                <?= $thSort('name', 'Name') ?>
                <?= $thSort('slug', 'Slug') ?>
                <th>Color</th>
                <th class="col-actions">Actions</th>
            </tr>
        </thead>
        <tbody id="tagsSearchResults">
            <?php foreach ($tags as $tag): ?>
                <tr>
                    <td class="col-name">
                        <a class="col-row-link" href="<?= htmlspecialchars(Auth::url('/tags/edit?id=' . $tag['id']), ENT_QUOTES, 'UTF-8') ?>">
                        <?php if (!empty($tag['color'])): ?>
                            <span class="tag-badge" style="background:<?= htmlspecialchars($tag['color'], ENT_QUOTES, 'UTF-8') ?>22;border-color:<?= htmlspecialchars($tag['color'], ENT_QUOTES, 'UTF-8') ?>44;color:<?= htmlspecialchars($tag['color'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($tag['name'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        <?php else: ?>
                            <?= htmlspecialchars($tag['name'], ENT_QUOTES, 'UTF-8') ?>
                        <?php endif; ?>
                        </a>
                    </td>
                    <td class="col-slug"><?= htmlspecialchars($tag['slug'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <?php if (!empty($tag['color'])): ?>
                            <span class="tag-color-display">
                                <span class="color-dot" style="background:<?= htmlspecialchars($tag['color'], ENT_QUOTES, 'UTF-8') ?>"></span>
                                <code class="tag-color-code"><?= htmlspecialchars($tag['color'], ENT_QUOTES, 'UTF-8') ?></code>
                            </span>
                        <?php else: ?>
                            <span class="badge-no">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="col-actions">
                        <div class="action-links">
                            <a class="action-btn action-edit" title="Edit" href="<?= htmlspecialchars(Auth::url('/tags/edit?id=' . $tag['id']), ENT_QUOTES, 'UTF-8') ?>">
                                <i class="ph ph-pencil"></i>
                            </a>
                            <a class="action-btn action-delete" title="Delete"
                               href="<?= htmlspecialchars(Auth::url('/tags/delete?id=' . $tag['id']), ENT_QUOTES, 'UTF-8') ?>"
                               onclick="return confirm('Delete this tag? Existing contact and client links will be removed.')">
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

