<?php

namespace Typecho\Cache;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class NullCache implements CacheInterface
{
    public function get(string $key, mixed $default = null): mixed
    {
        return $default;
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        return true;
    }

    public function delete(string $key): bool
    {
        return true;
    }

    public function deletePrefix(string $prefix): bool
    {
        return true;
    }

    public function clear(): bool
    {
        return true;
    }
}
