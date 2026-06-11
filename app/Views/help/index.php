<?php
$sections = $content['sections'] ?? [];
$availableLocales = $available_locales ?? ['es' => 'ES', 'en' => 'EN'];
?>

<div class="page-header help-header">
    <div>
        <h1><?= htmlspecialchars($content['title'] ?? '', ENT_QUOTES, 'UTF-8') ?></h1>
        <p><?= htmlspecialchars($content['intro'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
    </div>
</div>

<div class="help-toolbar">
    <div class="help-language">
        <span><?= htmlspecialchars($content['language_label'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
        <div class="help-language-switch">
            <?php foreach ($availableLocales as $localeCode => $localeLabel): ?>
                <a class="<?= $locale === $localeCode ? 'active' : '' ?>"
                   href="<?= htmlspecialchars(Auth::url('/help?lang=' . $localeCode), ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($localeLabel, ENT_QUOTES, 'UTF-8') ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="help-layout">
    <aside class="help-topics">
        <span class="help-topics-title"><?= htmlspecialchars($content['quick_topics_label'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
        <nav>
            <?php foreach ($sections as $section): ?>
                <a href="#<?= htmlspecialchars($section['id'], ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($section['title'], ENT_QUOTES, 'UTF-8') ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </aside>

    <div class="help-content">
        <?php foreach ($sections as $section): ?>
            <section class="help-section" id="<?= htmlspecialchars($section['id'], ENT_QUOTES, 'UTF-8') ?>">
                <h2><?= htmlspecialchars($section['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                <p><?= htmlspecialchars($section['summary'], ENT_QUOTES, 'UTF-8') ?></p>
                <ul>
                    <?php foreach ($section['items'] as $item): ?>
                        <li><?= htmlspecialchars($item, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endforeach; ?>
    </div>
</div>
