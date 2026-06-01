<?php
include 'common.php';
include 'header.php';
include 'menu.php';

$stat = \Widget\Stat::alloc();
$db = \Typecho\Db::get();
$draftSelect = $db->select('cid', 'title', 'modified', 'type')
    ->from('table.contents')
    ->where('table.contents.type IN ?', ['post_draft', 'page_draft'])
    ->where('table.contents.status <> ?', 'trash');

if (!$user->pass('editor', true)) {
    $draftSelect->where('table.contents.authorId = ?', $user->uid);
}

$dashboardDrafts = $db->fetchAll($draftSelect
    ->order('table.contents.modified', \Typecho\Db::SORT_DESC)
    ->limit(6));
$dashboardLogs = $user->pass('administrator', true) ? $db->fetchAll($db->select(
    'table.logs.created',
    'table.logs.action',
    'table.logs.targetType',
    'table.logs.targetId',
    'table.logs.targetTitle',
    'table.users.screenName'
)->from('table.logs')
    ->join('table.users', 'table.logs.userId = table.users.uid', \Typecho\Db::LEFT_JOIN)
    ->order('table.logs.lid', \Typecho\Db::SORT_DESC)
    ->limit(6)) : [];
?>
<main class="main">
    <div class="container typecho-dashboard">
        <?php include 'page-title.php'; ?>
        <div class="row typecho-page-main">
            <div class="col-mb-12 welcome-board" role="main">
                <p><?php _e('目前有 <em>%s</em> 篇文章, 并有 <em>%s</em> 条关于你的评论在 <em>%s</em> 个分类中.',
                        $stat->myPublishedPostsNum, $stat->myPublishedCommentsNum, $stat->categoriesNum); ?>
                    <br><?php _e('点击下面的链接快速开始:'); ?></p>

                <div class="dashboard-cards">
                    <a class="dashboard-card" href="<?php $options->adminUrl('manage-posts.php'); ?>">
                        <strong><?php $stat->publishedPostsNum(); ?></strong>
                        <span><?php _e('已发布文章'); ?></span>
                    </a>
                    <a class="dashboard-card" href="<?php $options->adminUrl('manage-posts.php?status=draft'); ?>">
                        <strong><?php $stat->draftPostsNum(); ?></strong>
                        <span><?php _e('草稿'); ?></span>
                    </a>
                    <a class="dashboard-card" href="<?php $options->adminUrl('manage-posts.php?status=waiting'); ?>">
                        <strong><?php $stat->waitingPostsNum(); ?></strong>
                        <span><?php _e('待审核'); ?></span>
                    </a>
                    <a class="dashboard-card" href="<?php $options->adminUrl('manage-posts.php?status=trash'); ?>">
                        <strong><?php $stat->trashPostsNum(); ?></strong>
                        <span><?php _e('回收站文章'); ?></span>
                    </a>
                    <a class="dashboard-card" href="<?php $options->adminUrl('manage-comments.php?status=waiting'); ?>">
                        <strong><?php $stat->waitingCommentsNum(); ?></strong>
                        <span><?php _e('待审核评论'); ?></span>
                    </a>
                </div>

                <ul id="start-link">
                    <?php if ($user->pass('contributor', true)): ?>
                        <li><a href="<?php $options->adminUrl('write-post.php'); ?>"><?php _e('撰写新文章'); ?></a></li>
                        <?php if ($user->pass('editor', true) && 'on' == $request->get('__typecho_all_comments') && $stat->waitingCommentsNum > 0): ?>
                            <li>
                                <a href="<?php $options->adminUrl('manage-comments.php?status=waiting'); ?>"><?php _e('待审核的评论'); ?></a>
                                <span class="balloon"><?php $stat->waitingCommentsNum(); ?></span>
                            </li>
                        <?php elseif ($stat->myWaitingCommentsNum > 0): ?>
                            <li>
                                <a href="<?php $options->adminUrl('manage-comments.php?status=waiting'); ?>"><?php _e('待审核评论'); ?></a>
                                <span class="balloon"><?php $stat->myWaitingCommentsNum(); ?></span>
                            </li>
                        <?php endif; ?>
                        <?php if ($user->pass('editor', true) && 'on' == $request->get('__typecho_all_comments') && $stat->spamCommentsNum > 0): ?>
                            <li>
                                <a href="<?php $options->adminUrl('manage-comments.php?status=spam'); ?>"><?php _e('垃圾评论'); ?></a>
                                <span class="balloon"><?php $stat->spamCommentsNum(); ?></span>
                            </li>
                        <?php elseif ($stat->mySpamCommentsNum > 0): ?>
                            <li>
                                <a href="<?php $options->adminUrl('manage-comments.php?status=spam'); ?>"><?php _e('垃圾评论'); ?></a>
                                <span class="balloon"><?php $stat->mySpamCommentsNum(); ?></span>
                            </li>
                        <?php endif; ?>
                        <?php if ($user->pass('administrator', true)): ?>
                            <li><a href="<?php $options->adminUrl('applications.php?tab=themes'); ?>"><?php _e('更换外观'); ?></a></li>
                            <li><a href="<?php $options->adminUrl('applications.php?tab=plugins'); ?>"><?php _e('插件管理'); ?></a></li>
                            <li><a href="<?php $options->adminUrl('options-general.php'); ?>"><?php _e('系统设置'); ?></a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                    <!--<li><a href="<?php $options->adminUrl('profile.php'); ?>"><?php _e('更新我的资料'); ?></a></li>-->
                </ul>
            </div>

            <div class="col-mb-12 col-tb-3" role="complementary">
                <section class="latest-link">
                    <h3><?php _e('最近发布的文章'); ?></h3>
                    <?php \Widget\Contents\Post\Recent::alloc('pageSize=10')->to($posts); ?>
                    <ul>
                        <?php if ($posts->have()): ?>
                            <?php while ($posts->next()): ?>
                                <li>
                                    <span><?php $posts->date('n.j'); ?></span>
                                    <a href="<?php $posts->permalink(); ?>" class="title"><?php $posts->title(); ?></a>
                                </li>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <li><em><?php _e('暂时没有文章'); ?></em></li>
                        <?php endif; ?>
                    </ul>
                </section>
            </div>

            <div class="col-mb-12 col-tb-3" role="complementary">
                <section class="latest-link">
                    <h3><?php _e('最近得到的回复'); ?></h3>
                    <ul>
                        <?php \Widget\Comments\Recent::alloc('pageSize=10')->to($comments); ?>
                        <?php if ($comments->have()): ?>
                            <?php while ($comments->next()): ?>
                                <li>
                                    <span><?php $comments->date('n.j'); ?></span>
                                    <a href="<?php $comments->permalink(); ?>"
                                       class="title"><?php $comments->author(false); ?></a>:
                                    <?php $comments->excerpt(35, '...'); ?>
                                </li>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <li><?php _e('暂时没有回复'); ?></li>
                        <?php endif; ?>
                    </ul>
                </section>
            </div>

            <div class="col-mb-12 col-tb-3" role="complementary">
                <section class="latest-link">
                    <h3><?php _e('最近草稿'); ?></h3>
                    <ul>
                        <?php if (!empty($dashboardDrafts)): ?>
                            <?php foreach ($dashboardDrafts as $draft): ?>
                                <?php $draftDate = new \Typecho\Date($draft['modified']); ?>
                                <li>
                                    <span><?php echo $draftDate->format('n.j'); ?></span>
                                    <a href="<?php $options->adminUrl(('page_draft' == $draft['type'] ? 'write-page.php' : 'write-post.php') . '?cid=' . $draft['cid']); ?>"><?php echo htmlspecialchars($draft['title'] ?: _t('未命名文档')); ?></a>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li><?php _e('暂时没有草稿'); ?></li>
                        <?php endif; ?>
                    </ul>
                </section>
            </div>

            <div class="col-mb-12 col-tb-3" role="complementary">
                <section class="latest-link">
                    <h3><?php _e('最近操作'); ?></h3>
                    <ul>
                        <?php if (!empty($dashboardLogs)): ?>
                            <?php foreach ($dashboardLogs as $log): ?>
                                <?php $logDate = new \Typecho\Date($log['created']); ?>
                                <li>
                                    <span><?php echo $logDate->format('n.j'); ?></span>
                                    <?php echo htmlspecialchars($log['screenName'] ?: _t('系统')); ?>:
                                    <a href="<?php $options->adminUrl('manage-logs.php'); ?>"><?php echo htmlspecialchars($log['targetTitle'] ?: $log['action']); ?></a>
                                </li>
                            <?php endforeach; ?>
                        <?php elseif ($user->pass('administrator', true)): ?>
                            <li><?php _e('暂时没有操作日志'); ?></li>
                        <?php else: ?>
                            <li><?php _e('仅管理员可见'); ?></li>
                        <?php endif; ?>
                    </ul>
                </section>
            </div>
        </div>
    </div>
</main>

<?php
include 'copyright.php';
include 'common-js.php';
?>

<script>
    $(document).ready(function () {
        var cache = window.sessionStorage,
            update = cache ? cache.getItem('update') : '';

        function applyUpdate(update) {
            if (update.available) {
                $('<div class="update-check message error"><p>'
                    + '<?php _e('您当前使用的版本是 %s'); ?>'.replace('%s', update.current) + '<br />'
                    + '<strong><a href="' + update.link + '" target="_blank">'
                    + '<?php _e('官方最新版本是 %s'); ?>'.replace('%s', update.latest) + '</a></strong></p></div>')
                    .insertAfter('.typecho-page-title').effect('highlight');
            }
        }

        if (!!update) {
            applyUpdate(JSON.parse(update));
        } else {
            $.get('<?php $options->index('/action/ajax?do=checkVersion'); ?>', function (o, status, resp) {
                applyUpdate(o);
                cache.setItem('update', resp.responseText);
            }, 'json');
        }
    });

</script>
<?php include 'footer.php'; ?>
