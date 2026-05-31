<?php

namespace Typecho\Db\Adapter;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * mysqli_stmt 在没有 mysqlnd 时的轻量结果集封装
 */
class MysqliResult
{
    private array $rows;

    private int $offset = 0;

    /**
     * @param array $rows
     */
    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    /**
     * @return array|null
     */
    public function fetchAssoc(): ?array
    {
        if (!isset($this->rows[$this->offset])) {
            return null;
        }

        return $this->rows[$this->offset++];
    }

    /**
     * @return array
     */
    public function fetchAll(): array
    {
        $rows = array_slice($this->rows, $this->offset);
        $this->offset = count($this->rows);
        return $rows;
    }

    /**
     * @return \stdClass|null
     */
    public function fetchObject(): ?\stdClass
    {
        $row = $this->fetchAssoc();
        return $row ? (object) $row : null;
    }
}
