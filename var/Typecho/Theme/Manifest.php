<?php

namespace Typecho\Theme;

use Typecho\Cache\Cache;
use Widget\Options;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class Manifest
{
    private static array $memory = [];

    private static array $themePhpBooted = [];

    private static array $buildMemory = [];

    private const TEMPLATE_FILES = [
        'index'      => 'index.php',
        'front'      => 'front.php',
        'front-page' => 'front-page.php',
        'home'       => 'home.php',
        'archive'    => 'archive.php',
        'single'     => 'single.php',
        'post'       => 'post.php',
        'page'       => 'page.php',
        'search'     => 'search.php',
        'category'   => 'category.php',
        'tag'        => 'tag.php',
        'author'     => 'author.php',
        'date'       => 'date.php',
        'attachment' => 'attachment.php',
        '404'        => '404.php',
    ];

    private const COMMON_PARTS = ['header', 'footer', 'sidebar', 'comments'];

    private const ASSET_EXTENSIONS = [
        'styles'  => 'css',
        'scripts' => 'js',
    ];

    private const IMAGE_EXTENSIONS = ['png', 'jpg', 'jpeg', 'webp', 'gif', 'avif', 'svg'];

    private const BUILD_MANIFEST_FILES = [
        'dist/.vite/manifest.json',
        'dist/manifest.json',
        'assets/manifest.json',
        'public/build/manifest.json',
        'manifest.json',
    ];

    private function __construct(
        private string $theme,
        private string $directory,
        private array $data,
        private bool $cacheHit = false
    ) {
    }

    public static function load(string $theme, Options $options): self
    {
        $theme = trim($theme, './');
        $directory = rtrim($options->themeFile($theme), '/');
        $file = $directory . '/theme.json';
        $signature = self::signature($directory, $file);
        $cacheKey = 'theme:manifest:' . sha1($directory . ':' . $signature);

        if (isset(self::$memory[$cacheKey])) {
            return self::$memory[$cacheKey];
        }

        $definition = self::loadThemePhp($theme, $directory);
        self::bootThemePhp($directory, $definition);

        $data = Cache::get()->get($cacheKey);
        $cacheHit = is_array($data);
        if (!is_array($data)) {
            $data = self::read($theme, $directory, $file, $definition->data());
            Cache::get()->set($cacheKey, $data);
        }

        return self::$memory[$cacheKey] = new self($theme, $directory, $data, $cacheHit);
    }

    private static function read(string $theme, string $directory, string $file, array $themePhp = []): array
    {
        $defaults = [
            'id'          => $theme,
            'name'        => $theme,
            'title'       => $theme,
            'description' => '',
            'author'      => '',
            'homepage'    => '',
            'version'     => '',
            'engine'      => 'php',
            'screenshot'  => '',
            'typecho'     => ['requires' => ''],
            'templates'   => ['index' => 'index.php'],
            'parts'       => [],
            'components'  => [],
            'assets'      => ['styles' => [], 'scripts' => [], 'images' => []],
            'settings'    => [],
            'navigation'  => ['primary' => '主导航'],
            'tokens'      => [],
            'build'       => [],
            'compatibility' => [],
            'supports'    => [
                'navigation'       => true,
                'seo'              => true,
                'responsiveImages' => true,
            ],
        ];

        $decoded = [];
        if (!is_file($file)) {
            $data = array_replace_recursive($defaults, self::discover($directory), $themePhp);
        } else {
            $json = json_decode((string) file_get_contents($file), true);
            if (is_array($json)) {
                $decoded = $json;
            }

            $data = array_replace_recursive($defaults, self::discover($directory), $decoded, $themePhp);
        }

        $data['id'] = self::slug((string) ($data['id'] ?: $theme), $theme);
        $data['name'] = self::slug((string) ($data['name'] ?: $theme), $theme);
        $data['engine'] = strtolower((string) ($data['engine'] ?: 'php'));
        $data['screenshot'] = is_string($data['screenshot'])
            ? (self::normalizePath($data['screenshot']) ?? '')
            : '';
        $data['templates'] = self::normalizeMap($data['templates']);
        $data['parts'] = self::normalizeMap($data['parts']);
        $data['components'] = self::normalizeMap($data['components']);
        $data['assets'] = self::normalizeAssets($data['assets']);
        $data['settings'] = is_array($data['settings']) ? array_values($data['settings']) : [];
        $data['navigation'] = self::normalizeNavigation($data['navigation']);
        $data['tokens'] = self::normalizeTokens($data['tokens']);
        $data['build'] = self::normalizeBuild($data['build']);
        $data['supports'] = is_array($data['supports']) ? $data['supports'] : [];

        return $data;
    }

    private static function loadThemePhp(string $theme, string $directory): Definition
    {
        $definition = new Definition($theme, $directory);
        $file = $directory . '/theme.php';

        if (!is_file($file)) {
            return $definition;
        }

        $result = (static function (Definition $theme) use ($file) {
            return require $file;
        })($definition);

        if (is_callable($result)) {
            $result($definition);
        } elseif (is_array($result)) {
            $definition->merge($result);
        }

        return $definition;
    }

    private static function bootThemePhp(string $directory, Definition $definition): void
    {
        $file = $directory . '/theme.php';
        if (!is_file($file)) {
            return;
        }

        $key = $file . ':' . filemtime($file);
        if (isset(self::$themePhpBooted[$key])) {
            return;
        }

        self::$themePhpBooted[$key] = true;
        $definition->bootEvents();
    }

    private static function signature(string $directory, string $manifestFile): string
    {
        $paths = [
            $manifestFile,
            $directory . '/theme.php',
            $directory,
            $directory . '/layouts',
            $directory . '/parts',
            $directory . '/components',
            $directory . '/assets',
            $directory . '/assets/css',
            $directory . '/assets/js',
            $directory . '/assets/img',
            $directory . '/assets/images',
            $directory . '/static',
            $directory . '/static/css',
            $directory . '/static/js',
            $directory . '/static/img',
            $directory . '/static/images',
            $directory . '/css',
            $directory . '/js',
            $directory . '/img',
            $directory . '/images',
            $directory . '/dist',
            $directory . '/dist/.vite',
            $directory . '/dist/.vite/manifest.json',
            $directory . '/dist/manifest.json',
            $directory . '/assets/manifest.json',
            $directory . '/public/build/manifest.json',
            $directory . '/manifest.json',
        ];

        $stats = [];
        foreach ($paths as $path) {
            if (is_file($path) || is_dir($path)) {
                $stats[] = $path . ':' . filemtime($path);
            }
        }

        return sha1(implode('|', $stats));
    }

    private static function discover(string $directory): array
    {
        $data = [
            'templates'  => self::discoverTemplates($directory),
            'parts'      => self::discoverParts($directory),
            'components' => self::discoverComponents($directory),
            'assets'     => self::discoverAssets($directory),
            'build'      => self::discoverBuild($directory),
        ];

        $screenshot = self::discoverScreenshot($directory);
        if ($screenshot !== null) {
            $data['screenshot'] = $screenshot;
        }

        return $data;
    }

    private static function discoverBuild(string $directory): array
    {
        foreach (self::BUILD_MANIFEST_FILES as $file) {
            if (is_file($directory . '/' . $file)) {
                return ['manifest' => $file];
            }
        }

        return [];
    }

    private static function discoverTemplates(string $directory): array
    {
        $templates = [];
        foreach (self::TEMPLATE_FILES as $key => $file) {
            if (is_file($directory . '/' . $file)) {
                $templates[$key] = $file;
            }
        }

        return $templates;
    }

    private static function discoverParts(string $directory): array
    {
        $parts = [];
        foreach (self::COMMON_PARTS as $part) {
            foreach (['parts/' . $part . '.php', $part . '.php'] as $file) {
                if (is_file($directory . '/' . $file)) {
                    $parts[$part] = $file;
                    break;
                }
            }
        }

        foreach (glob($directory . '/parts/*.php') ?: [] as $file) {
            $parts[pathinfo($file, PATHINFO_FILENAME)] = 'parts/' . basename($file);
        }

        return $parts;
    }

    private static function discoverComponents(string $directory): array
    {
        $base = $directory . '/components';
        if (!is_dir($base)) {
            return [];
        }

        $components = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
                continue;
            }

            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($directory) + 1));
            $name = substr($relative, strlen('components/'), -4);
            if ($name !== '') {
                $components[$name] = $relative;
            }
        }

        ksort($components);
        return $components;
    }

    private static function discoverAssets(string $directory): array
    {
        $assets = ['styles' => [], 'scripts' => [], 'images' => []];

        foreach (['theme', 'main', 'app', 'custom', 'normalize', 'grid'] as $name) {
            $path = self::resolveAssetPath($directory, 'styles', $name);
            if ($path !== null) {
                $assets['styles'][$name] = $path;
            }
        }

        foreach (['theme', 'main', 'app'] as $name) {
            $path = self::resolveAssetPath($directory, 'scripts', $name);
            if ($path !== null) {
                $assets['scripts'][$name] = $path;
            }
        }

        foreach (['logo', 'cover', 'screenshot'] as $name) {
            $path = self::resolveAssetPath($directory, 'images', $name);
            if ($path !== null) {
                $assets['images'][$name] = $path;
            }
        }

        return $assets;
    }

    private static function discoverScreenshot(string $directory): ?string
    {
        foreach (self::IMAGE_EXTENSIONS as $extension) {
            $file = 'screenshot.' . $extension;
            if (is_file($directory . '/' . $file)) {
                return $file;
            }
        }

        return null;
    }

    private static function slug(string $value, string $default): string
    {
        $value = trim($value, './');
        return preg_match('/^[a-zA-Z0-9_.-]+$/', $value) ? $value : $default;
    }

    private static function normalizeMap(mixed $map): array
    {
        if (!is_array($map)) {
            return [];
        }

        $normalized = [];
        foreach ($map as $key => $value) {
            if ((is_string($key) || is_int($key)) && is_string($value)) {
                $path = self::normalizePath($value);
                if ($path !== null) {
                    $normalized[(string) $key] = $path;
                }
            }
        }

        return $normalized;
    }

    private static function normalizeAssets(mixed $assets): array
    {
        $normalized = ['styles' => [], 'scripts' => [], 'images' => []];
        if (!is_array($assets)) {
            return $normalized;
        }

        foreach ($normalized as $type => $value) {
            $normalized[$type] = self::normalizeMap($assets[$type] ?? []);
        }

        return $normalized;
    }

    private static function normalizeNavigation(mixed $navigation): array
    {
        if (!is_array($navigation)) {
            return ['primary' => '主导航'];
        }

        $slots = [];
        foreach ($navigation as $key => $value) {
            if (!is_string($key) || !preg_match('/^[a-zA-Z0-9_-]+$/', $key)) {
                continue;
            }

            if (is_string($value)) {
                $slots[$key] = $value;
            } elseif (is_array($value) && is_string($value['label'] ?? null)) {
                $slots[$key] = $value['label'];
            }
        }

        return $slots ?: ['primary' => '主导航'];
    }

    private static function normalizeTokens(mixed $tokens): array
    {
        if (!is_array($tokens)) {
            return [];
        }

        $normalized = [];
        foreach ($tokens as $group => $values) {
            if (!is_string($group) || !is_array($values)) {
                continue;
            }

            foreach ($values as $key => $value) {
                if ((is_string($key) || is_int($key)) && is_scalar($value)) {
                    $normalized[$group][(string) $key] = $value;
                }
            }
        }

        return $normalized;
    }

    private static function normalizeBuild(mixed $build): array
    {
        if (!is_array($build)) {
            return [];
        }

        $manifest = is_string($build['manifest'] ?? null)
            ? self::normalizePath($build['manifest'])
            : null;

        if ($manifest === null) {
            return [];
        }

        $base = is_string($build['base'] ?? null) ? self::normalizePath($build['base']) : null;
        if ($base === null) {
            $directory = trim(str_replace('\\', '/', dirname($manifest)), '.');
            $base = str_ends_with($directory, '/.vite') ? dirname($directory) : $directory;
            $base = trim($base, '/.');
        }

        return [
            'manifest' => $manifest,
            'base'     => $base,
        ];
    }

    private static function resolveAssetPath(string $directory, string $type, string $key): ?string
    {
        $path = self::normalizePath($key);
        if ($path === null) {
            return null;
        }

        if (is_file($directory . '/' . $path)) {
            return $path;
        }

        $candidates = match ($type) {
            'styles'  => self::styleCandidates($path),
            'scripts' => self::scriptCandidates($path),
            'images'  => self::imageCandidates($path),
            default   => [],
        };

        foreach ($candidates as $candidate) {
            if (is_file($directory . '/' . $candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private static function styleCandidates(string $name): array
    {
        if (pathinfo($name, PATHINFO_EXTENSION) === self::ASSET_EXTENSIONS['styles']) {
            return [$name];
        }

        $files = match ($name) {
            'theme'  => ['style.css', 'main.css', 'app.css', 'theme.css'],
            'main'   => ['main.css', 'style.css', 'theme.css'],
            'app'    => ['app.css', 'main.css', 'style.css'],
            'custom' => ['custom.css', 'theme.css'],
            default  => [$name . '.css'],
        };

        return self::assetCandidates($files, ['', 'assets/css', 'static/css', 'css']);
    }

    private static function scriptCandidates(string $name): array
    {
        if (pathinfo($name, PATHINFO_EXTENSION) === self::ASSET_EXTENSIONS['scripts']) {
            return [$name];
        }

        $files = match ($name) {
            'theme' => ['theme.js', 'main.js', 'app.js'],
            'main'  => ['main.js', 'theme.js', 'app.js'],
            'app'   => ['app.js', 'main.js', 'theme.js'],
            default => [$name . '.js'],
        };

        return self::assetCandidates($files, ['', 'assets/js', 'static/js', 'js']);
    }

    private static function imageCandidates(string $name): array
    {
        if (pathinfo($name, PATHINFO_EXTENSION) !== '') {
            return [$name];
        }

        $files = [];
        foreach (self::IMAGE_EXTENSIONS as $extension) {
            $files[] = $name . '.' . $extension;
        }

        return self::assetCandidates($files, ['', 'assets/img', 'assets/images', 'static/img', 'static/images', 'img', 'images']);
    }

    private static function assetCandidates(array $files, array $directories): array
    {
        $candidates = [];
        foreach ($files as $file) {
            foreach ($directories as $directory) {
                $candidates[] = $directory === '' ? $file : $directory . '/' . $file;
            }
        }

        return array_values(array_unique($candidates));
    }

    public static function normalizePath(string $path): ?string
    {
        $path = trim(str_replace('\\', '/', $path));

        if (
            $path === ''
            || $path === '..'
            || str_starts_with($path, '/')
            || str_starts_with($path, '../')
            || str_contains($path, '/../')
            || str_ends_with($path, '/..')
        ) {
            return null;
        }

        return ltrim($path, './');
    }

    public function theme(): string
    {
        return $this->theme;
    }

    public function directory(): string
    {
        return $this->directory;
    }

    public function data(): array
    {
        return $this->data;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function templates(): array
    {
        return $this->data['templates'];
    }

    public function parts(): array
    {
        return $this->data['parts'];
    }

    public function components(): array
    {
        return $this->data['components'];
    }

    public function settings(): array
    {
        return $this->data['settings'];
    }

    public function supports(): array
    {
        return $this->data['supports'];
    }

    public function support(string $key, mixed $default = false): mixed
    {
        return $this->data['supports'][$key] ?? $default;
    }

    public function navigationSlots(): array
    {
        return $this->data['navigation'];
    }

    public function tokens(): array
    {
        return $this->data['tokens'];
    }

    public function build(): array
    {
        return $this->data['build'];
    }

    public function coreRequirement(): string
    {
        return Compatibility::coreRequirement($this->data);
    }

    /**
     * @return array<string, mixed>
     */
    public function compatibility(): array
    {
        return Compatibility::evaluate($this->coreRequirement());
    }

    public function isCompatible(): bool
    {
        return (bool) $this->compatibility()['compatible'];
    }

    public function compatibilityMessage(): string
    {
        return (string) $this->compatibility()['message'];
    }

    public function hasNavigationSlot(string $slot): bool
    {
        return isset($this->data['navigation'][$slot]);
    }

    public function isCacheHit(): bool
    {
        return $this->cacheHit;
    }

    public function template(string $key): ?string
    {
        return $this->templates()[$key] ?? null;
    }

    public function part(string $key): ?string
    {
        return $this->parts()[$key] ?? null;
    }

    public function component(string $key): ?string
    {
        return $this->components()[$key] ?? null;
    }

    public function asset(string $type, string $key): ?string
    {
        $assets = $this->data['assets'][$type] ?? [];
        $configured = is_array($assets) && is_string($assets[$key] ?? null)
            ? self::normalizePath($assets[$key])
            : null;

        return $configured ?? self::resolveAssetPath($this->directory, $type, $key);
    }

    public function buildManifest(): array
    {
        $build = $this->build();
        $manifest = $build['manifest'] ?? null;
        if (!is_string($manifest) || !$this->hasFile($manifest)) {
            return [];
        }

        $file = $this->path($manifest);
        $key = $file . ':' . filemtime($file);
        if (isset(self::$buildMemory[$key])) {
            return self::$buildMemory[$key];
        }

        $decoded = json_decode((string) file_get_contents($file), true);
        return self::$buildMemory[$key] = is_array($decoded) ? $decoded : [];
    }

    public function buildEntry(string $entry): ?array
    {
        $manifest = $this->buildManifest();
        $value = $manifest[$entry] ?? null;
        return is_array($value) ? $value : null;
    }

    public function buildPath(string $file): ?string
    {
        $file = self::normalizePath($file);
        if ($file === null) {
            return null;
        }

        $base = (string) ($this->build()['base'] ?? '');
        $path = trim($base . '/' . $file, '/');
        return self::normalizePath($path);
    }

    public function path(string $relative): string
    {
        $relative = self::normalizePath($relative);
        if ($relative === null) {
            throw new \InvalidArgumentException('Invalid theme path');
        }

        return $this->directory . '/' . $relative;
    }

    public function hasFile(?string $relative): bool
    {
        return is_string($relative)
            && ($path = self::normalizePath($relative)) !== null
            && is_file($this->directory . '/' . $path);
    }
}
