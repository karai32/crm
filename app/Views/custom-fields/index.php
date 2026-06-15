<!-- Header -->
<div class="page-header settings-header">
    <div>
        <h1>Custom fields</h1>
        <span class="count-label"><?= count($fields) ?> fields</span>
    </div>
    <div class="page-actions">
        <a class="btn btn-primary" href="<?= htmlspecialchars(Auth::url('/custom-fields/create'), ENT_QUOTES, 'UTF-8') ?>">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="btn-icon-sm">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Create field
        </a>
    </div>
</div>

<div class="settings-table-card">
    <?php if (empty($fields)): ?>
        <p class="table-empty-state">No custom fields found.</p>
    <?php else: ?>
    <table class="settings-table">
        <thead>
            <tr>
                <th>Entity</th>
                <th>Name</th>
                <th>Slug</th>
                <th>Type</th>
                <th>Required</th>
                <th>Filterable</th>
                <th>Sort</th>
                <th>Actions</th>
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
                    <td class="col-name"><?= htmlspecialchars($field['name'], ENT_QUOTES, 'UTF-8') ?></td>
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
                    <td>
                        <div class="action-links">
                            <a class="action-edit" href="<?= htmlspecialchars(Auth::url('/custom-fields/edit?id=' . $field['id']), ENT_QUOTES, 'UTF-8') ?>">Edit</a>
                            <a class="action-delete"
                               href="<?= htmlspecialchars(Auth::url('/custom-fields/delete?id=' . $field['id']), ENT_QUOTES, 'UTF-8') ?>"
                               onclick="return confirm('Delete this custom field? Values will also be deleted.')">Delete</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
