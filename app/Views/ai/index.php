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

<div class="settings-table-card">
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
                <th><?= t('common.status') ?></th>
                <?= thSort('created_at', t('common.created'), $sort, $dir, '/ai') ?>
                <th class="col-actions"><?= t('common.actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($contacts as $contact): ?>
                <tr>
                    <td class="col-id"><?= (int) $contact['id'] ?></td>
                    <td class="col-name">
                        <a class="col-row-link" href="<?= htmlspecialchars(Auth::url('/contacts/show?id=' . (int) $contact['id']), ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($contact['full_name'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </td>
                    <td><?= htmlspecialchars($contact['email'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <span class="ai-domain-pill">
                            <?= htmlspecialchars($contact['domain'], ENT_QUOTES, 'UTF-8') ?>
                            <?php if ((int) $contact['domain_contacts'] > 1): ?>
                                <span class="ai-domain-count">&times;<?= (int) $contact['domain_contacts'] ?></span>
                            <?php endif; ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($contact['email_status'] === 'valid'): ?>
                            <span class="badge-active"><?= t('common.valid') ?></span>
                        <?php elseif ($contact['email_status'] === 'invalid'): ?>
                            <span class="badge-inactive"><?= t('common.invalid') ?></span>
                        <?php else: ?>
                            <span class="col-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars(date('d/m/Y', strtotime($contact['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="col-actions">
                        <div class="action-links">
                            <a class="action-btn action-edit" href="<?= htmlspecialchars(Auth::url('/contacts/edit?id=' . (int) $contact['id']), ENT_QUOTES, 'UTF-8') ?>">
                                <i class="ph ph-pencil"></i>
                                <span class="tooltip-text"><?= t('common.edit') ?></span>
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php renderPagination($page, $totalPages, $total, $perPage, '/ai', ['sort' => $sort, 'dir' => $dir]); ?>
