<?php

namespace Widget\Options;

use Typecho\Cache\Cache;
use Typecho\Common;
use Typecho\Db\Exception;
use Widget\ActionInterface;
use Widget\Base\Options;
use Widget\Notice;
use Widget\Options as GlobalOptions;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 接口设置组件
 */
class Api extends Options implements ActionInterface
{
    /**
     * 重新生成 API Token
     *
     * @throws Exception
     */
    public function regenerateApiToken()
    {
        $token = Common::randString(48, true);
        $exists = $this->db->fetchRow($this->select('name')
            ->where('name = ? AND user = ?', 'apiToken', 0)
            ->limit(1));

        if ($exists) {
            $this->update(['value' => $token], $this->db->sql()->where('name = ? AND user = ?', 'apiToken', 0));
        } else {
            $this->insert([
                'name'  => 'apiToken',
                'user'  => 0,
                'value' => $token,
            ]);
        }

        Cache::get()->delete(GlobalOptions::cacheKey($this->db));
        Notice::alloc()->set(_t('API Token 已重新生成'), 'success');
        $this->response->goBack();
    }

    /**
     * 绑定动作
     */
    public function action()
    {
        $this->user->pass('administrator');
        $this->security->protect();
        $this->on($this->request->isPost() && $this->request->is('do=regenerate'))->regenerateApiToken();
        $this->response->redirect($this->options->adminUrl);
    }
}
