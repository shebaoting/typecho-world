<?php

use Typecho\Theme\WpCompat\Runtime;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

if (!function_exists('get_header')) {
    function get_header($name = null, $args = []): void
    {
        Runtime::renderHeader(is_string($name) ? $name : null, is_array($args) ? $args : []);
    }
}

if (!function_exists('get_footer')) {
    function get_footer($name = null, $args = []): void
    {
        Runtime::renderFooter(is_string($name) ? $name : null, is_array($args) ? $args : []);
    }
}

if (!function_exists('get_sidebar')) {
    function get_sidebar($name = null, $args = []): void
    {
        Runtime::renderSidebar(is_string($name) ? $name : null, is_array($args) ? $args : []);
    }
}

if (!function_exists('get_template_part')) {
    function get_template_part($slug, $name = null, $args = []): void
    {
        Runtime::renderTemplatePart((string) $slug, is_string($name) ? $name : null, is_array($args) ? $args : []);
    }
}

if (!function_exists('locate_template')) {
    function locate_template($template_names, $load = false, $load_once = true, $args = [])
    {
        return Runtime::locateTemplate($template_names, (bool) $load, is_array($args) ? $args : []);
    }
}

if (!function_exists('have_posts')) {
    function have_posts(): bool
    {
        return Runtime::havePosts();
    }
}

if (!function_exists('the_post')) {
    function the_post(): bool
    {
        return Runtime::thePost();
    }
}

if (!function_exists('rewind_posts')) {
    function rewind_posts(): void
    {
        while (Runtime::archive()->next()) {
        }
    }
}

if (!function_exists('wp_head')) {
    function wp_head(): void
    {
        echo Runtime::head();
    }
}

if (!function_exists('wp_footer')) {
    function wp_footer(): void
    {
        echo Runtime::footer();
    }
}

if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style($handle, $src = '', $deps = [], $ver = false, $media = 'all'): void
    {
        Runtime::enqueueStyle((string) $handle, (string) $src, (array) $deps, $ver, (string) $media);
    }
}

if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script($handle, $src = '', $deps = [], $ver = false, $in_footer = false): void
    {
        Runtime::enqueueScript((string) $handle, (string) $src, (array) $deps, $ver, (bool) $in_footer);
    }
}

if (!function_exists('add_action')) {
    function add_action($hook_name, $callback, $priority = 10, $accepted_args = 1): void
    {
        if (is_callable($callback)) {
            Runtime::addAction((string) $hook_name, $callback, (int) $priority);
        }
    }
}

if (!function_exists('do_action')) {
    function do_action($hook_name, ...$arg): void
    {
        Runtime::doAction((string) $hook_name, ...$arg);
    }
}

if (!function_exists('add_filter')) {
    function add_filter($hook_name, $callback, $priority = 10, $accepted_args = 1): void
    {
        if (is_callable($callback)) {
            Runtime::addFilter((string) $hook_name, $callback, (int) $priority);
        }
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters($hook_name, $value, ...$args)
    {
        return Runtime::applyFilters((string) $hook_name, $value, ...$args);
    }
}

if (!function_exists('get_bloginfo')) {
    function get_bloginfo($show = 'name')
    {
        return match ((string) $show) {
            'name'        => Runtime::siteOption('title'),
            'description' => Runtime::siteOption('description'),
            'charset'     => Runtime::siteOption('charset', 'UTF-8'),
            'url', 'wpurl' => Runtime::siteUrl(),
            default       => '',
        };
    }
}

if (!function_exists('bloginfo')) {
    function bloginfo($show = 'name'): void
    {
        echo Runtime::escHtml(get_bloginfo($show));
    }
}

if (!function_exists('home_url')) {
    function home_url($path = ''): string
    {
        return Runtime::siteUrl(is_string($path) ? $path : '');
    }
}

if (!function_exists('site_url')) {
    function site_url($path = ''): string
    {
        return Runtime::siteUrl(is_string($path) ? $path : '');
    }
}

if (!function_exists('get_template_directory_uri')) {
    function get_template_directory_uri(): string
    {
        return Runtime::themeUrl();
    }
}

if (!function_exists('get_stylesheet_directory_uri')) {
    function get_stylesheet_directory_uri(): string
    {
        return Runtime::themeUrl();
    }
}

if (!function_exists('get_theme_file_uri')) {
    function get_theme_file_uri($file = ''): string
    {
        return Runtime::themeUrl(is_string($file) ? $file : '');
    }
}

if (!function_exists('get_parent_theme_file_uri')) {
    function get_parent_theme_file_uri($file = ''): string
    {
        return Runtime::themeUrl(is_string($file) ? $file : '');
    }
}

if (!function_exists('get_template_directory')) {
    function get_template_directory(): string
    {
        return Runtime::themePath();
    }
}

if (!function_exists('get_theme_file_path')) {
    function get_theme_file_path($file = ''): string
    {
        return Runtime::themePath(is_string($file) ? $file : '');
    }
}

if (!function_exists('get_parent_theme_file_path')) {
    function get_parent_theme_file_path($file = ''): string
    {
        return Runtime::themePath(is_string($file) ? $file : '');
    }
}

if (!function_exists('language_attributes')) {
    function language_attributes(): void
    {
        echo 'lang="' . Runtime::escAttr(str_replace('_', '-', (string) Runtime::siteOption('lang', 'zh-CN'))) . '"';
    }
}

if (!function_exists('body_class')) {
    function body_class($class = ''): void
    {
        $classes = array_filter(array_merge(['typecho-world', 'wp-compat'], is_array($class) ? $class : explode(' ', (string) $class)));
        echo 'class="' . Runtime::escAttr(implode(' ', array_unique($classes))) . '"';
    }
}

if (!function_exists('post_class')) {
    function post_class($class = '', $post_id = null): void
    {
        $classes = array_filter(array_merge(['post'], is_array($class) ? $class : explode(' ', (string) $class)));
        echo 'class="' . Runtime::escAttr(implode(' ', array_unique($classes))) . '"';
    }
}

if (!function_exists('the_ID')) {
    function the_ID(): void
    {
        echo (int) Runtime::archive()->cid;
    }
}

if (!function_exists('get_the_ID')) {
    function get_the_ID(): int
    {
        return (int) Runtime::archive()->cid;
    }
}

if (!function_exists('get_the_title')) {
    function get_the_title($post = null): string
    {
        return html_entity_decode((string) Runtime::archive()->title, ENT_QUOTES, Runtime::view()->site()->charset);
    }
}

if (!function_exists('the_title')) {
    function the_title($before = '', $after = '', $echo = true)
    {
        $title = (string) $before . Runtime::escHtml(get_the_title()) . (string) $after;
        if ($echo) {
            echo $title;
            return null;
        }

        return $title;
    }
}

if (!function_exists('get_permalink')) {
    function get_permalink($post = null): string
    {
        return (string) Runtime::archive()->permalink;
    }
}

if (!function_exists('the_permalink')) {
    function the_permalink($post = null): void
    {
        echo Runtime::escUrl(get_permalink($post));
    }
}

if (!function_exists('get_the_content')) {
    function get_the_content($more_link_text = null, $strip_teaser = false, $post = null): string
    {
        return Runtime::view()->content($more_link_text ?? false);
    }
}

if (!function_exists('the_content')) {
    function the_content($more_link_text = null, $strip_teaser = false): void
    {
        echo get_the_content($more_link_text, $strip_teaser);
    }
}

if (!function_exists('get_the_date')) {
    function get_the_date($format = '', $post = null): string
    {
        return Runtime::archive()->date->format($format ?: Runtime::view()->site()->postDateFormat);
    }
}

if (!function_exists('the_date')) {
    function the_date($format = ''): void
    {
        echo Runtime::escHtml(get_the_date($format));
    }
}

if (!function_exists('the_time')) {
    function the_time($format = ''): void
    {
        echo Runtime::escHtml(get_the_date($format));
    }
}

if (!function_exists('the_category')) {
    function the_category($separator = '', $parents = '', $post_id = false): void
    {
        echo Runtime::view()->capture(fn () => Runtime::archive()->category((string) $separator, true, ''));
    }
}

if (!function_exists('the_tags')) {
    function the_tags($before = null, $sep = ', ', $after = ''): void
    {
        $tags = Runtime::view()->capture(fn () => Runtime::archive()->tags((string) $sep, true, ''));
        if ($tags !== '') {
            echo (string) $before . $tags . (string) $after;
        }
    }
}

if (!function_exists('comments_number')) {
    function comments_number($zero = false, $one = false, $more = false): void
    {
        $num = (int) Runtime::archive()->commentsNum;
        $format = $num === 0 ? ($zero ?: '0') : ($num === 1 ? ($one ?: '1') : ($more ?: '%'));
        echo Runtime::escHtml(str_replace('%', (string) $num, (string) $format));
    }
}

if (!function_exists('comments_template')) {
    function comments_template($file = '/comments.php', $separate_comments = false): void
    {
        $file = trim((string) $file, '/');
        if ($file !== '' && Runtime::view()->theme()->hasFile($file)) {
            echo Runtime::view()->renderFile($file);
            return;
        }

        Runtime::view()->part('comments');
    }
}

if (!function_exists('get_search_form')) {
    function get_search_form($args = []): void
    {
        if (Runtime::view()->theme()->hasFile('searchform.php')) {
            echo Runtime::view()->renderFile('searchform.php', is_array($args) ? $args : []);
            return;
        }

        echo '<form method="post" action="' . Runtime::escUrl(Runtime::siteUrl()) . '">'
            . '<input type="search" name="s">'
            . '</form>';
    }
}

if (!function_exists('wp_nav_menu')) {
    function wp_nav_menu($args = []): void
    {
        $args = is_array($args) ? $args : [];
        $format = $args['format'] ?? '<a href="{url}"{class}{target}>{label}</a>';
        echo Runtime::view()->navigation((string) $format);
    }
}

if (!function_exists('load_theme_textdomain')) {
    function load_theme_textdomain($domain, $path = false): bool
    {
        return true;
    }
}

if (!function_exists('is_admin')) {
    function is_admin(): bool
    {
        return false;
    }
}

if (!function_exists('is_singular')) {
    function is_singular($post_types = ''): bool
    {
        return Runtime::hasView() && Runtime::archive()->is('single');
    }
}

if (!function_exists('is_single')) {
    function is_single($post = ''): bool
    {
        return Runtime::hasView() && Runtime::archive()->is('post');
    }
}

if (!function_exists('is_page')) {
    function is_page($page = ''): bool
    {
        return Runtime::hasView() && Runtime::archive()->is('page');
    }
}

if (!function_exists('is_home')) {
    function is_home(): bool
    {
        return Runtime::hasView() && Runtime::archive()->is('index');
    }
}

if (!function_exists('is_front_page')) {
    function is_front_page(): bool
    {
        return Runtime::hasView() && Runtime::archive()->is('index');
    }
}

if (!function_exists('is_archive')) {
    function is_archive(): bool
    {
        return Runtime::hasView() && Runtime::archive()->is('archive');
    }
}

if (!function_exists('is_search')) {
    function is_search(): bool
    {
        return Runtime::hasView() && Runtime::archive()->is('search');
    }
}

if (!function_exists('is_404')) {
    function is_404(): bool
    {
        return Runtime::hasView() && Runtime::archive()->is('404');
    }
}

if (!function_exists('add_image_size')) {
    function add_image_size($name, $width = 0, $height = 0, $crop = false): void
    {
    }
}

if (!function_exists('wp_add_inline_style')) {
    function wp_add_inline_style($handle, $data): bool
    {
        Runtime::addInlineStyle((string) $handle, (string) $data);
        return true;
    }
}

if (!function_exists('wp_dequeue_style')) {
    function wp_dequeue_style($handle): void
    {
        Runtime::dequeueStyle((string) $handle);
    }
}

if (!function_exists('wp_dequeue_script')) {
    function wp_dequeue_script($handle): void
    {
        Runtime::dequeueScript((string) $handle);
    }
}

if (!function_exists('wp_script_add_data')) {
    function wp_script_add_data($handle, $key, $value): bool
    {
        return true;
    }
}

if (!function_exists('is_active_sidebar')) {
    function is_active_sidebar($index): bool
    {
        return false;
    }
}

if (!function_exists('dynamic_sidebar')) {
    function dynamic_sidebar($index = 1): bool
    {
        return false;
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text): string
    {
        return Runtime::escHtml($text);
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr($text): string
    {
        return Runtime::escAttr($text);
    }
}

if (!function_exists('esc_url')) {
    function esc_url($url): string
    {
        return Runtime::escUrl($url);
    }
}

if (!function_exists('__')) {
    function __($text, $domain = 'default'): string
    {
        return _t((string) $text);
    }
}

if (!function_exists('wp_kses_post')) {
    function wp_kses_post($data): string
    {
        return (string) $data;
    }
}

if (!function_exists('wp_strip_all_tags')) {
    function wp_strip_all_tags($text, $remove_breaks = false): string
    {
        return strip_tags((string) $text);
    }
}

if (!function_exists('absint')) {
    function absint($maybeint): int
    {
        return abs((int) $maybeint);
    }
}

if (!function_exists('sanitize_key')) {
    function sanitize_key($key): string
    {
        return preg_replace('/[^a-z0-9_-]/', '', strtolower((string) $key)) ?? '';
    }
}

if (!function_exists('has_post_thumbnail')) {
    function has_post_thumbnail($post = null): bool
    {
        return false;
    }
}

if (!function_exists('the_post_thumbnail')) {
    function the_post_thumbnail($size = 'post-thumbnail', $attr = ''): void
    {
    }
}

if (!function_exists('has_tag')) {
    function has_tag($tag = '', $post = null): bool
    {
        return !empty(Runtime::archive()->tags);
    }
}

if (!function_exists('wp_link_pages')) {
    function wp_link_pages($args = ''): void
    {
    }
}

if (!function_exists('wp_reset_postdata')) {
    function wp_reset_postdata(): void
    {
    }
}

if (!function_exists('add_theme_support')) {
    function add_theme_support($feature, ...$args): void
    {
    }
}

if (!function_exists('register_nav_menus')) {
    function register_nav_menus($locations = []): void
    {
    }
}
