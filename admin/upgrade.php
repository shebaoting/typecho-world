<?php
include 'common.php';
include 'header.php';
include 'menu.php';

$mustUpgrade = version_compare(\Typecho\Common::VERSION, $options->version, '>');
$systemUpdateUrl = $security->getTokenUrl(
    \Typecho\Router::url('do', ['action' => 'system-update', 'widget' => 'SystemUpdate'],
        \Typecho\Common::url('index.php', $options->rootUrl))
);
?>

<main class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <div class="row typecho-page-main" role="main">
            <div class="col-mb-12">
                <div id="typecho-welcome">
                    <?php if ($mustUpgrade): ?>
                        <form action="<?php echo $security->getTokenUrl(
                            \Typecho\Router::url('do', ['action' => 'upgrade', 'widget' => 'Upgrade'],
                                \Typecho\Common::url('index.php', $options->rootUrl))); ?>" method="post">
                            <h3><?php _e('检测到新版本!'); ?></h3>
                            <ul>
                                <li><?php _e('您已经更新了系统程序, 我们还需要执行一些后续步骤来完成升级'); ?></li>
                                <li><?php _e('此程序将把您的系统从 <strong>%s</strong> 升级到 <strong>%s</strong>', $options->version, \Typecho\Common::VERSION); ?></li>
                                <li><strong
                                        class="warning"><?php _e('在升级之前强烈建议先<a href="%s">备份您的数据</a>', \Typecho\Common::url('backup.php', $options->adminUrl)); ?></strong>
                                </li>
                            </ul>
                            <p>
                                <button class="btn primary" type="submit"><?php _e('完成升级 &raquo;'); ?></button>
                            </p>
                        </form>
                    <?php else: ?>
                        <div class="system-update-panel" data-online-update>
                            <h3><?php _e('Typecho World 在线更新'); ?></h3>
                            <ul>
                                <li><?php _e('当前程序版本：<strong>%s</strong>', \Typecho\Common::VERSION); ?></li>
                                <li><?php _e('系统会从 Typecho World 下载最新 tag 对应的程序包，并保留配置、上传文件、缓存、备份目录和用户自己的主题插件。'); ?></li>
                                <li><strong
                                        class="warning"><?php _e('升级前请先<a href="%s">备份您的数据</a>，并确认当前目录可写。', \Typecho\Common::url('backup.php', $options->adminUrl)); ?></strong>
                                </li>
                            </ul>

                            <div class="message notice" data-update-state>
                                <?php _e('正在检查 Typecho World 最新版本...'); ?>
                            </div>

                            <form action="<?php echo $systemUpdateUrl; ?>" method="post" data-update-form hidden>
                                <p class="system-update-actions">
                                    <button class="btn primary" type="submit"><?php _e('下载并更新 &raquo;'); ?></button>
                                    <a class="btn" href="https://typecho.world/download/" target="_blank" rel="noreferrer"><?php _e('手动下载'); ?></a>
                                </p>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
include 'copyright.php';
include 'common-js.php';
?>
<script>
    (function () {
        if (window.sessionStorage) {
            sessionStorage.removeItem('update');
        }
    })();
</script>
<?php if (!$mustUpgrade): ?>
<script>
    $(document).ready(function () {
        var state = $('[data-update-state]'),
            form = $('[data-update-form]');

        function render(update) {
            if (update && update.available) {
                state.removeClass('notice error success').addClass('success').html(
                    '<?php _e('发现 Typecho World 新版本：'); ?>'
                    + '<strong>' + update.latest + '</strong>'
                    + (update.tag ? ' <span>(' + update.tag + ')</span>' : '')
                    + '<br><?php _e('点击下方按钮会先下载并替换程序文件，随后继续执行数据库升级。'); ?>'
                );
                form.removeAttr('hidden');
                return;
            }

            if (update && update.error) {
                state.removeClass('notice success').addClass('error').text(update.error);
                return;
            }

            state.removeClass('notice error').addClass('success').text('<?php _e('当前已经是 Typecho World 最新版本。'); ?>');
        }

        $.get('<?php $options->index('/action/ajax?do=checkVersion'); ?>', function (update, status, resp) {
            render(update);

            if (window.sessionStorage) {
                sessionStorage.setItem('update', resp.responseText);
            }
        }, 'json').fail(function () {
            state.removeClass('notice success').addClass('error').text('<?php _e('暂时无法连接 Typecho World 版本服务。'); ?>');
        });
    });
</script>
<?php endif; ?>
<?php include 'footer.php'; ?>
