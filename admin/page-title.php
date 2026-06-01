<?php if (!defined('__TYPECHO_ADMIN__')) exit; ?>
<div class="typecho-page-title">
    <h2><?php echo $menu->title; ?></h2>
    <?php \Typecho\Plugin::factory('admin/page-title.php')->call('afterTitle', $menu); ?>
    <?php
    if (!empty($menu->addLink)) {
        echo "<a href=\"{$menu->addLink}\">" . _t("新增") . "</a>";
    }
    ?>
</div>
