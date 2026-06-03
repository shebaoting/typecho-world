<?php

namespace Typecho\Theme\Event;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

final class ThemeEvents
{
    public const RESOLVING = 'theme.resolving';
    public const RENDERING = 'theme.rendering';
    public const RENDERED = 'theme.rendered';
    public const CONTENT_RENDERING = 'theme.content.rendering';
}
