<?php

namespace Widget;

use Typecho\Common;
use Widget\Base\Options as BaseOptions;
use Utils\SystemUpdater;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Typecho World 在线更新动作
 */
class SystemUpdate extends BaseOptions implements ActionInterface
{
    /**
     * @throws \Typecho\Db\Exception
     */
    public function install()
    {
        try {
            $result = SystemUpdater::installLatest();
            Notice::alloc()->set(
                _t(
                    'Typecho World %s 已下载并更新程序文件。备份目录：%s。请继续完成数据库升级。',
                    $result['tag'] ?: $result['version'],
                    $result['backup'] ?: _t('未生成')
                ),
                'success'
            );
            $this->response->redirect(Common::url('upgrade.php', $this->options->adminUrl));
        } catch (\Exception $e) {
            Notice::alloc()->set($e->getMessage(), 'error');
            $this->response->goBack();
        }
    }

    /**
     * @throws \Typecho\Widget\Exception
     */
    public function action()
    {
        $this->user->pass('administrator');
        $this->security->protect();
        $this->on($this->request->isPost())->install();
        $this->response->redirect(Common::url('upgrade.php', $this->options->adminUrl));
    }
}
