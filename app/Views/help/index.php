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
?>

<div class="help-hub-hero">
    <h1><?= htmlspecialchars($content['title'] ?? '', ENT_QUOTES, 'UTF-8') ?></h1>
    <p><?= htmlspecialchars($content['intro'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
</div>

<div class="help-hub-grid">
    <?php foreach ($cards as $card):
        $iconClass = $icons[$card['icon']] ?? '';
        $url = htmlspecialchars(Auth::url('/help/' . $card['id']), ENT_QUOTES, 'UTF-8');
    ?>
    <a href="<?= $url ?>" class="help-hub-card">
        <div class="help-hub-card-icon help-card-icon--slate">
            <?php if ($iconClass): ?>
            <i class="<?= htmlspecialchars($iconClass, ENT_QUOTES, 'UTF-8') ?>"></i>
            <?php endif; ?>
        </div>
        <div class="help-hub-card-body">
            <div class="help-hub-card-title"><?= htmlspecialchars($card['title'], ENT_QUOTES, 'UTF-8') ?></div>
            <p class="help-hub-card-summary"><?= htmlspecialchars($card['summary'], ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    </a>
    <?php endforeach; ?>
</div>
