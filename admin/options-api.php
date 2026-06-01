<?php
include 'common.php';
include 'header.php';
include 'menu.php';

$token = trim((string) ($options->apiToken ?? ''));
$actionUrl = $security->getIndex('/action/options-api');
$apiUrl = function (string $endpoint) use ($options): string {
    return \Typecho\Router::url('api', ['endpoint' => $endpoint], $options->index);
};
$jsonExportUrl = $token === '' ? '#' : $apiUrl('export.json') . '?token=' . rawurlencode($token);
$backupExportUrl = $token === '' ? '#' : $apiUrl('backup/export') . '?token=' . rawurlencode($token);
$markdownExportUrl = $token === '' ? '#' : $apiUrl('markdown/{cid}') . '?token=' . rawurlencode($token);
?>

<main class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <div class="row typecho-page-main" role="main">
            <div class="col-mb-12 col-tb-8 col-tb-offset-2">
                <form action="<?php echo $actionUrl; ?>" method="post">
                    <ul class="typecho-option">
                        <li>
                            <label class="typecho-label" for="api-token"><?php _e('后台 API Token'); ?></label>
                            <input id="api-token" class="w-100 mono" type="text" readonly
                                value="<?php echo htmlspecialchars($token, ENT_QUOTES); ?>">
                            <p class="description">
                                <?php _e('导出、Markdown 下载和备份恢复接口需要此 Token. 可通过 Authorization: Bearer 或 X-Typecho-Token 传递.'); ?>
                            </p>
                        </li>
                    </ul>
                    <ul class="typecho-option typecho-option-submit">
                        <li>
                            <button class="btn primary" type="submit"><?php _e('重新生成 Token'); ?></button>
                            <input type="hidden" name="do" value="regenerate">
                        </li>
                    </ul>
                </form>

                <h3><?php _e('只读 REST API'); ?></h3>
                <ul class="typecho-option">
                    <li><code><?php echo htmlspecialchars($apiUrl('site'), ENT_QUOTES); ?></code></li>
                    <li><code><?php echo htmlspecialchars($apiUrl('posts'), ENT_QUOTES); ?></code></li>
                    <li><code><?php echo htmlspecialchars($apiUrl('posts/{cid}'), ENT_QUOTES); ?></code></li>
                    <li><code><?php echo htmlspecialchars($apiUrl('pages'), ENT_QUOTES); ?></code></li>
                    <li><code><?php echo htmlspecialchars($apiUrl('categories'), ENT_QUOTES); ?></code></li>
                    <li><code><?php echo htmlspecialchars($apiUrl('tags'), ENT_QUOTES); ?></code></li>
                    <li><code><?php echo htmlspecialchars($apiUrl('comments'), ENT_QUOTES); ?></code></li>
                </ul>

                <h3><?php _e('导入导出接口'); ?></h3>
                <ul class="typecho-option">
                    <li>
                        <label class="typecho-label"><?php _e('JSON 全站导出'); ?></label>
                        <p><a class="btn" href="<?php echo htmlspecialchars($jsonExportUrl, ENT_QUOTES); ?>"><?php _e('下载 JSON'); ?></a></p>
                    </li>
                    <li>
                        <label class="typecho-label"><?php _e('单篇 Markdown 导出'); ?></label>
                        <code><?php echo htmlspecialchars($markdownExportUrl, ENT_QUOTES); ?></code>
                    </li>
                    <li>
                        <label class="typecho-label"><?php _e('备份下载'); ?></label>
                        <p><a class="btn" href="<?php echo htmlspecialchars($backupExportUrl, ENT_QUOTES); ?>"><?php _e('下载备份'); ?></a></p>
                    </li>
                    <li>
                        <label class="typecho-label"><?php _e('备份恢复'); ?></label>
                        <code>POST <?php echo htmlspecialchars($apiUrl('backup/restore'), ENT_QUOTES); ?></code>
                        <p class="description"><?php _e('上传 file 文件字段, 或提交服务器备份文件名 file=xxx.dat.'); ?></p>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</main>

<?php
include 'copyright.php';
include 'common-js.php';
include 'form-js.php';
include 'footer.php';
?>
