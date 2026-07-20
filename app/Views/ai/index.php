<!-- Header -->
<div class="page-header settings-header">
    <div>
        <h1><?= t('ai.title') ?></h1>
        <span class="count-label"><?= t('ai.subtitle') ?></span>
    </div>
</div>

<!-- Summary -->
<div class="ai-stats">
    <div class="ai-stat-card">
        <span class="ai-stat-value"><?= number_format((int) $total, 0, ',', '.') ?></span>
        <span class="ai-stat-label"><?= t('ai.stat_contacts') ?></span>
    </div>
    <div class="ai-stat-card">
        <span class="ai-stat-value"><?= number_format((int) $domainsTotal, 0, ',', '.') ?></span>
        <span class="ai-stat-label"><?= t('ai.stat_domains') ?></span>
    </div>
</div>

<div class="settings-table-card" id="aiCompaniesTable"
    data-endpoint="<?= htmlspecialchars(Auth::url('/ajax/contacts/gemini-company-test'), ENT_QUOTES, 'UTF-8') ?>"
    data-csrf-token="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
    <?php if (empty($contacts)): ?>
        <p class="table-empty-state"><?= t('ai.no_found') ?></p>
    <?php else: ?>
    <table class="data-table settings-table">
        <thead>
            <tr>
                <?= thSort('id', t('common.id'), $sort, $dir, '/ai', [], 'col-id') ?>
                <?= thSort('full_name', t('common.name'), $sort, $dir, '/ai') ?>
                <?= thSort('email', t('common.email'), $sort, $dir, '/ai') ?>
                <?= thSort('domain', t('ai.domain'), $sort, $dir, '/ai') ?>
                <th class="col-ai-company"><?= t('contacts.company') ?></th>
                <th class="col-actions"><?= t('common.actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($contacts as $contact): ?>
                <tr>
                    <td class="col-id"><?= (int) $contact['id'] ?></td>
                    <td class="col-name">
                        <a class="col-row-link" target="_blank" href="<?= htmlspecialchars(Auth::url('/contacts/show?id=' . (int) $contact['id']), ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($contact['full_name'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </td>
                    <td><?= htmlspecialchars($contact['email'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <a class="ai-domain-pill" target="_blank" href="<?= 'https://' . htmlspecialchars($contact['domain'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?= htmlspecialchars($contact['domain'], ENT_QUOTES, 'UTF-8') ?>
                            <?php if ((int) $contact['domain_contacts'] > 1): ?>
                                <span class="ai-domain-count">&times;<?= (int) $contact['domain_contacts'] ?></span>
                            <?php endif; ?>
                        </a>
                    </td>
                    <td class="col-ai-company">
                        <input type="text"
                            class="ai-company-input"
                            data-contact-id="<?= (int) $contact['id'] ?>"
                            data-domain="<?= htmlspecialchars($contact['domain'], ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="<?= t('ai.company_placeholder') ?>"
                            autocomplete="off"
                            spellcheck="false">
                    </td>
                    <td class="col-actions">
                        <div class="action-links">
                            <button type="button"
                                class="action-btn action-ai ai-process-btn"
                                data-contact-id="<?= (int) $contact['id'] ?>"
                                data-domain="<?= htmlspecialchars($contact['domain'], ENT_QUOTES, 'UTF-8') ?>">
                                <i class="ph ph-sparkle"></i>
                                <span class="tooltip-text"><?= t('ai.process') ?></span>
                            </button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php renderPagination($page, $totalPages, $total, $perPage, '/ai', ['sort' => $sort, 'dir' => $dir]); ?>
