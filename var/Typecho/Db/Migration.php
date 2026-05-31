<?php

namespace Typecho\Db;

use Typecho\Db;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 数据库迁移辅助器
 */
class Migration
{
    private Db $db;

    private string $driver;

    private string $prefix;

    /**
     * @param Db $db
     */
    public function __construct(Db $db)
    {
        $this->db = $db;
        $this->driver = $db->getAdapter()->getDriver();
        $this->prefix = $db->getPrefix();
    }

    /**
     * 执行一次性迁移
     *
     * @param string $name
     * @param callable $callback
     * @return bool
     * @throws Exception
     */
    public function run(string $name, callable $callback): bool
    {
        $this->ensureMigrationTable();

        if ($this->hasRun($name)) {
            return false;
        }

        $callback($this);
        $this->markRun($name);
        return true;
    }

    /**
     * 增加索引, 已存在时跳过
     *
     * @param string $table
     * @param string $name
     * @param array $columns
     * @param bool $unique
     * @throws Exception
     */
    public function addIndex(string $table, string $name, array $columns, bool $unique = false)
    {
        $tableName = $this->tableName($table);
        $indexName = $this->indexName($tableName, $name);

        if ($this->indexExists($tableName, $indexName)) {
            return;
        }

        $quotedColumns = implode(', ', array_map([$this, 'quote'], $columns));
        $uniqueSql = $unique ? 'UNIQUE ' : '';

        if ($this->driver === 'mysql') {
            $sql = 'ALTER TABLE ' . $this->quote($tableName) . ' ADD '
                . $uniqueSql . 'INDEX ' . $this->quote($indexName) . ' (' . $quotedColumns . ')';
        } else {
            $sql = 'CREATE ' . $uniqueSql . 'INDEX ' . $this->quote($indexName)
                . ' ON ' . $this->quote($tableName) . ' (' . $quotedColumns . ')';
        }

        $this->db->query($sql, Db::WRITE, '');
    }

    /**
     * 建立迁移记录表
     *
     * @throws Exception
     */
    private function ensureMigrationTable()
    {
        $table = $this->quote($this->tableName('table.migrations'));

        if ($this->driver === 'mysql') {
            $sql = "CREATE TABLE IF NOT EXISTS {$table} ("
                . "`name` varchar(128) NOT NULL,"
                . "`executed` int(10) unsigned NOT NULL DEFAULT '0',"
                . "PRIMARY KEY (`name`)"
                . ")";
        } else {
            $sql = "CREATE TABLE IF NOT EXISTS {$table} ("
                . $this->quote('name') . " varchar(128) NOT NULL PRIMARY KEY,"
                . $this->quote('executed') . " int NOT NULL DEFAULT '0'"
                . ")";
        }

        $this->db->query($sql, Db::WRITE, '');
    }

    /**
     * @param string $name
     * @return bool
     * @throws Exception
     */
    private function hasRun(string $name): bool
    {
        return (bool) $this->db->fetchRow(
            $this->db->select('name')->from('table.migrations')->where('name = ?', $name)->limit(1)
        );
    }

    /**
     * @param string $name
     * @throws Exception
     */
    private function markRun(string $name)
    {
        $this->db->query($this->db->insert('table.migrations')->rows([
            'name'     => $name,
            'executed' => time()
        ]));
    }

    /**
     * @param string $table
     * @param string $indexName
     * @return bool
     * @throws Exception
     */
    private function indexExists(string $table, string $indexName): bool
    {
        return match ($this->driver) {
            'mysql' => (bool) $this->db->fetchRow(
                'SHOW INDEX FROM ' . $this->quote($table)
                . ' WHERE Key_name = ' . $this->db->getAdapter()->quoteValue($indexName)
            ),
            'pgsql' => (bool) $this->db->fetchRow(
                'SELECT 1 FROM pg_indexes WHERE schemaname = current_schema()'
                . ' AND tablename = ' . $this->db->getAdapter()->quoteValue($table)
                . ' AND indexname = ' . $this->db->getAdapter()->quoteValue($indexName)
            ),
            default => $this->sqliteIndexExists($table, $indexName),
        };
    }

    /**
     * @param string $table
     * @param string $indexName
     * @return bool
     * @throws Exception
     */
    private function sqliteIndexExists(string $table, string $indexName): bool
    {
        $rows = $this->db->fetchAll('PRAGMA index_list(' . $this->quote($table) . ')');

        foreach ($rows as $row) {
            if (($row['name'] ?? null) === $indexName) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $name
     * @return string
     */
    private function tableName(string $name): string
    {
        return str_starts_with($name, 'table.')
            ? $this->prefix . substr($name, 6)
            : $name;
    }

    /**
     * @param string $table
     * @param string $name
     * @return string
     */
    private function indexName(string $table, string $name): string
    {
        $indexName = $table . '_' . $name;

        if (strlen($indexName) <= 60) {
            return $indexName;
        }

        return substr($indexName, 0, 47) . '_' . substr(sha1($indexName), 0, 12);
    }

    /**
     * @param string $name
     * @return string
     */
    private function quote(string $name): string
    {
        return $this->db->getAdapter()->quoteColumn($name);
    }
}
