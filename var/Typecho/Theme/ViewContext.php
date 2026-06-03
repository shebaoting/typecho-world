<?php

namespace Typecho\Theme;

use Typecho\Common;
use Typecho\Plugin;
use Typecho\Theme\Event\ContentRendering;
use Typecho\Theme\WpCompat\Runtime as WpCompatRuntime;
use Widget\Archive;
use Widget\Base\Contents;
use Widget\Options;
use Widget\User;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class ViewContext
{
    private Escaper $escaper;

    private AssetManager $assets;

    private DataProvider $data;

    private DesignTokens $tokens;

    private ImageHelper $images;

    private FragmentCache $cache;

    private ?string $layoutFile = null;

    private array $layoutVars = [];

    private array $slots = [];

    private array $slotStack = [];

    private int $renderDepth = 0;

    public function __construct(private Archive $archive, private Manifest $manifest)
    {
        $this->escaper = new Escaper($this->site()->charset);
        $this->assets = new AssetManager($this->site(), $this->manifest, $this->escaper);
        $this->data = new DataProvider($this);
        $this->tokens = new DesignTokens($this->manifest->tokens(), $this->escaper);
        $this->images = new ImageHelper($this->assets, $this->escaper);
        $this->cache = new FragmentCache($this->manifest);

        if ($this->manifest->support('wpCompat')) {
            WpCompatRuntime::boot($this);
        }
    }

    public function archive(): Archive
    {
        return $this->archive;
    }

    public function site(): Options
    {
        return $this->archive->getOptions();
    }

    public function user(): User
    {
        return $this->archive->getUser();
    }

    public function theme(): Manifest
    {
        return $this->manifest;
    }

    public function e(): Escaper
    {
        return $this->escaper;
    }

    public function assets(): AssetManager
    {
        return $this->assets;
    }

    public function data(): DataProvider
    {
        return $this->data;
    }

    public function tokens(): DesignTokens
    {
        return $this->tokens;
    }

    public function images(): ImageHelper
    {
        return $this->images;
    }

    public function fragmentCache(): FragmentCache
    {
        return $this->cache;
    }

    public function cache(string $key, callable $callback, int $ttl = 300): string
    {
        return $this->cache->remember($key, $callback, $ttl);
    }

    public function layout(string $name, array $vars = []): void
    {
        if ($this->renderDepth > 1) {
            throw new \RuntimeException(_t('布局只能在页面模板中声明'));
        }

        $file = $this->resolveLayout($name);
        if (!$this->manifest->hasFile($file)) {
            throw new \RuntimeException(_t('布局文件 %s 不存在', $file));
        }

        $this->layoutFile = $file;
        $this->layoutVars = $vars;
    }

    public function start(string $name = 'content'): void
    {
        $this->slotStack[] = $name;
        ob_start();
    }

    public function end(): void
    {
        if ($this->slotStack === []) {
            throw new \RuntimeException(_t('没有可结束的 slot'));
        }

        $name = (string) array_pop($this->slotStack);
        $content = (string) ob_get_clean();
        $this->slots[$name] = ($this->slots[$name] ?? '') . $content;
    }

    public function slot(string $name = 'content', string $default = ''): string
    {
        return (string) ($this->slots[$name] ?? $default);
    }

    public function setSlot(string $name, string $content): void
    {
        $this->slots[$name] = $content;
    }

    public function part(string $name, array $vars = []): void
    {
        echo $this->renderPart($name, $vars);
    }

    public function renderPart(string $name, array $vars = []): string
    {
        $file = $this->manifest->part($name)
            ?? (str_starts_with($name, 'parts/') ? $name . '.php' : 'parts/' . $name . '.php');

        if (!$this->manifest->hasFile($file) && $this->manifest->hasFile($name . '.php')) {
            $file = $name . '.php';
        }

        Diagnostics::recordPart($name, $file);
        return $this->renderFile($file, $vars);
    }

    public function component(string $name, array $vars = []): void
    {
        echo $this->renderComponent($name, $vars);
    }

    public function renderComponent(string $name, array $vars = []): string
    {
        $file = $this->manifest->component($name)
            ?? (str_starts_with($name, 'components/') ? $name . '.php' : 'components/' . $name . '.php');

        Diagnostics::recordComponent($name, $file);
        return $this->renderFile($file, $vars);
    }

    public function renderFile(string $file, array $vars = []): string
    {
        if (!$this->manifest->hasFile($file)) {
            throw new \RuntimeException(_t('模板文件 %s 不存在', $file));
        }

        $isRootRender = $this->renderDepth === 0;
        if ($isRootRender) {
            $this->layoutFile = null;
            $this->layoutVars = [];
            $this->slots = [];
        }

        $this->renderDepth++;
        try {
            $content = $this->renderPhpFile($file, $vars);
        } finally {
            $this->renderDepth--;
        }

        if (!$isRootRender || $this->layoutFile === null) {
            return $content;
        }

        $layoutFile = $this->layoutFile;
        $layoutVars = $this->layoutVars;
        if (!array_key_exists('content', $this->slots)) {
            $this->slots['content'] = $content;
        }

        $this->layoutFile = null;
        $this->layoutVars = [];

        $this->renderDepth++;
        try {
            return $this->renderPhpFile($layoutFile, $layoutVars);
        } finally {
            $this->renderDepth--;
            $this->slots = [];
        }
    }

    private function renderPhpFile(string $file, array $vars = []): string
    {
        $view = $this;
        $archive = $this->archive;
        $site = $this->site();
        $theme = $this->manifest;
        $e = $this->escaper;
        $assets = $this->assets;
        $data = $this->data;
        $tokens = $this->tokens;
        $images = $this->images;
        $cache = $this->cache;
        $user = $this->user();
        $post = $archive;

        extract($vars, EXTR_OVERWRITE);
        ob_start();
        try {
            require $this->manifest->path($file);
            return (string) ob_get_clean();
        } catch (\Throwable $throwable) {
            ob_end_clean();
            throw $throwable;
        }
    }

    private function resolveLayout(string $name): string
    {
        $name = trim($name);
        $file = Manifest::normalizePath($name);
        if ($file === null) {
            return '';
        }

        if (!str_ends_with($file, '.php')) {
            $file .= '.php';
        }

        return str_starts_with($file, 'layouts/') ? $file : 'layouts/' . $file;
    }

    public function capture(callable $callback): string
    {
        ob_start();
        try {
            $callback();
            return (string) ob_get_clean();
        } catch (\Throwable $throwable) {
            ob_end_clean();
            throw $throwable;
        }
    }

    public function seoTitle(string $split = ' - '): string
    {
        return $this->archive->getSeoTitle($split);
    }

    public function head(?string $rule = null): string
    {
        return $this->capture(fn () => $this->archive->header($rule));
    }

    public function footer(): string
    {
        return $this->capture(fn () => $this->archive->footer());
    }

    public function siteUrl(?string $path = null): string
    {
        return Common::url($path, $this->site()->siteUrl);
    }

    public function feedUrl(): string
    {
        return $this->site()->feedUrl;
    }

    public function respondId(): string
    {
        return (string) $this->archive->respondId;
    }

    public function commentUrl(): string
    {
        return (string) $this->archive->commentUrl;
    }

    public function navigation(
        string $formatOrSlot = '<a href="{url}"{class}{target}>{label}</a>',
        string $currentClass = 'current'
    ): string {
        if ($this->manifest->hasNavigationSlot($formatOrSlot)) {
            return $this->navigationSlot($formatOrSlot);
        }

        return $this->navigationSlot('primary', $formatOrSlot, $currentClass);
    }

    public function navigationSlot(
        string $slot,
        string $format = '<a href="{url}"{class}{target}>{label}</a>',
        string $currentClass = 'current'
    ): string {
        return $this->capture(fn () => $this->archive->navMenu($format, $currentClass, $slot));
    }

    public function archiveTitle($defines = null, string $before = ' &raquo; ', string $end = ''): string
    {
        return $this->capture(fn () => $this->archive->archiveTitle($defines, $before, $end));
    }

    public function title(Contents $content, int $length = 0, string $trim = '...'): string
    {
        $title = htmlspecialchars_decode((string) $content->title, ENT_QUOTES);
        return $length > 0 ? Common::subStr($title, 0, $length, $trim) : $title;
    }

    public function date(Contents $content, ?string $format = null): string
    {
        return $content->date->format($format ?: $this->site()->postDateFormat);
    }

    public function content($more = false): string
    {
        $content = $this->capture(fn () => $this->archive->content($more));
        $event = new ContentRendering($this->archive, $this, $content, $more);
        Plugin::events()->dispatch($event);

        return $event->content();
    }

    public function pageNav(
        string $prev = '&laquo;',
        string $next = '&raquo;',
        int $splitPage = 3,
        string $splitWord = '...',
        $template = ''
    ): string {
        return $this->capture(fn () => $this->archive->pageNav($prev, $next, $splitPage, $splitWord, $template));
    }

    public function __call(string $method, array $args): string
    {
        if (method_exists($this->archive, $method) || isset($this->archive->{$method})) {
            return $this->capture(fn () => $this->archive->{$method}(...$args));
        }

        throw new \BadMethodCallException($method);
    }
}
