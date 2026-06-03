<?php
/**
 * Just another official theme
 *
 * @package Classic 22
 * @author Typecho Team
 * @version 1.0
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

<main class="container">
    <div class="container-thin">
        <?php if (!$archive->is('index') && !$archive->is('post')): ?>
            <h6 class="text-center text-muted">
                <?php echo $view->archiveTitle([
                    'category' => _t('分类 %s 下的文章'),
                    'search'   => _t('包含关键字 %s 的文章'),
                    'tag'      => _t('标签 %s 下的文章'),
                    'author'   => _t('%s 发布的文章')
                ], '', ''); ?>
            </h6>
        <?php endif; ?>

        <?php while ($archive->next()): ?>
            <?php $view->component('post-card', ['post' => $archive, 'moreText' => _t('阅读全文')]); ?>
            <hr class="post-separator">
        <?php endwhile; ?>

        <nav><?php echo $view->pageNav(_t('前一页'), _t('后一页'), 2, '...', array('wrapTag' => 'ul', 'itemTag' => 'li')); ?></nav>
    </div>

</main>
