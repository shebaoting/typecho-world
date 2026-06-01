<?php

namespace Widget\Options;

use Typecho\Db\Exception;
use Typecho\Widget\Helper\Form;
use Widget\ActionInterface;
use Widget\Base\Options;
use Widget\Notice;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 导航菜单设置
 */
class Navigation extends Options implements ActionInterface
{
    /**
     * @return void
     * @throws Exception
     */
    public function updateNavigation()
    {
        if ($this->form()->validate()) {
            $this->response->goBack();
        }

        $items = $this->parseNavigation($this->request->get('navigationText', ''));
        $value = json_encode($items);

        if ($this->options->__isSet('navigation')) {
            $this->update(['value' => $value], $this->db->sql()->where('name = ?', 'navigation'));
        } else {
            $this->insert(['name' => 'navigation', 'user' => 0, 'value' => $value]);
        }

        Notice::alloc()->set(_t('导航菜单已经保存'), 'success');
        $this->response->goBack();
    }

    /**
     * @return Form
     */
    public function form(): Form
    {
        $form = new Form($this->security->getIndex('/action/options-navigation'), Form::POST_METHOD);
        $text = new Form\Element\Textarea(
            'navigationText',
            null,
            $this->formatNavigation(),
            _t('导航菜单'),
            _t('每行一个菜单项，格式为：名称 | 地址。需要新窗口打开时，在第三列填写 _blank。')
        );
        $text->input->setAttribute('class', 'w-100 mono');
        $text->input->setAttribute('rows', '10');
        $form->addInput($text);

        $submit = new Form\Element\Submit(null, null, _t('保存菜单'));
        $submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);

        return $form;
    }

    /**
     * @return string
     */
    private function formatNavigation(): string
    {
        $items = json_decode((string) ($this->options->navigation ?? ''), true);
        $items = is_array($items) ? $items : [];

        if (empty($items)) {
            $items[] = ['label' => _t('首页'), 'url' => '/', 'target' => ''];
        }

        return implode("\n", array_map(function ($item) {
            $line = trim((string) ($item['label'] ?? '')) . ' | ' . trim((string) ($item['url'] ?? ''));

            if ('_blank' === ($item['target'] ?? '')) {
                $line .= ' | _blank';
            }

            return $line;
        }, $items));
    }

    /**
     * @param string $text
     * @return array<int, array<string, string>>
     */
    private function parseNavigation(string $text): array
    {
        $items = [];
        $lines = preg_split('/\r\n|\r|\n/', $text);

        foreach ($lines as $line) {
            $line = trim($line);

            if ('' === $line) {
                continue;
            }

            $parts = array_map('trim', explode('|', $line));
            $label = $parts[0] ?? '';
            $url = $parts[1] ?? '';

            if ('' === $label || '' === $url) {
                continue;
            }

            $items[] = [
                'label'  => $label,
                'url'    => $url,
                'target' => '_blank' === ($parts[2] ?? '') ? '_blank' : '',
            ];
        }

        return $items;
    }

    /**
     * @return void
     * @throws Exception|\Typecho\Widget\Exception
     */
    public function action()
    {
        $this->user->pass('administrator');
        $this->security->protect();
        $this->updateNavigation();
    }
}
