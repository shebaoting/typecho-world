<?php
include 'common.php';
include 'header.php';
include 'menu.php';

$logs = \Widget\Logs\Admin::alloc();
$actionLabels = [
    'publish'        => _t('发布'),
    'save'           => _t('保存草稿'),
    'snapshot'       => _t('草稿快照'),
    'trash'          => _t('移至回收站'),
    'restore'        => _t('恢复'),
    'delete_forever' => _t('永久删除'),
    'rollback'       => _t('回滚'),
    'batch'          => _t('批量编辑'),
];
?>
<main class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <div class="row typecho-page-main" role="main">
            <div class="col-mb-12 typecho-list">
                <form method="get" class="typecho-list-operate">
                    <div class="operate"></div>
                    <div class="search" role="search">
                        <?php if (isset($request->action) || isset($request->targetType)): ?>
                            <a href="<?php $options->adminUrl('manage-logs.php'); ?>"><?php _e('&laquo; 取消筛选'); ?></a>
                        <?php endif; ?>
                        <select name="action">
                            <option value=""><?php _e('全部操作'); ?></option>
                            <?php foreach ($actionLabels as $action => $label): ?>
                                <option value="<?php echo $action; ?>"<?php if ($request->get('action') == $action): ?> selected="true"<?php endif; ?>><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="targetType">
                            <option value=""><?php _e('全部对象'); ?></option>
                            <option value="post"<?php if ($request->get('targetType') == 'post'): ?> selected="true"<?php endif; ?>><?php _e('文章'); ?></option>
                            <option value="page"<?php if ($request->get('targetType') == 'page'): ?> selected="true"<?php endif; ?>><?php _e('页面'); ?></option>
                        </select>
                        <button type="submit" class="btn btn-s"><?php _e('筛选'); ?></button>
                    </div>
                </form>

                <table class="typecho-list-table">
                    <colgroup>
                        <col width="16%"/>
                        <col width="12%" class="kit-hidden-mb"/>
                        <col width="12%"/>
                        <col width="22%"/>
                        <col width=""/>
                        <col width="14%" class="kit-hidden-mb"/>
                    </colgroup>
                    <thead>
                    <tr>
                        <th><?php _e('时间'); ?></th>
                        <th class="kit-hidden-mb"><?php _e('操作者'); ?></th>
                        <th><?php _e('操作'); ?></th>
                        <th><?php _e('对象'); ?></th>
                        <th><?php _e('说明'); ?></th>
                        <th class="kit-hidden-mb"><?php _e('IP'); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($logs->have()): ?>
                        <?php while ($logs->next()): ?>
                            <?php $date = new \Typecho\Date($logs->created); ?>
                            <tr>
                                <td><?php echo $date->word(); ?></td>
                                <td class="kit-hidden-mb"><?php echo htmlspecialchars($logs->screenName ?: _t('系统')); ?></td>
                                <td><?php echo $actionLabels[$logs->action] ?? htmlspecialchars($logs->action); ?></td>
                                <td>
                                    <?php if ($logs->targetId): ?>
                                        <a href="<?php $options->adminUrl(($logs->targetType == 'page' ? 'write-page.php' : 'write-post.php') . '?cid=' . $logs->targetId); ?>">
                                            <?php echo htmlspecialchars($logs->targetTitle ?: ('#' . $logs->targetId)); ?>
                                        </a>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($logs->targetTitle ?: '-'); ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($logs->message); ?></td>
                                <td class="kit-hidden-mb"><?php echo htmlspecialchars($logs->ip); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="none"><?php _e('暂无操作日志'); ?></td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>

                <?php if ($logs->have()): ?>
                    <ul class="typecho-pager">
                        <?php $logs->pageNav(); ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php
include 'copyright.php';
include 'common-js.php';
include 'footer.php';
?>
