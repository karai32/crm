<!-- Header -->
<div class="page-header settings-header">
    <div>
        <h1>Custom fields</h1>
        <span class="count-label"><?= (int) $total ?> fields</span>
    </div>
    <div class="page-actions">
        <a class="btn btn-primary" href="<?= htmlspecialchars(Auth::url('/custom-fields/create'), ENT_QUOTES, 'UTF-8') ?>">
            <i class="ph ph-plus"></i>
            Create field
        </a>
    </div>
</div>

<div class="settings-table-card">
    <?php if (empty($fields)): ?>
        <p class="table-empty-state">No custom fields found.</p>
    <?php else: ?>
    <table class="data-table settings-table">
        <thead>
            <tr>
                <?= thSort('entity_type', 'Entity', $sort, $dir, '/custom-fields') ?>
                <?= thSort('name', 'Name', $sort, $dir, '/custom-fields') ?>
                <?= thSort('slug', 'Slug', $sort, $dir, '/custom-fields') ?>
                <?= thSort('field_type', 'Type', $sort, $dir, '/custom-fields') ?>
                <th>Required</th>
                <th>Filterable</th>
                <th>Sort</th>
                <th class="col-actions">Actions</th>
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
                            <span class="badge-active">Yes</span>
                        <?php else: ?>
                            <span class="badge-no">No</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ((int) $field['is_filterable'] === 1): ?>
                            <span class="badge-active">Yes</span>
                        <?php else: ?>
                            <span class="badge-no">No</span>
                        <?php endif; ?>
                    </td>
                    <td class="col-sort-order"><?= (int) $field['sort_order'] ?></td>
                    <td class="col-actions">
                        <div class="action-links">
                            <a class="action-btn action-edit" href="<?= htmlspecialchars(Auth::url('/custom-fields/edit?id=' . $field['id']), ENT_QUOTES, 'UTF-8') ?>">
                                <i class="ph ph-pencil"></i>
                                <span class="tooltip-text">Edit</span>
                            </a>
                            <a class="action-btn action-delete"
                               href="<?= htmlspecialchars(Auth::url('/custom-fields/delete?id=' . $field['id']), ENT_QUOTES, 'UTF-8') ?>"
                               onclick="return confirm('Delete this custom field? Values will also be deleted.')">
                                <i class="ph ph-trash"></i>
                                <span class="tooltip-text">Delete</span>
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php renderPagination($page, $totalPages, $total, $perPage, '/custom-fields', ['sort' => $sort, 'dir' => $dir]); ?>
