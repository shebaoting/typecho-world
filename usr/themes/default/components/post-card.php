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
/** @var string $moreText */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
?>
<article class="post" itemscope itemtype="http://schema.org/BlogPosting">
    <?php $view->component('post-meta', ['post' => $post]); ?>
    <div class="post-content" itemprop="articleBody">
        <?php echo $view->content($moreText ?? _t('阅读剩余部分')); ?>
    </div>
</article>
