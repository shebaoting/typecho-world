<?php
/** @var \Typecho\Theme\ViewContext $view */
/** @var \Widget\Archive $archive */
/** @var \Widget\Options $site */
/** @var \Typecho\Theme\Manifest $theme */
/** @var \Typecho\Theme\Escaper $e */
/** @var \Typecho\Theme\AssetManager $assets */
/** @var \Typecho\Theme\DataProvider $data */
/** @var \Widget\User $user */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
?>
<div class="col-mb-12 col-offset-1 col-3 kit-hidden-tb" id="secondary" role="complementary">
    <?php if (!empty($site->sidebarBlock) && in_array('ShowRecentPosts', $site->sidebarBlock)): ?>
        <section class="widget">
            <h3 class="widget-title"><?php _e('最新文章'); ?></h3>
            <ul class="widget-list">
                <?php echo $data->recentPostsHtml(); ?>
            </ul>
        </section>
    <?php endif; ?>

    <?php if (!empty($site->sidebarBlock) && in_array('ShowRecentComments', $site->sidebarBlock)): ?>
        <section class="widget">
            <h3 class="widget-title"><?php _e('最近回复'); ?></h3>
            <ul class="widget-list">
                <?php echo $data->recentCommentsHtml(); ?>
            </ul>
        </section>
    <?php endif; ?>

    <?php if (!empty($site->sidebarBlock) && in_array('ShowCategory', $site->sidebarBlock)): ?>
        <section class="widget">
            <h3 class="widget-title"><?php _e('分类'); ?></h3>
            <?php echo $data->categoriesHtml(); ?>
        </section>
    <?php endif; ?>

    <?php if (!empty($site->sidebarBlock) && in_array('ShowArchive', $site->sidebarBlock)): ?>
        <section class="widget">
            <h3 class="widget-title"><?php _e('归档'); ?></h3>
            <ul class="widget-list">
                <?php echo $data->archivesHtml(); ?>
            </ul>
        </section>
    <?php endif; ?>

    <?php if (!empty($site->sidebarBlock) && in_array('ShowOther', $site->sidebarBlock)): ?>
        <section class="widget">
            <h3 class="widget-title"><?php _e('其它'); ?></h3>
            <ul class="widget-list">
                <?php if ($user->hasLogin()): ?>
                    <li class="last"><a href="<?php echo $e->url($site->adminUrl); ?>"><?php _e('进入后台'); ?>
                            (<?php echo $e->html($user->screenName); ?>)</a></li>
                    <li><a href="<?php echo $e->url($site->logoutUrl); ?>"><?php _e('退出'); ?></a></li>
                <?php else: ?>
                    <li class="last"><a href="<?php echo $e->url(\Typecho\Common::url('login.php', $site->adminUrl)); ?>"><?php _e('登录'); ?></a>
                    </li>
                <?php endif; ?>
                <li><a href="<?php echo $e->url($site->feedUrl); ?>"><?php _e('文章 RSS'); ?></a></li>
                <li><a href="<?php echo $e->url($site->commentsFeedUrl); ?>"><?php _e('评论 RSS'); ?></a></li>
                <li><a href="https://typecho.org">Typecho</a></li>
            </ul>
        </section>
    <?php endif; ?>

</div><!-- end #sidebar -->
