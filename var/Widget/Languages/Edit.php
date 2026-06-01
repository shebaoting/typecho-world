<?php

namespace Widget\Languages;

use Typecho\Db\Exception as DbException;
use Typecho\Language;
use Typecho\Widget\Exception;
use Widget\ActionInterface;
use Widget\Base\Options;
use Widget\Notice;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 语言包管理组件
 */
class Edit extends Options implements ActionInterface
{
    /**
     * 启用语言包
     *
     * @param string $lang
     * @throws DbException
     * @throws Exception
     */
    public function changeLanguage(string $lang)
    {
        $lang = Language::normalizeName($lang);
        if ('' == $lang || !Language::isAvailable($lang)) {
            throw new Exception(_t('语言包不存在'));
        }

        $this->update(['value' => $lang], $this->db->sql()->where('name = ?', 'lang'));

        Notice::alloc()->highlight('language-' . $lang);
        Notice::alloc()->set(_t('语言包已经启用'), 'success');
        $this->response->goBack();
    }

    /**
     * 安装语言包
     *
     * @throws Exception
     */
    public function installLanguage()
    {
        if (empty($_FILES['language'])) {
            throw new Exception(_t('请选择要安装的语言包文件'));
        }

        $file = $_FILES['language'];
        if (UPLOAD_ERR_OK != $file['error'] || !is_uploaded_file($file['tmp_name'])) {
            throw new Exception(_t('语言包上传失败'));
        }

        $name = Language::normalizeName($file['name']);
        if ('' == $name || strtolower(substr($file['name'], -3)) != '.mo') {
            throw new Exception(_t('语言包文件名需要使用 en_US.mo 这样的格式'));
        }

        if (Language::isBuiltin($name)) {
            throw new Exception(_t('内置语言包不能被覆盖'));
        }

        if (!Language::isMoFile($file['tmp_name'])) {
            throw new Exception(_t('语言包文件损坏或者不是有效的 Gettext MO 文件'));
        }

        if (!Language::prepareDir()) {
            throw new Exception(_t('语言包目录无法写入'));
        }

        $target = Language::file($name);
        if (!@move_uploaded_file($file['tmp_name'], $target)) {
            throw new Exception(_t('无法写入语言包文件'));
        }

        Notice::alloc()->highlight('language-' . $name);
        Notice::alloc()->set(_t('语言包 %s 已经安装', $name), 'success');
        $this->response->goBack();
    }

    /**
     * 删除语言包
     *
     * @param string $lang
     * @throws Exception
     */
    public function deleteLanguage(string $lang)
    {
        $lang = Language::normalizeName($lang);
        if ('' == $lang || !Language::isAvailable($lang)) {
            throw new Exception(_t('语言包不存在'));
        }

        if (Language::isBuiltin($lang)) {
            throw new Exception(_t('内置语言包无法删除'));
        }

        if ($this->options->lang == $lang) {
            throw new Exception(_t('不能删除正在使用的语言包'));
        }

        if (!@unlink(Language::file($lang))) {
            throw new Exception(_t('无法删除语言包文件'));
        }

        Notice::alloc()->set(_t('语言包 %s 已经删除', $lang), 'success');
        $this->response->goBack();
    }

    /**
     * 绑定动作
     */
    public function action()
    {
        $this->user->pass('administrator');
        $this->security->protect();
        $this->on($this->request->isPost() && $this->request->is('do=install'))->installLanguage();
        $this->on($this->request->is('change'))->changeLanguage($this->request->get('change'));
        $this->on($this->request->is('delete'))->deleteLanguage($this->request->get('delete'));
        $this->response->redirect($this->options->adminUrl);
    }
}
