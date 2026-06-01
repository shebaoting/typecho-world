<?php

namespace Widget\Logs;

use Typecho\Db;
use Typecho\Db\Exception as DbException;
use Typecho\Widget\Exception;
use Typecho\Widget\Helper\PageNavigator\Box;
use Widget\Base;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 操作日志列表
 */
class Admin extends Base
{
    private int $total = 0;

    private int $currentPage = 1;

    /**
     * @param \Typecho\Config $parameter
     */
    protected function initParameter(\Typecho\Config $parameter)
    {
        $parameter->setDefault('pageSize=30');
    }

    /**
     * @throws DbException|Exception
     */
    public function execute()
    {
        $this->user->pass('administrator');
        $this->currentPage = $this->request->filter('int')->get('page', 1);

        $select = $this->db->select(
            'table.logs.lid',
            'table.logs.created',
            'table.logs.userId',
            'table.logs.action',
            'table.logs.targetType',
            'table.logs.targetId',
            'table.logs.targetTitle',
            'table.logs.message',
            'table.logs.ip',
            'table.users.screenName'
        )->from('table.logs')
            ->join('table.users', 'table.logs.userId = table.users.uid', Db::LEFT_JOIN);
        $count = $this->db->select(['COUNT(table.logs.lid)' => 'num'])->from('table.logs');

        if ($this->request->is('action')) {
            $action = $this->request->filter('html')->get('action');
            $select->where('table.logs.action = ?', $action);
            $count->where('table.logs.action = ?', $action);
        }

        if ($this->request->is('targetType')) {
            $targetType = $this->request->filter('html')->get('targetType');
            $select->where('table.logs.targetType = ?', $targetType);
            $count->where('table.logs.targetType = ?', $targetType);
        }

        $this->total = $this->db->fetchObject($count)->num;

        $select->order('table.logs.lid', Db::SORT_DESC)
            ->page($this->currentPage, $this->parameter->pageSize);

        $this->db->fetchAll($select, [$this, 'push']);
    }

    /**
     * @throws DbException
     */
    public function pageNav()
    {
        $nav = new Box(
            $this->total,
            $this->currentPage,
            $this->parameter->pageSize,
            $this->request->makeUriByRequest('page={page}')
        );

        $nav->render('&laquo;', '&raquo;');
    }
}
