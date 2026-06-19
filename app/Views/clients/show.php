<?php $canEditClients = Auth::can('clients.edit'); ?>

<!-- Hero -->
<div class="client-hero">
    <div class="client-hero-left">
        <div class="client-hero-avatar">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="btn-icon-md">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/>
            </svg>
        </div>
        <div>
            <div class="client-hero-name"><?= htmlspecialchars($client['commercial_name'], ENT_QUOTES, 'UTF-8') ?></div>
            <div class="client-hero-meta">
                <?php if (!empty($client['sector_name'])): ?>
                <span class="client-hero-meta-item">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z"/>
                    </svg>
                    <?= htmlspecialchars($client['sector_name'], ENT_QUOTES, 'UTF-8') ?>
                </span>
                <?php endif; ?>
                <?php if (!empty($client['city']) || !empty($client['country'])): ?>
                <span class="client-hero-meta-item">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/>
                    </svg>
                    <?= htmlspecialchars(implode(', ', array_filter([$client['city'] ?? '', $client['country'] ?? ''])), ENT_QUOTES, 'UTF-8') ?>
                </span>
                <?php endif; ?>
                <?php if (!empty($client['website'])): ?>
                <span class="client-hero-meta-item">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5a17.92 17.92 0 0 1-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418"/>
                    </svg>
                    <a href="<?= htmlspecialchars($client['website'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                        <?= htmlspecialchars($client['website'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="client-hero-actions">
        <a class="btn btn-outlined" href="<?= htmlspecialchars(Auth::url('/clients'), ENT_QUOTES, 'UTF-8') ?>">Back</a>
        <?php if ($canEditClients): ?>
        <a class="btn btn-primary" href="<?= htmlspecialchars(Auth::url('/clients/edit?id=' . $client['id']), ENT_QUOTES, 'UTF-8') ?>">Edit</a>
        <?php endif; ?>
    </div>
</div>

<!-- Detail layout -->
<div class="client-detail-layout">

    <!-- Main info -->
    <div class="card card-flush">
        <table class="detail-table">
            <tbody>
                <tr>
                    <th class="detail-th">Commercial name</th>
                    <td class="detail-td"><?= htmlspecialchars($client['commercial_name'], ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <tr>
                    <th class="detail-th">Legal name</th>
                    <td class="detail-td"><?= htmlspecialchars($client['legal_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <tr>
                    <th class="detail-th">CIF</th>
                    <td class="detail-td"><?= htmlspecialchars($client['cif'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <tr>
                    <th class="detail-th">Sector</th>
                    <td class="detail-td"><?= htmlspecialchars($client['sector_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <tr>
                    <th class="detail-th">Address</th>
                    <td class="detail-td"><?= htmlspecialchars($client['address'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <tr>
                    <th class="detail-th">Postal code</th>
                    <td class="detail-td"><?= htmlspecialchars($client['postal_code'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <tr>
                    <th class="detail-th">City</th>
                    <td class="detail-td"><?= htmlspecialchars($client['city'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <tr>
                    <th class="detail-th">Province</th>
                    <td class="detail-td"><?= htmlspecialchars($client['province'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <tr>
                    <th class="detail-th">Country</th>
                    <td class="detail-td"><?= htmlspecialchars($client['country'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <tr>
                    <th class="detail-th">Website</th>
                    <td class="detail-td">
                        <?php if (!empty($client['website'])): ?>
                            <a class="client-website-link" href="<?= htmlspecialchars($client['website'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                                <?= htmlspecialchars($client['website'], ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        <?php else: ?>-<?php endif; ?>
                    </td>
                </tr>
                <?php if (!empty($client['notes'])): ?>
                <tr>
                    <th class="detail-th">Notes</th>
                    <td class="detail-td"><?= nl2br(htmlspecialchars($client['notes'], ENT_QUOTES, 'UTF-8')) ?></td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Sidebar: tags -->
    <div class="card card-flush">
        <div class="card-section-header">
            <span class="card-section-label">Tags</span>
        </div>
        <?php if (empty($tags)): ?>
            <div class="sidebar-empty">No tags assigned.</div>
        <?php else: ?>
            <div class="card-tags-body">
                <?php foreach ($tags as $tag): ?>
                    <?php $c = htmlspecialchars($tag['color'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                    <span class="tag-badge" <?= $c ? 'style="background:' . $c . '22;border-color:' . $c . '44;color:' . $c . '"' : '' ?>>
                        <?php if ($c): ?>
                            <span class="tag-color-dot" style="background:<?= $c ?>"></span>
                        <?php endif; ?>
                        <?= htmlspecialchars($tag['name'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar: custom fields -->
    <?php if (!empty($customFields)): ?>
    <div class="card card-flush">
        <div class="card-section-header">
            <span class="card-section-label">Custom fields</span>
        </div>
        <table class="detail-table">
            <tbody>
                <?php foreach ($customFields as $field): ?>
                    <?php
                        $fieldId = (int) $field['id'];
                        $value   = $customFieldRepository->displayValue($field, $customValues[$fieldId] ?? null);
                    ?>
                    <tr>
                        <th class="detail-th"><?= htmlspecialchars($field['name'], ENT_QUOTES, 'UTF-8') ?></th>
                        <td class="detail-td"><?= htmlspecialchars($value !== '' ? $value : '-', ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

</div>

<!-- Related contacts -->
<div class="card card-related-contacts">
    <div class="card-related-contacts-header">
        <span class="card-related-contacts-title">Related contacts</span>
        <span class="card-related-contacts-count"><?= count($contacts) ?> total</span>
    </div>

    <?php if (empty($contacts)): ?>
        <p class="card-no-contacts">No contacts linked to this client.</p>
    <?php else: ?>
        <table class="data-table related-contacts-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($contacts as $contact): ?>
                    <tr>
                        <td class="col-contact-name">
                            <?= htmlspecialchars($contact['full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td class="col-contact-muted"><?= htmlspecialchars($contact['email'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="col-contact-muted"><?= htmlspecialchars($contact['phone'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <div class="action-links">
                                <a class="action-btn action-view" title="View" href="<?= htmlspecialchars(Auth::url('/contacts/show?id=' . $contact['id']), ENT_QUOTES, 'UTF-8') ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.099 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.099-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
