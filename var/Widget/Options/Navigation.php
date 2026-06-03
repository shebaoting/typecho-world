<?php

namespace Widget\Options;

use Typecho\Db\Exception;
use Typecho\Theme\Manifest;
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

        $texts = $this->request->get('navigationText', []);
        $texts = is_array($texts) ? $texts : ['primary' => (string) $texts];
        $slots = Manifest::load($this->options->theme, $this->options)->navigationSlots();
        $items = [];

        foreach ($slots as $slot => $label) {
            $items[$slot] = $this->parseNavigation((string) ($texts[$slot] ?? ''));
        }

        $value = json_encode($items, JSON_UNESCAPED_UNICODE);

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
        $slots = Manifest::load($this->options->theme, $this->options)->navigationSlots();

        foreach ($slots as $slot => $label) {
            $text = new Form\Element\Textarea(
                'navigationText[' . $slot . ']',
                null,
                $this->formatNavigation($slot),
                _t('%s', $label),
                _t('每行一个菜单项，格式为：名称 | 地址。需要新窗口打开时，在第三列填写 _blank。')
            );
            $text->input->setAttribute('class', 'w-100 mono');
            $text->input->setAttribute('rows', '8');
            $form->addInput($text);
        }

        $submit = new Form\Element\Submit(null, null, _t('保存菜单'));
        $submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);

        return $form;
    }

    /**
     * @return string
     */
    private function formatNavigation(string $slot): string
    {
        $navigation = json_decode((string) ($this->options->navigation ?? ''), true);
        $navigation = is_array($navigation) ? $navigation : [];
        $items = $this->resolveNavigationSlot($navigation, $slot);

        if (empty($items) && $slot === 'primary') {
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

    private function resolveNavigationSlot(array $navigation, string $slot): array
    {
        if (isset($navigation[$slot]) && is_array($navigation[$slot])) {
            return $navigation[$slot];
        }

        if ($slot === 'primary' && isset($navigation[0]) && is_array($navigation[0])) {
            return $navigation;
        }

        return [];
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
