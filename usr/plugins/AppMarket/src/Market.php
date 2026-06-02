<?php

namespace TypechoPlugin\AppMarket;

use Typecho\Common;
use Typecho\Db;
use Typecho\Language;
use Typecho\Plugin as TypechoPlugin;
use Widget\Options;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class Market
{
    private const STATE_OPTION = 'appMarketState';

    /**
     * @return array
     */
    public static function state(): array
    {
        $db = Db::get();
        $row = $db->fetchRow($db->select('value')->from('table.options')
            ->where('name = ?', self::STATE_OPTION)
            ->where('user = ?', 0));

        if (empty($row['value'])) {
            return ['installed' => []];
        }

        $state = json_decode($row['value'], true);
        return is_array($state) ? $state + ['installed' => []] : ['installed' => []];
    }

    /**
     * @param array $state
     * @throws \Typecho\Db\Exception
     */
    public static function saveState(array $state)
    {
        $db = Db::get();
        $value = json_encode($state);
        $exists = $db->fetchRow($db->select('name')->from('table.options')
            ->where('name = ?', self::STATE_OPTION)
            ->where('user = ?', 0));

        if ($exists) {
            $db->query($db->update('table.options')->rows(['value' => $value])
                ->where('name = ?', self::STATE_OPTION)
                ->where('user = ?', 0));
        } else {
            $db->query($db->insert('table.options')->rows([
                'name'  => self::STATE_OPTION,
                'value' => $value,
                'user'  => 0
            ]));
        }
    }

    /**
     * @param array $app
     * @return array
     */
    public static function decorate(array $app): array
    {
        $state = self::state();
        $options = Options::alloc();
        $latestTag = self::latestTag($app);
        $package = trim((string) ($app['package'] ?? $app['downloadUrl'] ?? ''));
        if ('' === $package && !empty($latestTag['downloadUrl'])) {
            $package = trim((string) $latestTag['downloadUrl']);
        }

        if ('' !== $package) {
            $app['package'] = $package;
            $app['downloadUrl'] = $package;
        }

        if (empty($app['tags']) && $latestTag) {
            $app['tags'] = [$latestTag];
        }

        $local = self::localInfo($app, $state, $options);
        $installedVersion = $local['version'] ?: ($app['localVersion'] ?? '');
        $latestVersion = $latestTag['name'] ?? '';
        $updateAvailable = $local['installed']
            && '' !== $installedVersion
            && '' !== $latestVersion
            && !self::sameVersion($latestVersion, $installedVersion);

        $status = self::status($app, $local['installed'], $updateAvailable);

        return array_merge($app, [
            'listed'           => $app['listed'] ?? true,
            'package'          => $package,
            'downloadUrl'      => $package,
            'latestTag'        => $latestTag,
            'latestVersion'    => $latestVersion,
            'installed'        => $local['installed'],
            'installedVersion' => $installedVersion,
            'enabled'          => $local['enabled'],
            'status'           => $status,
            'updateAvailable'  => $updateAvailable,
            'targetPath'       => self::targetPath($app),
        ]);
    }

    /**
     * @param array $apps
     * @param string $type
     * @param string $status
     * @param string $query
     * @return array
     */
    public static function filter(array $apps, string $type, string $status, string $query): array
    {
        $query = trim($query);
        $result = [];

        foreach ($apps as $app) {
            if (!($app['listed'] ?? true)) {
                continue;
            }

            $app = self::decorate($app);

            if ('all' !== $type && $app['type'] !== $type) {
                continue;
            }

            if (!self::matchStatus($app, $status)) {
                continue;
            }

            if ('' !== $query && !self::matchQuery($app, $query)) {
                continue;
            }

            $result[] = $app;
        }

        return $result;
    }

    /**
     * @param array $app
     * @return array|null
     */
    public static function latestTag(array $app): ?array
    {
        if (!empty($app['tags'])) {
            $tag = $app['tags'][0];
            if (is_array($tag)) {
                return self::normalizeTag($tag, $app);
            }
        }

        if (!empty($app['latestTag']) && is_array($app['latestTag'])) {
            return self::normalizeTag($app['latestTag'], $app);
        }

        $version = self::versionText($app['latestVersion'] ?? '');
        $downloadUrl = trim((string) ($app['downloadUrl'] ?? $app['package'] ?? ''));
        if ('' !== $version || '' !== $downloadUrl) {
            return [
                'name' => '' !== $version ? $version : _t('最新版本'),
                'date' => (string) ($app['updated'] ?? ''),
                'note' => '',
                'downloadUrl' => $downloadUrl,
            ];
        }

        return null;
    }

    /**
     * @param array $tag
     * @param array $app
     * @return array
     */
    private static function normalizeTag(array $tag, array $app): array
    {
        $version = trim((string) ($tag['name'] ?? ''));
        if ('' === $version) {
            $version = self::versionText($app['latestVersion'] ?? '');
        }

        $downloadUrl = trim((string) ($tag['downloadUrl'] ?? ''));
        if ('' === $downloadUrl) {
            $downloadUrl = trim((string) ($app['downloadUrl'] ?? $app['package'] ?? ''));
        }

        return [
            'name' => '' !== $version ? $version : _t('最新版本'),
            'date' => (string) ($tag['date'] ?? $app['updated'] ?? ''),
            'note' => (string) ($tag['note'] ?? ''),
            'downloadUrl' => $downloadUrl,
        ];
    }

    /**
     * @param mixed $value
     * @return string
     */
    private static function versionText(mixed $value): string
    {
        if (is_array($value)) {
            return trim((string) ($value['name'] ?? $value['version'] ?? $value['tagName'] ?? ''));
        }

        return trim((string) $value);
    }

    /**
     * @param string $type
     * @return string
     */
    public static function typeLabel(string $type): string
    {
        return [
            'plugin'   => _t('插件'),
            'theme'    => _t('外观'),
            'language' => _t('语言包'),
        ][$type] ?? $type;
    }

    /**
     * @param string $status
     * @return string
     */
    public static function compatibilityLabel(string $status): string
    {
        return [
            'compatible'   => _t('兼容'),
            'possible'     => _t('可能兼容'),
            'incompatible' => _t('不兼容'),
            'undeclared'   => _t('未声明'),
        ][$status] ?? _t('未声明');
    }

    /**
     * @param string $status
     * @return string
     */
    public static function buttonLabel(string $status): string
    {
        return [
            'installable'  => _t('安装'),
            'installed'    => _t('已安装'),
            'update'       => _t('更新'),
            'incompatible' => _t('不可安装'),
            'no-version'   => _t('不可安装'),
            'removed'      => _t('已下架'),
        ][$status] ?? _t('安装');
    }

    /**
     * @param array $app
     * @return string
     */
    public static function targetPath(array $app): string
    {
        return match ($app['type']) {
            'plugin'   => __TYPECHO_ROOT_DIR__ . __TYPECHO_PLUGIN_DIR__ . '/' . $app['slug'],
            'theme'    => __TYPECHO_ROOT_DIR__ . __TYPECHO_THEME_DIR__ . '/' . $app['slug'],
            'language' => Language::file($app['slug']),
            default    => __TYPECHO_ROOT_DIR__ . '/usr/' . $app['slug'],
        };
    }

    /**
     * @param array $app
     * @param string $mode
     * @return array
     * @throws \Exception
     */
    public static function install(array $app, string $mode): array
    {
        $app = self::decorate($app);

        if (!($app['listed'] ?? true)) {
            throw new \Exception(_t('应用已下架'));
        }

        if ('incompatible' === ($app['compat']['status'] ?? 'undeclared')) {
            throw new \Exception(_t('该应用暂不兼容当前版本'));
        }

        if (empty($app['latestTag']) && empty($app['package'])) {
            throw new \Exception(_t('该应用需要发布版本后才能安装'));
        }

        if (empty($app['package'])) {
            throw new \Exception(_t('没有可安装版本'));
        }

        $prepared = self::preparePackageSource($app);
        $source = $prepared['source'];
        $cleanup = $prepared['cleanup'];

        $target = self::targetPath($app);
        $backup = null;
        $parent = dirname($target);

        if (!is_dir($parent) && !@mkdir($parent, 0755, true)) {
            throw new \Exception(_t('安装失败，目标目录无法写入'));
        }

        if (!is_writable($parent)) {
            throw new \Exception(_t('安装失败，目标目录无法写入'));
        }

        try {
            if (file_exists($target)) {
                $backup = $target . '.app-market-backup-' . date('YmdHis');
                if (!@rename($target, $backup)) {
                    throw new \Exception(_t('校验失败，已停止安装'));
                }
            }

            if (is_dir($source)) {
                self::copyDirectory($source, $target);
            } else {
                if (!@copy($source, $target)) {
                    throw new \Exception(_t('解压失败'));
                }
            }

            self::markInstalled($app);

            if ($backup) {
                self::removePath($backup);
            }
        } catch (\Exception $e) {
            if (file_exists($target)) {
                self::removePath($target);
            }

            if ($backup && file_exists($backup)) {
                @rename($backup, $target);
            }

            throw new \Exception('update' === $mode ? _t('更新失败，已保留原版本') : _t('安装失败，原文件未被修改'));
        } finally {
            if ($cleanup && file_exists($cleanup)) {
                self::removePath($cleanup);
            }
        }

        return [
            'message' => 'update' === $mode ? _t('更新完成') : _t('安装完成'),
            'actions' => self::completionActions($app)
        ];
    }

    /**
     * @param array $app
     * @return array
     */
    public static function completionActions(array $app): array
    {
        $options = Options::alloc();
        $security = \Widget\Security::alloc();

        return match ($app['type']) {
            'plugin' => [
                [
                    'label' => _t('启用插件'),
                    'url'   => $security->getIndex('/action/plugins-edit?activate=' . rawurlencode($app['slug']))
                ],
                ['label' => _t('稍后处理'), 'url' => '']
            ],
            'theme' => [
                [
                    'label' => _t('预览外观'),
                    'url'   => rtrim($options->siteUrl, '/') . '/?themePreview=' . rawurlencode($app['slug'])
                ],
                [
                    'label' => _t('启用外观'),
                    'url'   => $security->getIndex('/action/themes-edit?change=' . $app['slug'])
                ],
                ['label' => _t('稍后处理'), 'url' => '']
            ],
            'language' => [
                [
                    'label' => _t('启用语言'),
                    'url'   => $security->getIndex('/action/languages-edit?change=' . $app['slug'])
                ],
                ['label' => _t('稍后处理'), 'url' => '']
            ],
            default => []
        };
    }

    /**
     * @param array $app
     * @throws \Typecho\Db\Exception
     */
    private static function markInstalled(array $app)
    {
        $state = self::state();
        $state['installed'][$app['id']] = [
            'version' => $app['latestVersion'],
            'type'    => $app['type'],
            'slug'    => $app['slug'],
            'time'    => time()
        ];
        self::saveState($state);
    }

    /**
     * @param array $app
     * @param array $state
     * @param Options $options
     * @return array
     */
    private static function localInfo(array $app, array $state, Options $options): array
    {
        $record = $state['installed'][$app['id']] ?? null;
        $version = is_array($record) ? ($record['version'] ?? '') : '';
        $installed = false;
        $enabled = false;

        switch ($app['type']) {
            case 'plugin':
                $installed = is_dir(self::targetPath($app));
                $enabled = $installed && TypechoPlugin::exists($app['slug']);
                if ($installed && '' === $version) {
                    try {
                        [$pluginFile] = TypechoPlugin::portal($app['slug'], $options->pluginDir);
                        $info = TypechoPlugin::parseInfo($pluginFile);
                        $version = $info['version'] ?: ($app['localVersion'] ?? '');
                    } catch (\Exception $e) {
                        $version = $app['localVersion'] ?? '';
                    }
                }
                break;
            case 'theme':
                $installed = is_dir(self::targetPath($app));
                $enabled = $installed && $options->theme === $app['slug'];
                if ($installed && '' === $version) {
                    $index = self::targetPath($app) . '/index.php';
                    if (file_exists($index)) {
                        $info = TypechoPlugin::parseInfo($index);
                        $version = $info['version'] ?: ($app['localVersion'] ?? '');
                    }
                }
                break;
            case 'language':
                $installed = Language::isAvailable($app['slug']);
                $enabled = $installed && $options->lang === $app['slug'];
                if ($installed && '' === $version) {
                    $version = $app['localVersion'] ?? '';
                }
                break;
        }

        return [
            'installed' => $installed,
            'enabled'   => $enabled,
            'version'   => $version
        ];
    }

    /**
     * @param array $app
     * @param bool $installed
     * @param bool $updateAvailable
     * @return string
     */
    private static function status(array $app, bool $installed, bool $updateAvailable): string
    {
        if (!($app['listed'] ?? true)) {
            return 'removed';
        }

        if ('incompatible' === ($app['compat']['status'] ?? 'undeclared')) {
            return 'incompatible';
        }

        if (empty($app['tags']) && empty($app['package'])) {
            return 'no-version';
        }

        if ($updateAvailable) {
            return 'update';
        }

        if ($installed) {
            return 'installed';
        }

        return 'installable';
    }

    /**
     * @param array $app
     * @param string $status
     * @return bool
     */
    private static function matchStatus(array $app, string $status): bool
    {
        return match ($status) {
            'installable' => 'installable' === $app['status'],
            'installed'   => in_array($app['status'], ['installed', 'update'], true),
            'updates'     => 'update' === $app['status'],
            default       => true,
        };
    }

    /**
     * @param array $app
     * @param string $query
     * @return bool
     */
    private static function matchQuery(array $app, string $query): bool
    {
        $haystack = implode(' ', [
            $app['name'],
            $app['description'],
            $app['author'],
            implode(' ', $app['keywords'] ?? []),
        ]);

        return false !== stripos($haystack, $query);
    }

    /**
     * @param string $left
     * @param string $right
     * @return int
     */
    private static function compareVersion(string $left, string $right): int
    {
        return version_compare(ltrim($left, 'vV'), ltrim($right, 'vV'));
    }

    /**
     * @param string $left
     * @param string $right
     * @return bool
     */
    private static function sameVersion(string $left, string $right): bool
    {
        $left = trim($left);
        $right = trim($right);

        return $left === $right || ltrim($left, 'vV') === ltrim($right, 'vV');
    }

    /**
     * @param array $app
     * @return array{source: string, cleanup: ?string}
     * @throws \Exception
     */
    private static function preparePackageSource(array $app): array
    {
        $package = (string) ($app['package'] ?? '');

        if (preg_match('/^https:\/\//i', $package)) {
            return self::prepareRemotePackageSource($app, $package);
        }

        $source = __DIR__ . '/../' . $package;
        if (!file_exists($source)) {
            throw new \Exception(_t('下载失败，请稍后重试'));
        }

        return ['source' => $source, 'cleanup' => null];
    }

    /**
     * @param array $app
     * @param string $url
     * @return array{source: string, cleanup: string}
     * @throws \Exception
     */
    private static function prepareRemotePackageSource(array $app, string $url): array
    {
        if (!self::isAllowedPackageUrl($url)) {
            throw new \Exception(_t('下载地址不受信任，已停止安装'));
        }

        if (!class_exists('\ZipArchive')) {
            throw new \Exception(_t('当前环境缺少 ZipArchive，无法安装远程应用'));
        }

        $workspace = self::createTemporaryDirectory();

        try {
            $zipFile = $workspace . '/package.zip';
            $extractDir = $workspace . '/extract';

            self::downloadFile($url, $zipFile);

            if (!@mkdir($extractDir, 0755, true) && !is_dir($extractDir)) {
                throw new \Exception(_t('解压失败'));
            }

            self::extractZip($zipFile, $extractDir);
            $source = self::selectPackageRoot($app, $extractDir);

            return ['source' => $source, 'cleanup' => $workspace];
        } catch (\Exception $e) {
            self::removePath($workspace);
            throw $e;
        }
    }

    /**
     * @param string $url
     * @return bool
     */
    private static function isAllowedPackageUrl(string $url): bool
    {
        $host = strtolower((string) (parse_url($url, PHP_URL_HOST) ?: ''));

        return in_array($host, [
            'typecho.world',
            'github.com',
            'codeload.github.com',
        ], true);
    }

    /**
     * @return string
     * @throws \Exception
     */
    private static function createTemporaryDirectory(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'app-market-');
        if (false === $path) {
            throw new \Exception(_t('无法创建临时目录'));
        }

        @unlink($path);
        if (!@mkdir($path, 0755, true)) {
            throw new \Exception(_t('无法创建临时目录'));
        }

        return $path;
    }

    /**
     * @param string $url
     * @param string $target
     * @throws \Exception
     */
    private static function downloadFile(string $url, string $target)
    {
        if (!extension_loaded('curl')) {
            throw new \Exception(_t('当前环境缺少 curl，无法下载应用包'));
        }

        $file = @fopen($target, 'wb');
        if (!$file) {
            throw new \Exception(_t('下载失败，请稍后重试'));
        }

        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_FILE, $file);
        curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($handle, CURLOPT_MAXREDIRS, 3);
        curl_setopt($handle, CURLOPT_TIMEOUT, 45);
        curl_setopt($handle, CURLOPT_USERAGENT, 'Typecho-World-AppMarket');
        curl_setopt($handle, CURLOPT_HTTPHEADER, ['Accept: application/zip, application/octet-stream']);

        $ok = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $error = curl_error($handle);
        curl_close($handle);
        fclose($file);

        if (!$ok || $status < 200 || $status >= 300) {
            @unlink($target);
            throw new \Exception($error ?: _t('下载失败，请稍后重试'));
        }

        if (!file_exists($target) || filesize($target) <= 0) {
            throw new \Exception(_t('下载失败，请稍后重试'));
        }

        if (filesize($target) > 50 * 1024 * 1024) {
            @unlink($target);
            throw new \Exception(_t('应用包超过大小限制'));
        }
    }

    /**
     * @param string $zipFile
     * @param string $target
     * @throws \Exception
     */
    private static function extractZip(string $zipFile, string $target)
    {
        $zip = new \ZipArchive();
        if (true !== $zip->open($zipFile)) {
            throw new \Exception(_t('解压失败'));
        }

        try {
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $name = (string) $zip->getNameIndex($index);
                if (
                    str_starts_with($name, '/')
                    || str_starts_with($name, '\\')
                    || preg_match('/(^|\/)\.\.(\/|$)/', $name)
                    || preg_match('/^[A-Za-z]:/', $name)
                ) {
                    throw new \Exception(_t('校验失败，已停止安装'));
                }
            }

            if (!$zip->extractTo($target)) {
                throw new \Exception(_t('解压失败'));
            }
        } finally {
            $zip->close();
        }
    }

    /**
     * @param array $app
     * @param string $extractDir
     * @return string
     * @throws \Exception
     */
    private static function selectPackageRoot(array $app, string $extractDir): string
    {
        $root = self::singleExtractedRoot($extractDir);

        if ('language' === $app['type']) {
            return self::findLanguageFile($root, $app['slug']);
        }

        $requiredFile = 'plugin' === $app['type'] ? 'Plugin.php' : 'index.php';
        if (!file_exists($root . '/' . $requiredFile)) {
            throw new \Exception(_t('应用包结构不正确，缺少 %s', $requiredFile));
        }

        return $root;
    }

    /**
     * @param string $extractDir
     * @return string
     */
    private static function singleExtractedRoot(string $extractDir): string
    {
        $items = array_values(array_filter(scandir($extractDir) ?: [], static fn($item) => !in_array($item, ['.', '..'], true)));

        if (1 === count($items) && is_dir($extractDir . '/' . $items[0])) {
            return $extractDir . '/' . $items[0];
        }

        return $extractDir;
    }

    /**
     * @param string $root
     * @param string $slug
     * @return string
     * @throws \Exception
     */
    private static function findLanguageFile(string $root, string $slug): string
    {
        $preferred = $root . '/' . $slug . '.mo';
        if (file_exists($preferred)) {
            return $preferred;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'mo') {
                return $file->getPathname();
            }
        }

        throw new \Exception(_t('应用包结构不正确，缺少语言文件'));
    }

    /**
     * @param string $source
     * @param string $target
     * @throws \Exception
     */
    private static function copyDirectory(string $source, string $target)
    {
        if (!@mkdir($target, 0755, true) && !is_dir($target)) {
            throw new \Exception(_t('解压失败'));
        }

        $items = scandir($source);
        if (false === $items) {
            throw new \Exception(_t('校验失败，已停止安装'));
        }

        foreach ($items as $item) {
            if ('.' === $item || '..' === $item) {
                continue;
            }

            $from = $source . '/' . $item;
            $to = $target . '/' . $item;

            if (is_link($from)) {
                throw new \Exception(_t('校验失败，已停止安装'));
            } elseif (is_dir($from)) {
                self::copyDirectory($from, $to);
            } elseif (!@copy($from, $to)) {
                throw new \Exception(_t('解压失败'));
            }
        }
    }

    /**
     * @param string $path
     */
    private static function removePath(string $path)
    {
        if (is_file($path) || is_link($path)) {
            @unlink($path);
            return;
        }

        if (!is_dir($path)) {
            return;
        }

        $items = scandir($path);
        if (false !== $items) {
            foreach ($items as $item) {
                if ('.' !== $item && '..' !== $item) {
                    self::removePath($path . '/' . $item);
                }
            }
        }

        @rmdir($path);
    }
}
