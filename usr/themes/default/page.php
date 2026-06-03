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
$view->layout('default');
?>

<div class="col-mb-12 col-tb-8 col-tb-offset-2" id="main" role="main">
    <article class="post" itemscope itemtype="http://schema.org/BlogPosting">
        <?php $view->component('post-meta', ['post' => $archive, 'metaType' => 'page']); ?>
        <div class="post-content" itemprop="articleBody">
            <?php echo $view->content(); ?>
        </div>
    </article>
    <?php $view->part('comments'); ?>
</div><!-- end #main-->
