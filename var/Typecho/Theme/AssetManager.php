<?php

namespace Typecho\Theme;

use Typecho\Common;
use Widget\Options;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class AssetManager
{
    public function __construct(
        private Options $options,
        private Manifest $manifest,
        private Escaper $escaper
    ) {
    }

    public function url(string $path, bool $version = true): string
    {
        $path = trim($path);
        if (preg_match('/^(?:https?:\/\/|\/\/|\/)/i', $path)) {
            return Common::safeUrl($path);
        }

        $path = $this->manifest->asset('images', $path)
            ?? $this->manifest->asset('styles', $path)
            ?? $this->manifest->asset('scripts', $path)
            ?? Manifest::normalizePath($path)
            ?? '';

        $url = $this->options->themeUrl($path, $this->manifest->theme());

        if ($version && $path !== '') {
            $file = $this->manifest->path($path);
            if (is_file($file)) {
                $url .= (str_contains($url, '?') ? '&' : '?') . 'v=' . filemtime($file);
            }
        }

        return $url;
    }

    public function style(string $name, array $attributes = []): string
    {
        $path = $this->manifest->asset('styles', $name) ?? $name;
        $url = $this->url($path);
        Diagnostics::recordAsset('style', $name, $path, $url);
        $attributes = ['rel' => 'stylesheet', 'href' => $url] + $attributes;
        return '<link' . $this->escaper->attrs($attributes) . '>';
    }

    public function script(string $name, array $attributes = []): string
    {
        $path = $this->manifest->asset('scripts', $name) ?? $name;
        $url = $this->url($path);
        Diagnostics::recordAsset('script', $name, $path, $url);
        $attributes = ['src' => $url] + $attributes;
        return '<script' . $this->escaper->attrs($attributes) . '></script>';
    }

    public function entry(string $entry, array $scriptAttributes = [], array $styleAttributes = []): string
    {
        $buildEntry = $this->manifest->buildEntry($entry);
        if ($buildEntry === null) {
            return '';
        }

        $html = [];
        foreach ((array) ($buildEntry['imports'] ?? []) as $import) {
            if (!is_string($import)) {
                continue;
            }

            $importEntry = $this->manifest->buildEntry($import);
            $file = is_array($importEntry) && is_string($importEntry['file'] ?? null)
                ? $this->manifest->buildPath($importEntry['file'])
                : null;

            if ($file !== null) {
                $url = $this->url($file);
                Diagnostics::recordAsset('modulepreload', $import, $file, $url);
                $html[] = '<link' . $this->escaper->attrs(['rel' => 'modulepreload', 'href' => $url]) . '>';
            }
        }

        foreach ((array) ($buildEntry['css'] ?? []) as $css) {
            if (!is_string($css)) {
                continue;
            }

            $path = $this->manifest->buildPath($css);
            if ($path === null) {
                continue;
            }

            $url = $this->url($path);
            Diagnostics::recordAsset('build-style', $entry, $path, $url);
            $html[] = '<link' . $this->escaper->attrs(['rel' => 'stylesheet', 'href' => $url] + $styleAttributes) . '>';
        }

        if (is_string($buildEntry['file'] ?? null)) {
            $path = $this->manifest->buildPath($buildEntry['file']);
            if ($path !== null) {
                $url = $this->url($path);
                Diagnostics::recordAsset('build-script', $entry, $path, $url);
                $html[] = '<script' . $this->escaper->attrs(['type' => 'module', 'src' => $url] + $scriptAttributes) . '></script>';
            }
        }

        return implode("\n", $html);
    }

    public function image(string $name, array $attributes = []): string
    {
        $path = $this->manifest->asset('images', $name) ?? $name;
        $url = $this->url($path);
        Diagnostics::recordAsset('image', $name, $path, $url);
        $attributes = ['src' => $url, 'alt' => ''] + $attributes;
        return '<img' . $this->escaper->attrs($attributes) . '>';
    }
}
