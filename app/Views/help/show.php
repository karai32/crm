<?php
$icons = [
    'map'      => 'ph ph-map-trifold',
    'person'   => 'ph ph-user',
    'building' => 'ph ph-buildings',
    'tag'      => 'ph ph-tag',
    'sliders'  => 'ph ph-sliders-horizontal',
    'upload'   => 'ph ph-upload-simple',
    'download' => 'ph ph-download-simple',
    'search'   => 'ph ph-magnifying-glass',
    'users'    => 'ph ph-users',
    'key'      => 'ph ph-key',
    'code'     => 'ph ph-code',
];
$iconClass = $icons[$section['icon']] ?? '';
?>

<?php require __DIR__ . '/_topic-header.php'; ?>

<div class="help-card help-layout">
    <div class="help-card-head">
        <div class="help-card-icon help-card-icon--slate">
            <?php if ($iconClass): ?>
            <i class="<?= htmlspecialchars($iconClass, ENT_QUOTES, 'UTF-8') ?>"></i>
            <?php endif; ?>
        </div>
        <div class="help-card-titles">
            <h2><?= htmlspecialchars($section['title'], ENT_QUOTES, 'UTF-8') ?></h2>
            <p class="help-card-summary"><?= htmlspecialchars($section['summary'], ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    </div>

    <div class="help-card-body">
        <ul class="help-list">
            <?php foreach ($section['items'] as $item): ?>
            <li class="help-list-item">
                <span class="help-check help-check--slate">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                    </svg>
                </span>
                <span><?= $item ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
