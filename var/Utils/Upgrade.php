<?php

namespace Utils;

use Typecho\Common;
use Typecho\Db;
use Typecho\Db\Migration;
use Widget\Options;

/**
 * 升级程序
 *
 * @category typecho
 * @package Upgrade
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Upgrade
{
    /**
     * @param Db $db
     * @param Options $options
     */
    public static function v1_3_0(Db $db, Options $options)
    {
        $routingTable = $options->routingTable;

        $routingTable['comment_page'] = [
            'url'    => '[permalink:string]/comment-page-[commentPage:digital]',
            'widget' => '\Widget\CommentPage',
            'action' => 'action'
        ];

        $routingTable['feed'] = [
            'url'    => '/feed[feed:string:0]',
            'widget' => '\Widget\Feed',
            'action' => 'render'
        ];

        unset($routingTable[0]);

        $db->query($db->update('table.options')
            ->rows(['value' => json_encode($routingTable)])
            ->where('name = ?', 'routingTable'));

        // fix options->commentsRequireURL
        $db->query($db->update('table.options')
            ->rows(['name' => 'commentsRequireUrl'])
            ->where('name = ?', 'commentsRequireURL'));

        // fix draft
        $db->query($db->update('table.contents')
            ->rows(['type' => 'revision'])
            ->where('parent <> 0 AND (type = ? OR type = ?)', 'post_draft', 'page_draft'));

        // fix attachment serialize
        $lastId = 0;
        do {
            $rows = $db->fetchAll(
                $db->select('cid', 'text')->from('table.contents')
                    ->where('cid > ?', $lastId)
                    ->where('type = ?', 'attachment')
                    ->order('cid', Db::SORT_ASC)
                    ->limit(100)
            );

            $rowCount = count($rows);
            if ($rowCount > 0) {
                $lastId = $rows[$rowCount - 1]['cid'];
            }

            foreach ($rows as $row) {
                if (strpos($row['text'], 'a:') !== 0) {
                    continue;
                }

                $value = @unserialize($row['text']);
                if ($value !== false) {
                    $db->query($db->update('table.contents')
                        ->rows(['text' => json_encode($value)])
                        ->where('cid = ?', $row['cid']));
                }
            }
        } while ($rowCount === 100);

        $rows = $db->fetchAll($db->select()->from('table.options'));

        foreach ($rows as $row) {
            if (
                in_array($row['name'], ['plugins', 'actionTable', 'panelTable'])
                || strpos($row['name'], 'plugin:') === 0
                || strpos($row['name'], 'theme:') === 0
            ) {
                $value = @unserialize($row['value']);
                if ($value !== false) {
                    $db->query($db->update('table.options')
                        ->rows(['value' => json_encode($value)])
                        ->where('name = ?', $row['name']));
                }
            }
        }
    }

    /**
     * @param Db $db
     * @param Options $options
     * @return string|null
     * @throws \Typecho\Db\Exception
     */
    public static function v1_3_1(Db $db, Options $options): ?string
    {
        $migration = new Migration($db);

        $ran = $migration->run('2026_05_31_core_indexes', function (Migration $migration) {
            $migration->addIndex('table.contents', 'type_status_created', ['type', 'status', 'created']);
            $migration->addIndex('table.contents', 'author_status_created', ['authorId', 'status', 'created']);
            $migration->addIndex('table.contents', 'parent_type', ['parent', 'type']);

            $migration->addIndex('table.comments', 'status_created', ['status', 'created']);
            $migration->addIndex('table.comments', 'cid_status_created', ['cid', 'status', 'created']);
            $migration->addIndex('table.comments', 'owner_status_created', ['ownerId', 'status', 'created']);

            $migration->addIndex('table.metas', 'type_slug', ['type', 'slug']);
            $migration->addIndex('table.metas', 'type_name', ['type', 'name']);
            $migration->addIndex('table.metas', 'type_order', ['type', 'order']);
            $migration->addIndex('table.metas', 'parent', ['parent']);

            $migration->addIndex('table.relationships', 'mid', ['mid']);
            $migration->addIndex('table.options', 'user', ['user']);
        });

        return $ran ? _t('已更新核心数据库索引') : null;
    }

    /**
     * @param Db $db
     * @param Options $options
     * @return string|null
     * @throws \Typecho\Db\Exception
     */
    public static function v1_3_2(Db $db, Options $options): ?string
    {
        $migration = new Migration($db);

        $ran = $migration->run('2026_06_01_content_media_features', function (Migration $migration) {
            $migration->addColumn('table.metas', 'aliases', 'TEXT NULL');
            $migration->addIndex('table.fields', 'name', ['name']);
            $migration->addIndex('table.fields', 'name_int_value', ['name', 'int_value']);
        });

        return $ran ? _t('已更新内容与媒体字段') : null;
    }

    /**
     * @param Db $db
     * @param Options $options
     * @return string|null
     * @throws \Typecho\Db\Exception
     */
    public static function v1_3_3(Db $db, Options $options): ?string
    {
        $migration = new Migration($db);
        $driver = $db->getAdapter()->getDriver();

        $ran = $migration->run('2026_06_01_writing_admin_stage_two', function (Migration $migration) use ($driver) {
            $definition = match ($driver) {
                'mysql' => "`lid` int(10) unsigned NOT NULL auto_increment,"
                    . "`created` int(10) unsigned default '0',"
                    . "`userId` int(10) unsigned default '0',"
                    . "`action` varchar(32) default NULL,"
                    . "`targetType` varchar(32) default NULL,"
                    . "`targetId` int(10) unsigned default '0',"
                    . "`targetTitle` varchar(150) default NULL,"
                    . "`message` text,"
                    . "`ip` varchar(64) default NULL,"
                    . "PRIMARY KEY (`lid`)",
                'pgsql' => '"lid" SERIAL NOT NULL,'
                    . '"created" INT NULL DEFAULT \'0\','
                    . '"userId" INT NULL DEFAULT \'0\','
                    . '"action" VARCHAR(32) NULL DEFAULT NULL,'
                    . '"targetType" VARCHAR(32) NULL DEFAULT NULL,'
                    . '"targetId" INT NULL DEFAULT \'0\','
                    . '"targetTitle" VARCHAR(150) NULL DEFAULT NULL,'
                    . '"message" TEXT NULL DEFAULT NULL,'
                    . '"ip" VARCHAR(64) NULL DEFAULT NULL,'
                    . 'PRIMARY KEY ("lid")',
                default => '"lid" INTEGER NOT NULL PRIMARY KEY,'
                    . '"created" int(10) default \'0\','
                    . '"userId" int(10) default \'0\','
                    . '"action" varchar(32) default NULL,'
                    . '"targetType" varchar(32) default NULL,'
                    . '"targetId" int(10) default \'0\','
                    . '"targetTitle" varchar(150) default NULL,'
                    . '"message" text,'
                    . '"ip" varchar(64) default NULL',
            };

            $migration->createTable('table.logs', $definition);
            $migration->addIndex('table.logs', 'created', ['created']);
            $migration->addIndex('table.logs', 'user_created', ['userId', 'created']);
            $migration->addIndex('table.logs', 'target', ['targetType', 'targetId']);
            $migration->addIndex('table.contents', 'parent_type_modified', ['parent', 'type', 'modified']);
            $migration->addIndex('table.contents', 'type_status_modified', ['type', 'status', 'modified']);
        });

        return $ran ? _t('已启用写作历史、回收站与操作日志') : null;
    }

    /**
     * @param Db $db
     * @param Options $options
     * @return string|null
     * @throws \Typecho\Db\Exception
     */
    public static function v1_3_4(Db $db, Options $options): ?string
    {
        $changed = false;
        $routingTable = $options->routingTable;
        unset($routingTable[0]);

        foreach ([
            'sitemap' => [
                'url'    => '/sitemap.xml',
                'widget' => '\Widget\Sitemap',
                'action' => 'render',
            ],
            'robots'  => [
                'url'    => '/robots.txt',
                'widget' => '\Widget\Robots',
                'action' => 'render',
            ],
        ] as $routeName => $route) {
            if (!isset($routingTable[$routeName])) {
                $routingTable[$routeName] = $route;
                $changed = true;
            }
        }

        if ($changed) {
            $db->query($db->update('table.options')
                ->rows(['value' => json_encode($routingTable)])
                ->where('name = ?', 'routingTable'));
        }

        $changed = self::ensureOption($db, 'robotsTxt', '') || $changed;
        $changed = self::ensureOption($db, 'navigation', '') || $changed;

        return $changed ? _t('已启用 SEO 与主题增强能力') : null;
    }

    /**
     * @param Db $db
     * @param Options $options
     * @return string|null
     * @throws \Typecho\Db\Exception
     */
    public static function v1_3_5(Db $db, Options $options): ?string
    {
        $changed = false;
        $routingTable = $options->routingTable;
        unset($routingTable[0]);

        $apiRoutes = [
            'api_root' => [
                'url'    => '/api',
                'widget' => '\Widget\Api',
                'action' => 'render',
            ],
            'api'      => [
                'url'    => '/api/[endpoint:string]',
                'widget' => '\Widget\Api',
                'action' => 'render',
            ],
        ];

        $originalRoutingTable = json_encode($routingTable);
        unset($routingTable['api_root'], $routingTable['api']);
        $routingTable = self::insertRoutesBefore($routingTable, 'feedback', $apiRoutes);

        if (json_encode($routingTable) !== $originalRoutingTable) {
            $changed = true;
            $db->query($db->update('table.options')
                ->rows(['value' => json_encode($routingTable)])
                ->where('name = ?', 'routingTable'));
        }

        $changed = self::ensureOption($db, 'apiToken', Common::randString(48, true)) || $changed;

        return $changed ? _t('已启用 API 与导入导出能力') : null;
    }

    /**
     * @param array $routingTable
     * @param string $before
     * @param array $routes
     * @return array
     */
    private static function insertRoutesBefore(array $routingTable, string $before, array $routes): array
    {
        $result = [];
        $inserted = false;

        foreach ($routingTable as $name => $route) {
            if (!$inserted && $name === $before) {
                $result += $routes;
                $inserted = true;
            }

            $result[$name] = $route;
        }

        if (!$inserted) {
            $result += $routes;
        }

        return $result;
    }

    /**
     * @param Db $db
     * @param string $name
     * @param string $value
     * @return bool
     * @throws \Typecho\Db\Exception
     */
    private static function ensureOption(Db $db, string $name, string $value): bool
    {
        $exists = $db->fetchRow($db->select('name')->from('table.options')
            ->where('name = ? AND user = ?', $name, 0)
            ->limit(1));

        if ($exists) {
            return false;
        }

        $db->query($db->insert('table.options')->rows([
            'name'  => $name,
            'user'  => 0,
            'value' => $value,
        ]));

        return true;
    }
}
