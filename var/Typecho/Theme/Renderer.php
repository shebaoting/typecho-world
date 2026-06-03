<?php

namespace Typecho\Theme;

use Typecho\Db;
use Typecho\Plugin;
use Typecho\Theme\Event\ThemeRendering;
use Typecho\Theme\WpCompat\Runtime as WpCompatRuntime;
use Widget\Archive;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class Renderer
{
    public static function render(Archive $archive, string $file, Manifest $manifest): void
    {
        $db = Db::get();
        $queryCount = $db->getQueryCount();
        $queryTime = $db->getQueryTime();
        Diagnostics::begin(
            $manifest->theme(),
            $file,
            $archive->getThemeTemplateCandidates(),
            $manifest->isCacheHit()
        );

        $view = new ViewContext($archive, $manifest);
        try {
            $before = new ThemeRendering($archive, $manifest, $view, $file, 'before');
            Plugin::events()->dispatch($before);

            $content = $view->renderFile($before->template());
            $after = new ThemeRendering($archive, $manifest, $view, $before->template(), 'after', $content);
            Plugin::events()->dispatch($after);

            echo $after->content();
        } finally {
            if ($manifest->support('wpCompat')) {
                WpCompatRuntime::clear();
            }

            Diagnostics::finish(
                $db->getQueryCount() - $queryCount,
                $db->getQueryTime() - $queryTime
            );
        }
    }
}
