<?php
/** @var \Typecho\Theme\ViewContext $view */
/** @var \Widget\Archive $archive */
/** @var \Widget\Options $site */
/** @var \Typecho\Theme\Manifest $theme */
/** @var \Typecho\Theme\Escaper $e */
/** @var \Typecho\Theme\AssetManager $assets */
/** @var \Typecho\Theme\DataProvider $data */
/** @var \Widget\User $user */
/** @var \Widget\Base\Contents $post */
/** @var string $metaType */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
?>
<?php $metaType = $metaType ?? 'archive'; ?>
<header class="entry-header text-center">
    <h1 class="entry-title" itemprop="name headline">
        <a href="<?php echo $e->url($post->permalink); ?>" itemprop="url"><?php echo $e->html($view->title($post)); ?></a>
    </h1>
    <?php if ($metaType !== 'page'): ?>
    <ul class="entry-meta list-inline text-muted">
        <li class="feather-calendar">
            <time datetime="<?php echo $e->attr($view->date($post, 'c')); ?>" itemprop="datePublished">
                <?php echo $e->html($view->date($post)); ?>
            </time>
        </li>
        <li class="feather-folder"><?php $post->category(', '); ?></li>
        <li class="feather-message">
            <a href="<?php echo $e->url($post->permalink); ?>#comments" itemprop="discussionUrl">
                <?php $post->commentsNum(_t('暂无评论'), _t('1 条评论'), _t('%d 条评论')); ?>
            </a>
        </li>
    </ul>
    <?php endif; ?>
</header>
