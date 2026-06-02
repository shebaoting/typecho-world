<?php

namespace Utils;

use Typecho\Common;
use Typecho\Http\Client;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Typecho World 在线更新工具
 */
class SystemUpdater
{
    public const VERSION_ENDPOINT = 'https://typecho.world/api/v1/typecho-world/latest.json';

    private const MAX_PACKAGE_SIZE = 80 * 1024 * 1024;

    /**
     * @return array
     * @throws \Exception
     */
    public static function check(): array
    {
        $latest = self::fetchLatest();
        $latestVersion = self::normalizeVersion((string) ($latest['version'] ?? $latest['tagName'] ?? ''));
        $currentVersion = Common::VERSION;
        $available = '' !== $latestVersion && version_compare($latestVersion, $currentVersion, '>');

        return [
            'available' => $available ? 1 : 0,
            'current' => $currentVersion,
            'latest' => $latestVersion,
            'tag' => (string) ($latest['tagName'] ?? ''),
            'downloadUrl' => (string) ($latest['downloadUrl'] ?? ''),
            'releaseUrl' => (string) ($latest['releaseUrl'] ?? 'https://typecho.world/download/'),
            'endpoint' => self::VERSION_ENDPOINT,
            'raw' => $latest,
        ];
    }

    /**
     * @return array
     * @throws \Exception
     */
    public static function installLatest(): array
    {
        $info = self::check();
        if (empty($info['available'])) {
            throw new \Exception(_t('当前已经是最新版本'));
        }

        $downloadUrl = (string) $info['downloadUrl'];
        if ('' === $downloadUrl || !self::isAllowedPackageUrl($downloadUrl)) {
            throw new \Exception(_t('更新包地址不受信任，已停止升级'));
        }

        if (!class_exists('\ZipArchive')) {
            throw new \Exception(_t('当前环境缺少 ZipArchive，无法解压更新包'));
        }

        if (!extension_loaded('curl')) {
            throw new \Exception(_t('当前环境缺少 curl，无法下载更新包'));
        }

        $workspace = self::createTemporaryDirectory('typecho-world-update-');

        try {
            $zipFile = $workspace . '/typecho-world.zip';
            $extractDir = $workspace . '/extract';
            self::downloadFile($downloadUrl, $zipFile);
            self::extractZip($zipFile, $extractDir);
            $sourceRoot = self::selectPackageRoot($extractDir);
            $backupRoot = self::backupRoot((string) $info['tag']);

            self::mergeDirectory($sourceRoot, __TYPECHO_ROOT_DIR__, $backupRoot);
        } catch (\Exception $e) {
            if (!empty($backupRoot ?? '')) {
                self::restoreBackup($backupRoot, __TYPECHO_ROOT_DIR__);
            }

            throw $e;
        } finally {
            self::removePath($workspace);
        }

        return [
            'version' => $info['latest'],
            'tag' => $info['tag'],
            'backup' => $backupRoot ?? '',
        ];
    }

    /**
     * @return array
     * @throws \Exception
     */
    private static function fetchLatest(): array
    {
        $client = Client::get();
        if (!$client) {
            throw new \Exception(_t('当前环境缺少 curl，无法检查最新版本'));
        }

        $client->setHeader('Accept', 'application/json')
            ->setHeader('User-Agent', 'Typecho-World-Updater/' . Common::VERSION)
            ->setTimeout(12)
            ->send(self::VERSION_ENDPOINT);

        $status = $client->getResponseStatus();
        if ($status < 200 || $status >= 300) {
            throw new \Exception(_t('版本服务返回异常状态：%d', $status));
        }

        $json = json_decode($client->getResponseBody(), true);
        $latest = is_array($json) ? ($json['latest'] ?? null) : null;
        if (!is_array($latest)) {
            throw new \Exception(_t('版本服务数据格式不正确'));
        }

        return $latest;
    }

    private static function normalizeVersion(string $version): string
    {
        $version = trim($version);
        $version = preg_replace('/^typecho-world[-_]?/i', '', $version) ?: $version;
        return ltrim($version, 'vV');
    }

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
     * @throws \Exception
     */
    private static function createTemporaryDirectory(string $prefix): string
    {
        $path = tempnam(sys_get_temp_dir(), $prefix);
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
     * @throws \Exception
     */
    private static function downloadFile(string $url, string $target)
    {
        $file = @fopen($target, 'wb');
        if (!$file) {
            throw new \Exception(_t('无法写入更新包'));
        }

        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_FILE, $file);
        curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($handle, CURLOPT_MAXREDIRS, 3);
        curl_setopt($handle, CURLOPT_TIMEOUT, 90);
        curl_setopt($handle, CURLOPT_USERAGENT, 'Typecho-World-Updater/' . Common::VERSION);
        curl_setopt($handle, CURLOPT_HTTPHEADER, ['Accept: application/zip, application/octet-stream']);

        $ok = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $error = curl_error($handle);
        curl_close($handle);
        fclose($file);

        if (!$ok || $status < 200 || $status >= 300) {
            @unlink($target);
            throw new \Exception($error ?: _t('更新包下载失败'));
        }

        $size = file_exists($target) ? filesize($target) : 0;
        if ($size <= 0) {
            @unlink($target);
            throw new \Exception(_t('更新包为空'));
        }

        if ($size > self::MAX_PACKAGE_SIZE) {
            @unlink($target);
            throw new \Exception(_t('更新包超过大小限制'));
        }
    }

    /**
     * @throws \Exception
     */
    private static function extractZip(string $zipFile, string $target)
    {
        if (!@mkdir($target, 0755, true) && !is_dir($target)) {
            throw new \Exception(_t('无法创建解压目录'));
        }

        $zip = new \ZipArchive();
        if (true !== $zip->open($zipFile)) {
            throw new \Exception(_t('更新包解压失败'));
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
                    throw new \Exception(_t('更新包路径校验失败'));
                }
            }

            if (!$zip->extractTo($target)) {
                throw new \Exception(_t('更新包解压失败'));
            }
        } finally {
            $zip->close();
        }
    }

    /**
     * @throws \Exception
     */
    private static function selectPackageRoot(string $extractDir): string
    {
        $items = array_values(array_filter(scandir($extractDir) ?: [], static fn($item) => !in_array($item, ['.', '..'], true)));
        $root = 1 === count($items) && is_dir($extractDir . '/' . $items[0])
            ? $extractDir . '/' . $items[0]
            : $extractDir;

        if (!file_exists($root . '/index.php') || !file_exists($root . '/var/Typecho/Common.php')) {
            throw new \Exception(_t('更新包结构不正确'));
        }

        return $root;
    }

    /**
     * @throws \Exception
     */
    private static function backupRoot(string $tag): string
    {
        $safeTag = preg_replace('/[^A-Za-z0-9_.-]+/', '-', $tag) ?: 'release';
        $path = __TYPECHO_ROOT_DIR__ . '/usr/backups/core-update-' . $safeTag . '-' . date('YmdHis');

        if (!@mkdir($path, 0755, true) && !is_dir($path)) {
            throw new \Exception(_t('无法创建升级备份目录'));
        }

        return $path;
    }

    /**
     * @throws \Exception
     */
    private static function mergeDirectory(string $source, string $targetRoot, string $backupRoot, string $relative = '')
    {
        $items = scandir($source);
        if (false === $items) {
            throw new \Exception(_t('无法读取更新包目录'));
        }

        foreach ($items as $item) {
            if ('.' === $item || '..' === $item) {
                continue;
            }

            $sourcePath = $source . '/' . $item;
            if (is_link($sourcePath)) {
                throw new \Exception(_t('更新包包含不安全的链接文件'));
            }

            $nextRelative = '' === $relative ? $item : $relative . '/' . $item;
            $isDir = is_dir($sourcePath);
            if (self::shouldSkip($nextRelative, $isDir)) {
                continue;
            }

            $targetPath = $targetRoot . '/' . $nextRelative;

            if ($isDir) {
                if (file_exists($targetPath) && !is_dir($targetPath)) {
                    self::backupExistingPath($targetPath, $backupRoot, $nextRelative);
                    self::removePath($targetPath);
                }

                if (!@mkdir($targetPath, 0755, true) && !is_dir($targetPath)) {
                    throw new \Exception(_t('无法创建目录：%s', $nextRelative));
                }

                self::mergeDirectory($sourcePath, $targetRoot, $backupRoot, $nextRelative);
                continue;
            }

            $parent = dirname($targetPath);
            if (!@mkdir($parent, 0755, true) && !is_dir($parent)) {
                throw new \Exception(_t('无法创建目录：%s', dirname($nextRelative)));
            }

            if (file_exists($targetPath)) {
                self::backupExistingPath($targetPath, $backupRoot, $nextRelative);
            }

            if (!@copy($sourcePath, $targetPath)) {
                throw new \Exception(_t('无法更新文件：%s', $nextRelative));
            }
        }
    }

    private static function shouldSkip(string $relative, bool $isDir): bool
    {
        $relative = trim(str_replace('\\', '/', $relative), '/');

        if ('' === $relative) {
            return false;
        }

        if (preg_match('/(^|\/)\.git(\/|$)/', $relative)) {
            return true;
        }

        if ('config.inc.php' === $relative || str_starts_with($relative, 'website/')) {
            return true;
        }

        foreach (['usr/uploads', 'usr/cache', 'usr/backups'] as $path) {
            if ($relative === $path || str_starts_with($relative, $path . '/')) {
                return true;
            }
        }

        if (preg_match('/^usr\/[^\/]+\.db$/', $relative)) {
            return true;
        }

        if ($relative === 'usr/plugins' || $relative === 'usr/themes') {
            return false;
        }

        if (str_starts_with($relative, 'usr/plugins/') && !self::isPathOrChild($relative, 'usr/plugins/AppMarket')) {
            return true;
        }

        if (
            str_starts_with($relative, 'usr/themes/')
            && !self::isPathOrChild($relative, 'usr/themes/default')
            && !self::isPathOrChild($relative, 'usr/themes/classic-22')
        ) {
            return true;
        }

        return false;
    }

    private static function isPathOrChild(string $relative, string $path): bool
    {
        return $relative === $path || str_starts_with($relative, $path . '/');
    }

    /**
     * @throws \Exception
     */
    private static function backupExistingPath(string $path, string $backupRoot, string $relative)
    {
        $backupPath = $backupRoot . '/' . $relative;
        $parent = dirname($backupPath);

        if (!@mkdir($parent, 0755, true) && !is_dir($parent)) {
            throw new \Exception(_t('无法创建备份目录：%s', dirname($relative)));
        }

        if (is_dir($path)) {
            if (!is_dir($backupPath) && !@mkdir($backupPath, 0755, true)) {
                throw new \Exception(_t('无法创建备份目录：%s', $relative));
            }
            return;
        }

        if (!@copy($path, $backupPath)) {
            throw new \Exception(_t('无法备份文件：%s', $relative));
        }
    }

    private static function restoreBackup(string $backupRoot, string $targetRoot)
    {
        if (!is_dir($backupRoot)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($backupRoot, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relative = substr($item->getPathname(), strlen($backupRoot) + 1);
            $target = $targetRoot . '/' . $relative;

            if ($item->isDir()) {
                @mkdir($target, 0755, true);
                continue;
            }

            @mkdir(dirname($target), 0755, true);
            @copy($item->getPathname(), $target);
        }
    }

    private static function removePath(string $path)
    {
        if (is_file($path) || is_link($path)) {
            @unlink($path);
            return;
        }

        if (!is_dir($path)) {
            return;
        }

        $items = array_diff(scandir($path) ?: [], ['.', '..']);
        foreach ($items as $item) {
            self::removePath($path . '/' . $item);
        }

        @rmdir($path);
    }
}
