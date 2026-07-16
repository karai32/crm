<!-- Header -->
<div class="page-header settings-header">
    <div>
        <h1><?= t('cf.title') ?></h1>
        <span class="count-label"><?= htmlspecialchars(Lang::get('cf.n_fields', ['n' => $total]), ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <div class="page-actions">
        <a class="btn btn-primary" href="<?= htmlspecialchars(Auth::url('/custom-fields/create'), ENT_QUOTES, 'UTF-8') ?>">
            <i class="ph ph-plus"></i>
            <?= t('cf.create_btn') ?>
        </a>
    </div>
</div>

<div class="settings-table-card">
    <?php if (empty($fields)): ?>
        <p class="table-empty-state"><?= t('cf.no_found') ?></p>
    <?php else: ?>
    <table class="data-table settings-table">
        <thead>
            <tr>
                <?= thSort('entity_type', t('cf.entity'), $sort, $dir, '/custom-fields') ?>
                <?= thSort('name', t('cf.field_name'), $sort, $dir, '/custom-fields') ?>
                <?= thSort('slug', t('sectors.slug'), $sort, $dir, '/custom-fields') ?>
                <?= thSort('field_type', t('cf.field_type'), $sort, $dir, '/custom-fields') ?>
                <th><?= t('cf.required') ?></th>
                <th><?= t('cf.filterable') ?></th>
                <th><?= t('cf.sort') ?></th>
                <th class="col-actions"><?= t('common.actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($fields as $field): ?>
                <tr>
                    <td>
                        <span class="badge-entity badge-<?= htmlspecialchars($field['entity_type'], ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars(ucfirst($field['entity_type']), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </td>
                    <td class="col-name"><a class="col-row-link" href="<?= htmlspecialchars(Auth::url('/custom-fields/edit?id=' . $field['id']), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($field['name'], ENT_QUOTES, 'UTF-8') ?></a></td>
                    <td class="col-slug"><?= htmlspecialchars($field['slug'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge-type"><?= htmlspecialchars($field['field_type'], ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td>
                        <?php if ((int) $field['is_required'] === 1): ?>
                            <span class="badge-active"><?= t('common.yes') ?></span>
                        <?php else: ?>
                            <span class="badge-no"><?= t('common.no') ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ((int) $field['is_filterable'] === 1): ?>
                            <span class="badge-active"><?= t('common.yes') ?></span>
                        <?php else: ?>
                            <span class="badge-no"><?= t('common.no') ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="col-sort-order"><?= (int) $field['sort_order'] ?></td>
                    <td class="col-actions">
                        <div class="action-links">
                            <a class="action-btn action-edit" href="<?= htmlspecialchars(Auth::url('/custom-fields/edit?id=' . $field['id']), ENT_QUOTES, 'UTF-8') ?>">
                                <i class="ph ph-pencil"></i>
                                <span class="tooltip-text"><?= t('common.edit') ?></span>
                            </a>
                            <?php renderDeleteButton('/custom-fields/delete', (int) $field['id'], Lang::get('cf.delete_confirm'), Lang::get('common.delete')); ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php renderPagination($page, $totalPages, $total, $perPage, '/custom-fields', ['sort' => $sort, 'dir' => $dir]); ?>
