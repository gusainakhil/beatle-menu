<?php

namespace App\Core;

class View {
    public static function render(string $view, array $data = [], string $layout = ''): void {
        extract($data);

        $viewFile = __DIR__ . "/../Views/{$view}.php";
        if (!file_exists($viewFile)) {
            throw new \Exception("View file [{$view}.php] not found.");
        }

        ob_start();
        include $viewFile;
        $content = ob_get_clean();

        if (empty($layout)) {
            echo $content;
            return;
        }

        $layoutFile = __DIR__ . "/../Views/layouts/{$layout}.php";
        if (!file_exists($layoutFile)) {
            throw new \Exception("Layout file [{$layout}.php] not found.");
        }

        include $layoutFile;
    }
}
