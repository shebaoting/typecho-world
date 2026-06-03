<?php

namespace Typecho\Theme;

use Widget\Archive;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class TemplateResolver
{
    public function __construct(private Manifest $manifest)
    {
    }

    public function resolve(Archive $archive): array
    {
        $type = (string) ($archive->getArchiveType() ?: 'index');
        $slug = (string) ($archive->getArchiveSlug() ?: '');
        $candidates = [];

        $custom = $archive->getThemeFile();
        if ($custom !== '') {
            $this->push($candidates, 'custom', $custom);
        }

        if ($type === 'index') {
            $this->push($candidates, 'front-page', $this->manifest->template('front-page'));
            $this->push($candidates, 'front-page', 'front-page.php');
            $this->push($candidates, 'home', $this->manifest->template('home'));
            $this->push($candidates, 'home', 'home.php');
        }

        if ($slug !== '') {
            $this->push($candidates, $type . ':' . $slug, $this->manifest->template($type . ':' . $slug));
            $this->push($candidates, $type . '-' . $slug, $type . '-' . $slug . '.php');
            $this->push($candidates, $type . '/' . $slug, $type . '/' . $slug . '.php');
        }

        $this->push($candidates, $type, $this->manifest->template($type));
        $this->push($candidates, $type, $type . '.php');

        if ($type === 'attachment') {
            $this->push($candidates, 'page', $this->manifest->template('page'));
            $this->push($candidates, 'page', 'page.php');
            $this->push($candidates, 'post', $this->manifest->template('post'));
            $this->push($candidates, 'post', 'post.php');
        }

        if (!in_array($type, ['index', 'front'], true)) {
            $fallback = $archive->is('single') ? 'single' : 'archive';
            $this->push($candidates, $fallback, $this->manifest->template($fallback));
            $this->push($candidates, $fallback, $fallback . '.php');
        }

        $this->push($candidates, 'index', $this->manifest->template('index'));
        $this->push($candidates, 'index', 'index.php');

        foreach ($candidates as $candidate) {
            if ($this->manifest->hasFile($candidate['file'])) {
                return [$candidate['file'], $candidates];
            }
        }

        throw new \RuntimeException(_t('文件不存在'));
    }

    private function push(array &$candidates, string $key, ?string $file): void
    {
        if (!is_string($file) || $file === '') {
            return;
        }

        $file = Manifest::normalizePath($file);
        if ($file === null) {
            return;
        }

        $id = $key . ':' . $file;
        foreach ($candidates as $candidate) {
            if ($candidate['id'] === $id) {
                return;
            }
        }

        $candidates[] = ['id' => $id, 'key' => $key, 'file' => $file];
    }
}
