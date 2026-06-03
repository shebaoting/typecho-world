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
<!DOCTYPE html>
<html lang="zh-Hans"<?php if ($site->colorSchema): ?> data-theme="<?php echo $e->attr($site->colorSchema); ?>"<?php endif; ?>>
<head>
    <meta charset="<?php echo $e->attr($site->charset); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $e->html($view->seoTitle(' | ')); ?><?php if ($archive->is('index')): ?> | <?php echo $e->html($site->description); ?><?php endif; ?></title>
    <?php echo $assets->style('theme'); ?>
    <?php if ($site->colorSchema == 'customize'): ?>
    <?php echo $assets->style('custom'); ?>
    <?php endif; ?>
    <?php echo $tokens->styleTag(); ?>
    <?php echo $view->head(); ?>
</head>

<body>

<header class="site-navbar container-fluid">
    <nav>
        <ul class="site-name">
        <?php if ($site->logoUrl): ?>
            <li><a href="<?php echo $e->url($view->siteUrl()); ?>" class="brand"><?php echo $images->tag((string) $site->logoUrl, ['alt' => $site->title, 'loading' => 'eager']); ?></a></li>
        <?php else: ?>
            <li>
                <a href="<?php echo $e->url($view->siteUrl()); ?>" class="brand"><?php echo $e->html($site->title); ?></a>
            </li>
            <li class="desc"><?php echo $e->html($site->description); ?></li>
        <?php endif; ?>
        </ul>

        <ul>
            <li>
                <label for="nav-toggler" class="nav-toggler-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12" /><line x1="3" y1="6" x2="21" y2="6" /><line x1="3" y1="18" x2="21" y2="18" /></svg>
                </label>
            </li>
        </ul>
    </nav>

    <nav class="site-nav">
        <input type="checkbox" id="nav-toggler">

        <ul class="nav-menu">
            <?php echo $view->navigation('<li><a href="{url}"{class}{target}>{label}</a></li>', 'active'); ?>
            <li>
                <form method="post" action="<?php echo $e->url($view->siteUrl()); ?>">
                    <input type="search" id="s" name="s">
                </form>
            </li>
        </ul>
    </nav>
</header>
