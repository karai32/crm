<?php
$activeFilters = array_filter($filters, function ($value) {
    return is_array($value) ? !empty($value) : ($value !== '' && $value !== 0 && $value !== null);
});

$paginateParams = array_merge($activeFilters, ['sort' => $sort, 'dir' => $dir]);

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

$preselectedSectorJson = '[]';
if (!empty($filters['sector_id'])) {
    foreach ($filterSectors as $_s) {
        if ((int) $_s['id'] === (int) $filters['sector_id']) {
            $preselectedSectorJson = json_encode([['id' => (int) $_s['id'], 'name' => $_s['name']]]);
            break;
        }
    }
}

$from = $total > 0 ? ($page - 1) * $perPage + 1 : 0;
$to   = min($page * $perPage, $total);


/* --- Build filter chips ---------------------------------------- */
$selectedTagObjects = array_values(array_filter($filterTags, function ($tag) use ($selectedFilterTagIds) {
    return in_array((int) $tag['id'], array_map('intval', $selectedFilterTagIds ?? []), true);
}));

$chips = [];
$base  = Auth::url('/contacts');

$textCols = [
    'full_name' => 'Name',
    'email'     => 'Email',
    'phone'     => 'Phone',
];

foreach ($textCols as $key => $label) {
    if (!empty($filters[$key])) {
        $f = $filters;
        unset($f[$key], $f['page']);
        $chips[] = ['text' => $label . ': ' . $filters[$key], 'href' => $base . '?' . http_build_query($f)];
    }
}

foreach (
    [
        ['from' => 'created_from', 'to' => 'created_to', 'label' => 'Created'],
        ['from' => 'updated_from', 'to' => 'updated_to', 'label' => 'Updated'],
    ] as $_dr
) {
    $hasFrom = !empty($filters[$_dr['from']]);
    $hasTo   = !empty($filters[$_dr['to']]);
    if ($hasFrom || $hasTo) {
        $text = $_dr['label'] . ': ';
        $text .= $hasFrom ? $filters[$_dr['from']] : '…';
        $text .= ' — ';
        $text .= $hasTo   ? $filters[$_dr['to']]   : '…';
        $f = $filters;
        unset($f[$_dr['from']], $f[$_dr['to']], $f['page']);
        $chips[] = ['text' => $text, 'href' => $base . '?' . http_build_query($f)];
    }
}

if (!empty($filters['client_id'])) {
    $f = $filters;
    unset($f['client_id'], $f['page']);
    $chips[] = ['text' => 'Client: ' . $filterClientName, 'href' => $base . '?' . http_build_query($f)];
}

if (!empty($filters['sector_id'])) {
    $sName = '';
    foreach ($filterSectors as $s) {
        if ((int) $s['id'] === (int) $filters['sector_id']) {
            $sName = $s['name'];
            break;
        }
    }
    $f = $filters;
    unset($f['sector_id'], $f['page']);
    $chips[] = ['text' => 'Sector: ' . $sName, 'href' => $base . '?' . http_build_query($f)];
}

foreach ($selectedTagObjects as $tag) {
    $f = $filters;
    $f['tag_ids'] = array_values(array_filter($f['tag_ids'] ?? [], fn($id) => (int) $id !== (int) $tag['id']));
    if (empty($f['tag_ids'])) unset($f['tag_ids']);
    unset($f['page']);
    $chips[] = ['text' => 'Tag: ' . $tag['name'], 'href' => $base . '?' . http_build_query($f)];
}

if (!empty($filters['custom_fields'])) {
    foreach ($filters['custom_fields'] as $fieldId => $value) {
        if ($value !== '') {
            $fieldName = '';
            foreach ($customFilterFields as $cf) {
                if ((int) $cf['id'] === (int) $fieldId) {
                    $fieldName = $cf['name'];
                    break;
                }
            }
            $f = $filters;
            unset($f['custom_fields'][(int) $fieldId]);
            if (empty($f['custom_fields'])) unset($f['custom_fields']);
            unset($f['page']);
            $chips[] = ['text' => ($fieldName ?: 'Field') . ': ' . $value, 'href' => $base . '?' . http_build_query($f)];
        }
    }
}

$hasExtended = !empty($filters['custom_fields']);
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
        <input type="hidden" name="dir" value="<?= htmlspecialchars($dir, ENT_QUOTES, 'UTF-8') ?>">

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
                <label>Tags</label>
                <div class="token-picker token-picker--filter"
                    data-endpoint="<?= htmlspecialchars(Auth::url('/ajax/tags/search'), ENT_QUOTES, 'UTF-8') ?>"
                    data-name="tag_ids[]"
                    data-with-color="1"
                    data-placeholder="All tags"
                    data-paginate="1"
                    data-selected="<?= htmlspecialchars($preselectedFilterTagsJson, ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>
            <div class="field">
                <label>Created date</label>
                <div class="date-range-field">
                    <input type="date" name="created_from"
                        value="<?= htmlspecialchars($filters['created_from'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <span class="date-range-sep">—</span>
                    <input type="date" name="created_to"
                        value="<?= htmlspecialchars($filters['created_to'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>
            <div class="field">
                <label>Updated date</label>
                <div class="date-range-field">
                    <input type="date" name="updated_from"
                        value="<?= htmlspecialchars($filters['updated_from'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <span class="date-range-sep">—</span>
                    <input type="date" name="updated_to"
                        value="<?= htmlspecialchars($filters['updated_to'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>
            <div class="field">
                <label>Linked client</label>
                <div class="token-picker token-picker--filter"
                    data-endpoint="<?= htmlspecialchars(Auth::url('/ajax/clients/search'), ENT_QUOTES, 'UTF-8') ?>"
                    data-name="client_id"
                    data-placeholder="All clients"
                    data-max="1"
                    data-paginate="1"
                    data-selected="<?= htmlspecialchars($preselectedFilterClientJson, ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>
            <div class="field">
                <label>Client sector</label>
                <div class="token-picker token-picker--filter"
                    data-name="sector_id"
                    data-max="1"
                    data-placeholder="All sectors"
                    data-options="<?= htmlspecialchars(json_encode(array_map(fn($s) => ['id' => (int) $s['id'], 'name' => $s['name']], $filterSectors)), ENT_QUOTES, 'UTF-8') ?>"
                    data-selected="<?= htmlspecialchars($preselectedSectorJson, ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>
        </div>

        <div class="filter-grid filter-grid--extra <?= $hasExtended ? 'open' : '' ?>" id="filterExtra">
            <?php if (!empty($customFilterFields)): ?>
                <?php foreach ($customFilterFields as $field):
                    $cfId  = (int) $field['id'];
                    $cfVal = $filters['custom_fields'][$cfId] ?? '';
                ?>
                    <div class="field">
                        <label><?= htmlspecialchars($field['name'], ENT_QUOTES, 'UTF-8') ?></label>
                        <?php if ($field['field_type'] === 'select'): ?>
                            <?php
                            $cfOptions = array_map(fn($o) => ['id' => $o['value'], 'name' => $o['label']], $field['options']);
                            $cfPreselected = '[]';
                            if ($cfVal !== '') {
                                $cfLabel = $cfVal;
                                foreach ($field['options'] as $_o) {
                                    if ($_o['value'] === $cfVal) {
                                        $cfLabel = $_o['label'];
                                        break;
                                    }
                                }
                                $cfPreselected = json_encode([['id' => $cfVal, 'name' => $cfLabel]]);
                            }
                            ?>
                            <div class="token-picker token-picker--filter"
                                data-name="custom_fields[<?= $cfId ?>]"
                                data-max="1"
                                data-placeholder="Any"
                                data-options="<?= htmlspecialchars(json_encode($cfOptions), ENT_QUOTES, 'UTF-8') ?>"
                                data-selected="<?= htmlspecialchars($cfPreselected, ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                        <?php elseif ($field['field_type'] === 'checkbox'): ?>
                            <?php
                            $cfPreselected = '[]';
                            if ($cfVal !== '') {
                                $cfPreselected = json_encode([['id' => $cfVal, 'name' => $cfVal === '1' ? 'Yes' : 'No']]);
                            }
                            ?>
                            <div class="token-picker token-picker--filter"
                                data-name="custom_fields[<?= $cfId ?>]"
                                data-max="1"
                                data-placeholder="Any"
                                data-options='[{"id":"1","name":"Yes"},{"id":"0","name":"No"}]'
                                data-selected="<?= htmlspecialchars($cfPreselected, ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                        <?php elseif ($field['field_type'] === 'text'): ?>
                            <?php
                            $cfPreselected = $cfVal !== '' ? json_encode([['id' => $cfVal, 'name' => $cfVal]]) : '[]';
                            ?>
                            <div class="token-picker token-picker--filter"
                                data-endpoint="<?= htmlspecialchars(Auth::url('/ajax/custom-field/values?field_id=' . $cfId), ENT_QUOTES, 'UTF-8') ?>"
                                data-name="custom_fields[<?= $cfId ?>]"
                                data-max="1"
                                data-paginate="1"
                                data-placeholder="Any"
                                data-selected="<?= htmlspecialchars($cfPreselected, ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                        <?php elseif ($field['field_type'] === 'number'): ?>
                            <input type="number" name="custom_fields[<?= $cfId ?>]"
                                value="<?= htmlspecialchars($cfVal, ENT_QUOTES, 'UTF-8') ?>">
                        <?php elseif ($field['field_type'] === 'date'): ?>
                            <input type="date" name="custom_fields[<?= $cfId ?>]"
                                value="<?= htmlspecialchars($cfVal, ENT_QUOTES, 'UTF-8') ?>">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="filter-footer">
            <?php if (!empty($customFilterFields)): ?>
                <button type="button" id="filterToggleBtn"
                    class="filter-toggle-btn <?= $hasExtended ? 'open' : '' ?>">
                    <span class="filter-toggle-label"><?= $hasExtended ? 'Less filters' : 'More filters' ?></span>
                    <svg class="filter-toggle-chevron" viewBox="0 0 10 6" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M1 1l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </button>
            <?php else: ?>
                <div></div>
            <?php endif; ?>
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
                                    data-paginate="1"
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
                    <?= thSort('id', 'ID', $sort, $dir, '/contacts', $activeFilters, 'col-id') ?>
                    <?= thSort('full_name', 'Name', $sort, $dir, '/contacts', $activeFilters) ?>
                    <?= thSort('email', 'Email', $sort, $dir, '/contacts', $activeFilters) ?>
                    <?= thSort('clients', 'Clients', $sort, $dir, '/contacts', $activeFilters) ?>
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
                                    <?php if (!empty($cList[0]['sector_icon'])): ?>
                                        <span class="sector-list-icon">
                                            <i class="ph ph-<?= htmlspecialchars($cList[0]['sector_icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                                        </span>
                                    <?php endif; ?>

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
                                <a class="action-btn action-view"
                                    href="<?= htmlspecialchars(Auth::url('/contacts/show?id=' . $contact['id']), ENT_QUOTES, 'UTF-8') ?>">
                                    <i class="ph ph-eye"></i>
                                    <span class="tooltip-text">View</span>
                                </a>
                                <?php if ($canEditContacts): ?>
                                    <a class="action-btn action-edit"
                                        href="<?= htmlspecialchars(Auth::url('/contacts/edit?id=' . $contact['id']), ENT_QUOTES, 'UTF-8') ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                                        </svg>
                                        <span class="tooltip-text">Edit</span>
                                    </a>
                                <?php endif; ?>
                                <?php if ($canDeleteContacts): ?>
                                    <a class="action-btn action-delete"
                                        href="<?= htmlspecialchars(Auth::url('/contacts/delete?id=' . $contact['id']), ENT_QUOTES, 'UTF-8') ?>"
                                        onclick="return confirm('Delete this contact?')">
                                        <i class="ph ph-trash"></i>
                                        <span class="tooltip-text">Delete</span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    </div>

    <?php renderPagination($page, $totalPages, $total, $perPage, '/contacts', $paginateParams, 'contacts-pagination'); ?>
<?php endif; ?>