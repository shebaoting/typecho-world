<?php

namespace TypechoPlugin\ContentTools;

use Typecho\Plugin\PluginInterface;
use Typecho\Widget\Helper\Form;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 内容工具箱
 *
 * 提供写作后台的内容检查与提示示例。
 *
 * @package ContentTools
 * @author Typecho Lab
 * @version 1.2.0
 * @since 1.3.0
 * @link https://example.com/typecho/content-tools
 */
class Plugin implements PluginInterface
{
    public static function activate()
    {
        return _t('内容工具箱已启用');
    }

    public static function deactivate()
    {
    }

    public static function config(Form $form)
    {
    }

    public static function personalConfig(Form $form)
    {
    }
}
