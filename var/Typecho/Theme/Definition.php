<?php

namespace Typecho\Theme;

use Typecho\Plugin;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class Definition
{
    private array $data = [
        'templates'  => [],
        'parts'      => [],
        'components' => [],
        'assets'     => ['styles' => [], 'scripts' => [], 'images' => []],
        'settings'   => [],
        'navigation' => [],
        'supports'   => [],
        'tokens'     => [],
        'build'      => [],
        'typecho'    => [],
        'compatibility' => [],
    ];

    private array $events = [];

    public function __construct(private string $theme, private string $directory)
    {
    }

    public function theme(): string
    {
        return $this->theme;
    }

    public function directory(): string
    {
        return $this->directory;
    }

    public function template(string $key, string $file): self
    {
        $this->data['templates'][$key] = $file;
        return $this;
    }

    public function part(string $key, string $file): self
    {
        $this->data['parts'][$key] = $file;
        return $this;
    }

    public function component(string $key, string $file): self
    {
        $this->data['components'][$key] = $file;
        return $this;
    }

    public function style(string $key, string $file): self
    {
        $this->data['assets']['styles'][$key] = $file;
        return $this;
    }

    public function script(string $key, string $file): self
    {
        $this->data['assets']['scripts'][$key] = $file;
        return $this;
    }

    public function image(string $key, string $file): self
    {
        $this->data['assets']['images'][$key] = $file;
        return $this;
    }

    public function setting(array $setting): self
    {
        $this->data['settings'][] = $setting;
        return $this;
    }

    public function navigation(string $slot, string $label): self
    {
        $this->data['navigation'][$slot] = $label;
        return $this;
    }

    public function support(string $key, mixed $value = true): self
    {
        $this->data['supports'][$key] = $value;
        return $this;
    }

    public function token(string $group, string $key, string|int|float|bool $value): self
    {
        $this->data['tokens'][$group][$key] = $value;
        return $this;
    }

    public function buildManifest(string $file, ?string $base = null): self
    {
        $this->data['build']['manifest'] = $file;
        if ($base !== null) {
            $this->data['build']['base'] = $base;
        }

        return $this;
    }

    public function requiresTypecho(string $constraint): self
    {
        $this->data['typecho']['requires'] = $constraint;
        return $this;
    }

    public function event(string $event, callable $listener, int $priority = 0): self
    {
        $this->events[] = [$event, $listener, $priority];
        return $this;
    }

    public function merge(array $data): self
    {
        unset($data['events']);
        $this->data = array_replace_recursive($this->data, $this->sanitize($data));
        return $this;
    }

    public function data(): array
    {
        return $this->data;
    }

    public function bootEvents(): void
    {
        foreach ($this->events as [$event, $listener, $priority]) {
            Plugin::on($event, $listener, $priority);
        }
    }

    private function sanitize(array $data): array
    {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitize($value);
            } elseif (is_scalar($value) || $value === null) {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}
