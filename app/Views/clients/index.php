<?php
$activeFilters = array_filter($filters, function ($value) {
    return is_array($value) ? !empty($value) : ($value !== '' && $value !== 0 && $value !== null);
});

$paginateParams = array_merge($activeFilters, ['sort' => $sort, 'dir' => $dir]);

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

$preselectedSectorJson = '[]';
if (!empty($filters['sector_id'])) {
    foreach ($filterSectors as $_s) {
        if ((int) $_s['id'] === (int) $filters['sector_id']) {
            $preselectedSectorJson = json_encode([['id' => (int) $_s['id'], 'name' => $_s['name']]]);
            break;
        }
    }
}

$preselectedWebJson = '[]';
if ($filters['is_web_connected'] !== '') {
    $preselectedWebJson = json_encode([[
        'id'   => $filters['is_web_connected'],
        'name' => $filters['is_web_connected'] === '1' ? Lang::get('common.connected') : Lang::get('common.not_connected'),
    ]]);
}

$preselectedActiveJson = '[]';
if ($filters['is_active'] !== '') {
    $preselectedActiveJson = json_encode([[
        'id'   => $filters['is_active'],
        'name' => $filters['is_active'] === '1' ? Lang::get('common.active') : Lang::get('common.inactive'),
    ]]);
}

$preselectedCountryJson  = '[]';
$preselectedProvinceJson = '[]';
$preselectedCityJson     = '[]';
if (!empty($filters['country']))  { $preselectedCountryJson  = json_encode([['id' => $filters['country'],  'name' => $filters['country']]]); }
if (!empty($filters['province'])) { $preselectedProvinceJson = json_encode([['id' => $filters['province'], 'name' => $filters['province']]]); }
if (!empty($filters['city']))     { $preselectedCityJson     = json_encode([['id' => $filters['city'],     'name' => $filters['city']]]); }

/* --- Build filter chips ---------------------------------------- */
$selectedTagObjects = array_values(array_filter($filterTags, function ($tag) use ($selectedFilterTagIds) {
    return in_array((int) $tag['id'], array_map('intval', $selectedFilterTagIds ?? []), true);
}));

$chips = [];
$base  = Auth::url('/clients');

foreach ([
    'commercial_name' => Lang::get('filter.commercial_name'),
    'legal_name'      => Lang::get('filter.legal_name'),
    'website'         => Lang::get('filter.website'),
    'country'         => Lang::get('filter.country'),
    'province'        => Lang::get('filter.province'),
    'city'            => Lang::get('filter.city'),
    'address'         => Lang::get('filter.address'),
] as $key => $label) {
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
    $chips[] = ['text' => Lang::get('filter.sector') . ': ' . $sName, 'href' => $base . '?' . http_build_query($f)];
}

if ($filters['is_web_connected'] !== '') {
    $f = $filters; unset($f['is_web_connected'], $f['page']);
    $chips[] = ['text' => $filters['is_web_connected'] === '1' ? Lang::get('filter.web') . ': ' . Lang::get('common.connected') : Lang::get('filter.web') . ': ' . Lang::get('common.not_connected'),
                'href' => $base . '?' . http_build_query($f)];
}

if ($filters['is_active'] !== '') {
    $f = $filters; unset($f['is_active'], $f['page']);
    $chips[] = ['text' => $filters['is_active'] === '1' ? Lang::get('filter.active') . ': ' . Lang::get('common.yes') : Lang::get('filter.active') . ': ' . Lang::get('common.no'),
                'href' => $base . '?' . http_build_query($f)];
}

if (!empty($filters['created_from']) || !empty($filters['created_to'])) {
    $text = Lang::get('filter.created') . ': ';
    $text .= !empty($filters['created_from']) ? $filters['created_from'] : '…';
    $text .= ' — ';
    $text .= !empty($filters['created_to']) ? $filters['created_to'] : '…';
    $f = $filters; unset($f['created_from'], $f['created_to'], $f['page']);
    $chips[] = ['text' => $text, 'href' => $base . '?' . http_build_query($f)];
}

foreach ($selectedTagObjects as $tag) {
    $f = $filters;
    $f['tag_ids'] = array_values(array_filter($f['tag_ids'] ?? [], fn ($id) => (int) $id !== (int) $tag['id']));
    if (empty($f['tag_ids'])) unset($f['tag_ids']);
    unset($f['page']);
    $chips[] = ['text' => Lang::get('filter.tag') . ': ' . $tag['name'], 'href' => $base . '?' . http_build_query($f)];
}

if (!empty($filters['custom_fields'])) {
    foreach ($filters['custom_fields'] as $fieldId => $value) {
        if ($value !== '') {
            $fieldName = '';
            foreach ($customFilterFields as $cf) {
                if ((int) $cf['id'] === (int) $fieldId) { $fieldName = $cf['name']; break; }
            }
            $f = $filters;
            unset($f['custom_fields'][(int) $fieldId]);
            if (empty($f['custom_fields'])) unset($f['custom_fields']);
            unset($f['page']);
            $chips[] = ['text' => ($fieldName ?: 'Field') . ': ' . $value, 'href' => $base . '?' . http_build_query($f)];
        }
    }
}

$hasExtended = !empty($filters['country']) || !empty($filters['province']) || !empty($filters['city'])
            || !empty($filters['address']) || !empty($filters['custom_fields']);
?>

<!-- Header -->
<div class="page-header clients-header">
    <div>
        <h1><?= t('clients.title') ?></h1>
        <span class="count-label"><?= htmlspecialchars(Lang::get('clients.found', ['n' => $total]), ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <div class="page-actions">
        <?php if ($canCreateClients): ?>
        <a class="btn btn-primary" href="<?= htmlspecialchars(Auth::url('/clients/create'), ENT_QUOTES, 'UTF-8') ?>">
            <i class="ph ph-plus"></i>
            <?= t('clients.create_btn') ?>
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Filter bar (compact row) -->
<div class="filter-bar">
    <button type="button" class="filter-bar-btn" id="filterBarBtn">
        <i class="ph ph-funnel"></i>
        <?= t('common.filters') ?>
        <?php if ($chips): ?>
        <span class="filter-bar-count"><?= count($chips) ?></span>
        <?php endif; ?>
    </button>

    <?php if ($hasBulkActions): ?>
    <button type="button" class="filter-bar-btn actions-bar-btn" id="actionsBarBtn">
        <i class="ph ph-list-dashes"></i>
        <?= t('common.actions') ?>
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
    <a class="filter-bar-reset" href="<?= htmlspecialchars(Auth::url('/clients'), ENT_QUOTES, 'UTF-8') ?>"><?= t('common.reset_all') ?></a>
    <?php endif; ?>
</div>

<!-- Collapsible filter panel -->
<div class="filter-panel" id="filterPanel">
    <form method="get" action="<?= htmlspecialchars(Auth::url('/clients'), ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="sort" value="<?= htmlspecialchars($sort, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="dir"  value="<?= htmlspecialchars($dir, ENT_QUOTES, 'UTF-8') ?>">

        <div class="filter-grid">
            <div class="field">
                <label for="commercial_name"><?= t('clients.commercial_name') ?></label>
                <input id="commercial_name" type="text" name="commercial_name"
                       value="<?= htmlspecialchars($filters['commercial_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="field">
                <label for="legal_name"><?= t('clients.legal_name') ?></label>
                <input id="legal_name" type="text" name="legal_name"
                       value="<?= htmlspecialchars($filters['legal_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="field">
                <label><?= t('common.sector') ?></label>
                <div class="token-picker token-picker--filter"
                     data-name="sector_id"
                     data-max="1"
                     data-placeholder="<?= t('common.all_sectors') ?>"
                     data-options="<?= htmlspecialchars(json_encode(array_map(fn ($s) => ['id' => (int) $s['id'], 'name' => $s['name']], $filterSectors)), ENT_QUOTES, 'UTF-8') ?>"
                     data-selected="<?= htmlspecialchars($preselectedSectorJson, ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>
            <div class="field">
                <label><?= t('common.tags') ?></label>
                <div class="token-picker token-picker--filter"
                     data-endpoint="<?= htmlspecialchars(Auth::url('/ajax/tags/search'), ENT_QUOTES, 'UTF-8') ?>"
                     data-name="tag_ids[]"
                     data-with-color="1"
                     data-placeholder="<?= t('common.all_tags') ?>"
                     data-paginate="1"
                     data-selected="<?= htmlspecialchars($preselectedFilterTagsJson, ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>
            <div class="field">
                <label><?= t('clients.web_api') ?></label>
                <div class="token-picker token-picker--filter"
                     data-name="is_web_connected"
                     data-max="1"
                     data-placeholder="<?= t('common.all') ?>"
                     data-options="<?= htmlspecialchars(json_encode([['id' => '1', 'name' => Lang::get('common.connected')], ['id' => '0', 'name' => Lang::get('common.not_connected')]]), ENT_QUOTES, 'UTF-8') ?>"
                     data-selected="<?= htmlspecialchars($preselectedWebJson, ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>
            <div class="field">
                <label><?= t('common.active') ?></label>
                <div class="token-picker token-picker--filter"
                     data-name="is_active"
                     data-max="1"
                     data-placeholder="<?= t('common.all') ?>"
                     data-options="<?= htmlspecialchars(json_encode([['id' => '1', 'name' => Lang::get('common.active')], ['id' => '0', 'name' => Lang::get('common.inactive')]]), ENT_QUOTES, 'UTF-8') ?>"
                     data-selected="<?= htmlspecialchars($preselectedActiveJson, ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>
            <div class="field">
                <label for="website"><?= t('common.website') ?></label>
                <input id="website" type="text" name="website"
                       value="<?= htmlspecialchars($filters['website'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="field">
                <label><?= t('filter.created') ?></label>
                <div class="date-range-field">
                    <input type="date" name="created_from"
                        value="<?= htmlspecialchars($filters['created_from'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <span class="date-range-sep">—</span>
                    <input type="date" name="created_to"
                        value="<?= htmlspecialchars($filters['created_to'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>
        </div>

        <div class="filter-grid filter-grid--extra <?= $hasExtended ? 'open' : '' ?>" id="clientFilterExtra">
            <div class="field">
                <label><?= t('common.country') ?></label>
                <div class="token-picker token-picker--filter"
                     data-endpoint="<?= htmlspecialchars(Auth::url('/ajax/clients/field?field=country'), ENT_QUOTES, 'UTF-8') ?>"
                     data-name="country"
                     data-max="1"
                     data-paginate="1"
                     data-placeholder="<?= t('common.all_countries') ?>"
                     data-selected="<?= htmlspecialchars($preselectedCountryJson, ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>
            <div class="field">
                <label><?= t('common.province') ?></label>
                <div class="token-picker token-picker--filter"
                     data-endpoint="<?= htmlspecialchars(Auth::url('/ajax/clients/field?field=province'), ENT_QUOTES, 'UTF-8') ?>"
                     data-name="province"
                     data-max="1"
                     data-paginate="1"
                     data-placeholder="<?= t('common.all_provinces') ?>"
                     data-selected="<?= htmlspecialchars($preselectedProvinceJson, ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>
            <div class="field">
                <label><?= t('common.city') ?></label>
                <div class="token-picker token-picker--filter"
                     data-endpoint="<?= htmlspecialchars(Auth::url('/ajax/clients/field?field=city'), ENT_QUOTES, 'UTF-8') ?>"
                     data-name="city"
                     data-max="1"
                     data-paginate="1"
                     data-placeholder="<?= t('common.all_cities') ?>"
                     data-selected="<?= htmlspecialchars($preselectedCityJson, ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>
            <div class="field">
                <label for="address"><?= t('common.address') ?></label>
                <input id="address" type="text" name="address"
                       value="<?= htmlspecialchars($filters['address'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <?php if (!empty($customFilterFields)): ?>
            <div class="filter-grid-sep"></div>
            <?php foreach ($customFilterFields as $field):
                $cfId  = (int) $field['id'];
                $cfVal = $filters['custom_fields'][$cfId] ?? '';
            ?>
            <div class="field">
                <label><?= htmlspecialchars($field['name'], ENT_QUOTES, 'UTF-8') ?></label>
                <?php if ($field['field_type'] === 'select'): ?>
                    <?php
                        $cfOptions = array_map(fn ($o) => ['id' => $o['value'], 'name' => $o['label']], $field['options']);
                        $cfPreselected = '[]';
                        if ($cfVal !== '') {
                            $cfLabel = $cfVal;
                            foreach ($field['options'] as $_o) {
                                if ($_o['value'] === $cfVal) { $cfLabel = $_o['label']; break; }
                            }
                            $cfPreselected = json_encode([['id' => $cfVal, 'name' => $cfLabel]]);
                        }
                    ?>
                    <div class="token-picker token-picker--filter"
                         data-name="custom_fields[<?= $cfId ?>]"
                         data-max="1"
                         data-placeholder="<?= t('common.any') ?>"
                         data-options="<?= htmlspecialchars(json_encode($cfOptions), ENT_QUOTES, 'UTF-8') ?>"
                         data-selected="<?= htmlspecialchars($cfPreselected, ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                <?php elseif ($field['field_type'] === 'checkbox'): ?>
                    <?php
                        $cfPreselected = '[]';
                        if ($cfVal !== '') {
                            $cfPreselected = json_encode([['id' => $cfVal, 'name' => $cfVal === '1' ? Lang::get('common.yes') : Lang::get('common.no')]]);
                        }
                    ?>
                    <div class="token-picker token-picker--filter"
                         data-name="custom_fields[<?= $cfId ?>]"
                         data-max="1"
                         data-placeholder="<?= t('common.any') ?>"
                         data-options="<?= htmlspecialchars(json_encode([['id' => '1', 'name' => Lang::get('common.yes')], ['id' => '0', 'name' => Lang::get('common.no')]]), ENT_QUOTES, 'UTF-8') ?>"
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
                         data-placeholder="<?= t('common.any') ?>"
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
            <button type="button" id="clientFilterToggleBtn"
                    class="filter-toggle-btn <?= $hasExtended ? 'open' : '' ?>">
                <span class="filter-toggle-label"><?= $hasExtended ? t('common.less_filters') : t('common.more_filters') ?></span>
                <svg class="filter-toggle-chevron" viewBox="0 0 10 6" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 1l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
            <div class="filter-actions">
                <button class="btn btn-primary" type="submit"><?= t('common.filter') ?></button>
                <a class="btn btn-outlined" href="<?= htmlspecialchars(Auth::url('/clients'), ENT_QUOTES, 'UTF-8') ?>"><?= t('common.reset') ?></a>
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
                <div class="actions-section-label"><?= t('common.tags') ?></div>
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
                        <button type="submit" name="bulk_action" value="add_tags" class="btn btn-sm btn-primary"><?= t('common.add_tags') ?></button>
                        <button type="submit" name="bulk_action" value="remove_tags" class="btn btn-sm btn-outlined"><?= t('common.remove_tags') ?></button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($canEditClients && $canDeleteClients): ?>
            <div class="actions-panel-sep"></div>
            <?php endif; ?>

            <?php if ($canDeleteClients): ?>
            <div class="actions-panel-section actions-panel-section--danger">
                <div class="actions-section-label"><?= t('common.delete') ?></div>
                <button type="submit" name="bulk_action" value="delete" class="btn btn-sm btn-danger" id="bulkDeleteBtn"
                        onclick="return confirm('<?= htmlspecialchars(Lang::get('clients.delete_bulk_confirm'), ENT_QUOTES, 'UTF-8') ?>')">
                    <?= t('common.delete') ?> <span id="deleteCountLabel">0</span> selected
                </button>
            </div>
            <?php endif; ?>

        </div>
        <div class="actions-panel-footer">
            <span class="actions-selected-hint" id="actionsSelectedHint">0 selected</span>
            <button type="button" class="actions-deselect-btn" id="actionsDeselectBtn"><?= t('common.deselect_all') ?></button>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- Table card -->
<div class="clients-table-card">

    <?php if (empty($clients)): ?>
        <p class="table-empty-state"><?= t('clients.no_found') ?></p>
    <?php else: ?>

    <table class="data-table clients-table">
        <thead>
            <tr>
                <th class="col-select">
                    <?php if ($hasBulkActions): ?>
                    <input type="checkbox" id="clientsSelectAll" aria-label="Select all">
                    <?php endif; ?>
                </th>
                <?= thSort('id', '#', $sort, $dir, '/clients', $activeFilters, 'col-id') ?>
                <?= thSort('commercial_name', t('clients.commercial_name'), $sort, $dir, '/clients', $activeFilters) ?>
                <?= thSort('legal_name', t('clients.legal_name'), $sort, $dir, '/clients', $activeFilters) ?>
                <?= thSort('sector_name', t('common.sector'), $sort, $dir, '/clients', $activeFilters) ?>
                <th><?= t('common.tags') ?></th>
                <?= thSort('is_active', t('common.active'), $sort, $dir, '/clients', $activeFilters, 'col-active-th') ?>
                <th>Web</th>
                <th class="col-actions"><?= t('common.actions') ?></th>
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
                        <a class="client-name-link col-row-link" href="<?= htmlspecialchars(Auth::url('/clients/show?id=' . $client['id']), ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($client['commercial_name'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </td>
                    <td class="col-muted"><?= htmlspecialchars($client['legal_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <?php if (!empty($client['sector_name'])): ?>
                            <span class="client-sector-cell">
                                <span class="sector-list-icon">
                                    <i class="ph ph-<?= htmlspecialchars($client['sector_icon'] ?: 'crosshair', ENT_QUOTES, 'UTF-8') ?>"></i>
                                </span>
                                <span><?= htmlspecialchars($client['sector_name'], ENT_QUOTES, 'UTF-8') ?></span>
                            </span>
                        <?php else: ?>
                            <span class="col-muted">-</span>
                        <?php endif; ?>
                    </td>
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
                    <td class="col-active">
                        <?php if ((int) $client['is_active']): ?>
                            <i class="ph ph-check-circle col-active-yes" title="<?= t('common.active') ?>"></i>
                        <?php else: ?>
                            <i class="ph ph-x-circle col-active-no" title="<?= t('common.inactive') ?>"></i>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ((int) $client['is_web_connected']): ?>
                            <span class="badge-web-connected"><?= t('common.connected') ?></span>
                        <?php else: ?>
                            <span class="col-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="col-actions">
                        <div class="action-links">
                            <a class="action-btn action-view" href="<?= htmlspecialchars(Auth::url('/clients/show?id=' . $client['id']), ENT_QUOTES, 'UTF-8') ?>">
                                <i class="ph ph-eye"></i>
                                <span class="tooltip-text"><?= t('common.view') ?></span>
                            </a>
                            <?php if ($canEditClients): ?>
                            <a class="action-btn action-edit" href="<?= htmlspecialchars(Auth::url('/clients/edit?id=' . $client['id']), ENT_QUOTES, 'UTF-8') ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/></svg>
                                <span class="tooltip-text"><?= t('common.edit') ?></span>
                            </a>
                            <?php endif; ?>
                            <?php if ($canDeleteClients): ?>
                            <a class="action-btn action-delete"
                               href="<?= htmlspecialchars(Auth::url('/clients/delete?id=' . $client['id']), ENT_QUOTES, 'UTF-8') ?>"
                               onclick="return confirm('<?= htmlspecialchars(Lang::get('clients.delete_confirm'), ENT_QUOTES, 'UTF-8') ?>')">
                                <i class="ph ph-trash"></i>
                                <span class="tooltip-text"><?= t('common.delete') ?></span>
                            </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php endif; ?>
</div>

<?php renderPagination($page, $totalPages, $total, $perPage, '/clients', $paginateParams, 'clients-pagination'); ?>
