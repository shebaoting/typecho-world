<?php

namespace Typecho\Plugin;

use Typecho\Event\Dispatcher;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

interface EventProviderInterface
{
    /**
     * 注册插件事件监听器
     *
     * @param Dispatcher $events
     */
    public static function register(Dispatcher $events);
}
