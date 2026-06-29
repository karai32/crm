<?php $canEditClients = Auth::can('clients.edit'); ?>

<!-- Hero -->
<div class="client-hero">
    <div class="client-hero-left">
        <div class="client-hero-avatar">
            <i class="ph ph-buildings"></i>
        </div>
        <div>
            <div class="client-hero-name">
                <?= htmlspecialchars($client['commercial_name'], ENT_QUOTES, 'UTF-8') ?>
                <?php if (!empty($client['is_active']) && $client['is_active'] === 1): ?>
                    <div class="client-active-status">
                        <i class="ph ph-check"></i>
                        <span class="tooltip-text">Active</span>
                    </div>
                <?php endif; ?>
            </div>
            <div class="client-hero-meta">
                <?php if (!empty($client['sector_name'])): ?>
                    <span class="client-hero-meta-item">
                        <i class="ph ph-tag"></i>
                        <?= htmlspecialchars($client['sector_name'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                <?php endif; ?>
                <?php if (!empty($client['website'])): ?>
                    <span class="client-hero-meta-item">
                        <i class="ph ph-globe"></i>
                        <a href="<?= htmlspecialchars($client['website'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                            <?= htmlspecialchars($client['website'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </span>
                <?php endif; ?>
                <?php if (!empty($client['is_web_connected']) && $client['is_web_connected'] === 1): ?>
                    <span class="client-hero-meta-item">
                        <i class="ph ph-plugs-connected"></i>
                        Connected to api
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
                                    <i class="ph ph-eye"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>