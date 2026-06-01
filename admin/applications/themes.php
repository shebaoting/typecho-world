<?php if (!defined('__TYPECHO_ADMIN__')) exit; ?>
<?php
$canEditTheme = \Widget\Themes\Files::isWriteable();
$hasThemeConfig = \Widget\Themes\Config::isExists();
?>
<div class="row typecho-page-main" role="main">
    <div class="col-mb-12">
        <?php if ($options->missingTheme): ?>
            <div class="message notice">
                <p><strong><?php _e('检测到您之前使用的 "%s" 外观文件不存在，您可以重新上传此外观或者启用其他外观。', $options->missingTheme); ?></strong></p>
                <ul>
                    <li><?php _e('重新上传此外观后刷新当前页面，此提示将会消失。'); ?></li>
                    <li><?php _e('启用新外观后，当前外观的设置数据将被删除。'); ?></li>
                </ul>
            </div>
        <?php endif; ?>

        <div class="typecho-theme-grid">
            <?php \Widget\Themes\Rows::alloc()->to($themes); ?>
            <?php while ($themes->next()): ?>
                <?php $isCurrentTheme = $themes->activated && !$options->missingTheme; ?>
                <article id="theme-<?php $themes->name(); ?>"
                         class="typecho-theme-card<?php if ($isCurrentTheme): ?> current<?php endif; ?>"
                         <?php if ($isCurrentTheme): ?>aria-current="true"<?php endif; ?>>
                    <figure class="typecho-theme-screen">
                        <?php if ($isCurrentTheme): ?><span class="sr-only"><?php _e('当前外观'); ?></span><?php endif; ?>
                        <img src="<?php $themes->screen(); ?>" alt="<?php $themes->name(); ?>"/>
                        <figcaption class="typecho-theme-actions">
                            <?php if ($isCurrentTheme): ?>
                                <a href="<?php $options->adminUrl('navigation.php'); ?>"><?php _e('导航菜单'); ?></a>
                                <?php if ($canEditTheme): ?>
                                    <a href="<?php $options->adminUrl('theme-editor.php?theme=' . $themes->name); ?>"><?php _e('编辑'); ?></a>
                                <?php endif; ?>
                                <?php if ($hasThemeConfig): ?>
                                    <a href="<?php $options->adminUrl('options-theme.php'); ?>"><?php _e('设置'); ?></a>
                                <?php endif; ?>
                            <?php else: ?>
                                <a href="<?php echo rtrim($options->siteUrl, '/') . '/?themePreview=' . rawurlencode($themes->name); ?>"
                                   target="_blank" rel="noopener"><?php _e('预览'); ?></a>
                                <?php if ($canEditTheme): ?>
                                    <a href="<?php $options->adminUrl('theme-editor.php?theme=' . $themes->name); ?>"><?php _e('编辑'); ?></a>
                                <?php endif; ?>
                                <a href="<?php $security->index('/action/themes-edit?change=' . $themes->name); ?>"><?php _e('启用'); ?></a>
                            <?php endif; ?>
                        </figcaption>
                    </figure>
                    <div class="typecho-theme-detail">
                        <h3><?php '' != $themes->title ? $themes->title() : $themes->name(); ?></h3>
                        <cite>
                            <?php if ($themes->author): ?><?php _e('作者'); ?>: <?php if ($themes->homepage): ?><a href="<?php $themes->homepage() ?>"><?php endif; ?><?php $themes->author(); ?><?php if ($themes->homepage): ?></a><?php endif; ?><?php endif; ?>
                            <?php if ($themes->version): ?><span><?php _e('版本'); ?>: <?php $themes->version() ?></span><?php endif; ?>
                        </cite>
                        <p><?php echo nl2br($themes->description); ?></p>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>
    </div>
</div>
