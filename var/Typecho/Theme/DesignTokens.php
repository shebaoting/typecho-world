<?php

namespace Typecho\Theme;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class DesignTokens
{
    public function __construct(private array $tokens, private Escaper $escaper)
    {
    }

    public function all(): array
    {
        return $this->tokens;
    }

    public function get(string $group, ?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->tokens[$group] ?? $default;
        }

        return $this->tokens[$group][$key] ?? $default;
    }

    public function var(string $group, string $key, ?string $fallback = null): string
    {
        $name = '--tw-' . $this->slug($group) . '-' . $this->slug($key);
        return $fallback === null ? 'var(' . $name . ')' : 'var(' . $name . ', ' . $fallback . ')';
    }

    public function cssVariables(): string
    {
        $rules = [];
        foreach ($this->tokens as $group => $values) {
            if (!is_array($values)) {
                continue;
            }

            foreach ($values as $key => $value) {
                if (!is_scalar($value)) {
                    continue;
                }

                $rules[] = '  --tw-' . $this->slug((string) $group) . '-' . $this->slug((string) $key)
                    . ': ' . $this->sanitizeValue((string) $value) . ';';
            }
        }

        return $rules === [] ? '' : ":root {\n" . implode("\n", $rules) . "\n}";
    }

    public function styleTag(): string
    {
        $css = $this->cssVariables();
        return $css === '' ? '' : '<style id="theme-design-tokens">' . $this->escaper->html($css) . '</style>';
    }

    private function slug(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9_-]+/', '-', $value) ?? '';
        return trim($value, '-') ?: 'token';
    }

    private function sanitizeValue(string $value): string
    {
        return str_replace(['<', '>', '{', '}'], '', trim($value));
    }
}
