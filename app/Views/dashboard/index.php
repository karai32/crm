<?php
$avatarClasses = ['avatar-c1','avatar-c2','avatar-c3','avatar-c4','avatar-c5','avatar-c6','avatar-c7','avatar-c8'];

$maxSectorCount = 1;
if (!empty($clientsBySector)) {
    $maxSectorCount = max(array_column($clientsBySector, 'clients_count')) ?: 1;
}

function fmtDate(string $date): string {
    $ts = strtotime($date);
    return $ts ? date('M j, Y', $ts) : $date;
}
?>

<div class="dashboard-header">
    <h1>Dashboard Overview</h1>
    <p>A summary of your CRM data and recent activity.</p>
</div>

<!-- -- Stat Cards --------------------------------------- -->
<div class="stat-cards">

    <a class="stat-card" href="<?= htmlspecialchars(Auth::url('/contacts'), ENT_QUOTES, 'UTF-8') ?>">
        <div class="stat-card-header">
            <div class="stat-card-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
                </svg>
            </div>
            <span class="stat-card-label">Total Contacts</span>
        </div>
        <div class="stat-card-number"><?= number_format((int) $stats['contacts']) ?></div>
        <div class="stat-card-caption">Registered in the system</div>
    </a>

    <a class="stat-card" href="<?= htmlspecialchars(Auth::url('/clients'), ENT_QUOTES, 'UTF-8') ?>">
        <div class="stat-card-header">
            <div class="stat-card-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z"/>
                </svg>
            </div>
            <span class="stat-card-label">Total Clients</span>
        </div>
        <div class="stat-card-number"><?= number_format((int) $stats['clients']) ?></div>
        <div class="stat-card-caption">Active in the CRM</div>
    </a>

    <a class="stat-card" href="<?= htmlspecialchars(Auth::url('/sectors'), ENT_QUOTES, 'UTF-8') ?>">
        <div class="stat-card-header">
            <div class="stat-card-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/>
                </svg>
            </div>
            <span class="stat-card-label">Total Sectors</span>
        </div>
        <div class="stat-card-number"><?= number_format((int) $stats['sectors']) ?></div>
        <div class="stat-card-caption">Available categories</div>
    </a>

    <a class="stat-card" href="<?= htmlspecialchars(Auth::url('/tags'), ENT_QUOTES, 'UTF-8') ?>">
        <div class="stat-card-header">
            <div class="stat-card-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L9.568 3ZM6.75 8.25a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3Z"/>
                </svg>
            </div>
            <span class="stat-card-label">Total Tags</span>
        </div>
        <div class="stat-card-number"><?= number_format((int) $stats['tags']) ?></div>
        <div class="stat-card-caption">Available labels</div>
    </a>

</div>

<!-- -- Dashboard Grid ----------------------------------- -->
<div class="dashboard-grid">

    <!-- Recent Contacts -->
    <div class="card">
        <div class="card-header">
            <h2>Recent Contacts</h2>
            <a class="btn btn-primary btn-sm" href="<?= htmlspecialchars(Auth::url('/contacts'), ENT_QUOTES, 'UTF-8') ?>">View All</a>
        </div>

        <?php if (empty($latestContacts)): ?>
            <div class="empty-state">No contacts yet.</div>
        <?php else: ?>
        <div class="recent-table-wrap">
        <table class="recent-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Company</th>
                    <th>Clients</th>
                    <th>Created</th>
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
                            <?= ($contact['company'] ?? '') !== '' ? htmlspecialchars($contact['company'], ENT_QUOTES, 'UTF-8') : 'No' ?>
                        </span>
                    </td>
                    <td class="col-clients">
                        <?php
                            $cList  = $latestContactClients[(int) $contact['id']] ?? [];
                            $cCount = count($cList);
                        ?>
                        <?php if ($cCount > 0): ?>
                            <a class="col-client-link" href="<?= htmlspecialchars(Auth::url('/clients/show?id=' . (int) $cList[0]['id']), ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($cList[0]['commercial_name'], ENT_QUOTES, 'UTF-8') ?>
                            </a>
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
                <h2>Sector Distribution</h2>
            </div>

            <?php if (empty($clientsBySector)): ?>
                <div class="sector-empty">No sector data yet.</div>
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
                    <span class="sector-row-count"><?= (int) $row['clients_count'] ?> clients</span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Top Clients -->
        <div class="card">
            <div class="card-header">
                <h2>Top Clients</h2>
            </div>

            <?php if (empty($topClients)): ?>
                <div class="empty-state">No clients yet.</div>
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
                        <span class="client-count-label">Contacts</span>
                    </div>
                </a>
                <?php endforeach; ?>

                <div class="card-footer">
                    <a class="btn btn-primary btn-sm" href="<?= htmlspecialchars(Auth::url('/clients'), ENT_QUOTES, 'UTF-8') ?>">View Client Directory</a>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>
