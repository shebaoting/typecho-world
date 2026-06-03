<?php

namespace Typecho\Theme;

use Typecho\Common;
use Widget\Comments\Recent as RecentComments;
use Widget\Contents\Post\Date as PostDate;
use Widget\Contents\Post\Recent as RecentPosts;
use Widget\Metas\Category\Rows as CategoryRows;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class DataProvider
{
    public function __construct(private ViewContext $view)
    {
    }

    /**
     * 获取最新文章数据
     *
     * @return array<int, array<string, mixed>>
     */
    public function recentPosts(int $limit = 0): array
    {
        $limit = $limit > 0 ? $limit : (int) $this->view->site()->postsListSize;
        $posts = RecentPosts::alloc(['pageSize' => $limit], null, false);
        $items = [];

        while ($posts->next()) {
            $items[] = [
                'title'     => (string) $posts->title,
                'permalink' => (string) $posts->permalink,
                'created'   => (int) $posts->created,
                'date'      => $posts->date,
            ];
        }

        return $items;
    }

    public function recentPostsHtml(int $limit = 0): string
    {
        $e = $this->view->e();
        $html = '';

        foreach ($this->recentPosts($limit) as $post) {
            $html .= '<li><a href="' . $e->url($post['permalink']) . '">' . $e->html($post['title']) . '</a></li>';
        }

        return $html;
    }

    /**
     * 获取最新评论数据
     *
     * @return array<int, array<string, mixed>>
     */
    public function recentComments(int $limit = 0, int $excerptLength = 35): array
    {
        $limit = $limit > 0 ? $limit : (int) $this->view->site()->commentsListSize;
        $comments = RecentComments::alloc(['pageSize' => $limit], null, false);
        $items = [];

        while ($comments->next()) {
            $items[] = [
                'author'    => (string) $comments->author,
                'permalink' => (string) $comments->permalink,
                'excerpt'   => Common::subStr(strip_tags((string) $comments->content), 0, $excerptLength, '...'),
                'created'   => (int) $comments->created,
                'date'      => $comments->date,
            ];
        }

        return $items;
    }

    public function recentCommentsHtml(int $limit = 0, int $excerptLength = 35): string
    {
        $e = $this->view->e();
        $html = '';

        foreach ($this->recentComments($limit, $excerptLength) as $comment) {
            $html .= '<li><a href="' . $e->url($comment['permalink']) . '">' . $e->html($comment['author'])
                . '</a>: ' . $e->html($comment['excerpt']) . '</li>';
        }

        return $html;
    }

    public function categoriesHtml(string $options = 'wrapClass=widget-list'): string
    {
        return $this->view->capture(fn () => CategoryRows::alloc(null, null, false)->listCategories($options));
    }

    public function archivesHtml(string $format = 'F Y'): string
    {
        return $this->view->capture(fn () => PostDate::alloc(
            'type=month&format=' . rawurlencode($format),
            null,
            false
        )->parse('<li><a href="{permalink}">{date}</a></li>'));
    }
}
