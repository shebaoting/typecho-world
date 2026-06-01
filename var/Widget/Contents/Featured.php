<?php

namespace Widget\Contents;

use Typecho\Db;
use Typecho\Db\Exception;
use Widget\Base\Contents;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 推荐文章组件
 */
class Featured extends Contents
{
    /**
     * @return void
     * @throws Exception
     */
    public function execute()
    {
        $this->parameter->setDefault([
            'limit' => 5,
            'type'  => 'post',
        ]);

        $select = $this->select('table.contents.*')
            ->join(
                'table.fields featured_field',
                "table.contents.cid = featured_field.cid AND featured_field.name = '_featured'"
            )
            ->where('featured_field.int_value = ?', 1)
            ->where('table.contents.status = ?', 'publish')
            ->where('table.contents.password IS NULL OR table.contents.password = ?', '')
            ->where('table.contents.created < ?', $this->options->time)
            ->where('table.contents.type = ?', $this->parameter->type)
            ->order('table.contents.created', Db::SORT_DESC);

        if ((int) $this->parameter->limit > 0) {
            $select->limit((int) $this->parameter->limit);
        }

        $this->db->fetchAll($select, [$this, 'push']);
    }
}
