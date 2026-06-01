<?php

namespace Widget\Contents\Attachment;

use Typecho\Config;
use Typecho\Db;
use Typecho\Db\Exception;
use Widget\Base\Contents;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 可复用媒体列表组件
 */
class Library extends Contents
{
    /**
     * @param Config $parameter
     */
    protected function initParameter(Config $parameter)
    {
        $parameter->setDefault([
            'parentId' => 0,
            'limit'    => 8,
        ]);
    }

    /**
     * @return void
     * @throws Exception|\Typecho\Widget\Exception
     */
    public function execute()
    {
        $select = $this->select()->where('table.contents.type = ?', 'attachment');

        if (!$this->user->pass('editor', true)) {
            $select->where('table.contents.authorId = ?', $this->user->uid);
        }

        $parentId = (int) $this->parameter->parentId;

        if ($parentId > 0) {
            $select->where('table.contents.parent <> ? OR table.contents.parent IS NULL', $parentId);
        } else {
            $select->where('table.contents.parent <> ? AND table.contents.parent IS NOT NULL', 0);
        }

        $select->order('table.contents.created', Db::SORT_DESC)
            ->limit((int) $this->parameter->limit);

        $this->db->fetchAll($select, [$this, 'push']);
    }
}
