<!-- Header -->
<div class="page-header settings-header">
    <div>
        <h1><?= t('tags.title') ?></h1>
        <span class="count-label"><?= htmlspecialchars(Lang::get('tags.n_tags', ['n' => $total]), ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <div class="page-actions">
        <a class="btn btn-primary" href="<?= htmlspecialchars(Auth::url('/tags/create'), ENT_QUOTES, 'UTF-8') ?>">
            <i class="ph ph-plus"></i>
            <?= t('tags.create_btn') ?>
        </a>
    </div>
</div>

<div class="settings-search-card">
    <input type="text"
           class="settings-live-search"
           placeholder="<?= t('common.search_tags') ?>"
           autocomplete="off"
           data-settings-search="tags"
           data-search-endpoint="<?= htmlspecialchars(Auth::url('/ajax/tags/search'), ENT_QUOTES, 'UTF-8') ?>"
           data-search-target="tagsSearchResults"
           data-edit-url="<?= htmlspecialchars(Auth::url('/tags/edit?id='), ENT_QUOTES, 'UTF-8') ?>"
           data-delete-url="<?= htmlspecialchars(Auth::url('/tags/delete?id='), ENT_QUOTES, 'UTF-8') ?>">
</div>

<div class="settings-table-card">
    <?php if (empty($tags)): ?>
        <p class="table-empty-state"><?= t('tags.no_found') ?></p>
    <?php else: ?>
    <table class="data-table settings-table">
        <thead>
            <tr>
                <?= thSort('name', t('common.name'), $sort, $dir, '/tags') ?>
                <?= thSort('slug', t('sectors.slug'), $sort, $dir, '/tags') ?>
                <th><?= t('tags.color') ?></th>
                <th class="col-actions"><?= t('common.actions') ?></th>
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
                            <a class="action-btn action-edit" href="<?= htmlspecialchars(Auth::url('/tags/edit?id=' . $tag['id']), ENT_QUOTES, 'UTF-8') ?>">
                                <i class="ph ph-pencil"></i>
                                <span class="tooltip-text"><?= t('common.edit') ?></span>
                            </a>
                            <a class="action-btn action-delete"
                               href="<?= htmlspecialchars(Auth::url('/tags/delete?id=' . $tag['id']), ENT_QUOTES, 'UTF-8') ?>"
                               onclick="return confirm('<?= htmlspecialchars(Lang::get('tags.delete_confirm'), ENT_QUOTES, 'UTF-8') ?>')">
                                <i class="ph ph-trash"></i>
                                <span class="tooltip-text"><?= t('common.delete') ?></span>
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php renderPagination($page, $totalPages, $total, $perPage, '/tags', ['sort' => $sort, 'dir' => $dir]); ?>
