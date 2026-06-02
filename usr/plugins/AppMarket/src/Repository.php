<?php

namespace TypechoPlugin\AppMarket;

use Typecho\Common;
use Typecho\Db;
use Typecho\Http\Client as HttpClient;
use Typecho\Language;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class Repository
{
    private const MARKET_BASE = 'https://typecho.world';

    private const INDEX_URL = self::MARKET_BASE . '/api/v1/app-market/index.json';

    private const CACHE_OPTION = 'appMarketRepositoryCache';

    private const CACHE_TTL = 600;

    private const CACHE_VERSION = 5;

    private static bool $offline = false;

    private static string $offlineMessage = '';

    /**
     * 获取应用市场数据
     *
     * @param bool $forceRefresh
     * @return array
     */
    public static function all(bool $forceRefresh = false): array
    {
        self::$offline = false;
        self::$offlineMessage = '';

        $cache = self::cache();
        if (!$forceRefresh && self::isFreshCache($cache)) {
            return $cache['apps'];
        }

        try {
            $apps = self::fetchRemoteApps();
            self::saveCache([
                'time' => time(),
                'source' => self::INDEX_URL,
                'version' => self::CACHE_VERSION,
                'apps' => $apps,
            ]);

            return $apps;
        } catch (\Exception $e) {
            self::$offline = true;
            self::$offlineMessage = $e->getMessage();

            return $cache['apps'] ?? [];
        }
    }

    /**
     * @param string $id
     * @return array|null
     */
    public static function find(string $id): ?array
    {
        foreach (self::all() as $app) {
            if ($app['id'] === $id) {
                return self::freshDetail($app) ?? $app;
            }
        }

        return null;
    }

    /**
     * @return bool
     */
    public static function isOffline(): bool
    {
        return self::$offline;
    }

    /**
     * @return string
     */
    public static function offlineMessage(): string
    {
        return self::$offlineMessage;
    }

    /**
     * @return array{apps?: array, time?: int}
     */
    private static function cache(): array
    {
        $db = Db::get();
        $row = $db->fetchRow($db->select('value')->from('table.options')
            ->where('name = ?', self::CACHE_OPTION)
            ->where('user = ?', 0));

        if (empty($row['value'])) {
            return [];
        }

        $value = (string) $row['value'];
        if (str_starts_with($value, 'gz:') && function_exists('gzuncompress')) {
            $decoded = base64_decode(substr($value, 3), true);
            $value = false !== $decoded ? (gzuncompress($decoded) ?: '') : '';
        }

        $cache = json_decode($value, true);
        return is_array($cache) ? $cache : [];
    }

    /**
     * @param array $cache
     * @return bool
     */
    private static function isFreshCache(array $cache): bool
    {
        return !empty($cache['apps'])
            && !empty($cache['time'])
            && ($cache['source'] ?? '') === self::INDEX_URL
            && (int) ($cache['version'] ?? 0) >= self::CACHE_VERSION
            && !self::cacheNeedsRepair($cache['apps'])
            && time() - (int) $cache['time'] < self::CACHE_TTL;
    }

    /**
     * @param mixed $apps
     * @return bool
     */
    private static function cacheNeedsRepair(mixed $apps): bool
    {
        if (!is_array($apps)) {
            return true;
        }

        foreach ($apps as $app) {
            if (!is_array($app)) {
                return true;
            }

            $tag = is_array($app['tags'] ?? null) ? ($app['tags'][0] ?? null) : null;
            $latestTag = is_array($app['latestTag'] ?? null) ? $app['latestTag'] : null;
            $hasVersion = '' !== self::versionName(
                $tag['name'] ?? $latestTag['name'] ?? $app['latestVersion'] ?? ''
            );
            $hasPackage = '' !== trim((string) (
                $tag['downloadUrl']
                ?? $latestTag['downloadUrl']
                ?? $app['package']
                ?? $app['downloadUrl']
                ?? ''
            ));

            if (!$hasVersion || !$hasPackage) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array $cache
     * @throws \Typecho\Db\Exception
     */
    private static function saveCache(array $cache)
    {
        $db = Db::get();
        $json = json_encode($cache);
        $compressed = false !== $json && function_exists('gzcompress') ? gzcompress($json, 6) : false;
        $value = false !== $compressed ? 'gz:' . base64_encode($compressed) : (string) $json;
        $exists = $db->fetchRow($db->select('name')->from('table.options')
            ->where('name = ?', self::CACHE_OPTION)
            ->where('user = ?', 0));

        if ($exists) {
            $db->query($db->update('table.options')->rows(['value' => $value])
                ->where('name = ?', self::CACHE_OPTION)
                ->where('user = ?', 0));
        } else {
            $db->query($db->insert('table.options')->rows([
                'name' => self::CACHE_OPTION,
                'value' => $value,
                'user' => 0
            ]));
        }
    }

    /**
     * @return array
     * @throws \Exception
     */
    private static function fetchRemoteApps(): array
    {
        $client = HttpClient::get();
        if (!$client) {
            throw new \Exception(_t('当前环境缺少 curl，无法连接应用中心'));
        }

        $client->setTimeout(10)
            ->setHeader('Accept', 'application/json')
            ->setAgent('Typecho-World-AppMarket/' . Common::VERSION);
        $client->send(self::INDEX_URL);

        $status = $client->getResponseStatus();
        if ($status < 200 || $status >= 300) {
            throw new \Exception(_t('应用中心返回异常状态：%d', $status));
        }

        $payload = json_decode($client->getResponseBody(), true);
        if (!is_array($payload) || empty($payload['packages']) || !is_array($payload['packages'])) {
            throw new \Exception(_t('应用中心数据格式不正确'));
        }

        $apps = [];
        foreach ($payload['packages'] as $item) {
            if (!is_array($item)) {
                continue;
            }

            $app = self::mapPackage($item);
            if ($app) {
                $apps[] = $app;
            }
        }

        return $apps;
    }

    /**
     * @param array $item
     * @return array|null
     */
    private static function mapPackage(array $item): ?array
    {
        $type = (string) ($item['type'] ?? '');
        if (!in_array($type, ['plugin', 'theme', 'language'], true)) {
            return null;
        }

        $manifest = is_array($item['manifest'] ?? null) ? $item['manifest'] : [];
        $githubMeta = is_array($item['githubMeta'] ?? null) ? $item['githubMeta'] : [];
        $repoUrl = (string) ($item['repository'] ?? $item['repoUrl'] ?? '');
        $repoOwner = (string) ($item['repoOwner'] ?? '');
        $repoName = (string) ($item['repoName'] ?? '');

        if ('' === $repoOwner || '' === $repoName) {
            [$repoOwner, $repoName] = self::repoPartsFromUrl($repoUrl);
        }

        if ('' === $repoOwner || '' === $repoName) {
            return null;
        }

        $repoUrl = '' !== $repoUrl ? $repoUrl : sprintf('https://github.com/%s/%s', $repoOwner, $repoName);
        $installSlug = self::installSlug($type, $item, $repoOwner, $repoName, $manifest);
        if ('' === $installSlug) {
            return null;
        }

        $defaultBranch = (string) ($githubMeta['default_branch'] ?? $manifest['branch'] ?? 'main');
        $date = self::dateOnly((string) ($item['updated'] ?? $item['approvedAt'] ?? $item['updatedAt'] ?? $item['createdAt'] ?? ''));
        $compatibility = trim((string) ($item['compatibility'] ?? $manifest['compatibility'] ?? $item['compat']['typecho'] ?? ''));
        $coverUrl = (string) ($item['coverUrl'] ?? $manifest['screenshot'] ?? '');
        $name = (string) ($item['name'] ?? $manifest['name'] ?? $repoName);
        $description = (string) ($item['description'] ?? $manifest['description'] ?? '');
        $tags = self::tags($item, $repoOwner, $repoName);
        $latestTag = $tags[0] ?? null;
        $latestVersion = self::versionName($latestTag['name'] ?? $item['latestVersion'] ?? '');
        $downloadUrl = self::downloadUrl($item, $latestTag, $repoOwner, $repoName);

        return [
            'id' => (string) ($item['id'] ?? sprintf('%s/%s', $repoOwner, $repoName)),
            'type' => $type,
            'slug' => $installSlug,
            'marketSlug' => (string) ($item['marketSlug'] ?? $item['slug'] ?? ''),
            'name' => '' !== $name ? $name : $repoName,
            'icon' => (string) ($item['icon'] ?? self::iconText($name ?: $repoName)),
            'description' => '' !== $description ? $description : _t('这个作品暂时还没有补充说明。'),
            'author' => (string) ($item['author'] ?? $manifest['author'] ?? $repoOwner),
            'updated' => '' !== $date ? $date : date('Y-m-d'),
            'repository' => $repoUrl,
            'package' => $downloadUrl,
            'downloadUrl' => $downloadUrl,
            'latestTag' => $latestTag,
            'latestVersion' => $latestVersion,
            'keywords' => array_values(array_filter([
                $repoOwner,
                $repoName,
                $name,
                $compatibility,
                $item['authorLogin'] ?? null,
            ])),
            'installTo' => (string) ($item['installTo'] ?? self::installTo($type, $installSlug)),
            'preview' => 'theme' === $type ? $coverUrl : '',
            'compat' => self::compat($compatibility),
            'tags' => $tags,
            'language' => 'language' === $type ? [
                'locale' => $installSlug,
                'region' => $installSlug,
                'progress' => (string) ($manifest['progress'] ?? _t('未声明')),
            ] : null,
            'readme' => (string) ($item['readme'] ?? $item['readmeMarkdown'] ?? ''),
            'readmeHtml' => (string) ($item['readmeHtml'] ?? ''),
            'readmeSyncedAt' => (string) ($item['readmeSyncedAt'] ?? ''),
            'syncedAt' => (string) ($item['syncedAt'] ?? ''),
            'syncError' => (string) ($item['syncError'] ?? ''),
            'apiUrl' => (string) (
                $item['apiUrl']
                ?? sprintf('%s/api/v1/app-market/packages/%s', self::MARKET_BASE, $item['marketSlug'] ?? $item['slug'] ?? '')
            ),
            'remote' => [
                'source' => self::MARKET_BASE,
                'repoOwner' => $repoOwner,
                'repoName' => $repoName,
                'defaultBranch' => $defaultBranch,
            ],
        ];
    }

    /**
     * @param string $type
     * @param array $item
     * @param string $repoOwner
     * @param string $repoName
     * @param array $manifest
     * @return string
     */
    private static function installSlug(string $type, array $item, string $repoOwner, string $repoName, array $manifest): string
    {
        $slug = (string) ($manifest['installSlug'] ?? $item['slug'] ?? $manifest['slug'] ?? '');

        if ('' === $slug && !empty($item['installTo'])) {
            $slug = basename((string) $item['installTo']);
            if ('language' === $type) {
                $slug = preg_replace('/\.mo$/i', '', $slug) ?: $slug;
            }
        }

        if ('' === $slug && 'language' !== $type) {
            $slug = $repoOwner . '-' . $repoName;
        }

        if ('' === $slug) {
            $slug = $repoName;
        }

        if ('language' === $type) {
            return Language::normalizeName($slug);
        }

        return self::safeName($slug);
    }

    /**
     * @param string $type
     * @param string $slug
     * @return string
     */
    private static function installTo(string $type, string $slug): string
    {
        return match ($type) {
            'plugin' => 'usr/plugins/' . $slug,
            'theme' => 'usr/themes/' . $slug,
            'language' => 'usr/langs/' . $slug . '.mo',
            default => 'usr/' . $slug,
        };
    }

    /**
     * @param array $item
     * @param array|null $latestTag
     * @return string
     */
    private static function downloadUrl(array $item, ?array $latestTag, string $repoOwner, string $repoName): string
    {
        $packageUrl = (string) (
            $latestTag['downloadUrl']
            ?? $item['package']
            ?? $item['downloadUrl']
            ?? ''
        );

        if (preg_match('/^https:\/\/(typecho\.world|github\.com|codeload\.github\.com)\//i', $packageUrl)) {
            return $packageUrl;
        }

        $tagName = self::versionName($latestTag['name'] ?? $item['latestVersion'] ?? '');
        if ('' !== $tagName && '' !== $repoOwner && '' !== $repoName) {
            return sprintf(
                'https://codeload.github.com/%s/%s/zip/refs/tags/%s',
                rawurlencode($repoOwner),
                rawurlencode($repoName),
                str_replace('%2F', '/', rawurlencode($tagName))
            );
        }

        return '';
    }

    /**
     * @param array $item
     * @return array
     */
    private static function tags(array $item, string $repoOwner = '', string $repoName = ''): array
    {
        $tags = [];
        foreach (($item['tags'] ?? []) as $tag) {
            if (!is_array($tag)) {
                continue;
            }

            $name = self::versionName($tag['name'] ?? '');
            if ('' === $name) {
                continue;
            }

            $downloadUrl = (string) ($tag['downloadUrl'] ?? '');
            if ('' === trim($downloadUrl) && '' !== $repoOwner && '' !== $repoName) {
                $downloadUrl = sprintf(
                    'https://codeload.github.com/%s/%s/zip/refs/tags/%s',
                    rawurlencode($repoOwner),
                    rawurlencode($repoName),
                    str_replace('%2F', '/', rawurlencode($name))
                );
            }

            $tags[] = [
                'name' => $name,
                'date' => self::dateOnly((string) ($tag['date'] ?? '')),
                'note' => (string) ($tag['note'] ?? ''),
                'downloadUrl' => $downloadUrl,
            ];
        }

        if (empty($tags) && !empty($item['latestTag']) && is_array($item['latestTag'])) {
            $tag = $item['latestTag'];
            $name = self::versionName($tag['name'] ?? $item['latestVersion'] ?? '');
            if ('' !== $name) {
                $downloadUrl = (string) ($tag['downloadUrl'] ?? $item['package'] ?? $item['downloadUrl'] ?? '');
                if ('' === trim($downloadUrl) && '' !== $repoOwner && '' !== $repoName) {
                    $downloadUrl = sprintf(
                        'https://codeload.github.com/%s/%s/zip/refs/tags/%s',
                        rawurlencode($repoOwner),
                        rawurlencode($repoName),
                        str_replace('%2F', '/', rawurlencode($name))
                    );
                }

                $tags[] = [
                    'name' => $name,
                    'date' => self::dateOnly((string) ($tag['date'] ?? '')),
                    'note' => (string) ($tag['note'] ?? ''),
                    'downloadUrl' => $downloadUrl,
                ];
            }
        }

        if (empty($tags)) {
            $name = self::versionName($item['latestVersion'] ?? '');
            $downloadUrl = trim((string) ($item['package'] ?? $item['downloadUrl'] ?? ''));
            if ('' !== $name || '' !== $downloadUrl) {
                if ('' === $downloadUrl && '' !== $name && '' !== $repoOwner && '' !== $repoName) {
                    $downloadUrl = sprintf(
                        'https://codeload.github.com/%s/%s/zip/refs/tags/%s',
                        rawurlencode($repoOwner),
                        rawurlencode($repoName),
                        str_replace('%2F', '/', rawurlencode($name))
                    );
                }

                $tags[] = [
                    'name' => '' !== $name ? $name : _t('最新版本'),
                    'date' => self::dateOnly((string) ($item['updated'] ?? $item['syncedAt'] ?? '')),
                    'note' => '',
                    'downloadUrl' => $downloadUrl,
                ];
            }
        }

        return $tags;
    }

    /**
     * @param mixed $value
     * @return string
     */
    private static function versionName(mixed $value): string
    {
        if (is_array($value)) {
            return trim((string) ($value['name'] ?? $value['tagName'] ?? $value['version'] ?? ''));
        }

        return trim((string) $value);
    }

    /**
     * @param array $app
     * @return array|null
     */
    private static function freshDetail(array $app): ?array
    {
        if (
            !empty($app['tags'])
            && ('' !== ($app['readme'] ?? '') || '' !== ($app['readmeHtml'] ?? '') || '' !== ($app['readmeSyncedAt'] ?? ''))
        ) {
            return $app;
        }

        $url = (string) ($app['apiUrl'] ?? '');
        $host = strtolower((string) (parse_url($url, PHP_URL_HOST) ?: ''));

        if ('typecho.world' !== $host) {
            return null;
        }

        try {
            $client = HttpClient::get();
            if (!$client) {
                return null;
            }

            $client->setTimeout(10)
                ->setHeader('Accept', 'application/json')
                ->setAgent('Typecho-World-AppMarket/' . Common::VERSION);
            $client->send($url);

            $status = $client->getResponseStatus();
            if ($status < 200 || $status >= 300) {
                return null;
            }

            $payload = json_decode($client->getResponseBody(), true);
            $item = is_array($payload['data'] ?? null) ? $payload['data'] : $payload;

            return is_array($item) ? self::mapPackage($item) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @param string $compatibility
     * @return array
     */
    private static function compat(string $compatibility): array
    {
        if ('' === $compatibility) {
            return [
                'status' => 'undeclared',
                'typecho' => _t('未声明'),
                'php' => _t('未声明'),
                'note' => _t('服务端尚未提供兼容声明。')
            ];
        }

        $major = explode('.', Common::VERSION)[0] . '.x';
        $status = false !== stripos($compatibility, $major) ? 'compatible' : 'possible';

        return [
            'status' => $status,
            'typecho' => $compatibility,
            'php' => _t('未声明'),
            'note' => _t('来自 Typecho World 的兼容声明。')
        ];
    }

    /**
     * @param string $url
     * @return array{0: string, 1: string}
     */
    private static function repoPartsFromUrl(string $url): array
    {
        $parts = parse_url($url);
        if (empty($parts['host']) || 'github.com' !== strtolower($parts['host']) || empty($parts['path'])) {
            return ['', ''];
        }

        $segments = array_values(array_filter(explode('/', trim($parts['path'], '/'))));
        if (count($segments) < 2) {
            return ['', ''];
        }

        return [$segments[0], preg_replace('/\.git$/i', '', $segments[1])];
    }

    /**
     * @param string $value
     * @return string
     */
    private static function safeName(string $value): string
    {
        $value = basename(str_replace('\\', '/', trim($value)));
        $value = preg_replace('/[^A-Za-z0-9_.-]+/', '-', $value) ?: '';
        return trim($value, '.-');
    }

    /**
     * @param string $value
     * @return string
     */
    private static function iconText(string $value): string
    {
        preg_match_all('/[A-Z]/', $value, $upper);
        if (count($upper[0]) >= 2) {
            return implode('', array_slice($upper[0], 0, 2));
        }

        preg_match_all('/[\p{L}\p{N}]/u', $value, $chars);
        $text = implode('', array_slice($chars[0] ?? [], 0, 2));
        return '' !== $text ? strtoupper($text) : 'TW';
    }

    /**
     * @param string $value
     * @return string
     */
    private static function dateOnly(string $value): string
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}/', $value, $match) ? $match[0] : '';
    }
}
