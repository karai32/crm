<?php
$sections         = $content['sections'] ?? [];
$availableLocales = $available_locales ?? ['es' => 'ES', 'en' => 'EN'];

// SVG icon paths keyed by icon name
$icons = [
    'map'      => 'M9 6.75V15m6-6v8.25m.503 3.498 4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 0 0-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0Z',
    'person'   => 'M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z',
    'building'  => 'M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z',
    'tag'      => 'M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L9.568 3ZM6.75 8.25a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3Z',
    'sliders'  => 'M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75',
    'upload'   => 'M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5',
    'download' => 'M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3',
    'search'   => 'm21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z',
    'users'    => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z',
    'shield'   => 'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z',
];
?>

<div class="help-hero">
    <div class="help-hero-text">
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

<div class="help-layout">

    <!-- Sticky sidebar TOC -->
    <aside class="help-toc">
        <span class="help-toc-label"><?= htmlspecialchars($content['quick_topics_label'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
        <nav class="help-toc-nav">
            <?php foreach ($sections as $section): ?>
            <?php $iconPath = $icons[$section['icon'] ?? ''] ?? ''; ?>
            <a href="#<?= htmlspecialchars($section['id'], ENT_QUOTES, 'UTF-8') ?>"
               class="help-toc-item help-toc-item--<?= htmlspecialchars($section['accent'] ?? 'blue', ENT_QUOTES, 'UTF-8') ?>"
               data-section="<?= htmlspecialchars($section['id'], ENT_QUOTES, 'UTF-8') ?>">
                <span class="help-toc-dot"></span>
                <?= htmlspecialchars($section['title'], ENT_QUOTES, 'UTF-8') ?>
            </a>
            <?php endforeach; ?>
        </nav>
    </aside>

    <!-- Main content -->
    <div class="help-content">
        <?php foreach ($sections as $section): ?>
        <?php
            $accent   = htmlspecialchars($section['accent'] ?? 'blue', ENT_QUOTES, 'UTF-8');
            $iconPath = $icons[$section['icon'] ?? ''] ?? '';
        ?>
        <section class="help-card" id="<?= htmlspecialchars($section['id'], ENT_QUOTES, 'UTF-8') ?>">

            <div class="help-card-head">
                <div class="help-card-icon help-card-icon--<?= $accent ?>">
                    <?php if ($iconPath): ?>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="<?= htmlspecialchars($iconPath, ENT_QUOTES, 'UTF-8') ?>"/>
                    </svg>
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
                        <span><?= $item /* already trusted content from controller, contains <strong>/<em> */ ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>

                <?php if (!empty($section['tip'])): ?>
                <div class="help-tip help-tip--<?= $accent ?>">
                    <span class="help-tip-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.517 0c.85.493 1.509 1.333 1.509 2.316V18"/>
                        </svg>
                    </span>
                    <p><?= htmlspecialchars($section['tip'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <?php endif; ?>
            </div>

        </section>
        <?php endforeach; ?>
    </div>

</div>

<script>
(function () {
    var items = document.querySelectorAll('.help-toc-item');
    if (!items.length || !window.IntersectionObserver) return;

    var active = null;

    function setActive(id) {
        if (active === id) return;
        active = id;
        items.forEach(function (a) {
            a.classList.toggle('is-active', a.dataset.section === id);
        });
    }

    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
            if (e.isIntersecting) setActive(e.target.id);
        });
    }, { rootMargin: '-20% 0px -70% 0px', threshold: 0 });

    document.querySelectorAll('.help-card').forEach(function (card) {
        observer.observe(card);
    });

    // Smooth scroll for TOC clicks
    items.forEach(function (a) {
        a.addEventListener('click', function (e) {
            var target = document.getElementById(a.dataset.section);
            if (!target) return;
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });
}());
</script>
