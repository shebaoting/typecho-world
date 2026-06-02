<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>
<main class="site-main">
    <article class="post-content">
        <h1><?php $this->title(); ?></h1>
        <p class="post-meta"><?php $this->date(); ?> · <?php $this->category(','); ?></p>
        <?php $this->content(); ?>
    </article>
</main>
<?php $this->need('footer.php'); ?>
