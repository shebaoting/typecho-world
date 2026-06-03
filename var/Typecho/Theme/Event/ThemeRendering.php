<?php

namespace Typecho\Theme\Event;

use Typecho\Event\Event;
use Typecho\Theme\Manifest;
use Typecho\Theme\ViewContext;
use Widget\Archive;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class ThemeRendering extends Event
{
    public function __construct(
        Archive $archive,
        Manifest $manifest,
        ViewContext $view,
        string $template,
        string $stage,
        string $content = ''
    ) {
        parent::__construct($stage === 'after' ? ThemeEvents::RENDERED : ThemeEvents::RENDERING, [
            'archive'  => $archive,
            'manifest' => $manifest,
            'view'     => $view,
            'template' => $template,
            'stage'    => $stage,
            'content'  => $content,
        ]);
    }

    public function template(): string
    {
        return (string) $this->get('template');
    }

    public function setTemplate(string $template): self
    {
        $this->set('template', $template);
        return $this;
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
