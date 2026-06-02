<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>
<main class="site-main">
    <?php while ($this->next()): ?>
        <article class="post-card">
            <h2><a href="<?php $this->permalink(); ?>"><?php $this->title(); ?></a></h2>
            <p class="post-meta"><?php $this->date(); ?> · <?php $this->category(','); ?></p>
            <div class="post-excerpt"><?php $this->excerpt(160); ?></div>
        </article>
    <?php endwhile; ?>
</main>
<?php $this->need('footer.php'); ?>
