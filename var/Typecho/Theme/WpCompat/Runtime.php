<?php

namespace Typecho\Theme\WpCompat;

use Typecho\Common;
use Typecho\Theme\ViewContext;
use Widget\Archive;
use Widget\Options;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class Runtime
{
    private static ?ViewContext $view = null;

    private static ?Options $options = null;

    private static string $theme = '';

    private static string $themeDirectory = '';

    private static string $themeUrl = '';

    private static bool $enqueueBooted = false;

    private static array $actions = [];

    private static array $filters = [];

    private static array $styles = [];

    private static array $scripts = [];

    public static function preload(string $theme, Options $options): void
    {
        self::$options = $options;
        self::$theme = trim($theme, './');
        self::$themeDirectory = rtrim($options->themeFile(self::$theme), '/');
        self::$themeUrl = rtrim((string) $options->themeUrl(null, self::$theme), '/');

        require_once __DIR__ . '/functions.php';
    }

    public static function boot(ViewContext $view): void
    {
        self::$view = $view;
        self::$options = $view->site();
        self::$theme = $view->theme()->theme();
        self::$themeDirectory = $view->theme()->directory();
        self::$themeUrl = rtrim((string) $view->site()->themeUrl(null, $view->theme()->theme()), '/');
        require_once __DIR__ . '/functions.php';
    }

    public static function clear(): void
    {
        self::$view = null;
        self::$enqueueBooted = false;
        self::$styles = [];
        self::$scripts = [];
    }

    public static function view(): ViewContext
    {
        if (!self::$view) {
            throw new \RuntimeException('WordPress compatibility runtime is not booted');
        }

        return self::$view;
    }

    public static function hasView(): bool
    {
        return self::$view !== null;
    }

    public static function siteOption(string $key, mixed $default = ''): mixed
    {
        if (self::$view) {
            return self::$view->site()->{$key} ?? $default;
        }

        return self::$options ? (self::$options->{$key} ?? $default) : $default;
    }

    public static function archive(): Archive
    {
        return self::view()->archive();
    }

    public static function havePosts(): bool
    {
        $archive = self::archive();
        return $archive->have() && (int) $archive->sequence < (int) $archive->length;
    }

    public static function thePost(): bool
    {
        return false !== self::archive()->next();
    }

    public static function renderHeader(?string $name = null, array $args = []): void
    {
        self::renderNamedTemplate('header', $name, $args);
    }

    public static function renderFooter(?string $name = null, array $args = []): void
    {
        self::renderNamedTemplate('footer', $name, $args);
    }

    public static function renderSidebar(?string $name = null, array $args = []): void
    {
        self::renderNamedTemplate('sidebar', $name, $args);
    }

    public static function renderTemplatePart(string $slug, ?string $name = null, array $args = []): void
    {
        $templates = [];
        if ($name !== null && $name !== '') {
            $templates[] = $slug . '-' . $name . '.php';
        }
        $templates[] = $slug . '.php';

        $file = self::locateTemplate($templates);
        if ($file !== '') {
            echo self::view()->renderFile(self::relativePath($file), $args);
        }
    }

    public static function locateTemplate(string|array $templates, bool $load = false, array $args = []): string
    {
        foreach ((array) $templates as $template) {
            $template = trim(str_replace('\\', '/', (string) $template));
            if ($template === '') {
                continue;
            }

            if (!str_ends_with($template, '.php')) {
                $template .= '.php';
            }

            if (self::view()->theme()->hasFile($template)) {
                $path = self::view()->theme()->path($template);
                if ($load) {
                    echo self::view()->renderFile($template, $args);
                }

                return $path;
            }
        }

        return '';
    }

    public static function head(): string
    {
        self::bootEnqueue();
        self::doAction('wp_head');

        return implode("\n", self::$styles)
            . (self::$styles ? "\n" : '')
            . self::view()->head();
    }

    public static function footer(): string
    {
        self::doAction('wp_footer');

        return implode("\n", self::$scripts)
            . (self::$scripts ? "\n" : '')
            . self::view()->footer();
    }

    public static function enqueueStyle(
        string $handle,
        string $src = '',
        array $deps = [],
        string|bool|null $version = false,
        string $media = 'all'
    ): void {
        if ($src === '') {
            return;
        }

        $url = self::assetUrl($src);
        if ($version) {
            $url .= (str_contains($url, '?') ? '&' : '?') . 'ver=' . rawurlencode((string) $version);
        }

        self::$styles[$handle] = '<link rel="stylesheet" id="' . self::escAttr($handle)
            . '-css" href="' . self::escUrl($url) . '" media="' . self::escAttr($media) . '">';
    }

    public static function enqueueScript(
        string $handle,
        string $src = '',
        array $deps = [],
        string|bool|null $version = false,
        bool $inFooter = false
    ): void {
        if ($src === '') {
            return;
        }

        $url = self::assetUrl($src);
        if ($version) {
            $url .= (str_contains($url, '?') ? '&' : '?') . 'ver=' . rawurlencode((string) $version);
        }

        $tag = '<script id="' . self::escAttr($handle) . '-js" src="' . self::escUrl($url) . '"></script>';
        if ($inFooter) {
            self::$scripts[$handle] = $tag;
        } else {
            self::$styles[$handle] = $tag;
        }
    }

    public static function addInlineStyle(string $handle, string $css): void
    {
        if ($css === '') {
            return;
        }

        self::$styles[$handle . '-inline'] = '<style id="' . self::escAttr($handle)
            . '-inline-css">' . self::escHtml($css) . '</style>';
    }

    public static function dequeueStyle(string $handle): void
    {
        unset(self::$styles[$handle], self::$styles[$handle . '-inline']);
    }

    public static function dequeueScript(string $handle): void
    {
        unset(self::$scripts[$handle], self::$styles[$handle]);
    }

    public static function addAction(string $hook, callable $callback, int $priority = 10): void
    {
        self::$actions[$hook][$priority][] = $callback;
        ksort(self::$actions[$hook], SORT_NUMERIC);
    }

    public static function doAction(string $hook, mixed ...$args): void
    {
        foreach (self::$actions[$hook] ?? [] as $callbacks) {
            foreach ($callbacks as $callback) {
                $callback(...$args);
            }
        }
    }

    public static function addFilter(string $hook, callable $callback, int $priority = 10): void
    {
        self::$filters[$hook][$priority][] = $callback;
        ksort(self::$filters[$hook], SORT_NUMERIC);
    }

    public static function applyFilters(string $hook, mixed $value, mixed ...$args): mixed
    {
        foreach (self::$filters[$hook] ?? [] as $callbacks) {
            foreach ($callbacks as $callback) {
                $value = $callback($value, ...$args);
            }
        }

        return $value;
    }

    public static function themeUrl(?string $path = null): string
    {
        if (!self::$view && self::$themeUrl !== '') {
            return $path === null || $path === ''
                ? self::$themeUrl
                : Common::url($path, self::$themeUrl);
        }

        return self::view()->site()->themeUrl($path, self::view()->theme()->theme());
    }

    public static function themePath(?string $path = null): string
    {
        if (!self::$view && self::$themeDirectory !== '') {
            return $path === null || $path === ''
                ? self::$themeDirectory
                : self::$themeDirectory . '/' . ltrim($path, '/');
        }

        $base = self::view()->theme()->directory();
        return $path === null ? $base : $base . '/' . ltrim($path, '/');
    }

    public static function siteUrl(?string $path = null): string
    {
        if (!self::$view && self::$options) {
            return Common::url($path, self::$options->siteUrl);
        }

        return self::view()->siteUrl($path);
    }

    public static function escHtml(mixed $value): string
    {
        if (!self::$view) {
            return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        return self::view()->e()->html($value);
    }

    public static function escAttr(mixed $value): string
    {
        if (!self::$view) {
            return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        return self::view()->e()->attr($value);
    }

    public static function escUrl(mixed $value): string
    {
        if (!self::$view) {
            return htmlspecialchars(Common::safeUrl((string) $value), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        return self::view()->e()->url($value);
    }

    private static function renderNamedTemplate(string $base, ?string $name = null, array $args = []): void
    {
        $templates = [];
        if ($name !== null && $name !== '') {
            $templates[] = $base . '-' . $name . '.php';
        }
        $templates[] = $base . '.php';

        $file = self::locateTemplate($templates);
        if ($file !== '') {
            echo self::view()->renderFile(self::relativePath($file), $args);
        }
    }

    private static function bootEnqueue(): void
    {
        if (self::$enqueueBooted) {
            return;
        }

        self::$enqueueBooted = true;
        self::doAction('wp_enqueue_scripts');
    }

    private static function assetUrl(string $src): string
    {
        if (preg_match('/^(?:https?:\/\/|\/\/|\/)/i', $src)) {
            return Common::safeUrl($src);
        }

        return self::themeUrl($src);
    }

    private static function relativePath(string $path): string
    {
        return str_replace('\\', '/', substr($path, strlen(self::view()->theme()->directory()) + 1));
    }
}
