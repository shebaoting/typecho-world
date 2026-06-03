<?php
/**
 * Default theme for Typecho
 *
 * @package Typecho Replica Theme
 * @author Typecho Team
 * @version 1.2
 * @link http://typecho.org
 */

/** @var \Typecho\Theme\ViewContext $view */
/** @var \Widget\Archive $archive */
/** @var \Widget\Options $site */
/** @var \Typecho\Theme\Manifest $theme */
/** @var \Typecho\Theme\Escaper $e */
/** @var \Typecho\Theme\AssetManager $assets */
/** @var \Typecho\Theme\DataProvider $data */
/** @var \Widget\User $user */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$view->layout('default');
?>

<div class="col-mb-12 col-8" id="main" role="main">
    <?php if (!$archive->is('index') && !$archive->is('post')): ?>
    <h3 class="archive-title"><?php echo $view->archiveTitle([
            'category' => _t('分类 %s 下的文章'),
            'search'   => _t('包含关键字 %s 的文章'),
            'tag'      => _t('标签 %s 下的文章'),
            'author'   => _t('%s 发布的文章')
        ], '', ''); ?></h3>
    <?php endif; ?>
    <?php if ($archive->have()): ?>
    <?php while ($archive->next()): ?>
        <?php $view->component('post-card', ['post' => $archive, 'moreText' => _t('阅读剩余部分')]); ?>
    <?php endwhile; ?>
    <?php else: ?>
        <article class="post">
            <h2 class="post-title"><?php _e('没有找到内容'); ?></h2>
        </article>
    <?php endif; ?>

    <?php echo $view->pageNav('&laquo; ' . _t('前一页'), _t('后一页') . ' &raquo;'); ?>
</div><!-- end #main-->

<?php $view->part('sidebar'); ?>
