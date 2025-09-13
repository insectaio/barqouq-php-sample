<?php
namespace App\Core;

class View {
    private static function viewsBase(): string {
        // src/Core -> project root is two levels up
        return dirname(__DIR__,2) . '/app/views';
    }

    public static function render(string $template, array $vars = [], ?string $layout = 'layout'): void {
        $base = self::viewsBase();
        $templatePath = $base . '/' . ltrim($template, '/');
        if (!str_ends_with($templatePath, '.php')) {
            $templatePath .= '.php';
        }
        extract($vars, EXTR_SKIP);
        ob_start();
        if (is_file($templatePath)) {
            include $templatePath;
        } else {
            echo 'Template not found: ' . htmlspecialchars($templatePath);
        }
        $content = ob_get_clean();
        if ($layout) {
            $layoutFile = $base . '/' . $layout . '.php';
            if (is_file($layoutFile)) {
                include $layoutFile;
                return;
            }
        }
        echo $content;
    }
}
