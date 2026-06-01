<?php

namespace Widget;

use Typecho\Common;
use Typecho\Db;
use Widget\Base\Contents;
use Widget\Contents\From as ContentsFrom;
use Widget\Metas\From as MetasFrom;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Sitemap 输出
 */
class Sitemap extends Contents
{
    /**
     * @var array<int, array<string, mixed>>
     */
    private array $items = [];

    /**
     * @throws Db\Exception
     */
    public function execute()
    {
        $this->addUrl($this->options->siteUrl, $this->options->time, 'daily', '1.0');

        $contents = $this->db->fetchAll($this->select('cid', 'modified', 'created', 'type')
            ->where('type IN ?', ['post', 'page'])
            ->where('status = ?', 'publish')
            ->where("password IS NULL OR password = ''")
            ->where('created < ?', $this->options->time)
            ->order('modified', Db::SORT_DESC)
            ->limit(50000));

        foreach ($contents as $row) {
            $content = ContentsFrom::allocWithAlias('sitemap-content-' . $row['cid'], ['cid' => $row['cid']]);

            if ($content->have()) {
                $this->addUrl(
                    $content->permalink,
                    max((int) $row['modified'], (int) $row['created']),
                    'weekly',
                    $row['type'] == 'page' ? '0.7' : '0.8'
                );
            }
        }

        $metas = $this->db->fetchAll($this->db->select('mid', 'type', 'count')
            ->from('table.metas')
            ->where('type IN ?', ['category', 'tag'])
            ->where('count > ?', 0)
            ->order('type', Db::SORT_ASC)
            ->order('order', Db::SORT_ASC));

        foreach ($metas as $row) {
            $meta = MetasFrom::allocWithAlias('sitemap-meta-' . $row['mid'], ['mid' => $row['mid']]);

            if ($meta->have()) {
                $this->addUrl($meta->permalink, null, 'weekly', $row['type'] == 'category' ? '0.6' : '0.5');
            }
        }

        self::pluginHandle()->call('sitemap', $this);
    }

    /**
     * @param string $url
     * @param int|null $modified
     * @param string $changefreq
     * @param string $priority
     * @return void
     */
    public function addUrl(string $url, ?int $modified = null, string $changefreq = 'weekly', string $priority = '0.5')
    {
        $url = Common::safeUrl($url);

        if (isset($this->items[$url])) {
            return;
        }

        $this->items[$url] = [
            'loc'        => $url,
            'lastmod'    => $modified,
            'changefreq' => $changefreq,
            'priority'   => $priority,
        ];
    }

    /**
     * @return void
     */
    public function render()
    {
        $this->response->setContentType('application/xml');
        echo '<?xml version="1.0" encoding="' . $this->options->charset . '"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($this->items as $item) {
            echo "  <url>\n";
            echo '    <loc>' . htmlspecialchars($item['loc'], ENT_XML1) . "</loc>\n";

            if (!empty($item['lastmod'])) {
                echo '    <lastmod>' . date('c', (int) $item['lastmod']) . "</lastmod>\n";
            }

            echo '    <changefreq>' . htmlspecialchars($item['changefreq'], ENT_XML1) . "</changefreq>\n";
            echo '    <priority>' . htmlspecialchars($item['priority'], ENT_XML1) . "</priority>\n";
            echo "  </url>\n";
        }

        echo "</urlset>\n";
    }
}
