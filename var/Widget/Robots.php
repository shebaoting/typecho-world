<?php

namespace Widget;

use Typecho\Router;
use Widget\Base;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Robots 输出
 */
class Robots extends Base
{
    /**
     * @var string
     */
    private string $content = '';

    /**
     * @return void
     */
    public function execute()
    {
        $content = trim((string) ($this->options->robotsTxt ?? ''));

        if ('' === $content) {
            $content = "User-agent: *\n";
            $content .= "Allow: /\n";
            $content .= 'Sitemap: ' . Router::url('sitemap', [], $this->options->index) . "\n";
        }

        $this->content = self::pluginHandle()->filter('robots', $content, $this);
    }

    /**
     * @return void
     */
    public function render()
    {
        $this->response->setContentType('text/plain');
        echo rtrim($this->content) . "\n";
    }
}
