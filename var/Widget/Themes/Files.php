<?php

namespace Widget\Themes;

use Typecho\Theme\Manifest;
use Typecho\Widget;
use Widget\Base;
use Widget\Options;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 风格文件列表组件
 *
 * @author qining
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Files extends Base
{
    /**
     * 当前风格
     *
     * @access private
     * @var string
     */
    private string $currentTheme;

    /**
     * 当前文件
     *
     * @access private
     * @var string
     */
    private string $currentFile;

    /**
     * 执行函数
     *
     * @throws Widget\Exception
     */
    public function execute()
    {
        /** 管理员权限 */
        $this->user->pass('administrator');
        $this->currentTheme = $this->request->filter('slug')->get('theme', Options::alloc()->theme);

        if (
            preg_match("/^([_0-9a-z-. ])+$/i", $this->currentTheme)
            && is_dir($dir = rtrim(Options::alloc()->themeFile($this->currentTheme), '/'))
            && (!defined('__TYPECHO_THEME_WRITEABLE__') || __TYPECHO_THEME_WRITEABLE__)
        ) {
            $files = $this->scanFiles($dir);

            $this->currentFile = Manifest::normalizePath($this->request->get('file', 'index.php')) ?? 'index.php';

            if (
                preg_match("/^([_0-9a-z-. \/])+$/i", $this->currentFile)
                && file_exists($dir . '/' . $this->currentFile)
            ) {
                foreach ($files as $file) {
                    if (file_exists($dir . '/' . $file)) {
                        $this->push([
                            'file'    => $file,
                            'theme'   => $this->currentTheme,
                            'current' => ($file == $this->currentFile)
                        ]);
                    }
                }

                return;
            }
        }

        throw new Widget\Exception('风格文件不存在', 404);
    }

    private function scanFiles(string $dir): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || !preg_match("/\.(php|js|css|vbs|json)$/i", $file->getFilename())) {
                continue;
            }

            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($dir) + 1));
            if (Manifest::normalizePath($relative) !== null) {
                $files[] = $relative;
            }
        }

        sort($files);
        return $files;
    }

    /**
     * 判断是否拥有写入权限
     *
     * @return bool
     */
    public static function isWriteable(): bool
    {
        return (!defined('__TYPECHO_THEME_WRITEABLE__') || __TYPECHO_THEME_WRITEABLE__)
            && !Options::alloc()->missingTheme;
    }

    /**
     * 获取菜单标题
     *
     * @return string
     */
    public function getMenuTitle(): string
    {
        return _t('编辑文件 %s', $this->currentFile);
    }

    /**
     * 获取文件内容
     *
     * @return string
     */
    public function currentContent(): string
    {
        return htmlspecialchars(file_get_contents(Options::alloc()
            ->themeFile($this->currentTheme, $this->currentFile)));
    }

    /**
     * 获取文件是否可读
     *
     * @return bool
     */
    public function currentIsWriteable(): bool
    {
        return is_writable(Options::alloc()
                ->themeFile($this->currentTheme, $this->currentFile))
            && self::isWriteable();
    }

    /**
     * 获取当前文件
     *
     * @return string
     */
    public function currentFile(): string
    {
        return $this->currentFile;
    }

    /**
     * 获取当前风格
     *
     * @return string
     */
    public function currentTheme(): string
    {
        return $this->currentTheme;
    }
}
