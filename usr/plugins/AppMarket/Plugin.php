<?php

namespace TypechoPlugin\AppMarket;

use Typecho\Common;
use Typecho\Plugin\PluginInterface;
use Typecho\Widget\Helper\Form;
use Utils\Helper;
use Widget\Options;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 应用市场
 *
 * 为后台应用管理提供插件、外观和语言包的浏览、搜索、详情与安装入口。
 *
 * @package 应用市场
 * @author Typecho World
 * @version 1.0.9
 * @since 1.3.10
 * @link https://typecho.world
 */
class Plugin implements PluginInterface
{
    public const PANEL = 'AppMarket/panel.php';

    public const VERSION = '1.0.9';

    /**
     * 启用插件
     */
    public static function activate()
    {
        Helper::removePanel(1, self::PANEL);
        Helper::addPanel(1, self::PANEL, _t('应用中心'), _t('应用中心'), 'administrator', 'silent');

        \Typecho\Plugin::factory('admin/header.php')->header = __CLASS__ . '::header';
        \Typecho\Plugin::factory('admin/page-title.php')->afterTitle = __CLASS__ . '::renderTitleBadge';

        return _t('应用市场已启用');
    }

    /**
     * 禁用插件
     */
    public static function deactivate()
    {
        Helper::removePanel(1, self::PANEL);
    }

    /**
     * 插件配置
     *
     * @param Form $form
     */
    public static function config(Form $form)
    {
    }

    /**
     * 个人配置
     *
     * @param Form $form
     */
    public static function personalConfig(Form $form)
    {
    }

    /**
     * 注入后台样式
     *
     * @param string $header
     * @return string
     */
    public static function header(string $header): string
    {
        $style = self::assetUrl('assets/market.css?v=' . self::VERSION);

        return $header . "\n" . '<link rel="stylesheet" href="' . htmlspecialchars($style, ENT_QUOTES) . '">';
    }

    /**
     * 应用管理标题右侧徽标
     *
     * @param mixed $menu
     */
    public static function renderTitleBadge($menu)
    {
        if (!method_exists($menu, 'getCurrentMenuUrl')) {
            return;
        }

        $currentMenuUrl = rawurldecode($menu->getCurrentMenuUrl());

        if ('applications.php' === $currentMenuUrl) {
            echo '<a class="app-market-title-badge" href="'
                . htmlspecialchars(Helper::url(self::PANEL), ENT_QUOTES)
                . '">' . _t('市场') . '</a>';
            return;
        }

        if ('extending.php?panel=' . self::PANEL === $currentMenuUrl) {
            $options = Options::alloc();
            echo '<a class="app-market-title-back" href="'
                . htmlspecialchars(Common::url('applications.php', $options->adminUrl), ENT_QUOTES)
                . '" title="' . _t('返回应用管理') . '" aria-label="' . _t('返回应用管理') . '">'
                . '<span aria-hidden="true">←</span></a>';
        }
    }

    /**
     * 获取后台当前域名下的插件资源地址
     *
     * @param string $path
     * @return string
     */
    private static function assetUrl(string $path): string
    {
        $options = Options::alloc();
        $baseUrl = defined('__TYPECHO_PLUGIN_URL__')
            ? __TYPECHO_PLUGIN_URL__
            : Common::url(__TYPECHO_PLUGIN_DIR__, $options->rootUrl);

        return Common::url('AppMarket/' . ltrim($path, '/'), $baseUrl);
    }
}
