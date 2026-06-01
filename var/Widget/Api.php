<?php

namespace Widget;

use Typecho\Common;
use Typecho\Db;
use Typecho\Router;
use Widget\Base\Options as BaseOptions;
use Widget\Contents\From as ContentsFrom;
use Widget\Metas\From as MetasFrom;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 只读 REST API 与导出接口
 */
class Api extends BaseOptions
{
    private const MAX_PAGE_SIZE = 50;

    /**
     * 输出 API 响应
     *
     * @throws \Typecho\Db\Exception
     */
    public function render()
    {
        $endpoint = trim((string) $this->request->get('endpoint', ''), '/');
        $endpoint = preg_replace('/\/+/', '/', $endpoint);

        if (!$this->request->isGet() && 'backup/restore' !== $endpoint) {
            $this->jsonError(_t('请求方法不被允许'), 405);
        }

        match (true) {
            '' === $endpoint || 'site' === $endpoint => $this->site(),
            'posts' === $endpoint => $this->contentList('post'),
            preg_match('/^posts\/(\d+)$/', $endpoint, $matches) === 1 => $this->contentDetail('post', (int) $matches[1]),
            'pages' === $endpoint => $this->contentList('page'),
            preg_match('/^pages\/(\d+)$/', $endpoint, $matches) === 1 => $this->contentDetail('page', (int) $matches[1]),
            'categories' === $endpoint => $this->metaList('category'),
            'tags' === $endpoint => $this->metaList('tag'),
            'comments' === $endpoint => $this->comments(),
            'export.json' === $endpoint => $this->jsonExport(),
            preg_match('/^markdown\/(\d+)$/', $endpoint, $matches) === 1 => $this->markdownExport((int) $matches[1]),
            'backup/export' === $endpoint => $this->backupExport(),
            'backup/restore' === $endpoint => $this->backupRestore(),
            default => $this->jsonError(_t('请求的地址不存在'), 404),
        };
    }

    /**
     * 站点基础信息
     */
    private function site(): never
    {
        $this->json([
            'title'       => $this->options->title,
            'description' => $this->options->description,
            'keywords'    => $this->options->keywords,
            'siteUrl'     => $this->options->siteUrl,
            'feedUrl'     => $this->options->feedUrl,
            'generator'   => $this->options->generator,
            'timezone'    => (int) $this->options->timezone,
            'routes'      => [
                'posts'      => Router::url('api', ['endpoint' => 'posts'], $this->options->index),
                'pages'      => Router::url('api', ['endpoint' => 'pages'], $this->options->index),
                'categories' => Router::url('api', ['endpoint' => 'categories'], $this->options->index),
                'tags'       => Router::url('api', ['endpoint' => 'tags'], $this->options->index),
            ],
        ]);
    }

    /**
     * 内容列表
     *
     * @param string $type
     * @throws \Typecho\Db\Exception
     */
    private function contentList(string $type): never
    {
        $page = max(1, (int) $this->request->filter('int')->get('page', 1));
        $pageSize = min(self::MAX_PAGE_SIZE, max(1, (int) $this->request->filter('int')->get('pageSize', 10)));
        $select = $this->publicContentSelect($type)
            ->order('table.contents.' . ('page' === $type ? 'order' : 'created'), 'page' === $type ? Db::SORT_ASC : Db::SORT_DESC)
            ->page($page, $pageSize);
        $rows = $this->db->fetchAll($select);

        $this->json([
            'page'     => $page,
            'pageSize' => $pageSize,
            'total'    => $this->publicContentCount($type),
            'items'    => array_values(array_filter(array_map(function ($row) {
                return $this->contentPayload((int) $row['cid'], false);
            }, $rows))),
        ]);
    }

    /**
     * 内容详情
     *
     * @param string $type
     * @param int $cid
     * @throws \Typecho\Db\Exception
     */
    private function contentDetail(string $type, int $cid): never
    {
        $row = $this->db->fetchRow($this->publicContentSelect($type)
            ->where('table.contents.cid = ?', $cid)
            ->limit(1));

        if (!$row) {
            $this->jsonError(_t('内容不存在'), 404);
        }

        $this->json($this->contentPayload((int) $row['cid'], true));
    }

    /**
     * 分类或标签列表
     *
     * @param string $type
     * @throws \Typecho\Db\Exception
     */
    private function metaList(string $type): never
    {
        $rows = $this->db->fetchAll($this->db->select()
            ->from('table.metas')
            ->where('type = ?', $type)
            ->where('count > ?', 0)
            ->order('order', Db::SORT_ASC));

        $items = [];
        foreach ($rows as $row) {
            $meta = MetasFrom::allocWithAlias('api-meta-' . $row['mid'], ['mid' => $row['mid']]);
            $items[] = [
                'mid'         => (int) $meta->mid,
                'name'        => $meta->name,
                'slug'        => $meta->slug,
                'type'        => $meta->type,
                'description' => $meta->description,
                'count'       => (int) $meta->count,
                'order'       => (int) $meta->order,
                'parent'      => (int) $meta->parent,
                'permalink'   => $meta->permalink,
            ];
        }

        $this->json(['items' => $items]);
    }

    /**
     * 评论列表
     *
     * @throws \Typecho\Db\Exception
     */
    private function comments(): never
    {
        $page = max(1, (int) $this->request->filter('int')->get('page', 1));
        $pageSize = min(self::MAX_PAGE_SIZE, max(1, (int) $this->request->filter('int')->get('pageSize', 10)));
        $cid = (int) $this->request->filter('int')->get('cid', 0);

        $select = $this->db->select(
            'coid',
            'cid',
            'created',
            'author',
            'url',
            'text',
            'type',
            'parent'
        )->from('table.comments')
            ->where('status = ?', 'approved')
            ->order('coid', Db::SORT_DESC)
            ->page($page, $pageSize);

        $count = $this->db->select(['COUNT(coid)' => 'num'])
            ->from('table.comments')
            ->where('status = ?', 'approved');

        if ($cid > 0) {
            $select->where('cid = ?', $cid);
            $count->where('cid = ?', $cid);
        }

        $rows = $this->db->fetchAll($select);
        $items = array_map(fn($row) => [
            'coid'    => (int) $row['coid'],
            'cid'     => (int) $row['cid'],
            'created' => (int) $row['created'],
            'date'    => date(DATE_ATOM, (int) $row['created']),
            'author'  => $row['author'],
            'url'     => $row['url'],
            'text'    => $row['text'],
            'type'    => $row['type'],
            'parent'  => (int) $row['parent'],
        ], $rows);

        $this->json([
            'page'     => $page,
            'pageSize' => $pageSize,
            'total'    => (int) $this->db->fetchObject($count)->num,
            'items'    => $items,
        ]);
    }

    /**
     * JSON 全站导出
     *
     * @throws \Typecho\Db\Exception
     */
    private function jsonExport(): never
    {
        $this->requireToken();

        $data = [
            'type'       => 'typecho-json-export',
            'version'    => Common::VERSION,
            'exportedAt' => date(DATE_ATOM, $this->options->time),
            'site'       => [
                'title'       => $this->options->title,
                'description' => $this->options->description,
                'keywords'    => $this->options->keywords,
                'siteUrl'     => $this->options->siteUrl,
                'timezone'    => (int) $this->options->timezone,
            ],
            'tables'     => [
                'contents'      => $this->fetchTable('contents'),
                'metas'         => $this->fetchTable('metas'),
                'relationships' => $this->fetchTable('relationships'),
                'fields'        => $this->fetchTable('fields'),
                'comments'      => $this->fetchTable('comments'),
                'users'         => $this->fetchPublicUsers(),
                'options'       => $this->fetchPublicOptions(),
            ],
        ];

        $host = parse_url($this->options->siteUrl, PHP_URL_HOST) ?: 'site';
        $this->response->setHeader(
            'Content-Disposition',
            'attachment; filename="' . date('Ymd') . '_' . $host . '_site.json"'
        );
        $this->json($data);
    }

    /**
     * 单篇 Markdown 导出
     *
     * @param int $cid
     * @throws \Typecho\Db\Exception
     */
    private function markdownExport(int $cid): never
    {
        $this->requireToken();
        $content = ContentsFrom::allocWithAlias('api-markdown-' . $cid, ['cid' => $cid]);

        if (!$content->have() || !in_array($content->type, ['post', 'page', 'post_draft', 'page_draft'])) {
            $this->jsonError(_t('内容不存在'), 404);
        }

        $meta = [
            'title'      => htmlspecialchars_decode($content->title, ENT_QUOTES),
            'slug'       => $content->slug,
            'cid'        => (int) $content->cid,
            'type'       => $content->type,
            'status'     => $content->status,
            'date'       => $content->date->format(DATE_ATOM),
            'modified'   => date(DATE_ATOM, (int) $content->modified),
            'permalink'  => $content->permalink,
            'categories' => array_column($content->categories, 'name'),
            'tags'       => array_column($content->tags, 'name'),
            'fields'     => $content->fields->toArray(),
        ];

        $markdown = $this->frontMatter($meta) . "\n" . (string) $content->text;
        $filename = preg_replace('/[^\w.-]+/', '-', $content->slug ?: ('post-' . $content->cid));

        $this->response->setHeader(
            'Content-Disposition',
            'attachment; filename="' . trim($filename, '-') . '.md"'
        );
        $this->response->throwContent($markdown, 'text/markdown');
        exit;
    }

    /**
     * 备份下载接口
     */
    private function backupExport(): never
    {
        $this->requireToken();
        Backup::alloc()->exportBackup();
    }

    /**
     * 备份恢复接口
     */
    private function backupRestore(): never
    {
        if (!$this->request->isPost()) {
            $this->jsonError(_t('请求方法不被允许'), 405);
        }

        $this->requireToken();
        $path = null;

        if (!empty($_FILES)) {
            $file = array_pop($_FILES);
            if (UPLOAD_ERR_OK === $file['error'] && is_uploaded_file($file['tmp_name'])) {
                $path = $file['tmp_name'];
            }
        } elseif ($this->request->is('file')) {
            $file = basename((string) $this->request->get('file'));
            $path = __TYPECHO_BACKUP_DIR__ . '/' . $file;
        }

        if (empty($path) || !file_exists($path)) {
            $this->jsonError(_t('备份文件不存在'), 400);
        }

        Backup::alloc()->restoreFromPath($path);
    }

    /**
     * 公开内容查询
     *
     * @param string $type
     * @return \Typecho\Db\Query
     */
    private function publicContentSelect(string $type): \Typecho\Db\Query
    {
        return $this->db->select()
            ->from('table.contents')
            ->where('table.contents.type = ?', $type)
            ->where('table.contents.status = ?', 'publish')
            ->where('table.contents.created < ?', $this->options->time)
            ->where('(table.contents.password IS NULL OR table.contents.password = ?)', '');
    }

    /**
     * 公开内容总数
     *
     * @param string $type
     * @return int
     * @throws \Typecho\Db\Exception
     */
    private function publicContentCount(string $type): int
    {
        return (int) $this->db->fetchObject($this->db->select(['COUNT(table.contents.cid)' => 'num'])
            ->from('table.contents')
            ->where('table.contents.type = ?', $type)
            ->where('table.contents.status = ?', 'publish')
            ->where('table.contents.created < ?', $this->options->time)
            ->where('(table.contents.password IS NULL OR table.contents.password = ?)', ''))->num;
    }

    /**
     * 内容输出结构
     *
     * @param int $cid
     * @param bool $includeContent
     * @return array|null
     */
    private function contentPayload(int $cid, bool $includeContent): ?array
    {
        $content = ContentsFrom::allocWithAlias(
            'api-content-' . $cid . '-' . (int) $includeContent,
            ['cid' => $cid]
        );

        if (!$content->have()) {
            return null;
        }

        $fields = $content->fields->toArray();
        $publicFields = array_filter($fields, fn($key) => !str_starts_with((string) $key, '_'), ARRAY_FILTER_USE_KEY);
        $payload = [
            'cid'         => (int) $content->cid,
            'title'       => htmlspecialchars_decode($content->title, ENT_QUOTES),
            'slug'        => $content->slug,
            'type'        => $content->type,
            'created'     => (int) $content->created,
            'date'        => $content->date->format(DATE_ATOM),
            'modified'    => (int) $content->modified,
            'modifiedDate'=> date(DATE_ATOM, (int) $content->modified),
            'permalink'   => $content->permalink,
            'commentsNum' => (int) $content->commentsNum,
            'allowComment'=> (bool) $content->allowComment,
            'author'      => [
                'uid'        => (int) $content->author->uid,
                'screenName' => $content->author->screenName,
                'url'        => $content->author->url,
                'permalink'  => $content->author->permalink,
            ],
            'categories'  => $content->categories,
            'tags'        => $content->tags,
            'seo'         => [
                'title'       => $fields['_seo_title'] ?? '',
                'description' => $fields['_seo_description'] ?? '',
                'ogImage'     => $fields['_og_image'] ?? '',
            ],
            'meta'        => [
                'pinned'   => (bool) ($fields['_pinned'] ?? false),
                'featured' => (bool) ($fields['_featured'] ?? false),
                'series'   => (string) ($fields['_series'] ?? ''),
            ],
            'fields'      => $publicFields,
        ];

        if ($includeContent) {
            $payload['content'] = [
                'html'    => $content->content,
                'excerpt' => $content->plainExcerpt,
            ];
        } else {
            $payload['excerpt'] = $content->plainExcerpt;
        }

        return $payload;
    }

    /**
     * API Token 验证
     */
    private function requireToken(): void
    {
        $token = trim((string) ($this->options->apiToken ?? ''));

        if ('' === $token) {
            $this->jsonError(_t('API Token 尚未生成'), 403);
        }

        $input = trim((string) $this->request->get('token', ''));
        $header = trim((string) $this->request->getHeader('Authorization', ''));

        if (preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            $input = trim($matches[1]);
        } elseif ('' === $input) {
            $input = trim((string) $this->request->getHeader('X-Typecho-Token', ''));
        }

        if ('' === $input || !hash_equals($token, $input)) {
            $this->jsonError(_t('禁止访问'), 403);
        }
    }

    /**
     * 获取整张表
     *
     * @param string $table
     * @return array
     * @throws \Typecho\Db\Exception
     */
    private function fetchTable(string $table): array
    {
        return $this->db->fetchAll($this->db->select()->from('table.' . $table));
    }

    /**
     * 导出公开用户资料
     *
     * @return array
     * @throws \Typecho\Db\Exception
     */
    private function fetchPublicUsers(): array
    {
        return $this->db->fetchAll($this->db->select('uid', 'name', 'mail', 'url', 'screenName', 'created', 'group')
            ->from('table.users'));
    }

    /**
     * 导出非敏感设置
     *
     * @return array
     * @throws \Typecho\Db\Exception
     */
    private function fetchPublicOptions(): array
    {
        $rows = $this->db->fetchAll($this->db->select('name', 'value')->from('table.options')->where('user = ?', 0));
        $blocked = ['secret', 'apiToken'];

        return array_values(array_filter($rows, function ($row) use ($blocked) {
            return !in_array($row['name'], $blocked) && strpos($row['name'], 'plugin:') !== 0;
        }));
    }

    /**
     * 生成 Markdown front matter
     *
     * @param array $meta
     * @return string
     */
    private function frontMatter(array $meta): string
    {
        $lines = ['---'];

        foreach ($meta as $key => $value) {
            if (is_array($value)) {
                $this->appendYamlArray($lines, $key, $value);
            } else {
                $lines[] = $key . ': ' . $this->yamlScalar($value);
            }
        }

        $lines[] = '---';
        return implode("\n", $lines);
    }

    /**
     * @param array $lines
     * @param string $key
     * @param array $value
     */
    private function appendYamlArray(array &$lines, string $key, array $value): void
    {
        if (empty($value)) {
            $lines[] = $key . ': []';
            return;
        }

        $isList = array_keys($value) === range(0, count($value) - 1);
        $lines[] = $key . ':';

        foreach ($value as $itemKey => $item) {
            $item = is_array($item)
                ? json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : $item;
            $lines[] = $isList
                ? '  - ' . $this->yamlScalar($item)
                : '  ' . $itemKey . ': ' . $this->yamlScalar($item);
        }
    }

    /**
     * @param mixed $value
     * @return string
     */
    private function yamlScalar($value): string
    {
        return '"' . str_replace(['\\', '"', "\n", "\r"], ['\\\\', '\"', '\n', ''], (string) $value) . '"';
    }

    /**
     * 输出 JSON
     *
     * @param mixed $data
     * @param int $status
     */
    private function json($data, int $status = 200): never
    {
        $this->response->setStatus($status)
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->throwCallback(function () use ($data) {
                echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }, 'application/json');
    }

    /**
     * 输出 JSON 错误
     *
     * @param string $message
     * @param int $status
     */
    private function jsonError(string $message, int $status): never
    {
        $this->json([
            'error'   => true,
            'code'    => $status,
            'message' => $message,
        ], $status);
    }
}
