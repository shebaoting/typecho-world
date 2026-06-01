<?php

namespace Widget\Contents\Related;

use Typecho\Db;
use Typecho\Db\Exception;
use Widget\Base\Contents;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 同系列内容组件
 */
class Series extends Contents
{
    /**
     * @return void
     * @throws Exception
     */
    public function execute()
    {
        $this->parameter->setDefault([
            'limit'  => 0,
            'series' => '',
            'cid'    => 0,
            'type'   => 'post',
        ]);

        $series = trim((string) $this->parameter->series);

        if ('' === $series) {
            return;
        }

        $select = $this->select('table.contents.*')
            ->join(
                'table.fields series_field',
                "table.contents.cid = series_field.cid AND series_field.name = '_series'"
            )
            ->where('series_field.str_value = ?', $series)
            ->where('table.contents.cid <> ?', (int) $this->parameter->cid)
            ->where('table.contents.status = ?', 'publish')
            ->where('table.contents.password IS NULL OR table.contents.password = ?', '')
            ->where('table.contents.created < ?', $this->options->time)
            ->where('table.contents.type = ?', $this->parameter->type)
            ->order('table.contents.created', Db::SORT_ASC);

        if ((int) $this->parameter->limit > 0) {
            $select->limit((int) $this->parameter->limit);
        }

        $this->db->fetchAll($select, [$this, 'push']);
    }
}
