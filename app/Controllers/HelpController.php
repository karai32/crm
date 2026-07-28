<?php

class HelpController
{
    private const TOPIC_ALIASES = [
        'getting-started' => 'start',
        'tags-sectors' => 'sectors-tags',
        'imports' => 'import-export',
        'exports' => 'import-export',
        'ai' => 'ai-tools',
        'users-roles' => 'users-settings',
        'technical-guide' => 'technical',
    ];

    public function index(): void
    {
        $this->renderTopic('start');
    }

    public function show(): void
    {
        $topic = trim((string) ($_GET['topic'] ?? ''), '/');
        $this->renderTopic(self::TOPIC_ALIASES[$topic] ?? $topic);
    }

    public function technicalPage(): void
    {
        $section = trim((string) ($_GET['section'] ?? ''), '/');
        $this->renderTechnicalPage($section);
    }

    private function renderTopic(string $topic): void
    {
        Auth::requireLogin();

        [$locale, $content] = $this->localizedContent();
        $navigation = $this->navigation($content);
        $activeIndex = array_search($topic, array_column($navigation, 'id'), true);

        if ($activeIndex === false) {
            Auth::redirect('/help');
            return;
        }

        if ($topic === 'technical') {
            $page = $content['technical']['meta'];
            $page['id'] = 'technical';
            $page['is_technical'] = true;
            $page['sections'] = [];
        } else {
            $page = $content['pages'][$topic] ?? null;
            if (!is_array($page)) {
                Auth::redirect('/help');
                return;
            }

            $page['id'] = $topic;
            $page['sections'] = $page['sections'] ?? [];
        }

        $this->render($locale, $content['ui'], $navigation, $activeIndex, $page);
    }

    private function renderTechnicalPage(string $section): void
    {
        Auth::requireLogin();

        [$locale, $content] = $this->localizedContent();
        $pages = $content['technical']['pages'];

        if (!isset($pages[$section])) {
            $firstSection = array_key_first($pages);
            Auth::redirect($firstSection === null ? '/help/technical' : '/help/technical/' . $firstSection);
            return;
        }

        $navigation = $this->navigation($content);
        $activeIndex = array_search('technical', array_column($navigation, 'id'), true);

        if ($activeIndex === false) {
            Auth::redirect('/help');
            return;
        }

        $page = $pages[$section];
        $page['id'] = 'technical';
        $page['technical_id'] = $section;
        $page['is_technical'] = true;
        $page['sections'] = $page['sections'] ?? [];

        $this->render($locale, $content['ui'], $navigation, $activeIndex, $page);
    }

    private function render(
        string $locale,
        array $copy,
        array $navigation,
        int $activeIndex,
        array $page
    ): void {
        View::render('help/index', [
            'title' => $page['title'] . ' — ' . $copy['center_title'],
            'styles' => ['help.css'],
            'scripts' => ['help.js'],
            'locale' => $locale,
            'copy' => $copy,
            'navigation' => $navigation,
            'activeIndex' => $activeIndex,
            'page' => $page,
        ]);
    }

    private function navigation(array $content): array
    {
        $navigation = [];

        foreach ($content['pages'] as $id => $page) {
            $navigation[] = $this->navigationItem($id, $page);
        }

        $technical = $this->navigationItem('technical', $content['technical']['meta']);
        $technical['children'] = [];

        foreach ($content['technical']['pages'] as $id => $page) {
            $technical['children'][] = $this->navigationItem($id, $page);
        }

        $navigation[] = $technical;

        return $navigation;
    }

    private function navigationItem(string $id, array $page): array
    {
        return [
            'id' => $id,
            'title' => (string) ($page['title'] ?? $id),
            'description' => (string) ($page['description'] ?? ''),
            'icon' => (string) ($page['icon'] ?? 'ph-file-text'),
        ];
    }

    private function localizedContent(): array
    {
        $locale = in_array(Lang::locale(), ['ru', 'es', 'en'], true)
            ? Lang::locale()
            : 'en';
        $path = dirname(__DIR__, 2) . '/lang/help/' . $locale . '/index.php';

        if (!is_file($path)) {
            $locale = 'en';
            $path = dirname(__DIR__, 2) . '/lang/help/en/index.php';
        }

        $content = require $path;

        if (
            !is_array($content)
            || !isset($content['ui'], $content['pages'], $content['technical'])
            || !is_array($content['ui'])
            || !is_array($content['pages'])
            || !is_array($content['technical'])
            || !isset($content['technical']['meta'], $content['technical']['pages'])
            || !is_array($content['technical']['meta'])
            || !is_array($content['technical']['pages'])
        ) {
            throw new RuntimeException('Invalid help content package for locale: ' . $locale);
        }

        return [$locale, $content];
    }
}
