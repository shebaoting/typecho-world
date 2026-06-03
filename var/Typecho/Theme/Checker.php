<?php

namespace Typecho\Theme;

use Widget\Options;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class Checker
{
    /**
     * 检查一个主题目录
     *
     * @return array<int, array<string, string>>
     */
    public function check(string $theme, Options $options): array
    {
        $theme = trim($theme, './');
        $dir = rtrim($options->themeFile($theme), '/');
        $findings = [];

        if (!is_dir($dir)) {
            return [[
                'level'   => 'error',
                'code'    => 'theme_missing',
                'message' => _t('主题目录不存在'),
                'file'    => $theme,
            ]];
        }

        $manifest = Manifest::load($theme, $options);
        $this->checkManifestFile($dir, $findings);
        $this->checkManifestData($manifest, $findings);
        $this->checkPhpFiles($dir, $findings);

        return $findings;
    }

    /**
     * 按严重程度统计结果
     *
     * @param array<int, array<string, string>> $findings
     * @return array<string, int>
     */
    public static function summary(array $findings): array
    {
        $summary = ['error' => 0, 'warning' => 0, 'info' => 0];
        foreach ($findings as $finding) {
            $level = $finding['level'] ?? 'info';
            $summary[$level] = ($summary[$level] ?? 0) + 1;
        }

        return $summary;
    }

    /**
     * @param array<int, array<string, string>> $findings
     */
    private function checkManifestFile(string $dir, array &$findings): void
    {
        $file = $dir . '/theme.json';
        if (!is_file($file)) {
            $this->add($findings, 'info', 'manifest_optional', _t('未提供 theme.json；后台启用仍要求主题明确声明核心版本约束。'));
            return;
        }

        $decoded = json_decode((string) file_get_contents($file), true);
        if (!is_array($decoded)) {
            $this->add($findings, 'error', 'manifest_json', _t('theme.json 不是有效的 JSON。'), 'theme.json');
            return;
        }

        foreach (['templates', 'parts', 'components'] as $section) {
            $this->checkPathMap($dir, $decoded[$section] ?? [], $section, $findings);
        }

        foreach (($decoded['assets'] ?? []) as $type => $assets) {
            $this->checkPathMap($dir, $assets, 'assets.' . $type, $findings);
        }

        foreach (($decoded['settings'] ?? []) as $index => $setting) {
            if (!is_array($setting) || empty($setting['name'])) {
                $this->add(
                    $findings,
                    'warning',
                    'setting_name',
                    _t('第 %d 个设置项缺少 name。', $index + 1),
                    'theme.json'
                );
            }
        }

        foreach (($decoded['navigation'] ?? []) as $slot => $label) {
            if (!is_string($slot) || !preg_match('/^[a-zA-Z0-9_-]+$/', $slot)) {
                $this->add($findings, 'warning', 'navigation_slot', _t('导航位名称只能包含字母、数字、下划线和短横线。'), 'theme.json');
            }
        }

        if (is_array($decoded['build'] ?? null) && is_string($decoded['build']['manifest'] ?? null)) {
            $this->checkPathMap($dir, ['manifest' => $decoded['build']['manifest']], 'build', $findings);
        }

        foreach (($decoded['tokens'] ?? []) as $group => $values) {
            if (!is_array($values)) {
                $this->add($findings, 'warning', 'token_group', _t('Design Token 分组必须是对象。'), 'theme.json');
                continue;
            }

            foreach ($values as $key => $value) {
                if (!is_scalar($value)) {
                    $this->add($findings, 'warning', 'token_value', _t('Design Token 值必须是字符串、数字或布尔值。'), 'theme.json');
                    break;
                }
            }
        }

        if (is_file($dir . '/theme.php')) {
            $this->add($findings, 'info', 'theme_php', _t('检测到 theme.php，高级主题能力会在运行时注册。'), 'theme.php');
        }
    }

    /**
     * @param array<int, array<string, string>> $findings
     */
    private function checkManifestData(Manifest $manifest, array &$findings): void
    {
        if (!$manifest->hasFile($manifest->template('index') ?? 'index.php')) {
            $this->add($findings, 'error', 'index_missing', _t('主题必须提供 index.php 或在 theme.json 中声明 index 模板。'));
        }

        $compatibility = $manifest->compatibility();
        if (empty($compatibility['compatible'])) {
            $code = match ($compatibility['reason'] ?? '') {
                'missing' => 'core_requirement_missing',
                'invalid' => 'core_requirement_invalid',
                'floor'   => 'core_requirement_floor',
                'current' => 'core_requirement_current',
                default   => 'core_requirement',
            };

            $this->add(
                $findings,
                'error',
                $code,
                (string) ($compatibility['message'] ?? _t('主题不兼容当前 Typecho World 核心版本。')),
                'theme.json'
            );
        }

        if (empty($manifest->navigationSlots())) {
            $this->add($findings, 'info', 'navigation_default', _t('未声明导航位，系统会使用默认主导航。'));
        }
    }

    /**
     * @param mixed $map
     * @param array<int, array<string, string>> $findings
     */
    private function checkPathMap(string $dir, mixed $map, string $section, array &$findings): void
    {
        if (!is_array($map)) {
            return;
        }

        foreach ($map as $key => $path) {
            if (!is_string($path)) {
                continue;
            }

            $normalized = Manifest::normalizePath($path);
            if ($normalized === null) {
                $this->add($findings, 'error', 'path_invalid', _t('%s.%s 使用了不安全路径。', $section, (string) $key), 'theme.json');
                continue;
            }

            if (!is_file($dir . '/' . $normalized)) {
                $this->add($findings, 'warning', 'path_missing', _t('%s.%s 指向的文件不存在：%s', $section, (string) $key, $path), 'theme.json');
            }
        }
    }

    /**
     * @param array<int, array<string, string>> $findings
     */
    private function checkPhpFiles(string $dir, array &$findings): void
    {
        foreach ($this->phpFiles($dir) as $file) {
            $relative = str_replace('\\', '/', substr($file, strlen($dir) + 1));
            $source = (string) file_get_contents($file);
            $scan = $this->stripComments($source);

            if (preg_match('/\$this->/', $scan)) {
                $this->add($findings, 'warning', 'old_this_api', _t('发现旧模板 API：请改用 $view、$archive、$site 等标准上下文。'), $relative);
            }

            if (preg_match('/\\\\Widget\\\\|Widget\\\\|Db::get\\(|->db\b|\\\\Typecho\\\\Db/', $scan)) {
                $this->add($findings, 'warning', 'direct_query_boundary', _t('模板中直接调用组件或数据库，建议改用 $data 标准数据 API。'), $relative);
            }

            if (preg_match('/\b(?:require|include)(?:_once)?\s*\(?\s*[\'"][^\'"]+\.php[\'"]/i', $scan)) {
                $this->add($findings, 'warning', 'manual_include', _t('发现手写 include/require，建议使用 $view->part() 或 $view->component()。'), $relative);
            }

            if (preg_match('/\$_(?:GET|POST|REQUEST|COOKIE|SERVER)\b/', $scan)) {
                $this->add($findings, 'warning', 'superglobal', _t('模板中直接读取超全局变量，建议通过核心上下文或标准数据 API 传入。'), $relative);
            }

            if (preg_match('/(?:echo|<\?=)\s+\$(?!e\b|view\b|assets\b|data\b|tokens\b|images\b|cache\b)[a-zA-Z_][a-zA-Z0-9_]*->(?:title|permalink|slug|screenName|description|url|mail)\b/', $scan)) {
                $this->add($findings, 'warning', 'escaping', _t('发现可能未转义的直接输出，请使用 $e->html()、$e->attr() 或 $e->url()。'), $relative);
            }
        }
    }

    /**
     * @return array<int, string>
     */
    private function phpFiles(string $dir): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
                $files[] = $file->getPathname();
            }
        }

        sort($files);
        return $files;
    }

    private function stripComments(string $source): string
    {
        $source = preg_replace('/\/\*.*?\*\//s', '', $source) ?? $source;
        return preg_replace('/^\s*\/\/.*$/m', '', $source) ?? $source;
    }

    /**
     * @param array<int, array<string, string>> $findings
     */
    private function add(array &$findings, string $level, string $code, string $message, string $file = ''): void
    {
        $findings[] = [
            'level'   => $level,
            'code'    => $code,
            'message' => $message,
            'file'    => $file,
        ];
    }
}
