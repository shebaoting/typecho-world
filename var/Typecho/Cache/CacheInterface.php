<?php

namespace Typecho\Cache;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

interface CacheInterface
{
    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl
     * @return bool
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool;

    /**
     * @param string $key
     * @return bool
     */
    public function delete(string $key): bool;

    /**
     * @param string $prefix
     * @return bool
     */
    public function deletePrefix(string $prefix): bool;

    /**
     * @return bool
     */
    public function clear(): bool;
}
