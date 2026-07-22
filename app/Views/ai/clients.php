<!-- Header -->
<div class="page-header settings-header">
    <div>
        <h1><?= t('ai.clients_title') ?></h1>
        <span class="count-label"><?= t('ai.clients_subtitle') ?></span>
    </div>
    <?php if (!empty($clients)): ?>
        <div class="page-actions">
            <button type="button" class="btn btn-primary" id="aiAutoBtn"
                data-label-start="<?= htmlspecialchars(t('ai.auto_start'), ENT_QUOTES, 'UTF-8') ?>"
                data-label-stop="<?= htmlspecialchars(t('ai.auto_stop'), ENT_QUOTES, 'UTF-8') ?>">
                <i class="ph ph-play"></i>
                <span class="ai-auto-label"><?= t('ai.auto_start') ?></span>
            </button>
        </div>
    <?php endif; ?>
</div>

<!-- Entity tabs -->
<div class="data-tabs">
    <a href="<?= htmlspecialchars(Auth::url('/ai'), ENT_QUOTES, 'UTF-8') ?>" class="data-tab">
        <i class="ph ph-sparkle"></i>
        <?= t('ai.title') ?>
    </a>
    <a href="<?= htmlspecialchars(Auth::url('/ai/clients'), ENT_QUOTES, 'UTF-8') ?>" class="data-tab active">
        <i class="ph ph-buildings"></i>
        <?= t('ai.clients_title') ?>
    </a>
</div>

<datalist id="aiSectorsList">
    <?php foreach ($sectors as $sector): ?>
        <option value="<?= htmlspecialchars($sector['name'], ENT_QUOTES, 'UTF-8') ?>">
    <?php endforeach; ?>
</datalist>

<div class="settings-table-card" id="aiClientsTable"
    data-endpoint="<?= htmlspecialchars(Auth::url('/ajax/clients/gemini-enrich'), ENT_QUOTES, 'UTF-8') ?>"
    data-apply-endpoint="<?= htmlspecialchars(Auth::url('/ajax/clients/enrich'), ENT_QUOTES, 'UTF-8') ?>"
    data-csrf-token="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>"
    data-empty-error="<?= htmlspecialchars(t('ai.clients_empty'), ENT_QUOTES, 'UTF-8') ?>"
    data-empty-message="<?= htmlspecialchars(t('ai.clients_no_found'), ENT_QUOTES, 'UTF-8') ?>">
    <?php if (empty($clients)): ?>
        <p class="table-empty-state"><?= t('ai.clients_no_found') ?></p>
    <?php else: ?>
    <table class="data-table settings-table">
        <thead>
            <tr>
                <?= thSort('id', t('common.id'), $sort, $dir, '/ai/clients', [], 'col-id') ?>
                <?= thSort('commercial_name', t('common.name'), $sort, $dir, '/ai/clients') ?>
                <th class="col-ai-company"><?= t('common.website') ?></th>
                <th class="col-ai-company"><?= t('common.sector') ?></th>
                <th class="col-actions"><?= t('common.actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($clients as $client): ?>
                <tr data-row-id="<?= (int) $client['id'] ?>">
                    <td class="col-id"><?= (int) $client['id'] ?></td>
                    <td class="col-name">
                        <a class="col-row-link" target="_blank" href="<?= htmlspecialchars(Auth::url('/clients/show?id=' . (int) $client['id']), ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($client['commercial_name'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </td>
                    <td class="col-ai-company">
                        <input type="text"
                            class="ai-company-input ai-client-website"
                            data-client-id="<?= (int) $client['id'] ?>"
                            value="<?= htmlspecialchars($client['website'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="<?= t('ai.clients_website_placeholder') ?>"
                            autocomplete="off"
                            spellcheck="false">
                    </td>
                    <td class="col-ai-company">
                        <input type="text"
                            class="ai-company-input ai-client-sector"
                            list="aiSectorsList"
                            data-client-id="<?= (int) $client['id'] ?>"
                            value="<?= htmlspecialchars($client['sector_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="<?= t('ai.clients_sector_placeholder') ?>"
                            autocomplete="off"
                            spellcheck="false">
                    </td>
                    <td class="col-actions">
                        <div class="action-links">
                            <button type="button"
                                class="action-btn action-ai ai-process-btn"
                                data-client-id="<?= (int) $client['id'] ?>">
                                <i class="ph ph-sparkle"></i>
                                <span class="tooltip-text"><?= t('ai.process') ?></span>
                            </button>
                            <button type="button"
                                class="action-btn action-apply ai-apply-btn"
                                data-client-id="<?= (int) $client['id'] ?>">
                                <i class="ph ph-check"></i>
                                <span class="tooltip-text"><?= t('ai.apply') ?></span>
                            </button>
                            <button type="button"
                                class="action-btn action-warn ai-skip-btn"
                                data-client-id="<?= (int) $client['id'] ?>">
                                <i class="ph ph-x"></i>
                                <span class="tooltip-text"><?= t('ai.skip') ?></span>
                            </button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php renderPagination($page, $totalPages, $total, $perPage, '/ai/clients', ['sort' => $sort, 'dir' => $dir]); ?>
