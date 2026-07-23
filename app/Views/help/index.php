<?php
$isTechnical = !empty($page['is_technical']);
$technicalId = $page['technical_id'] ?? null;
$technicalParent = null;
foreach ($navigation as $navigationItem) {
    if ($navigationItem['id'] === 'technical') {
        $technicalParent = $navigationItem;
        break;
    }
}
$firstTechnicalId = $technicalParent['children'][0]['id'] ?? 'server';
$topicUrl = static function (string $id) use ($firstTechnicalId): string {
    if ($id === 'start') {
        return Auth::url('/help');
    }
    if ($id === 'technical') {
        return Auth::url('/help/technical/' . $firstTechnicalId);
    }
    return Auth::url('/help/' . $id);
};

$previous = !$isTechnical && $activeIndex > 0 ? $navigation[$activeIndex - 1] : null;
$next = !$isTechnical && $activeIndex < count($navigation) - 1 ? $navigation[$activeIndex + 1] : null;
?>

<div class="help-center" data-help-center>
    <header class="page-header settings-header help-page-header">
        <div>
            <h1><?= htmlspecialchars($copy['center_title'], ENT_QUOTES, 'UTF-8') ?></h1>
            <span class="count-label"><?= htmlspecialchars($copy['center_intro'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>

        <label class="help-page-search">
            <i class="ph ph-magnifying-glass" aria-hidden="true"></i>
            <span class="sr-only"><?= htmlspecialchars($copy['search_placeholder'], ENT_QUOTES, 'UTF-8') ?></span>
            <input
                type="search"
                placeholder="<?= htmlspecialchars($copy['search_placeholder'], ENT_QUOTES, 'UTF-8') ?>"
                autocomplete="off"
                data-help-search
            >
        </label>
    </header>

    <button
        class="help-mobile-trigger"
        type="button"
        aria-expanded="false"
        aria-controls="helpSectionNavigation"
        data-help-mobile-trigger
    >
        <span>
            <i class="ph <?= htmlspecialchars($page['icon'], ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i>
            <?= htmlspecialchars($page['title'], ENT_QUOTES, 'UTF-8') ?>
        </span>
        <i class="ph ph-caret-down" aria-hidden="true"></i>
    </button>

    <div class="help-center-layout">
        <aside class="help-section-navigation" id="helpSectionNavigation" data-help-navigation>
            <div class="help-section-navigation-head">
                <span><?= htmlspecialchars($copy['navigation_label'], ENT_QUOTES, 'UTF-8') ?></span>
                <button type="button" data-help-mobile-close aria-label="<?= htmlspecialchars($copy['close_navigation'], ENT_QUOTES, 'UTF-8') ?>">
                    <i class="ph ph-x" aria-hidden="true"></i>
                </button>
            </div>

            <nav aria-label="<?= htmlspecialchars($copy['navigation_label'], ENT_QUOTES, 'UTF-8') ?>">
                <?php foreach ($navigation as $item):
                    $isActive = $item['id'] === $page['id'];
                    $children = $item['children'] ?? [];
                ?>
                    <?php if ($children !== []): ?>
                        <?php
                        $childSearchText = implode(' ', array_map(
                            static fn (array $child): string => ($child['title'] ?? '') . ' ' . ($child['description'] ?? ''),
                            $children
                        ));
                        ?>
                        <div
                            class="help-section-group<?= $isActive ? ' is-active' : '' ?>"
                            data-help-technical-group
                            data-help-search-item
                            data-search-text="<?= htmlspecialchars($item['title'] . ' ' . $item['description'] . ' ' . $childSearchText, ENT_QUOTES, 'UTF-8') ?>"
                        >
                            <button
                                type="button"
                                class="help-section-link help-section-toggle<?= $isActive ? ' is-active' : '' ?>"
                                aria-expanded="<?= $isActive ? 'true' : 'false' ?>"
                                data-help-technical-toggle
                            >
                                <span class="help-section-link-icon">
                                    <i class="ph <?= htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i>
                                </span>
                                <span class="help-section-link-copy">
                                    <strong><?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <small><?= htmlspecialchars($item['description'], ENT_QUOTES, 'UTF-8') ?></small>
                                </span>
                                <i class="ph ph-caret-down help-section-toggle-caret" aria-hidden="true"></i>
                            </button>
                            <div class="help-section-submenu" data-help-technical-menu <?= $isActive ? '' : 'hidden' ?>>
                                <?php foreach ($children as $child):
                                    $isChildActive = $isTechnical && $technicalId === $child['id'];
                                ?>
                                    <a
                                        href="<?= htmlspecialchars(Auth::url('/help/technical/' . $child['id']), ENT_QUOTES, 'UTF-8') ?>"
                                        class="help-subsection-link<?= $isChildActive ? ' is-active' : '' ?>"
                                        data-help-section-link
                                        <?= $isChildActive ? 'aria-current="page"' : '' ?>
                                    >
                                        <i class="ph <?= htmlspecialchars($child['icon'], ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i>
                                        <span><?= htmlspecialchars($child['title'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <a
                            href="<?= htmlspecialchars($topicUrl($item['id']), ENT_QUOTES, 'UTF-8') ?>"
                            class="help-section-link<?= $isActive ? ' is-active' : '' ?>"
                            data-help-section-link
                            data-help-search-item
                            data-search-text="<?= htmlspecialchars($item['title'] . ' ' . $item['description'], ENT_QUOTES, 'UTF-8') ?>"
                            <?= $isActive ? 'aria-current="page"' : '' ?>
                        >
                            <span class="help-section-link-icon">
                                <i class="ph <?= htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i>
                            </span>
                            <span class="help-section-link-copy">
                                <strong><?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <small><?= htmlspecialchars($item['description'], ENT_QUOTES, 'UTF-8') ?></small>
                            </span>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>

            <p class="help-search-empty" data-help-search-empty hidden>
                <?= htmlspecialchars($copy['search_empty'], ENT_QUOTES, 'UTF-8') ?>
            </p>

            <div class="help-section-navigation-foot">
                <i class="ph ph-book-open-text" aria-hidden="true"></i>
                <span><?= htmlspecialchars($copy['updated_label'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </aside>

        <div class="help-mobile-backdrop" data-help-mobile-backdrop></div>

        <main class="help-article">
            <article>
                <header class="help-article-header">
                    <div class="help-article-context">
                        <span><?= htmlspecialchars($isTechnical ? $copy['technical_label'] : $copy['article_label'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span aria-hidden="true">/</span>
                        <span><?= htmlspecialchars($page['title'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <h1><?= htmlspecialchars($page['title'], ENT_QUOTES, 'UTF-8') ?></h1>
                    <?php if (($page['description'] ?? '') !== ''): ?>
                        <p><?= htmlspecialchars($page['description'], ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                </header>

                <div class="help-article-body">
                    <?php foreach ($page['sections'] as $section): ?>
                        <section id="<?= htmlspecialchars($section['id'], ENT_QUOTES, 'UTF-8') ?>" data-help-article-section>
                            <h2><?= htmlspecialchars($section['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                            <?php if (!empty($section['paragraphs'])): ?>
                                <?php foreach ($section['paragraphs'] as $paragraph): ?>
                                    <p><?= htmlspecialchars($paragraph, ENT_QUOTES, 'UTF-8') ?></p>
                                <?php endforeach; ?>
                            <?php elseif (($section['description'] ?? '') !== ''): ?>
                                <p><?= htmlspecialchars($section['description'], ENT_QUOTES, 'UTF-8') ?></p>
                            <?php endif; ?>
                            <?php foreach (($section['examples'] ?? []) as $example): ?>
                                <div class="help-code-example">
                                    <div class="help-code-example-title">
                                        <i class="ph ph-code" aria-hidden="true"></i>
                                        <span><?= htmlspecialchars($example['title'] ?? 'Пример', ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <pre><code><?= htmlspecialchars($example['code'] ?? '', ENT_QUOTES, 'UTF-8') ?></code></pre>
                                </div>
                            <?php endforeach; ?>
                        </section>
                    <?php endforeach; ?>
                </div>
            </article>

            <?php if ($previous !== null || $next !== null): ?>
            <nav class="help-article-pagination" aria-label="<?= htmlspecialchars($copy['navigation_label'], ENT_QUOTES, 'UTF-8') ?>">
                <?php if ($previous): ?>
                    <a href="<?= htmlspecialchars($topicUrl($previous['id']), ENT_QUOTES, 'UTF-8') ?>" class="help-pagination-link help-pagination-link--previous">
                        <i class="ph ph-arrow-left" aria-hidden="true"></i>
                        <span>
                            <small><?= htmlspecialchars($copy['previous_label'], ENT_QUOTES, 'UTF-8') ?></small>
                            <strong><?= htmlspecialchars($previous['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                        </span>
                    </a>
                <?php else: ?>
                    <span></span>
                <?php endif; ?>

                <?php if ($next): ?>
                    <a href="<?= htmlspecialchars($topicUrl($next['id']), ENT_QUOTES, 'UTF-8') ?>" class="help-pagination-link help-pagination-link--next">
                        <span>
                            <small><?= htmlspecialchars($copy['next_label'], ENT_QUOTES, 'UTF-8') ?></small>
                            <strong><?= htmlspecialchars($next['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                        </span>
                        <i class="ph ph-arrow-right" aria-hidden="true"></i>
                    </a>
                <?php endif; ?>
            </nav>
            <?php endif; ?>
        </main>

    </div>
</div>
