<?php

namespace Widget;

use Typecho\Db;
use Typecho\Request;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 操作日志写入器
 */
class Log
{
    /**
     * @param string $action
     * @param string $targetType
     * @param int $targetId
     * @param string|null $targetTitle
     * @param string $message
     * @return void
     */
    public static function record(
        string $action,
        string $targetType = '',
        int $targetId = 0,
        ?string $targetTitle = null,
        string $message = ''
    ): void {
        try {
            $db = Db::get();
            $user = User::alloc();
            $request = Request::getInstance();

            $db->query($db->insert('table.logs')->rows([
                'created'     => time(),
                'userId'      => (int) ($user->uid ?? 0),
                'action'      => $action,
                'targetType'  => $targetType,
                'targetId'    => $targetId,
                'targetTitle' => $targetTitle,
                'message'     => $message,
                'ip'          => $request->getIp(),
            ]));
        } catch (\Throwable) {
            // 日志不应影响主操作。
        }
    }
}
