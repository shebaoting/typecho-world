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
<footer class="site-footer container-fluid">
    <div class="d-flex justify-content-between">
        <ul class="list-inline text-muted">
            <li>&copy; <?php echo date('Y'); ?> <a href="<?php echo $e->url($view->siteUrl()); ?>"><?php echo $e->html($site->title); ?></a></li>
            <li><a href="<?php echo $e->url($view->feedUrl()); ?>"><?php _e('RSS'); ?></a></li>
        </ul>
        <ul class="list-inline text-muted">
            <li>
                <?php _e('Powered by <a href="https://typecho.org">Typecho</a>'); ?>
            </li>
        </ul>
    </div>
</footer>

<?php echo $view->footer(); ?>

</body>
</html>
