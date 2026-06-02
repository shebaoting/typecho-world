<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
/**
 * Writer Grid
 *
 * 一个用于应用市场演示的轻量内容外观。
 *
 * @package Writer Grid
 * @author Typecho Lab
 * @version 0.9.0
 * @link https://example.com/typecho/writer-grid
 */
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="<?php $this->options->charset(); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php $this->options->title(); ?></title>
    <link rel="stylesheet" href="<?php $this->options->themeUrl('style.css'); ?>">
</head>
<body>
<main class="site">
    <header class="site-head">
        <h1><a href="<?php $this->options->siteUrl(); ?>"><?php $this->options->title(); ?></a></h1>
        <p><?php $this->options->description(); ?></p>
    </header>
    <section class="post-grid">
        <?php while ($this->next()): ?>
            <article class="post-card">
                <h2><a href="<?php $this->permalink(); ?>"><?php $this->title(); ?></a></h2>
                <p><?php $this->excerpt(120, '...'); ?></p>
            </article>
        <?php endwhile; ?>
    </section>
</main>
</body>
</html>
