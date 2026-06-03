<?php
/** @var \Typecho\Theme\ViewContext $view */
/** @var \Widget\Archive $archive */
/** @var \Widget\Options $site */
/** @var \Typecho\Theme\Manifest $theme */
/** @var \Typecho\Theme\Escaper $e */
/** @var \Typecho\Theme\AssetManager $assets */
/** @var \Typecho\Theme\DataProvider $data */
/** @var \Typecho\Theme\DesignTokens $tokens */
/** @var \Typecho\Theme\ImageHelper $images */
/** @var \Typecho\Theme\FragmentCache $cache */
/** @var \Widget\User $user */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
?>
<!DOCTYPE HTML>
<html>
<head>
    <meta charset="<?php echo $e->attr($site->charset); ?>">
    <meta name="renderer" content="webkit">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title><?php echo $e->html($view->seoTitle()); ?></title>

    <!-- 使用url函数转换相关路径 -->
    <?php echo $assets->style('normalize'); ?>
    <?php echo $assets->style('grid'); ?>
    <?php echo $assets->style('theme'); ?>
    <?php echo $tokens->styleTag(); ?>

    <!-- 通过自有函数输出HTML头部信息 -->
    <?php echo $view->head(); ?>
</head>
<body>

<header id="header" class="clearfix">
    <div class="container">
        <div class="row">
            <div class="site-name col-mb-12 col-9">
                <?php if ($site->logoUrl): ?>
                    <a id="logo" href="<?php echo $e->url($view->siteUrl()); ?>">
                        <?php echo $images->tag((string) $site->logoUrl, ['alt' => $site->title, 'loading' => 'eager']); ?>
                    </a>
                <?php else: ?>
                    <a id="logo" href="<?php echo $e->url($view->siteUrl()); ?>"><?php echo $e->html($site->title); ?></a>
                    <p class="description"><?php echo $e->html($site->description); ?></p>
                <?php endif; ?>
            </div>
            <div class="site-search col-3 kit-hidden-tb">
                <form id="search" method="post" action="<?php echo $e->url($view->siteUrl()); ?>" role="search">
                    <label for="s" class="sr-only"><?php _e('搜索关键字'); ?></label>
                    <input type="text" id="s" name="s" class="text" placeholder="<?php _e('输入关键字搜索'); ?>"/>
                    <button type="submit" class="submit"><?php _e('搜索'); ?></button>
                </form>
            </div>
            <div class="col-mb-12">
                <nav id="nav-menu" class="clearfix" role="navigation">
                    <?php echo $view->navigation(); ?>
                </nav>
            </div>
        </div><!-- end .row -->
    </div>
</header><!-- end #header -->
<div id="body">
    <div class="container">
        <div class="row">

    
    
