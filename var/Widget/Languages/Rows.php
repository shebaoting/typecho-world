<?php

namespace Widget\Languages;

use Typecho\Language;
use Typecho\Widget;
use Widget\Options;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 语言包列表组件
 */
class Rows extends Widget
{
    /**
     * 执行函数
     */
    public function execute()
    {
        $options = Options::alloc();
        $current = $options->lang ?: Language::DEFAULT_LANG;
        $langs = Language::scan();

        if (isset($langs[$current])) {
            $currentInfo = $langs[$current];
            unset($langs[$current]);
            $langs = [$current => $currentInfo] + $langs;
        }

        foreach ($langs as $info) {
            $info['activated'] = $current == $info['name'];
            $this->push($info);
        }
    }
}
