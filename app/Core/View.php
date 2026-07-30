<?php

class View
{
    public static function render(string $view, array $data = [], string $layout = 'main'): void
    {
        $viewsPath = dirname(__DIR__) . '/Views';
        $viewFile = $viewsPath . '/' . $view . '.php';
        $layoutFile = $viewsPath . '/layouts/' . $layout . '.php';

        if (!file_exists($viewFile)) {
            http_response_code(500);
            echo 'View not found';
            return;
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        if (file_exists($layoutFile)) {
            require $layoutFile;
            return;
        }

        echo $content;
    }
}
