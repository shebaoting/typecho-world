<?php

namespace Typecho\Theme\Event;

use Typecho\Event\Event;
use Typecho\Theme\ViewContext;
use Widget\Archive;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class ContentRendering extends Event
{
    public function __construct(Archive $archive, ViewContext $view, string $content, mixed $more = false)
    {
        parent::__construct(ThemeEvents::CONTENT_RENDERING, [
            'archive' => $archive,
            'view'    => $view,
            'content' => $content,
            'more'    => $more,
        ]);
    }

    public function content(): string
    {
        return (string) $this->get('content', '');
    }

    public function setContent(string $content): self
    {
        $this->set('content', $content);
        return $this;
    }
}
