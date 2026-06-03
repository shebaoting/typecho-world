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
<?php
$metaType = $metaType ?? 'archive';
$titleTag = $metaType === 'archive' ? 'h2' : 'h1';
?>
<<?php echo $titleTag; ?> class="post-title" itemprop="name headline">
    <a itemprop="url"
       href="<?php echo $e->url($post->permalink); ?>"><?php echo $e->html($view->title($post)); ?></a>
</<?php echo $titleTag; ?>>
<?php if ($metaType !== 'page'): ?>
    <ul class="post-meta">
        <li itemprop="author" itemscope itemtype="http://schema.org/Person">
            <?php _e('作者'); ?>:
            <a itemprop="name"
               href="<?php echo $e->url($post->author->permalink); ?>"
               rel="author"><?php echo $e->html($post->author->screenName); ?></a>
        </li>
        <li><?php _e('时间'); ?>:
            <time datetime="<?php echo $e->attr($view->date($post, 'c')); ?>" itemprop="datePublished">
                <?php echo $e->html($view->date($post)); ?>
            </time>
        </li>
        <li><?php _e('分类'); ?>: <?php $post->category(','); ?></li>
        <?php if ($metaType === 'archive'): ?>
            <li itemprop="interactionCount">
                <a itemprop="discussionUrl"
                   href="<?php echo $e->url($post->permalink); ?>#comments"><?php $post->commentsNum(_t('暂无评论'), _t('1 条评论'), _t('%d 条评论')); ?></a>
            </li>
        <?php endif; ?>
    </ul>
<?php endif; ?>
