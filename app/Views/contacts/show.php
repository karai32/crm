<?php
if (!function_exists('contactStatusClass')) {
    function contactStatusClass(string $s): string {
        static $map = [
            'lead'      => 'status-lead',
            'client'    => 'status-client',
            'partner'   => 'status-partner',
            'prospect'  => 'status-prospect',
            'supplier'  => 'status-supplier',
            'pending'   => 'status-pending',
            'hot'       => 'status-hot',
            'cold'      => 'status-cold',
        ];
        return $map[strtolower(trim($s))] ?? 'status-default';
    }
}

$fullName     = $contact['full_name'] ?? '';
$nameParts    = explode(' ', $fullName, 2);
$heroInitials = strtoupper(substr($nameParts[0] ?? '', 0, 1) . substr($nameParts[1] ?? '', 0, 1));
$canEditContacts = Auth::can('contacts.edit');
?>

<!-- Hero -->
<div class="contact-hero">
    <div class="contact-hero-left">
        <div class="contact-hero-avatar"><?= htmlspecialchars($heroInitials, ENT_QUOTES, 'UTF-8') ?></div>
        <div>
            <div class="contact-hero-name"><?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?></div>
            <div class="contact-hero-meta">
                <?php if (!empty($contact['email'])): ?>
                <span class="contact-hero-meta-item">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/>
                    </svg>
                    <?= htmlspecialchars($contact['email'], ENT_QUOTES, 'UTF-8') ?>
                </span>
                <?php endif; ?>
                <?php if (!empty($contact['phone'])): ?>
                <span class="contact-hero-meta-item">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"/>
                    </svg>
                    <?= htmlspecialchars($contact['phone'], ENT_QUOTES, 'UTF-8') ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="contact-hero-actions">
        <a class="btn btn-outlined btn-sm" href="<?= htmlspecialchars(Auth::url('/contacts'), ENT_QUOTES, 'UTF-8') ?>">Back</a>
        <?php if ($canEditContacts): ?>
        <a class="btn btn-primary btn-sm" href="<?= htmlspecialchars(Auth::url('/contacts/edit?id=' . $contact['id']), ENT_QUOTES, 'UTF-8') ?>">Edit</a>
        <?php endif; ?>
    </div>
</div>

<!-- Two-column detail -->
<div class="contact-detail-layout">

    <!-- Left column: main info + custom fields -->
    <div class="contact-detail-main">

        <!-- Main info card -->
        <div class="card">
            <div class="card-header"><h2>Contact Information</h2></div>
            <table class="detail-table">
                <tbody>
                    <tr>
                        <td class="detail-th">ID</td>
                        <td class="detail-td"><?= (int) $contact['id'] ?></td>
                    </tr>
                    <tr>
                        <td class="detail-th">Full name</td>
                        <td class="detail-td"><?= htmlspecialchars($contact['full_name'], ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                    <tr>
                        <td class="detail-th">Email</td>
                        <td class="detail-td"><?= htmlspecialchars($contact['email'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                    <tr>
                        <td class="detail-th">Phone</td>
                        <td class="detail-td"><?= htmlspecialchars($contact['phone'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                    <tr>
                        <td class="detail-th">Company</td>
                        <td class="detail-td"><?= htmlspecialchars($contact['company'] !== '' ? $contact['company'] : '—', ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                    <tr>
                        <td class="detail-th">Created</td>
                        <td class="detail-td"><?= htmlspecialchars($contact['created_at'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Custom Fields -->
        <?php if (!empty($customFields)): ?>
        <div class="card">
            <div class="card-header"><h2>Custom Fields</h2></div>
            <table class="detail-table">
                <tbody>
                    <?php foreach ($customFields as $field): ?>
                    <?php
                        $fieldId = (int) $field['id'];
                        $value   = $customFieldRepository->displayValue($field, $customValues[$fieldId] ?? null);
                    ?>
                    <tr>
                        <td class="detail-th"><?= htmlspecialchars($field['name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="detail-td"><?= htmlspecialchars($value !== '' ? $value : '-', ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

    </div>

    <!-- Sidebar -->
    <div class="contact-detail-sidebar">

        <!-- Tags -->
        <div class="card">
            <div class="card-header"><h2>Tags</h2></div>
            <?php if (empty($tags)): ?>
                <div class="sidebar-empty">No tags assigned.</div>
            <?php else: ?>
                <div class="card-tags-body">
                    <?php foreach ($tags as $tag): ?>
                        <span class="tag-badge" style="<?= !empty($tag['color']) ? 'background:' . htmlspecialchars($tag['color'], ENT_QUOTES, 'UTF-8') . '22; border-color:' . htmlspecialchars($tag['color'], ENT_QUOTES, 'UTF-8') . '44; color:' . htmlspecialchars($tag['color'], ENT_QUOTES, 'UTF-8') : '' ?>">
                            <?php if (!empty($tag['color'])): ?>
                                <span class="tag-color-dot" style="background:<?= htmlspecialchars($tag['color'], ENT_QUOTES, 'UTF-8') ?>"></span>
                            <?php endif; ?>
                            <?= htmlspecialchars($tag['name'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Clients -->
        <div class="card">
            <div class="card-header"><h2>Linked Clients</h2></div>
            <?php if (empty($clients)): ?>
                <div class="sidebar-empty">No clients linked.</div>
            <?php else: ?>
                <div class="contact-sidebar-list">
                    <?php foreach ($clients as $client): ?>
                        <div class="contact-sidebar-item">
                            <i class="ph ph-buildings contact-sidebar-icon"></i>
                            <a href="<?= htmlspecialchars(Auth::url('/clients/show?id=' . $client['id']), ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($client['commercial_name'], ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>
