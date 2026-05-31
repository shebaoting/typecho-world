<?php

namespace Typecho\Cache;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class Cache
{
    private static ?CacheInterface $driver = null;

    /**
     * @return CacheInterface
     */
    public static function get(): CacheInterface
    {
        if (!isset(self::$driver)) {
            self::$driver = self::createDefaultDriver();
        }

        return self::$driver;
    }

    /**
     * @param CacheInterface $driver
     */
    public static function set(CacheInterface $driver)
    {
        self::$driver = $driver;
    }

    /**
     * @return CacheInterface
     */
    private static function createDefaultDriver(): CacheInterface
    {
        $driver = defined('__TYPECHO_CACHE__') ? strtolower((string) __TYPECHO_CACHE__) : 'null';

        return match ($driver) {
            'array' => new ArrayCache(),
            'file' => new FileCache(defined('__TYPECHO_CACHE_DIR__')
                ? __TYPECHO_CACHE_DIR__
                : __TYPECHO_ROOT_DIR__ . '/usr/cache'),
            default => new NullCache(),
        };
    }
}
