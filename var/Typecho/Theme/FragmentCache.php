<?php

namespace Typecho\Theme;

use Typecho\Cache\Cache;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class FragmentCache
{
    public function __construct(private Manifest $manifest)
    {
    }

    public function remember(string $key, callable $callback, int $ttl = 300): string
    {
        $cacheKey = $this->key($key);
        $cached = Cache::get()->get($cacheKey);

        if (is_string($cached)) {
            Diagnostics::recordFragment($key, true, $ttl);
            return $cached;
        }

        ob_start();
        try {
            $result = $callback();
            $content = (string) ob_get_clean();
        } catch (\Throwable $throwable) {
            ob_end_clean();
            throw $throwable;
        }

        if ($content === '' && $result !== null) {
            $content = (string) $result;
        }

        Cache::get()->set($cacheKey, $content, $ttl > 0 ? $ttl : null);
        Diagnostics::recordFragment($key, false, $ttl);
        return $content;
    }

    public function key(string $key): string
    {
        return 'theme:fragment:' . sha1($this->manifest->theme() . ':' . $key);
    }
}
