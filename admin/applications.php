<?php
include 'common.php';
include 'header.php';
include 'menu.php';

function has_official_app_market(): bool
{
    $pluginFile = __TYPECHO_ROOT_DIR__ . __TYPECHO_PLUGIN_DIR__ . '/AppMarket/Plugin.php';
    if (!is_file($pluginFile) || !is_readable($pluginFile)) {
        return false;
    }

    $source = (string) file_get_contents($pluginFile);

    return str_contains($source, 'namespace TypechoPlugin\\AppMarket;')
        && str_contains($source, 'class Plugin implements PluginInterface')
        && str_contains($source, "public const PANEL = 'AppMarket/panel.php'")
        && str_contains($source, '@author Typecho World');
}

$applicationTab = $request->get('tab', 'themes');
$applicationTabs = ['themes', 'plugins', 'languages'];

if (!in_array($applicationTab, $applicationTabs, true)) {
    $applicationTab = 'themes';
}
?>

<main class="main">
    <div class="body container">
        <div class="typecho-page-title">
            <h2><?php echo $menu->title; ?></h2>
            <?php \Typecho\Plugin::factory('admin/page-title.php')->call('afterTitle', $menu); ?>
            <?php if (!has_official_app_market()): ?>
                <a href="https://typecho.world/ecosystem/packages/shebaoting-typecho-world-appmarket/"
                   target="_blank" rel="noopener"><?php _e('安装应用市场'); ?></a>
            <?php endif; ?>
            <?php
            if (!empty($menu->addLink)) {
                echo "<a href=\"{$menu->addLink}\">" . _t("新增") . "</a>";
            }
            ?>
        </div>
        <?php include 'application-tabs.php'; ?>
        <?php include __DIR__ . '/applications/' . $applicationTab . '.php'; ?>
    </div>
</main>

<?php
include 'copyright.php';
include 'common-js.php';
if (!empty($applicationDeleteConfirm)): ?>
<script>
    $('.operate-delete').click(function () {
        var message = $(this).attr('lang');
        return !message || confirm(message);
    });
</script>
<?php endif;
include 'footer.php';
?>
