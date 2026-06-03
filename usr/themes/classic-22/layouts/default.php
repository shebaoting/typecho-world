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
<?php $view->part('header'); ?>
<?php echo $view->slot('content'); ?>
<?php $view->part('footer'); ?>
