<?php

namespace Typecho\Theme;

use Typecho\Cache\Cache;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class Diagnostics
{
    private static array $current = [];

    public static function begin(string $theme, string $template, array $candidates, bool $manifestCacheHit): void
    {
        self::$current = [
            'theme'            => $theme,
            'template'         => $template,
            'candidates'       => $candidates,
            'manifestCacheHit' => $manifestCacheHit,
            'assets'           => [],
            'parts'            => [],
            'components'       => [],
            'fragments'        => [],
            'startedAt'        => microtime(true),
        ];
    }

    public static function recordAsset(string $type, string $name, string $path, string $url): void
    {
        if (empty(self::$current)) {
            return;
        }

        self::$current['assets'][] = [
            'type' => $type,
            'name' => $name,
            'path' => $path,
            'url'  => $url,
        ];
    }

    public static function recordPart(string $name, string $file): void
    {
        if (!empty(self::$current)) {
            self::$current['parts'][] = ['name' => $name, 'file' => $file];
        }
    }

    public static function recordComponent(string $name, string $file): void
    {
        if (!empty(self::$current)) {
            self::$current['components'][] = ['name' => $name, 'file' => $file];
        }
    }

    public static function recordFragment(string $key, bool $hit, int $ttl): void
    {
        if (!empty(self::$current)) {
            self::$current['fragments'][] = ['key' => $key, 'hit' => $hit, 'ttl' => $ttl];
        }
    }

    public static function finish(int $queryCount, float $queryTime): void
    {
        if (empty(self::$current)) {
            return;
        }

        $data = self::$current;
        $data['renderTimeMs'] = round((microtime(true) - $data['startedAt']) * 1000, 3);
        $data['queryCount'] = $queryCount;
        $data['queryTimeMs'] = round($queryTime * 1000, 3);
        $data['finishedAt'] = date('Y-m-d H:i:s');
        unset($data['startedAt']);

        self::store($data);
        self::$current = [];
    }

    public static function last(string $theme): ?array
    {
        $key = self::key($theme);
        $data = Cache::get()->get($key);
        if (is_array($data)) {
            return $data;
        }

        $file = self::file($theme);
        if (is_file($file)) {
            $decoded = json_decode((string) file_get_contents($file), true);
            return is_array($decoded) ? $decoded : null;
        }

        return null;
    }

    private static function store(array $data): void
    {
        $theme = (string) ($data['theme'] ?? '');
        if ($theme === '') {
            return;
        }

        Cache::get()->set(self::key($theme), $data);

        $file = self::file($theme);
        $dir = dirname($file);
        if ((is_dir($dir) || @mkdir($dir, 0775, true)) && is_writable($dir)) {
            @file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }
    }

    private static function key(string $theme): string
    {
        return 'theme:diagnostics:last:' . sha1($theme);
    }

    private static function file(string $theme): string
    {
        $safeTheme = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $theme);
        return __TYPECHO_ROOT_DIR__ . '/usr/cache/theme-diagnostics-' . $safeTheme . '.json';
    }
}
