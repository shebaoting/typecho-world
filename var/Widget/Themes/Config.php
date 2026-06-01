<?php

namespace Widget\Themes;

use Typecho\Widget\Exception;
use Typecho\Widget\Helper\Form;
use Typecho\Widget\Helper\Form\Element\Submit;
use Typecho\Widget\Helper\Form\Element;
use Widget\Base\Options as BaseOptions;
use Widget\Options;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 皮肤配置组件
 *
 * @author qining
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Config extends BaseOptions
{
    /**
     * 绑定动作
     *
     * @throws Exception|\Typecho\Db\Exception
     */
    public function execute()
    {
        $this->user->pass('administrator');

        if (!self::isExists()) {
            throw new Exception(_t('外观配置功能不存在'), 404);
        }
    }

    /**
     * 配置功能是否存在
     *
     * @param string|null $theme
     * @return boolean
     */
    public static function isExists(?string $theme = null): bool
    {
        $options = Options::alloc();
        $theme = $theme ?? $options->theme;
        $configFile = $options->themeFile($theme, 'functions.php');

        if (!$options->missingTheme && file_exists($configFile)) {
            require_once $configFile;

            if (function_exists('themeConfig') || function_exists('themeConfigSchema')) {
                return true;
            }
        }

        return false;
    }

    /**
     * 配置外观
     *
     * @return Form
     */
    public function config(): Form
    {
        $form = new Form(
            $this->security->getIndex('/action/themes-edit?config=' . Options::alloc()->theme),
            Form::POST_METHOD
        );

        $schema = self::getSchema();
        if (!empty($schema)) {
            self::applySchema($form, $schema);
        } elseif (function_exists('themeConfig')) {
            themeConfig($form);
        }

        $inputs = $form->getInputs();

        if (!empty($inputs)) {
            foreach ($inputs as $key => $val) {
                if (isset($this->options->{$key})) {
                    $form->getInput($key)->value($this->options->{$key});
                }
            }
        }

        $submit = new Submit(null, null, _t('保存设置'));
        $submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);
        return $form;
    }

    /**
     * @param string|null $theme
     * @return array
     */
    public static function getSchema(?string $theme = null): array
    {
        $options = Options::alloc();
        $theme = $theme ?? $options->theme;
        $configFile = $options->themeFile($theme, 'functions.php');

        if (!$options->missingTheme && file_exists($configFile)) {
            require_once $configFile;

            if (function_exists('themeConfigSchema')) {
                $schema = themeConfigSchema();
                return is_array($schema) ? $schema : [];
            }
        }

        return [];
    }

    /**
     * @param Form $form
     * @param array $schema
     * @return void
     */
    public static function applySchema(Form $form, array $schema)
    {
        foreach ($schema as $item) {
            if (empty($item['name'])) {
                continue;
            }

            $element = self::createElement($item);
            if (!$element) {
                continue;
            }

            if (($item['type'] ?? '') === 'checkbox') {
                $element->multiMode();
            }

            foreach ($item['attributes'] ?? [] as $name => $value) {
                if (is_scalar($value)) {
                    $element->input->setAttribute((string) $name, (string) $value);
                }
            }

            foreach ($item['rules'] ?? [] as $rule => $message) {
                if (is_string($rule) && is_string($message)) {
                    $element->addRule($rule, $message);
                }
            }

            $form->addInput($element);
        }
    }

    /**
     * @param array $item
     * @return Element|null
     */
    private static function createElement(array $item): ?Element
    {
        $name = (string) $item['name'];
        $type = strtolower((string) ($item['type'] ?? 'text'));
        $options = isset($item['options']) && is_array($item['options']) ? $item['options'] : null;
        $value = $item['default'] ?? null;
        $label = $item['label'] ?? $name;
        $description = $item['description'] ?? null;

        return match ($type) {
            'textarea' => new Form\Element\Textarea($name, null, $value, $label, $description),
            'select'   => new Form\Element\Select($name, $options ?? [], $value, $label, $description),
            'radio'    => new Form\Element\Radio($name, $options ?? [], $value, $label, $description),
            'checkbox' => new Form\Element\Checkbox($name, $options ?? [], $value, $label, $description),
            'number'   => new Form\Element\Number($name, null, $value, $label, $description),
            'url'      => new Form\Element\Url($name, null, $value, $label, $description),
            default    => new Form\Element\Text($name, null, $value, $label, $description),
        };
    }
}
