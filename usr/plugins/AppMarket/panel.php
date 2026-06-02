<?php

use Typecho\Common;
use TypechoPlugin\AppMarket\Market;
use TypechoPlugin\AppMarket\Plugin as AppMarketPlugin;
use TypechoPlugin\AppMarket\Repository;
use Utils\Helper;
use Utils\Markdown;
use Widget\Notice;
use Widget\Options;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

require_once __DIR__ . '/src/Repository.php';
require_once __DIR__ . '/src/Market.php';

function app_market_admin_file(string $file): string
{
    $adminDir = defined('__TYPECHO_ADMIN_DIR__') ? trim(__TYPECHO_ADMIN_DIR__, '/') : 'admin';
    return __TYPECHO_ROOT_DIR__ . '/' . $adminDir . '/' . $file;
}

function app_market_url(array $params = []): string
{
    $options = Options::alloc();
    $params = ['panel' => AppMarketPlugin::PANEL] + $params;

    return Common::url('extending.php?' . http_build_query($params), $options->adminUrl);
}

function app_market_asset_url(string $path): string
{
    $options = Options::alloc();
    $baseUrl = defined('__TYPECHO_PLUGIN_URL__')
        ? __TYPECHO_PLUGIN_URL__
        : Common::url(__TYPECHO_PLUGIN_DIR__, $options->rootUrl);

    return Common::url('AppMarket/' . ltrim($path, '/'), $baseUrl);
}

function app_market_theme_asset_url(string $theme, string $path): string
{
    $options = Options::alloc();

    return Common::url(trim(__TYPECHO_THEME_DIR__, '/') . '/' . trim($theme, '/') . '/' . ltrim($path, '/'), $options->rootUrl);
}

function app_market_type_tabs(string $type, string $status, string $query): void
{
    $tabs = [
        'all'      => _t('全部'),
        'plugin'   => _t('插件'),
        'theme'    => _t('外观'),
        'language' => _t('语言包'),
    ];

    echo '<ul class="typecho-option-tabs fix-tabs app-market-tabs">';
    foreach ($tabs as $key => $label) {
        $url = app_market_url(array_filter([
            'type'   => $key,
            'status' => 'all' === $status ? null : $status,
            'q'      => '' === $query ? null : $query,
        ], static fn($value) => null !== $value));

        echo '<li' . ($type === $key ? ' class="current"' : '') . '><a href="'
            . htmlspecialchars($url, ENT_QUOTES) . '">' . htmlspecialchars($label) . '</a></li>';
    }
    echo '</ul>';
}

function app_market_status_tabs(string $type, string $status, string $query): void
{
    $tabs = [
        'all'         => _t('全部'),
        'installable' => _t('可安装'),
        'installed'   => _t('已安装'),
        'updates'     => _t('可更新'),
    ];

    echo '<ul class="typecho-option-tabs fix-tabs app-market-tabs app-market-status-tabs">';
    foreach ($tabs as $key => $label) {
        $url = app_market_url(array_filter([
            'type'   => 'all' === $type ? null : $type,
            'status' => $key,
            'q'      => '' === $query ? null : $query,
        ], static fn($value) => null !== $value));

        echo '<li' . ($status === $key ? ' class="current"' : '') . '><a href="'
            . htmlspecialchars($url, ENT_QUOTES) . '">' . htmlspecialchars($label) . '</a></li>';
    }
    echo '</ul>';
}

function app_market_search_form(string $type, string $status, string $query): void
{
    $options = Options::alloc();
    echo '<form class="app-market-search" action="' . htmlspecialchars(Common::url('extending.php', $options->adminUrl), ENT_QUOTES) . '" method="get">';
    echo '<input type="hidden" name="panel" value="' . htmlspecialchars(AppMarketPlugin::PANEL, ENT_QUOTES) . '">';
    echo '<input type="hidden" name="type" value="' . htmlspecialchars($type, ENT_QUOTES) . '">';
    echo '<input type="hidden" name="status" value="' . htmlspecialchars($status, ENT_QUOTES) . '">';
    echo '<input class="text" type="text" name="q" value="' . htmlspecialchars($query, ENT_QUOTES)
        . '" placeholder="' . _t('搜索插件、外观、语言包') . '">';
    echo '<button class="btn" type="submit">' . _t('筛选') . '</button>';
    if ('' !== $query) {
        echo '<a class="btn" href="' . htmlspecialchars(app_market_url([
            'type'   => $type,
            'status' => $status,
        ]), ENT_QUOTES) . '">' . _t('清除搜索') . '</a>';
    }
    echo '<a class="btn app-market-refresh" href="' . htmlspecialchars(app_market_url([
        'type'   => $type,
        'status' => $status,
        'q'      => $query,
        'refresh' => 1,
    ]), ENT_QUOTES) . '">' . _t('刷新') . '</a>';
    echo '</form>';
}

function app_market_render_controls(string $type, string $status, string $query): void
{
    echo '<section class="app-market-controls">';
    echo '<div class="app-market-controls-main">';
    echo '<div class="app-market-control-copy">';
    echo '<strong>' . _t('查找应用') . '</strong>';
    echo '<span>' . _t('搜索并安装网站外观、插件和语言包。') . '</span>';
    echo '</div>';
    app_market_search_form($type, $status, $query);
    echo '</div>';
    echo '<div class="app-market-controls-tabs">';
    echo '<div><span>' . _t('类型') . '</span>';
    app_market_type_tabs($type, $status, $query);
    echo '</div>';
    echo '<div><span>' . _t('状态') . '</span>';
    app_market_status_tabs($type, $status, $query);
    echo '</div>';
    echo '</div>';
    echo '</section>';
}

function app_market_badge(string $class, string $text): string
{
    return '<span class="app-market-badge ' . htmlspecialchars($class, ENT_QUOTES) . '">'
        . htmlspecialchars($text) . '</span>';
}

function app_market_enabled_label(array $app): string
{
    if (!$app['installed']) {
        return '';
    }

    return $app['enabled'] ? app_market_badge('is-enabled', _t('已启用')) : app_market_badge('is-muted', _t('未启用'));
}

function app_market_action_form(array $app, string $source = 'list'): void
{
    global $request, $security;

    $mode = 'update' === $app['status'] ? 'update' : 'install';
    $disabled = in_array($app['status'], ['installed', 'incompatible', 'no-version', 'removed'], true);
    $buttonLabel = Market::buttonLabel($app['status']);
    $latest = $app['latestVersion'] ?: _t('暂无发布版本');
    $current = $app['installedVersion'] ?: _t('未安装');
    $message = 'update' === $mode
        ? _t('将从 %s 更新到 %s', $current, $latest)
        : _t('将安装最新版本 %s', $latest);

    echo '<form class="app-market-action-form" action="' . htmlspecialchars($request->getRequestUrl(), ENT_QUOTES)
        . '" method="post">';
    echo '<input type="hidden" name="_" value="' . htmlspecialchars($security->getToken($request->getRequestUrl()), ENT_QUOTES) . '">';
    echo '<input type="hidden" name="app_market_action" value="' . htmlspecialchars($mode, ENT_QUOTES) . '">';
    echo '<input type="hidden" name="id" value="' . htmlspecialchars($app['id'], ENT_QUOTES) . '">';
    echo '<button class="btn ' . ('update' === $app['status'] || 'installable' === $app['status'] ? 'primary' : '')
        . ' app-market-action" type="submit"'
        . ($disabled ? ' disabled' : '')
        . ' data-name="' . htmlspecialchars($app['name'], ENT_QUOTES) . '"'
        . ' data-type="' . htmlspecialchars(Market::typeLabel($app['type']), ENT_QUOTES) . '"'
        . ' data-mode="' . htmlspecialchars($mode, ENT_QUOTES) . '"'
        . ' data-version="' . htmlspecialchars($latest, ENT_QUOTES) . '"'
        . ' data-current="' . htmlspecialchars($current, ENT_QUOTES) . '"'
        . ' data-location="' . htmlspecialchars($app['installTo'], ENT_QUOTES) . '"'
        . ' data-compat="' . htmlspecialchars(Market::compatibilityLabel($app['compat']['status']), ENT_QUOTES) . '"'
        . ' data-message="' . htmlspecialchars($message, ENT_QUOTES) . '"'
        . ' data-note="' . htmlspecialchars($app['latestTag']['note'] ?? '', ENT_QUOTES) . '"'
        . ' data-source="' . htmlspecialchars($source, ENT_QUOTES) . '">'
        . htmlspecialchars($buttonLabel) . '</button>';
    echo '</form>';
}

function app_market_render_card(array $app): void
{
    $detailUrl = app_market_url(['view' => 'detail', 'id' => $app['id']]);
    $compat = $app['compat']['status'] ?? 'undeclared';
    $extra = '';

    if ('language' === $app['type'] && !empty($app['language'])) {
        $extra = ' · ' . $app['language']['locale'] . ' · ' . _t('翻译完成度') . ' ' . $app['language']['progress'];
    }

    echo '<article class="app-market-list-row status-' . htmlspecialchars($app['status'], ENT_QUOTES) . '">';
    echo '<a class="app-market-card-main" href="' . htmlspecialchars($detailUrl, ENT_QUOTES) . '">';
    echo '<span class="app-market-icon">' . htmlspecialchars($app['icon']) . '</span>';
    echo '<span class="app-market-summary">';
    echo '<strong>' . htmlspecialchars($app['name']) . '</strong>';
    echo '<span>' . htmlspecialchars($app['description']) . '</span>';
    echo '<small>' . _t('作者') . ': ' . htmlspecialchars($app['author']) . ' · '
        . _t('最新版本') . ': ' . htmlspecialchars($app['latestVersion'] ?: _t('暂无')) . ' · '
        . _t('更新时间') . ': ' . htmlspecialchars($app['updated']) . htmlspecialchars($extra) . '</small>';
    echo '</span>';
    echo '</a>';
    echo '<div class="app-market-card-side">';
    echo '<div class="app-market-card-tags">'
        . app_market_badge('type-' . $app['type'], Market::typeLabel($app['type']))
        . app_market_badge('compat-' . $compat, Market::compatibilityLabel($compat))
        . app_market_enabled_label($app)
        . '</div>';
    if ($app['installed']) {
        echo '<div class="app-market-version-line">' . _t('已安装') . ': '
            . htmlspecialchars($app['installedVersion'] ?: _t('未知版本')) . '</div>';
    }
    app_market_action_form($app);
    echo '</div>';
    echo '</article>';
}

function app_market_theme_preview(array $app): string
{
    $options = Options::alloc();

    if (!empty($app['preview'])) {
        if (preg_match('/^https?:\/\//i', $app['preview'])) {
            return $app['preview'];
        }

        return app_market_asset_url($app['preview']);
    }

    $target = Market::targetPath($app);
    foreach (['png', 'jpg', 'jpeg', 'gif', 'webp', 'avif', 'svg'] as $ext) {
        $screen = $target . '/screenshot.' . $ext;
        if (file_exists($screen)) {
            return app_market_theme_asset_url($app['slug'], 'screenshot.' . $ext);
        }
    }

    return '';
}

function app_market_render_theme_tile(array $app): void
{
    $detailUrl = app_market_url(['view' => 'detail', 'id' => $app['id']]);
    $compat = $app['compat']['status'] ?? 'undeclared';
    $preview = app_market_theme_preview($app);

    echo '<article class="app-market-theme-tile status-' . htmlspecialchars($app['status'], ENT_QUOTES) . '">';
    echo '<a class="app-market-theme-preview" href="' . htmlspecialchars($detailUrl, ENT_QUOTES) . '">';
    if ('' !== $preview) {
        echo '<img src="' . htmlspecialchars($preview, ENT_QUOTES) . '" alt="">';
    } else {
        echo '<span>' . _t('暂无预览图') . '</span>';
    }
    echo '</a>';
    echo '<div class="app-market-theme-body">';
    echo '<div class="app-market-theme-head">';
    echo '<h3><a href="' . htmlspecialchars($detailUrl, ENT_QUOTES) . '">' . htmlspecialchars($app['name']) . '</a></h3>';
    echo '<div class="app-market-card-tags">'
        . app_market_badge('compat-' . $compat, Market::compatibilityLabel($compat))
        . app_market_enabled_label($app)
        . '</div>';
    echo '</div>';
    echo '<p>' . htmlspecialchars($app['description']) . '</p>';
    echo '<small>' . _t('作者') . ': ' . htmlspecialchars($app['author']) . ' · '
        . _t('最新版本') . ': ' . htmlspecialchars($app['latestVersion'] ?: _t('暂无')) . '</small>';
    echo '</div>';
    echo '<div class="app-market-theme-foot">';
    if ($app['installed']) {
        echo '<span>' . _t('已安装') . ': ' . htmlspecialchars($app['installedVersion'] ?: _t('未知版本')) . '</span>';
    } else {
        echo '<span>' . htmlspecialchars($app['updated']) . '</span>';
    }
    app_market_action_form($app);
    echo '</div>';
    echo '</article>';
}

function app_market_render_collection(string $type, array $apps, string $query): void
{
    if (empty($apps)) {
        app_market_render_empty($type, $query);
        return;
    }

    if ('theme' === $type) {
        echo '<div class="app-market-theme-grid">';
        foreach ($apps as $app) {
            app_market_render_theme_tile($app);
        }
        echo '</div>';
        return;
    }

    echo '<div class="app-market-list">';
    foreach ($apps as $app) {
        app_market_render_card($app);
    }
    echo '</div>';
}

function app_market_group_copy(string $type): array
{
    return match ($type) {
        'theme' => [_t('外观'), _t('先看缩略图与视觉气质，再进入详情查看 README。')],
        'plugin' => [_t('插件'), _t('扩展后台和站点能力，快速比较功能、版本与状态。')],
        'language' => [_t('语言包'), _t('查看地区、完成度和兼容性，安装后可切换后台语言。')],
        default => [_t('应用'), _t('')],
    };
}

function app_market_render_group(string $type, array $apps, string $query, string $variant = ''): void
{
    [$title, $description] = app_market_group_copy($type);
    $classes = 'app-market-group app-market-group-' . $type;
    if ('' !== $variant) {
        $classes .= ' app-market-group-' . $variant;
    }

    echo '<section class="' . htmlspecialchars($classes, ENT_QUOTES) . '">';
    echo '<div class="app-market-group-head">';
    echo '<div><h3>' . htmlspecialchars($title) . '</h3><p>' . htmlspecialchars($description) . '</p></div>';
    echo '<span>' . _t('%d 个应用', count($apps)) . '</span>';
    echo '</div>';
    app_market_render_collection($type, $apps, $query);
    echo '</section>';
}

function app_market_render_home(array $sourceApps, string $status, string $query): void
{
    $themeApps = Market::filter($sourceApps, 'theme', $status, $query);
    $pluginApps = Market::filter($sourceApps, 'plugin', $status, $query);

    if ('' !== $query && empty($themeApps) && empty($pluginApps)) {
        app_market_render_empty('all', $query);
        return;
    }

    echo '<div class="app-market-home">';
    app_market_render_group('theme', $themeApps, $query, 'featured');
    app_market_render_group('plugin', $pluginApps, $query, 'wide');
    echo '</div>';
}

function app_market_render_empty(string $type, string $query): void
{
    if ('' !== $query) {
        echo '<div class="app-market-empty"><strong>' . _t('没有找到相关应用') . '</strong></div>';
        return;
    }

    $message = match ($type) {
        'plugin'   => _t('暂无插件应用'),
        'theme'    => _t('暂无外观应用'),
        'language' => _t('暂无语言包'),
        default    => _t('当前筛选没有应用'),
    };

    echo '<div class="app-market-empty"><strong>' . htmlspecialchars($message) . '</strong></div>';
}

function app_market_readme(string $markdown, string $html = ''): void
{
    $html = trim($html);
    if ('' !== $html) {
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);
        $html = preg_replace('/\son[a-z]+\s*=\s*(["\']).*?\1/is', '', $html);
        $html = preg_replace('/javascript:/i', '#', $html);
        $html = preg_replace('/<a\s+href="(https?:\/\/[^"]+)"/i', '<a href="$1" target="_blank" rel="noopener"', $html);
        echo $html;
        return;
    }

    $markdown = trim($markdown);
    if ('' === $markdown) {
        echo '<p class="description">' . _t('该应用暂未提供详细说明') . '</p>';
        return;
    }

    $html = Markdown::convert($markdown);
    $html = preg_replace('/<a\s+href="(https?:\/\/[^"]+)"/i', '<a href="$1" target="_blank" rel="noopener"', $html);
    echo $html;
}

function app_market_render_detail(array $app): void
{
    $app = Market::decorate($app);
    $compat = $app['compat']['status'] ?? 'undeclared';

    if (!($app['listed'] ?? true)) {
        echo '<div class="message error"><p>' . _t('应用已下架') . '</p></div>';
        echo '<p><a class="btn" href="' . htmlspecialchars(app_market_url(), ENT_QUOTES) . '">' . _t('返回列表') . '</a></p>';
        return;
    }

    echo '<div class="app-market-detail-head">';
    echo '<span class="app-market-icon is-large">' . htmlspecialchars($app['icon']) . '</span>';
    echo '<div class="app-market-detail-title">';
    echo '<h3>' . htmlspecialchars($app['name']) . '</h3>';
    echo '<p>' . htmlspecialchars($app['description']) . '</p>';
    echo '<div class="app-market-card-tags">'
        . app_market_badge('type-' . $app['type'], Market::typeLabel($app['type']))
        . app_market_badge('compat-' . $compat, Market::compatibilityLabel($compat))
        . app_market_enabled_label($app)
        . '</div>';
    echo '</div>';
    echo '<div class="app-market-detail-actions">';
    app_market_action_form($app, 'detail');
    echo '<a class="btn" href="' . htmlspecialchars(app_market_url(), ENT_QUOTES) . '">' . _t('返回列表') . '</a>';
    echo '</div>';
    echo '</div>';

    echo '<div class="app-market-detail-grid">';
    echo '<section class="app-market-section">';
    echo '<h3>' . _t('简介') . '</h3>';
    echo '<dl class="app-market-meta">';
    echo '<dt>' . _t('作者') . '</dt><dd>' . htmlspecialchars($app['author']) . '</dd>';
    echo '<dt>' . _t('最新版本') . '</dt><dd>' . htmlspecialchars($app['latestVersion'] ?: _t('暂无发布版本')) . '</dd>';
    echo '<dt>' . _t('更新时间') . '</dt><dd>' . htmlspecialchars($app['updated']) . '</dd>';
    if ('language' === $app['type'] && !empty($app['language'])) {
        echo '<dt>' . _t('语言') . '</dt><dd>' . htmlspecialchars($app['language']['locale']) . ' · '
            . htmlspecialchars($app['language']['region']) . ' · ' . _t('翻译完成度')
            . ' ' . htmlspecialchars($app['language']['progress']) . '</dd>';
    }
    echo '</dl>';
    echo '</section>';

    echo '<section class="app-market-section">';
    echo '<h3>' . _t('兼容性') . '</h3>';
    echo '<dl class="app-market-meta">';
    echo '<dt>' . _t('Typecho 版本') . '</dt><dd>' . htmlspecialchars($app['compat']['typecho']) . '</dd>';
    echo '<dt>' . _t('PHP 版本') . '</dt><dd>' . htmlspecialchars($app['compat']['php']) . '</dd>';
    echo '<dt>' . _t('当前环境') . '</dt><dd>' . htmlspecialchars($app['compat']['note']) . '</dd>';
    echo '</dl>';
    echo '</section>';

    echo '<section class="app-market-section app-market-readme">';
    echo '<h3>README</h3>';
    app_market_readme($app['readme'] ?? '', $app['readmeHtml'] ?? '');
    echo '</section>';

    echo '<section class="app-market-section">';
    echo '<h3>' . _t('版本') . '</h3>';
    if (empty($app['tags'])) {
        echo '<p class="description">' . _t('暂无发布版本') . '</p>';
        echo '<p class="description">' . _t('该应用需要发布版本后才能安装') . '</p>';
    } else {
        echo '<p><strong>' . _t('当前最新') . ':</strong> ' . htmlspecialchars($app['latestVersion'])
            . ' <span class="description">' . htmlspecialchars($app['latestTag']['date'] ?? '') . '</span></p>';
        echo '<ul class="app-market-version-list">';
        foreach (array_slice($app['tags'], 1, 4) as $tag) {
            echo '<li>' . htmlspecialchars($tag['name']) . ' <span>' . htmlspecialchars($tag['date'] ?? '') . '</span></li>';
        }
        echo '</ul>';
    }
    echo '</section>';

    echo '<section class="app-market-section">';
    echo '<h3>' . _t('安装信息') . '</h3>';
    echo '<dl class="app-market-meta">';
    echo '<dt>' . _t('安装位置') . '</dt><dd><code>' . htmlspecialchars($app['installTo']) . '</code></dd>';
    echo '<dt>' . _t('已安装版本') . '</dt><dd>' . htmlspecialchars($app['installedVersion'] ?: _t('未安装')) . '</dd>';
    echo '<dt>' . _t('状态') . '</dt><dd>' . htmlspecialchars(Market::buttonLabel($app['status'])) . '</dd>';
    echo '</dl>';
    echo '</section>';

    echo '<section class="app-market-section">';
    echo '<h3>' . _t('仓库信息') . '</h3>';
    echo '<p><a href="' . htmlspecialchars($app['repository'], ENT_QUOTES) . '" target="_blank" rel="noopener">'
        . htmlspecialchars($app['repository']) . '</a></p>';
    echo '</section>';
    echo '</div>';
}

function app_market_render_modal(): void
{
    ?>
    <div class="app-market-modal" id="app-market-modal" hidden>
        <div class="app-market-modal-backdrop" data-market-close></div>
        <div class="app-market-dialog" role="dialog" aria-modal="true" aria-labelledby="app-market-modal-title">
            <div class="app-market-dialog-head">
                <h3 id="app-market-modal-title"><?php _e('安装确认'); ?></h3>
                <button type="button" class="btn btn-xs" data-market-close><?php _e('关闭'); ?></button>
            </div>
            <div class="app-market-dialog-body" id="app-market-modal-body"></div>
            <div class="app-market-dialog-foot" id="app-market-modal-foot"></div>
        </div>
    </div>
    <?php
}

if ($request->isPost() && $request->get('app_market_action')) {
    $user->pass('administrator');
    $security->protect();

    $id = $request->filter('slug')->get('id');
    $mode = $request->get('app_market_action') === 'update' ? 'update' : 'install';
    $app = Repository::find($id);

    try {
        if (!$app) {
            throw new \Exception(_t('应用不存在'));
        }

        $result = Market::install($app, $mode);
        $payload = [
            'success' => true,
            'message' => $result['message'],
            'actions' => $result['actions'],
        ];

        if ($request->isAjax()) {
            $response->throwJson($payload);
        }

        Notice::alloc()->set($result['message'], 'success');
    } catch (\Exception $e) {
        $payload = [
            'success' => false,
            'message' => $e->getMessage(),
        ];

        if ($request->isAjax()) {
            $response->setStatus(500)->throwJson($payload);
        }

        Notice::alloc()->set($e->getMessage(), 'error');
    }

    $response->redirect(app_market_url($app ? ['view' => 'detail', 'id' => $app['id']] : []));
}

$type = $request->get('type', 'all');
$status = $request->get('status', 'all');
$query = trim($request->get('q', ''));
$view = $request->get('view', 'list');
$validTypes = ['all', 'plugin', 'theme', 'language'];
$validStatuses = ['all', 'installable', 'installed', 'updates'];

if (!in_array($type, $validTypes, true)) {
    $type = 'all';
}

if (!in_array($status, $validStatuses, true)) {
    $status = 'all';
}

$forceRefresh = (bool) $request->get('refresh');
$sourceApps = Repository::all($forceRefresh);
$offline = Repository::isOffline();
$offlineMessage = Repository::offlineMessage();

include app_market_admin_file('header.php');
include app_market_admin_file('menu.php');
?>

<main class="main">
    <div class="body container app-market-page">
        <?php include app_market_admin_file('page-title.php'); ?>

        <?php if ('detail' === $view): ?>
            <?php
            $detailApp = Repository::find($request->filter('slug')->get('id'));
            if (!$detailApp):
            ?>
                <div class="message error"><p><?php _e('应用不存在'); ?></p></div>
                <p><a class="btn" href="<?php echo htmlspecialchars(app_market_url(), ENT_QUOTES); ?>"><?php _e('返回列表'); ?></a></p>
            <?php else: ?>
                <?php app_market_render_detail($detailApp); ?>
            <?php endif; ?>
        <?php else: ?>
            <?php app_market_render_controls($type, $status, $query); ?>

            <?php if ($offline && empty($sourceApps)): ?>
                <div class="message error app-market-server-error">
                    <p><?php _e('无法连接应用中心，请稍后重试。'); ?></p>
                    <?php if ($offlineMessage): ?>
                        <p><?php echo htmlspecialchars($offlineMessage); ?></p>
                    <?php endif; ?>
                    <p><a class="btn" href="<?php echo htmlspecialchars(app_market_url(), ENT_QUOTES); ?>"><?php _e('重新连接'); ?></a></p>
                </div>
            <?php else: ?>
                <?php
                $apps = Market::filter($sourceApps, $type, $status, $query);
                $visibleApps = 'all' === $type
                    ? array_values(array_filter($apps, static fn($app) => 'language' !== $app['type']))
                    : $apps;
                ?>
                <?php if ($offline): ?>
                    <div class="message notice app-market-server-error">
                        <p><?php _e('暂时无法连接应用中心，当前显示本地缓存数据。'); ?></p>
                    </div>
                <?php endif; ?>
                <?php if ('' !== $query): ?>
                    <p class="app-market-result-count"><?php _e('找到 %d 个应用', count($visibleApps)); ?></p>
                <?php endif; ?>

                <?php if ('all' === $type): ?>
                    <?php app_market_render_home($sourceApps, $status, $query); ?>
                <?php else: ?>
                    <?php app_market_render_collection($type, $apps, $query); ?>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>

<?php
app_market_render_modal();
include app_market_admin_file('copyright.php');
include app_market_admin_file('common-js.php');
?>
<script src="<?php echo htmlspecialchars(app_market_asset_url('assets/market.js?v=' . AppMarketPlugin::VERSION), ENT_QUOTES); ?>"></script>
<?php include app_market_admin_file('footer.php'); ?>
