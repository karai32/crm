<?php
$avatarClasses = ['avatar-c1', 'avatar-c2', 'avatar-c3', 'avatar-c4', 'avatar-c5', 'avatar-c6', 'avatar-c7', 'avatar-c8'];

$maxSectorCount = 1;
if (!empty($clientsBySector)) {
    $maxSectorCount = max(array_column($clientsBySector, 'clients_count')) ?: 1;
}

function fmtDate(string $date): string
{
    $ts = strtotime($date);
    return $ts ? date('M j, Y', $ts) : $date;
}
?>

<div class="dashboard-header">
    <h1><?= t('dashboard.title') ?></h1>
    <p><?= t('dashboard.subtitle') ?></p>
</div>

<!-- -- Stat Cards --------------------------------------- -->
<div class="stat-cards">

    <a class="stat-card" href="<?= htmlspecialchars(Auth::url('/contacts'), ENT_QUOTES, 'UTF-8') ?>">
        <div class="stat-card-header">
            <div class="stat-card-icon"><i class="ph ph-user"></i></div>
            <span class="stat-card-label"><?= t('dashboard.total_contacts') ?></span>
        </div>
        <div class="stat-card-number"><?= number_format((int) $stats['contacts'], 0, '', '.') ?></div>
        <div class="stat-card-caption"><?= t('dashboard.registered') ?></div>
    </a>

    <a class="stat-card" href="<?= htmlspecialchars(Auth::url('/clients'), ENT_QUOTES, 'UTF-8') ?>">
        <div class="stat-card-header">
            <div class="stat-card-icon"><i class="ph ph-buildings"></i></div>
            <span class="stat-card-label"><?= t('dashboard.total_clients') ?></span>
        </div>
        <div class="stat-card-number"><?= number_format((int) $stats['clients'], 0, '', '.') ?></div>
        <div class="stat-card-caption"><?= t('dashboard.active_in_crm') ?></div>
    </a>

    <a class="stat-card" href="<?= htmlspecialchars(Auth::url('/sectors'), ENT_QUOTES, 'UTF-8') ?>">
        <div class="stat-card-header">
            <div class="stat-card-icon"><i class="ph ph-crosshair"></i></div>
            <span class="stat-card-label"><?= t('dashboard.total_sectors') ?></span>
        </div>
        <div class="stat-card-number"><?= number_format((int) $stats['sectors'], 0, '', '.') ?></div>
        <div class="stat-card-caption"><?= t('dashboard.available_cats') ?></div>
    </a>

    <a class="stat-card" href="<?= htmlspecialchars(Auth::url('/tags'), ENT_QUOTES, 'UTF-8') ?>">
        <div class="stat-card-header">
            <div class="stat-card-icon"><i class="ph ph-tag"></i></div>
            <span class="stat-card-label"><?= t('dashboard.total_tags') ?></span>
        </div>
        <div class="stat-card-number"><?= number_format((int) $stats['tags'], 0, '', '.') ?></div>
        <div class="stat-card-caption"><?= t('dashboard.available_labels') ?></div>
    </a>

</div>

<!-- -- Dashboard Grid ----------------------------------- -->
<div class="dashboard-grid">

    <!-- Recent Contacts -->
    <div class="card">
        <div class="card-header">
            <h2><?= t('dashboard.recent_contacts') ?></h2>
            <a class="btn btn-primary btn-sm" href="<?= htmlspecialchars(Auth::url('/contacts'), ENT_QUOTES, 'UTF-8') ?>"><?= t('dashboard.view_all') ?></a>
        </div>

        <?php if (empty($latestContacts)): ?>
            <div class="empty-state"><?= t('dashboard.no_contacts') ?></div>
        <?php else: ?>
            <div class="recent-table-wrap">
                <table class="data-table recent-table">
                    <thead>
                        <tr>
                            <th><?= t('common.name') ?></th>
                            <th><?= t('common.email') ?></th>
                            <th><?= t('dashboard.company') ?></th>
                            <th><?= t('dashboard.clients') ?></th>
                            <th><?= t('common.created') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($latestContacts as $contact): ?>
                            <tr>
                                <td>
                                    <a class="contact-name-link"
                                        href="<?= htmlspecialchars(Auth::url('/contacts/show?id=' . $contact['id']), ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($contact['full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($contact['email'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="company-indicator">
                                        <span class="company-indicator-dot <?= ($contact['company'] ?? '') !== '' ? 'yes' : 'no' ?>"></span>
                                        <?= ($contact['company'] ?? '') !== '' ? htmlspecialchars($contact['company'], ENT_QUOTES, 'UTF-8') : t('dashboard.no_company') ?>
                                    </span>
                                </td>
                                <td class="col-clients">
                                    <?php
                                    $cList  = $latestContactClients[(int) $contact['id']] ?? [];
                                    $cCount = count($cList);
                                    ?>
                                    <?php if ($cCount > 0): ?>
                                        <span class="col-client-main">
                                            <?php if (!empty($cList[0]['sector_icon'])): ?>
                                                <span class="sector-list-icon">
                                                    <i class="ph ph-<?= htmlspecialchars($cList[0]['sector_icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                                                    <span class="tooltip-text"><?= htmlspecialchars($cList[0]['sector_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                                </span>
                                            <?php endif; ?>

                                            <a class="col-client-link" href="<?= htmlspecialchars(Auth::url('/clients/show?id=' . (int) $cList[0]['id']), ENT_QUOTES, 'UTF-8') ?>">
                                                <?= htmlspecialchars($cList[0]['commercial_name'], ENT_QUOTES, 'UTF-8') ?>
                                            </a>
                                        </span>

                                        <?php if ($cCount > 1):
                                            $moreUrl = Auth::url('/clients?' . http_build_query(['contact_name' => $contact['full_name']]));
                                        ?>
                                            <a class="col-clients-more" href="<?= htmlspecialchars($moreUrl, ENT_QUOTES, 'UTF-8') ?>">+<?= $cCount - 1 ?></a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="created-date">
                                        <?= htmlspecialchars(!empty($contact['created_at']) ? fmtDate($contact['created_at']) : '-', ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Right column -->
    <div class="dashboard-right">

        <!-- Sector Distribution -->
        <div class="card">
            <div class="card-header">
                <h2><?= t('dashboard.sector_dist') ?></h2>
            </div>

            <?php if (empty($clientsBySector)): ?>
                <div class="sector-empty"><?= t('dashboard.no_sector_data') ?></div>
            <?php else: ?>
                <div class="sector-rows">
                    <?php foreach (array_slice($clientsBySector, 0, 8) as $row): ?>
                        <?php $pct = round(($row['clients_count'] / $maxSectorCount) * 100); ?>
                        <div class="sector-row">
                            <span class="sector-row-name"><?= htmlspecialchars($row['sector_name'], ENT_QUOTES, 'UTF-8') ?></span>
                            <div class="sector-row-bar-wrap">
                                <div class="sector-row-bar">
                                    <div class="sector-row-bar-fill" style="width:<?= $pct ?>%"></div>
                                </div>
                            </div>
                            <span class="sector-row-count"><?= (int) $row['clients_count'] ?> <?= t('dashboard.clients_label') ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Top Clients -->
        <div class="card">
            <div class="card-header">
                <h2><?= t('dashboard.top_clients') ?></h2>
            </div>

            <?php if (empty($topClients)): ?>
                <div class="empty-state"><?= t('dashboard.no_clients') ?></div>
            <?php else: ?>
                <?php foreach (array_slice($topClients, 0, 5) as $i => $client): ?>
                    <?php
                    $initials = strtoupper(substr($client['commercial_name'], 0, 2));
                    $colorClass = $avatarClasses[$i % count($avatarClasses)];
                    ?>
                    <a class="top-client-item"
                        href="<?= htmlspecialchars(Auth::url('/clients/show?id=' . $client['id']), ENT_QUOTES, 'UTF-8') ?>">
                        <div class="client-avatar <?= $colorClass ?>">
                            <?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <div class="client-meta">
                            <div class="client-meta-name">
                                <?= htmlspecialchars($client['commercial_name'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <?php if (!empty($client['sector_name'])): ?>
                                <div class="client-meta-sector">
                                    <?= htmlspecialchars($client['sector_name'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="client-count">
                            <span class="client-count-num"><?= (int) $client['contacts_count'] ?></span>
                            <span class="client-count-label"><?= t('dashboard.contacts_label') ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>

                <div class="card-footer">
                    <a class="btn btn-primary btn-sm" href="<?= htmlspecialchars(Auth::url('/clients'), ENT_QUOTES, 'UTF-8') ?>"><?= t('dashboard.view_directory') ?></a>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>