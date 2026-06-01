<?php

namespace Typecho;

use Typecho\I18n\GetText;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 语言包工具
 */
class Language
{
    public const DEFAULT_LANG = 'zh_CN';

    private const BUILTIN_LANGS = [
        'zh_CN' => [
            'title'       => '简体中文',
            'description' => 'Typecho 内置的默认中文语言包。'
        ],
        'en_US' => [
            'title'       => 'English',
            'description' => 'Typecho built-in English language pack.'
        ],
    ];

    /**
     * 获取语言包目录
     *
     * @return string
     */
    public static function dir(): string
    {
        return defined('__TYPECHO_LANG_DIR__') ? __TYPECHO_LANG_DIR__ : __TYPECHO_ROOT_DIR__ . '/usr/langs';
    }

    /**
     * 获取语言包文件路径
     *
     * @param string $name
     * @return string
     */
    public static function file(string $name): string
    {
        return self::dir() . '/' . self::normalizeName($name) . '.mo';
    }

    /**
     * 规范化语言名称
     *
     * @param string $name
     * @return string
     */
    public static function normalizeName(string $name): string
    {
        $name = basename(str_replace('\\', '/', trim($name)));

        if (strtolower(substr($name, -3)) == '.mo') {
            $name = substr($name, 0, -3);
        }

        return preg_match("/^[A-Za-z]{2,3}(?:_[A-Za-z0-9]{2,8})*$/", $name) ? $name : '';
    }

    /**
     * 是否为内置语言包
     *
     * @param string $name
     * @return bool
     */
    public static function isBuiltin(string $name): bool
    {
        return isset(self::BUILTIN_LANGS[$name]);
    }

    /**
     * 判断语言包是否可用
     *
     * @param string $name
     * @return bool
     */
    public static function isAvailable(string $name): bool
    {
        $name = self::normalizeName($name);
        return self::DEFAULT_LANG == $name || ('' != $name && self::isMoFile(self::file($name)));
    }

    /**
     * 获取语言包列表
     *
     * @return array
     */
    public static function scan(): array
    {
        $langs = [
            self::DEFAULT_LANG => self::buildInfo(self::DEFAULT_LANG, null, true)
        ];

        $files = glob(self::dir() . '/*.mo');
        if (!empty($files)) {
            foreach ($files as $file) {
                $name = self::normalizeName($file);
                if ('' == $name || !self::isMoFile($file)) {
                    continue;
                }

                $langs[$name] = self::buildInfo($name, $file, self::isBuiltin($name));
            }
        }

        ksort($langs);
        return $langs;
    }

    /**
     * 获取可用于选择的语言包
     *
     * @return array
     */
    public static function getLangs(): array
    {
        $langs = [];

        foreach (self::scan() as $name => $info) {
            $langs[$name] = $info['title'];
        }

        return $langs;
    }

    /**
     * 创建语言包目录
     *
     * @return bool
     */
    public static function prepareDir(): bool
    {
        $dir = self::dir();

        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
            return false;
        }

        return is_writable($dir);
    }

    /**
     * 判断文件是否为 Gettext MO 文件
     *
     * @param string $file
     * @return bool
     */
    public static function isMoFile(string $file): bool
    {
        $handle = @fopen($file, 'rb');
        if (!$handle) {
            return false;
        }

        $magic = bin2hex((string) fread($handle, 4));
        fclose($handle);

        return in_array($magic, ['de120495', '950412de'], true);
    }

    /**
     * 构造语言包信息
     *
     * @param string $name
     * @param string|null $file
     * @param bool $builtin
     * @return array
     */
    private static function buildInfo(string $name, ?string $file, bool $builtin): array
    {
        $base = self::BUILTIN_LANGS[$name] ?? [
            'title'       => $name,
            'description' => ''
        ];

        $info = [
            'name'        => $name,
            'title'       => $base['title'],
            'description' => $base['description'],
            'file'        => $file,
            'builtin'     => $builtin,
            'size'        => $file && file_exists($file) ? filesize($file) : null,
            'modified'    => $file && file_exists($file) ? filemtime($file) : null
        ];

        if ($file && file_exists($file)) {
            $getText = new GetText($file, false);
            if (0 == $getText->error) {
                $title = $getText->translate('lang', $count);
                if ($count > - 1 && '' != $title) {
                    $info['title'] = $title;
                }
            }
        }

        return $info;
    }
}
