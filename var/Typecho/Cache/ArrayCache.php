<?php

namespace Typecho\Cache;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class ArrayCache implements CacheInterface
{
    private array $values = [];

    private array $expires = [];

    public function get(string $key, mixed $default = null): mixed
    {
        if (!array_key_exists($key, $this->values)) {
            return $default;
        }

        if (isset($this->expires[$key]) && $this->expires[$key] < time()) {
            $this->delete($key);
            return $default;
        }

        return $this->values[$key];
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $this->values[$key] = $value;
        $this->expires[$key] = $ttl === null ? null : time() + $ttl;
        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->values[$key], $this->expires[$key]);
        return true;
    }

    public function deletePrefix(string $prefix): bool
    {
        foreach (array_keys($this->values) as $key) {
            if (str_starts_with($key, $prefix)) {
                $this->delete($key);
            }
        }

        return true;
    }

    public function clear(): bool
    {
        $this->values = [];
        $this->expires = [];
        return true;
    }
}
