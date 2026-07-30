<?php

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

class View
{
    private static ?Environment $twig = null;

    public static function render(string $view, array $data = [], string $layout = 'main'): void
    {
        $viewsPath = dirname(__DIR__) . '/Views';
        $twigView = $view . '.twig';
        $phpView = $viewsPath . '/' . $view . '.php';

        if (file_exists($viewsPath . '/' . $twigView)) {
            $content = self::twig()->render($twigView, self::context($data));
        } elseif (file_exists($phpView)) {
            $content = self::renderPhp($phpView, $data);
        } else {
            http_response_code(500);
            echo 'View not found';
            return;
        }

        $twigLayout = 'layouts/' . $layout . '.twig';
        if (file_exists($viewsPath . '/' . $twigLayout)) {
            echo self::twig()->render($twigLayout, self::context($data + ['content' => $content]));
            return;
        }

        echo $content;
    }

    private static function twig(): Environment
    {
        if (self::$twig !== null) {
            return self::$twig;
        }

        self::$twig = new Environment(
            new FilesystemLoader(dirname(__DIR__) . '/Views'),
            ['autoescape' => 'html', 'strict_variables' => true]
        );
        self::$twig->addFunction(new TwigFunction('t', [Lang::class, 'get']));
        self::$twig->addFunction(new TwigFunction('url', [Auth::class, 'url']));
        self::$twig->addFunction(new TwigFunction('can', [Auth::class, 'can']));
        self::$twig->addFunction(new TwigFunction('is_admin', [Auth::class, 'isAdmin']));
        self::$twig->addFunction(new TwigFunction('csrf_field', [Csrf::class, 'field'], ['is_safe' => ['html']]));
        self::$twig->addFilter(new TwigFilter(
            'json_for_script',
            static fn (mixed $value): string => json_encode($value, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) ?: '{}',
            ['is_safe' => ['html']]
        ));

        return self::$twig;
    }

    private static function context(array $data): array
    {
        return $data + [
            'locale' => Lang::locale(),
            'auth_user' => Auth::check() ? Auth::user() : null,
            'request_path' => strtok($_SERVER['REQUEST_URI'] ?? '/', '?') ?: '/',
        ];
    }

    private static function renderPhp(string $file, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();

        try {
            require $file;
            return (string) ob_get_clean();
        } catch (Throwable $exception) {
            ob_end_clean();
            throw $exception;
        }
    }
}
