<?php

namespace Typecho\Theme\Event;

use Typecho\Event\Event;
use Typecho\Theme\Manifest;
use Widget\Archive;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class ThemeResolving extends Event
{
    public function __construct(Archive $archive, Manifest $manifest, string $template, array $candidates)
    {
        parent::__construct(ThemeEvents::RESOLVING, [
            'archive'    => $archive,
            'manifest'   => $manifest,
            'template'   => $template,
            'candidates' => $candidates,
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

    public function candidates(): array
    {
        return (array) $this->get('candidates', []);
    }

    public function setCandidates(array $candidates): self
    {
        $this->set('candidates', $candidates);
        return $this;
    }
}
