<?php

namespace Typecho\Theme;

use Typecho\Common;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class ImageHelper
{
    public function __construct(private AssetManager $assets, private Escaper $escaper)
    {
    }

    public function url(string $src): string
    {
        $src = trim($src);
        if ($src === '') {
            return '';
        }

        if (preg_match('/^(?:https?:\/\/|\/\/|\/)/i', $src)) {
            return Common::safeUrl($src);
        }

        return $this->assets->url($src);
    }

    public function tag(string $src, array $attributes = [], array $srcset = []): string
    {
        $attributes = [
            'src'      => $this->url($src),
            'alt'      => '',
            'loading'  => 'lazy',
            'decoding' => 'async',
        ] + $attributes;

        if ($srcset !== []) {
            $attributes['srcset'] = $this->srcset($srcset);
        }

        return '<img' . $this->escaper->attrs($attributes) . '>';
    }

    public function srcset(array $sources): string
    {
        $items = [];
        foreach ($sources as $descriptor => $src) {
            if (!is_string($src) || trim($src) === '') {
                continue;
            }

            $descriptor = is_int($descriptor) ? '' : trim((string) $descriptor);
            $items[] = trim($this->escaper->url($this->url($src)) . ' ' . $descriptor);
        }

        return implode(', ', $items);
    }

    public function picture(array $sources, string $fallback, array $attributes = []): string
    {
        $html = '<picture>';
        foreach ($sources as $media => $srcset) {
            $attrs = ['srcset' => is_array($srcset) ? $this->srcset($srcset) : $this->url((string) $srcset)];
            if (is_string($media) && $media !== '') {
                $attrs['media'] = $media;
            }

            $html .= '<source' . $this->escaper->attrs($attrs) . '>';
        }

        return $html . $this->tag($fallback, $attributes) . '</picture>';
    }
}
