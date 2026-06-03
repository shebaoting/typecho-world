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

<main class="container">
    <div class="container-thin">

        <h1 class="text-center"><?php _e('搜索'); ?></h1>
        
        <form method="post" action="<?php echo $e->url($view->siteUrl()); ?>">
            <input type="search" id="s" name="s" placeholder="<?php _e('搜索关键字'); ?>" value="<?php echo $e->attr($archive->getArchiveTitle()); ?>">
        </form>

        <div class="text-center">
            <?php echo $data->categoriesHtml('wrapClass=list-inline'); ?>
        </div>
    
        <hr class="post-separator">

    <?php if ($archive->have()): ?>
        <?php while ($archive->next()): ?>
        <?php $view->component('post-card', ['post' => $archive, 'moreText' => _t('阅读全文')]); ?>
        <hr class="post-separator">
        <?php endwhile; ?>
    <?php else: ?>
        <article class="post">
            <div class="entry-content fmt text-center" itemprop="articleBody">
                <p><?php _e('没有找到内容'); ?></p>
            </div>
        </article>
    <?php endif; ?>
    </div>

    <?php echo $view->pageNav('&laquo; 前一页', '后一页 &raquo;'); ?>
</main>
