<!-- Header -->
<div class="page-header settings-header">
    <div>
        <h1><?= t('sectors.title') ?></h1>
        <span class="count-label"><?= htmlspecialchars(Lang::get('sectors.n_sectors', ['n' => $total]), ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <div class="page-actions">
        <a class="btn btn-primary" href="<?= htmlspecialchars(Auth::url('/sectors/create'), ENT_QUOTES, 'UTF-8') ?>">
            <i class="ph ph-plus"></i>
            <?= t('sectors.create_btn') ?>
        </a>
    </div>
</div>

<div class="settings-search-card">
    <input type="text"
           class="settings-live-search"
           placeholder="<?= t('sectors.search') ?>"
           autocomplete="off"
           data-settings-search="sectors"
           data-search-endpoint="<?= htmlspecialchars(Auth::url('/ajax/sectors/search'), ENT_QUOTES, 'UTF-8') ?>"
           data-search-target="sectorsSearchResults"
           data-edit-url="<?= htmlspecialchars(Auth::url('/sectors/edit?id='), ENT_QUOTES, 'UTF-8') ?>"
           data-delete-url="<?= htmlspecialchars(Auth::url('/sectors/delete'), ENT_QUOTES, 'UTF-8') ?>"
           data-csrf-token="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
</div>

<div class="settings-table-card">
    <?php if (empty($sectors)): ?>
        <p class="table-empty-state"><?= t('sectors.no_found') ?></p>
    <?php else: ?>
    <table class="data-table settings-table">
        <thead>
            <tr>
                <?= thSort('name', t('common.name'), $sort, $dir, '/sectors') ?>
                <?= thSort('slug', t('sectors.slug'), $sort, $dir, '/sectors') ?>
                <?= thSort('is_active', t('common.status'), $sort, $dir, '/sectors') ?>
                <th class="col-actions"><?= t('common.actions') ?></th>
            </tr>
        </thead>
        <tbody id="sectorsSearchResults">
            <?php foreach ($sectors as $sector): ?>
                <tr>
                    <td class="col-name">
                        <a class="col-row-link sector-name-cell" href="<?= htmlspecialchars(Auth::url('/sectors/edit?id=' . $sector['id']), ENT_QUOTES, 'UTF-8') ?>">
                            <span class="sector-list-icon">
                                <i class="ph ph-<?= htmlspecialchars($sector['icon'] ?: 'crosshair', ENT_QUOTES, 'UTF-8') ?>"></i>
                            </span>
                            <span><?= htmlspecialchars($sector['name'], ENT_QUOTES, 'UTF-8') ?></span>
                        </a>
                    </td>
                    <td class="col-slug"><?= htmlspecialchars($sector['slug'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <?php if ((int) $sector['is_active'] === 1): ?>
                            <span class="badge-active"><?= t('common.active') ?></span>
                        <?php else: ?>
                            <span class="badge-inactive"><?= t('common.inactive') ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="col-actions">
                        <div class="action-links">
                            <a class="action-btn action-edit" href="<?= htmlspecialchars(Auth::url('/sectors/edit?id=' . $sector['id']), ENT_QUOTES, 'UTF-8') ?>">
                                <i class="ph ph-pencil"></i>
                                <span class="tooltip-text"><?= t('common.edit') ?></span>
                            </a>
                            <?php renderDeleteButton('/sectors/delete', (int) $sector['id'], Lang::get('sectors.delete_confirm'), Lang::get('common.delete')); ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php renderPagination($page, $totalPages, $total, $perPage, '/sectors', ['sort' => $sort, 'dir' => $dir]); ?>
