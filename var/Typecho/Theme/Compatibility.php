<?php

namespace Typecho\Theme;

use Typecho\Common;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class Compatibility
{
    public const MIN_CORE_VERSION = '2.0.0';

    public static function coreRequirement(array $data): string
    {
        $candidates = [
            $data['typecho']['requires'] ?? null,
            $data['typecho']['require'] ?? null,
            $data['typecho']['version'] ?? null,
            $data['typecho']['constraint'] ?? null,
            $data['requires']['typecho'] ?? null,
            $data['requires']['typechoWorld'] ?? null,
            $data['requires']['core'] ?? null,
            $data['compatibility']['typecho'] ?? null,
            $data['compatibility']['typechoWorld'] ?? null,
            $data['compatibility']['core'] ?? null,
            $data['engines']['typecho'] ?? null,
            $data['engines']['typechoWorld'] ?? null,
        ];

        if (is_string($data['typecho'] ?? null)) {
            array_unshift($candidates, $data['typecho']);
        }

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return '';
    }

    /**
     * 检查主题声明是否明确支持当前 Typecho World 核心版本。
     *
     * @return array<string, mixed>
     */
    public static function evaluate(string $requirement, ?string $currentVersion = null): array
    {
        $requirement = trim($requirement);
        $currentVersion = self::normalizeVersion($currentVersion ?? Common::VERSION) ?? Common::VERSION;
        $base = [
            'compatible' => false,
            'declared'   => $requirement !== '',
            'requirement' => $requirement,
            'current'    => $currentVersion,
            'minimum'    => self::MIN_CORE_VERSION,
            'floor'      => '',
            'reason'     => '',
            'message'    => '',
        ];

        if ($requirement === '') {
            return array_replace($base, [
                'reason'  => 'missing',
                'message' => _t('主题未声明 Typecho World 核心版本要求，请在 theme.json 写入 "typecho": {"requires": ">=2.0.0"}。'),
            ]);
        }

        $groups = self::groups($requirement);
        if (empty($groups)) {
            return array_replace($base, [
                'reason'  => 'invalid',
                'message' => _t('主题核心版本约束无法识别：%s', $requirement),
            ]);
        }

        $hasParsableGroup = false;
        $currentAllowed = false;
        $matchedFloor = null;
        $matchedWithoutFloor = false;

        foreach ($groups as $group) {
            $tokens = self::tokens($group);
            if (empty($tokens)) {
                continue;
            }

            $hasParsableGroup = true;
            if (!self::satisfiesTokens($tokens, $currentVersion)) {
                continue;
            }

            $currentAllowed = true;
            $floor = self::floorForTokens($tokens);
            if ($floor === null) {
                $matchedWithoutFloor = true;
                continue;
            }

            $matchedFloor = $floor;
            if (version_compare($floor, self::MIN_CORE_VERSION, '>=')) {
                return [
                    'compatible' => true,
                    'declared'   => true,
                    'requirement' => $requirement,
                    'current'    => $currentVersion,
                    'minimum'    => self::MIN_CORE_VERSION,
                    'floor'      => $floor,
                    'reason'     => '',
                    'message'    => _t('主题兼容当前 Typecho World 版本。'),
                ];
            }
        }

        if (!$hasParsableGroup) {
            return array_replace($base, [
                'reason'  => 'invalid',
                'message' => _t('主题核心版本约束无法识别：%s', $requirement),
            ]);
        }

        if (!$currentAllowed) {
            return array_replace($base, [
                'reason'  => 'current',
                'message' => _t('主题要求 Typecho World %s，当前核心版本是 %s。', $requirement, $currentVersion),
            ]);
        }

        if ($matchedWithoutFloor || $matchedFloor === null) {
            return array_replace($base, [
                'reason'  => 'floor',
                'message' => _t('主题版本约束必须包含不低于 %s 的最低支持版本。', self::MIN_CORE_VERSION),
            ]);
        }

        return [
            'compatible' => false,
            'declared'   => true,
            'requirement' => $requirement,
            'current'    => $currentVersion,
            'minimum'    => self::MIN_CORE_VERSION,
            'floor'      => $matchedFloor,
            'reason'     => 'floor',
            'message'    => _t('主题最低支持版本必须不低于 %s，当前声明从 %s 开始。', self::MIN_CORE_VERSION, $matchedFloor),
        ];
    }

    public static function satisfies(string $requirement, ?string $currentVersion = null): bool
    {
        return (bool) self::evaluate($requirement, $currentVersion)['compatible'];
    }

    /**
     * @return array<int, string>
     */
    private static function groups(string $requirement): array
    {
        $groups = preg_split('/\s*\|\|\s*/', trim($requirement)) ?: [];
        return array_values(array_filter($groups, static fn ($group) => trim($group) !== ''));
    }

    /**
     * @return array<int, string>
     */
    private static function tokens(string $group): array
    {
        if (!preg_match_all('/(?:\^|~|>=|<=|>|<|==|=|!=)?\s*v?\d+(?:\.\d+){0,2}(?:[-+][0-9A-Za-z.-]+)?|\*/', $group, $matches)) {
            return [];
        }

        return array_values(array_map(
            static fn ($token) => preg_replace('/\s+/', '', trim($token)) ?: '',
            array_filter($matches[0], static fn ($token) => trim($token) !== '')
        ));
    }

    /**
     * @param array<int, string> $tokens
     */
    private static function satisfiesTokens(array $tokens, string $currentVersion): bool
    {
        foreach ($tokens as $token) {
            if (!self::satisfiesToken($token, $currentVersion)) {
                return false;
            }
        }

        return true;
    }

    private static function satisfiesToken(string $token, string $currentVersion): bool
    {
        if ($token === '*') {
            return true;
        }

        if (!preg_match('/^(\^|~|>=|<=|>|<|==|=|!=)?v?(.+)$/', $token, $match)) {
            return false;
        }

        $operator = $match[1] ?: '=';
        $version = self::normalizeVersion($match[2]);
        if ($version === null) {
            return false;
        }

        if ($operator === '^') {
            return version_compare($currentVersion, $version, '>=')
                && version_compare($currentVersion, self::nextMajor($version), '<');
        }

        if ($operator === '~') {
            return version_compare($currentVersion, $version, '>=')
                && version_compare($currentVersion, self::nextMinor($version), '<');
        }

        if ($operator === '=') {
            $operator = '==';
        }

        return version_compare($currentVersion, $version, $operator);
    }

    /**
     * @param array<int, string> $tokens
     */
    private static function floorForTokens(array $tokens): ?string
    {
        $floor = null;
        foreach ($tokens as $token) {
            $candidate = self::floorForToken($token);
            if ($candidate !== null && ($floor === null || version_compare($candidate, $floor, '>'))) {
                $floor = $candidate;
            }
        }

        return $floor;
    }

    private static function floorForToken(string $token): ?string
    {
        if ($token === '*') {
            return null;
        }

        if (!preg_match('/^(\^|~|>=|>|==|=)?v?(.+)$/', $token, $match)) {
            return null;
        }

        return self::normalizeVersion($match[2]);
    }

    private static function normalizeVersion(string $version): ?string
    {
        $version = trim($version);
        $version = preg_replace('/^v/i', '', $version) ?? $version;
        $version = preg_replace('/[-+].*$/', '', $version) ?? $version;

        if (!preg_match('/^\d+(?:\.\d+){0,2}$/', $version)) {
            return null;
        }

        $parts = array_map('intval', explode('.', $version));
        while (count($parts) < 3) {
            $parts[] = 0;
        }

        return implode('.', array_slice($parts, 0, 3));
    }

    private static function nextMajor(string $version): string
    {
        [$major] = array_map('intval', explode('.', $version));
        return ($major + 1) . '.0.0';
    }

    private static function nextMinor(string $version): string
    {
        [$major, $minor] = array_map('intval', explode('.', $version));
        return $major . '.' . ($minor + 1) . '.0';
    }
}
