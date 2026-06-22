<?php
$icons = [
    'map'      => 'ph ph-airplane-tilt',
    'person'   => 'ph ph-user',
    'building' => 'ph ph-buildings',
    'tag'      => 'ph ph-tag',
    'sliders'  => 'ph ph-sliders-horizontal',
    'upload'   => 'ph ph-upload-simple',
    'download' => 'ph ph-download-simple',
    'search'   => 'ph ph-magnifying-glass',
    'users'    => 'ph ph-users',
    'shield'   => 'ph ph-shield-check',
    'key'      => 'ph ph-key',
];
?>

<div class="help-hub-hero">
    <div class="help-hub-hero-row">
        <div>
            <h1><?= htmlspecialchars($content['title'] ?? '', ENT_QUOTES, 'UTF-8') ?></h1>
            <p><?= htmlspecialchars($content['intro'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <div class="help-lang-switch">
            <span><?= htmlspecialchars($content['language_label'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
            <div class="help-lang-pills">
                <?php foreach ($availableLocales as $code => $label): ?>
                <a class="help-lang-pill <?= $locale === $code ? 'active' : '' ?>"
                   href="<?= htmlspecialchars(Auth::url('/help?lang=' . $code), ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div class="help-hub-grid">
    <?php foreach ($cards as $card):
        $iconClass = $icons[$card['icon']] ?? '';
        $url = htmlspecialchars(Auth::url('/help/' . $card['id'] . '?lang=' . $locale), ENT_QUOTES, 'UTF-8');
    ?>
    <a href="<?= $url ?>" class="help-hub-card">
        <div class="help-hub-card-icon help-card-icon--<?= htmlspecialchars($card['accent'], ENT_QUOTES, 'UTF-8') ?>">
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
