<?php

define('__TYPECHO_ROOT_DIR__', dirname(__DIR__));
define('__TYPECHO_PLUGIN_DIR__', '/usr/plugins');
define('__TYPECHO_THEME_DIR__', '/usr/themes');
define('__TYPECHO_ADMIN_DIR__', '/admin/');

require_once __TYPECHO_ROOT_DIR__ . '/var/Typecho/Common.php';

if (!function_exists('singlePing')) {
    function singlePing(\Widget\Comments\Ping $ping, \Typecho\Config $singlePingOptions): void
    {
    }
}
