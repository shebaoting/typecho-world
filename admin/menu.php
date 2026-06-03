<?php if (!defined('__TYPECHO_ADMIN__')) exit; ?>
<header class="typecho-head-nav" role="banner">
    <a class="typecho-nav-brand" href="<?php echo $options->adminUrl; ?>">
        <i class="i-logo-s">Typecho</i>
        <span><?php $options->title(); ?></span>
    </a>
    <nav class="typecho-nav-main" role="navigation" aria-label="<?php _e('后台导航'); ?>">
        <details class="menu-bar">
            <summary><?php _e('菜单'); ?></summary>
        </details>
        <menu class="typecho-nav-menu">
            <?php $menu->output(); ?>
            <li class="operate">
                <?php \Typecho\Plugin::factory('admin/menu.php')->call('navBar'); ?><a title="<?php
                if ($user->logged > 0) {
                    $logged = new \Typecho\Date($user->logged);
                    _e('最后登录: %s', $logged->word());
                }
                ?>" href="<?php $options->adminUrl('profile.php'); ?>" class="author"><?php $user->screenName(); ?></a><a
                    class="exit" href="<?php $options->logoutUrl(); ?>"><?php _e('登出'); ?></a><a
                    href="<?php $options->siteUrl(); ?>"><?php _e('网站'); ?></a>
            </li>
        </menu>
    </nav>
</header>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.typecho-nav-menu > li.has-children').forEach(function (item) {
        var topLine = item.querySelector('.typecho-nav-topline');
        var link = topLine ? topLine.querySelector('a') : null;
        var button = topLine ? topLine.querySelector('.typecho-nav-toggle') : null;

        function setOpen(open) {
            item.classList.toggle('is-open', open);
            if (button) {
                button.setAttribute('aria-expanded', open ? 'true' : 'false');
            }
        }

        function toggle(event) {
            event.preventDefault();
            setOpen(!item.classList.contains('is-open'));
        }

        if (link) {
            link.addEventListener('click', toggle);
        }

        if (button) {
            button.addEventListener('click', toggle);
        }
    });
});
</script>
