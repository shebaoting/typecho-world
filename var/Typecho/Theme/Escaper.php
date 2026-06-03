<?php

namespace Typecho\Theme;

use Typecho\Common;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class Escaper
{
    public function __construct(private string $charset = 'UTF-8')
    {
    }

    public function html(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, $this->charset);
    }

    public function attr(mixed $value): string
    {
        return $this->html($value);
    }

    public function url(mixed $value): string
    {
        return $this->attr(Common::safeUrl((string) $value));
    }

    public function js(mixed $value): string
    {
        return json_encode((string) $value, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?: '""';
    }

    public function raw(mixed $value): string
    {
        return (string) $value;
    }

    public function attrs(array $attributes): string
    {
        $html = '';

        foreach ($attributes as $name => $value) {
            if ($value === null || $value === false) {
                continue;
            }

            $name = preg_replace('/[^a-zA-Z0-9_:-]/', '', (string) $name);
            if ($name === '') {
                continue;
            }

            if ($value === true) {
                $html .= ' ' . $name;
            } else {
                $html .= ' ' . $name . '="' . $this->attr($value) . '"';
            }
        }

        return $html;
    }
}
