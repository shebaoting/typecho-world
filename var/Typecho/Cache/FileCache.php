<?php

namespace Typecho\Cache;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class FileCache implements CacheInterface
{
    private string $path;

    /**
     * @param string $path
     */
    public function __construct(string $path)
    {
        $this->path = rtrim($path, '/');
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $file = $this->file($key);

        if (!is_file($file)) {
            return $default;
        }

        $payload = include $file;

        if (!is_array($payload) || ($payload['key'] ?? null) !== $key) {
            return $default;
        }

        if (isset($payload['expires']) && $payload['expires'] < time()) {
            $this->delete($key);
            return $default;
        }

        return $payload['value'] ?? $default;
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        if (!is_dir($this->path) && !mkdir($this->path, 0775, true) && !is_dir($this->path)) {
            return false;
        }

        $payload = [
            'key'     => $key,
            'expires' => $ttl === null ? null : time() + $ttl,
            'value'   => $value
        ];

        return false !== file_put_contents(
            $this->file($key),
            "<?php\nreturn " . var_export($payload, true) . ";\n",
            LOCK_EX
        );
    }

    public function delete(string $key): bool
    {
        $file = $this->file($key);
        return !is_file($file) || unlink($file);
    }

    public function deletePrefix(string $prefix): bool
    {
        foreach ($this->files() as $file) {
            $payload = include $file;

            if (is_array($payload) && str_starts_with($payload['key'] ?? '', $prefix)) {
                @unlink($file);
            }
        }

        return true;
    }

    public function clear(): bool
    {
        foreach ($this->files() as $file) {
            @unlink($file);
        }

        return true;
    }

    /**
     * @param string $key
     * @return string
     */
    private function file(string $key): string
    {
        return $this->path . '/' . sha1($key) . '.php';
    }

    /**
     * @return array
     */
    private function files(): array
    {
        return is_dir($this->path) ? glob($this->path . '/*.php') ?: [] : [];
    }
}
