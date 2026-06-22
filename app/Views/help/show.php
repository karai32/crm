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
$iconClass = $icons[$section['icon']] ?? '';
$backUrl   = htmlspecialchars(Auth::url('/help?lang=' . $locale), ENT_QUOTES, 'UTF-8');
$accent    = htmlspecialchars($section['accent'], ENT_QUOTES, 'UTF-8');
?>

<!-- Back button + lang switch -->
<div class="help-back-row">
    <a href="<?= $backUrl ?>" class="help-topic-back">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
        </svg>
        <?= $locale === 'es' ? 'Centro de ayuda' : 'Help Center' ?>
    </a>
    <div class="help-lang-switch">
        <span><?= htmlspecialchars($content['language_label'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
        <div class="help-lang-pills">
            <?php foreach ($availableLocales as $code => $label): ?>
            <a class="help-lang-pill <?= $locale === $code ? 'active' : '' ?>"
               href="<?= htmlspecialchars(Auth::url('/help/' . $section['id'] . '?lang=' . $code), ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Section card -->
<div class="help-card help-layout">
    <div class="help-card-head">
        <div class="help-card-icon help-card-icon--<?= $accent ?>">
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
                <span class="help-check help-check--<?= $accent ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                    </svg>
                </span>
                <span><?= $item ?></span>
            </li>
            <?php endforeach; ?>
        </ul>

        <?php if (!empty($section['tip'])): ?>
        <div class="help-tip help-tip--<?= $accent ?>">
            <span class="help-tip-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.517 0c.85.493 1.509 1.333 1.509 2.316V18"/>
                </svg>
            </span>
            <p><?= htmlspecialchars($section['tip'], ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <?php endif; ?>
    </div>
</div>
