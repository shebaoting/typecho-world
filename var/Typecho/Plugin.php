<?php

namespace Typecho;

use Typecho\Event\Dispatcher;
use Typecho\Event\Event;
use Typecho\Plugin\EventProviderInterface;
use Typecho\Plugin\Exception as PluginException;

/**
 * 插件处理类
 *
 * @category typecho
 * @package Plugin
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Plugin
{
    /**
     * 所有启用的插件
     *
     * @var array
     */
    private static array $plugin = [];

    /**
     * 实例化的插件对象
     *
     * @var array
     */
    private static array $instances = [];

    /**
     * 事件分发器
     *
     * @var Dispatcher|null
     */
    private static ?Dispatcher $dispatcher = null;

    /**
     * 插件目录
     *
     * @var string|null
     */
    private static ?string $pluginDir = null;

    /**
     * 临时存储变量
     *
     * @var array
     */
    private static array $tmp = [];

    /**
     * 唯一句柄
     *
     * @var string
     */
    private string $handle;

    /**
     * 组件
     *
     * @var string
     */
    private $component;

    /**
     * 是否触发插件的信号
     *
     * @var boolean
     */
    private bool $signal = false;

    /**
     * 插件初始化
     *
     * @param string $handle 插件
     */
    public function __construct(string $handle)
    {
        if (defined('__TYPECHO_CLASS_ALIASES__')) {
            $alias = array_search('\\' . ltrim($handle, '\\'), __TYPECHO_CLASS_ALIASES__);
            $handle = $alias ?: $handle;
        }

        $this->handle = Common::nativeClassName($handle);
    }

    /**
     * 插件初始化
     *
     * @param array $plugins 插件列表
     */
    public static function init(array $plugins, ?string $pluginDir = null)
    {
        $plugins['activated'] = array_key_exists('activated', $plugins) ? $plugins['activated'] : [];
        $plugins['handles'] = array_key_exists('handles', $plugins) ? $plugins['handles'] : [];
        $plugins['events'] = array_key_exists('events', $plugins) ? $plugins['events'] : [];

        /** 初始化变量 */
        self::$plugin = $plugins;
        self::$pluginDir = $pluginDir ?: __TYPECHO_ROOT_DIR__ . __TYPECHO_PLUGIN_DIR__;
        self::$instances = [];
        self::rebuildDispatcher();
    }

    /**
     * 获取全局事件分发器
     *
     * @return Dispatcher
     */
    public static function events(): Dispatcher
    {
        if (!isset(self::$dispatcher)) {
            self::$dispatcher = new Dispatcher();
        }

        return self::$dispatcher;
    }

    /**
     * 注册事件监听器
     *
     * @param string $event
     * @param callable $listener
     * @param int $priority
     * @param bool $persist
     */
    public static function on(string $event, callable $listener, int $priority = 0, bool $persist = false)
    {
        self::events()->listen($event, $listener, $priority);

        if (!$persist) {
            return;
        }

        if (!isset(self::$plugin['events'][$event])) {
            self::$plugin['events'][$event] = [];
        }

        if (!isset(self::$tmp['events'][$event])) {
            self::$tmp['events'][$event] = [];
        }

        $weight = $priority;
        while (isset(self::$plugin['events'][$event][(string) $weight])) {
            $weight += 0.001;
        }

        self::$plugin['events'][$event][(string) $weight] = $listener;
        self::$tmp['events'][$event][] = $listener;
    }

    /**
     * 生成事件名称
     *
     * @param string $handle
     * @param string $component
     * @return string
     */
    public static function eventName(string $handle, string $component): string
    {
        return Common::nativeClassName($handle) . ':' . $component;
    }

    /**
     * 获取实例化插件对象
     *
     * @param string $handle 插件
     * @return Plugin
     */
    public static function factory(string $handle): Plugin
    {
        return self::$instances[$handle] ?? (self::$instances[$handle] = new self($handle));
    }

    /**
     * 启用插件
     *
     * @param string $pluginName 插件名称
     */
    public static function activate(string $pluginName)
    {
        self::$plugin['activated'][$pluginName] = self::$tmp;
        self::$tmp = [];
        self::rebuildDispatcher();
    }

    /**
     * 禁用插件
     *
     * @param string $pluginName 插件名称
     */
    public static function deactivate(string $pluginName)
    {
        /** 去掉所有相关回调函数 */
        if (
            isset(self::$plugin['activated'][$pluginName]['handles'])
            && is_array(self::$plugin['activated'][$pluginName]['handles'])
        ) {
            foreach (self::$plugin['activated'][$pluginName]['handles'] as $handle => $handles) {
                self::$plugin['handles'][$handle] = self::pluginHandlesDiff(
                    empty(self::$plugin['handles'][$handle]) ? [] : self::$plugin['handles'][$handle],
                    empty($handles) ? [] : $handles
                );
                if (empty(self::$plugin['handles'][$handle])) {
                    unset(self::$plugin['handles'][$handle]);
                }
            }
        }

        /** 去掉所有相关新事件回调函数 */
        if (
            isset(self::$plugin['activated'][$pluginName]['events'])
            && is_array(self::$plugin['activated'][$pluginName]['events'])
        ) {
            foreach (self::$plugin['activated'][$pluginName]['events'] as $event => $events) {
                self::$plugin['events'][$event] = self::pluginHandlesDiff(
                    empty(self::$plugin['events'][$event]) ? [] : self::$plugin['events'][$event],
                    empty($events) ? [] : $events
                );
                if (empty(self::$plugin['events'][$event])) {
                    unset(self::$plugin['events'][$event]);
                }
            }
        }

        /** 禁用当前插件 */
        unset(self::$plugin['activated'][$pluginName]);
        self::rebuildDispatcher();
    }

    /**
     * 重新构建运行时事件分发器
     */
    private static function rebuildDispatcher()
    {
        self::$dispatcher = new Dispatcher();

        foreach (self::$plugin['handles'] ?? [] as $componentKey => $callbacks) {
            foreach ($callbacks as $weight => $callback) {
                if (is_callable($callback)) {
                    self::registerLegacyHandle($componentKey, $callback, self::legacyPriority($weight));
                }
            }
        }

        foreach (self::$plugin['events'] ?? [] as $event => $callbacks) {
            foreach ($callbacks as $priority => $callback) {
                if (is_callable($callback)) {
                    self::events()->listen($event, $callback, (int) $priority);
                }
            }
        }

        self::registerEventProviders();
    }

    /**
     * 注册新插件 API 的事件 provider
     */
    private static function registerEventProviders()
    {
        if (!isset(self::$pluginDir)) {
            return;
        }

        foreach (array_keys(self::$plugin['activated'] ?? []) as $pluginName) {
            try {
                [$pluginFileName, $className] = self::portal($pluginName, self::$pluginDir);
            } catch (PluginException $e) {
                continue;
            }

            require_once $pluginFileName;

            if (class_exists($className) && is_subclass_of($className, EventProviderInterface::class)) {
                call_user_func([$className, 'register'], self::events());
            }
        }
    }

    /**
     * 注册旧插件回调为运行时事件监听器
     *
     * @param string $componentKey
     * @param callable $callback
     * @param int $priority
     */
    private static function registerLegacyHandle(string $componentKey, callable $callback, int $priority)
    {
        self::events()->listen($componentKey, function (Event $event) use ($callback) {
            if ($event->isFilter()) {
                $args = $event->get('args', []);
                $current = $event->getValue();
                $result = call_user_func_array($callback, array_merge([$current], $args, [$current]));
                $event->setValue($result);
                return $result;
            }

            $result = call_user_func_array($callback, $event->get('args', []));
            $event->setResult($result);
            return $result;
        }, $priority);
    }

    /**
     * @param int|string $weight
     * @return int
     */
    private static function legacyPriority($weight): int
    {
        return (int) round(-floatval($weight) * 1000);
    }

    /**
     * 插件handle比对
     *
     * @param array $pluginHandles
     * @param array $otherPluginHandles
     * @return array
     */
    private static function pluginHandlesDiff(array $pluginHandles, array $otherPluginHandles): array
    {
        foreach ($otherPluginHandles as $handle) {
            while (false !== ($index = array_search($handle, $pluginHandles))) {
                unset($pluginHandles[$index]);
            }
        }

        return $pluginHandles;
    }

    /**
     * 导出当前插件设置
     *
     * @return array
     */
    public static function export(): array
    {
        return self::$plugin;
    }

    /**
     * 获取插件文件的头信息
     *
     * @param string $pluginFile 插件文件路径
     * @return array
     */
    public static function parseInfo(string $pluginFile): array
    {
        $tokens = token_get_all(file_get_contents($pluginFile));
        $isDoc = false;
        $isFunction = false;
        $isClass = false;
        $isInClass = false;
        $isInFunction = false;
        $isDefined = false;
        $current = null;

        /** 初始信息 */
        $info = [
            'description' => '',
            'title' => '',
            'author' => '',
            'homepage' => '',
            'version' => '',
            'since' => '',
            'activate' => false,
            'deactivate' => false,
            'config' => false,
            'personalConfig' => false,
            'eventProvider' => false
        ];

        $map = [
            'package' => 'title',
            'author' => 'author',
            'link' => 'homepage',
            'since' => 'since',
            'version' => 'version'
        ];

        foreach ($tokens as $token) {
            /** 获取doc comment */
            if (!$isDoc && is_array($token) && T_DOC_COMMENT == $token[0]) {

                /** 分行读取 */
                $described = false;
                $lines = preg_split("([\r\n])", $token[1]);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (!empty($line) && '*' == $line[0]) {
                        $line = trim(substr($line, 1));
                        if (!$described && !empty($line) && '@' == $line[0]) {
                            $described = true;
                        }

                        if (!$described && !empty($line)) {
                            $info['description'] .= $line . "\n";
                        } elseif ($described && !empty($line) && '@' == $line[0]) {
                            $info['description'] = trim($info['description']);
                            $line = trim(substr($line, 1));
                            $args = explode(' ', $line);
                            $key = array_shift($args);

                            if (isset($map[$key])) {
                                $info[$map[$key]] = trim(implode(' ', $args));
                            }
                        }
                    }
                }

                $isDoc = true;
            }

            if (is_array($token)) {
                switch ($token[0]) {
                    case T_FUNCTION:
                        $isFunction = true;
                        break;
                    case T_IMPLEMENTS:
                        $isClass = true;
                        break;
                    case T_WHITESPACE:
                    case T_COMMENT:
                    case T_DOC_COMMENT:
                        break;
                    case T_STRING:
                    case T_NAME_QUALIFIED:
                        $string = strtolower($token[1]);
                        $string = substr($string, strrpos('\\' . $string, '\\'));
                        switch ($string) {
                            case 'typecho_plugin_interface':
                            case 'plugininterface':
                                $isInClass = $isClass;
                                break;
                            case 'eventproviderinterface':
                                $info['eventProvider'] = true;
                                $isInClass = $isClass;
                                break;
                            case 'activate':
                            case 'deactivate':
                            case 'config':
                            case 'personalconfig':
                                if ($isFunction) {
                                    $current = ('personalconfig' == $string ? 'personalConfig' : $string);
                                }
                                break;
                            default:
                                if (!empty($current) && $isInFunction && $isInClass) {
                                    $info[$current] = true;
                                }
                                break;
                        }
                        break;
                    default:
                        if (!empty($current) && $isInFunction && $isInClass) {
                            $info[$current] = true;
                        }
                        break;
                }
            } else {
                $token = strtolower($token);
                switch ($token) {
                    case '{':
                        if ($isDefined) {
                            $isInFunction = true;
                        }
                        break;
                    case '(':
                        if ($isFunction && !$isDefined) {
                            $isDefined = true;
                        }
                        break;
                    case '}':
                    case ';':
                        $isDefined = false;
                        $isFunction = false;
                        $isInFunction = false;
                        $current = null;
                        break;
                    default:
                        if (!empty($current) && $isInFunction && $isInClass) {
                            $info[$current] = true;
                        }
                        break;
                }
            }
        }

        if ($info['eventProvider']) {
            $info['activate'] = true;
            $info['deactivate'] = true;
        }

        return $info;
    }

    /**
     * 获取插件路径和类名
     * 返回值为一个数组
     * 第一项为插件路径,第二项为类名
     *
     * @param string $pluginName 插件名
     * @param string $path 插件目录
     * @return array
     * @throws PluginException
     */
    public static function portal(string $pluginName, string $path): array
    {
        switch (true) {
            case file_exists($pluginFileName = $path . '/' . $pluginName . '/Plugin.php'):
                $className = "\\" . PLUGIN_NAMESPACE . "\\{$pluginName}\\Plugin";
                break;
            case file_exists($pluginFileName = $path . '/' . $pluginName . '.php'):
                $className = "\\" . PLUGIN_NAMESPACE . "\\" . $pluginName;
                break;
            default:
                throw new PluginException('Missing Plugin ' . $pluginName, 404);
        }

        return [$pluginFileName, $className];
    }

    /**
     * 版本依赖性检测
     *
     * @param string|null $version 插件版本
     * @return boolean
     */
    public static function checkDependence(?string $version): bool
    {
        //如果没有检测规则,直接掠过
        if (empty($version)) {
            return true;
        }

        return version_compare(Common::VERSION, $version, '>=');
    }

    /**
     * 判断是否为可用插件类
     *
     * @param string $className
     * @return bool
     */
    public static function isPluginClass(string $className): bool
    {
        return class_exists($className)
            && (is_subclass_of($className, \Typecho\Plugin\PluginInterface::class)
                || is_subclass_of($className, EventProviderInterface::class)
                || method_exists($className, 'activate'));
    }

    /**
     * 调用插件静态方法,不存在则跳过
     *
     * @param string $className
     * @param string $method
     * @param mixed ...$args
     * @return mixed
     */
    public static function callPluginMethod(string $className, string $method, ...$args)
    {
        if (!method_exists($className, $method)) {
            return null;
        }

        return call_user_func_array([$className, $method], $args);
    }

    /**
     * 判断插件是否存在
     *
     * @param string $pluginName 插件名称
     * @return bool
     */
    public static function exists(string $pluginName): bool
    {
        return array_key_exists($pluginName, self::$plugin['activated']);
    }

    /**
     * 插件调用后的触发器
     *
     * @param boolean|null $signal 触发器
     * @return Plugin
     */
    public function trigger(?bool &$signal): Plugin
    {
        $signal = false;
        $this->signal = &$signal;
        return $this;
    }

    /**
     * 通过魔术函数设置当前组件位置
     *
     * @param string $component 当前组件
     * @return Plugin
     */
    public function __get(string $component)
    {
        $this->component = $component;
        return $this;
    }

    /**
     * 设置回调函数
     *
     * @param string $component 当前组件
     * @param callable $value 回调函数
     */
    public function __set(string $component, callable $value)
    {
        $weight = 0;

        if (strpos($component, '_') > 0) {
            $parts = explode('_', $component, 2);
            [$component, $weight] = $parts;
            $weight = intval($weight) - 10;
        }

        $component = $this->handle . ':' . $component;

        if (!isset(self::$plugin['handles'][$component])) {
            self::$plugin['handles'][$component] = [];
        }

        if (!isset(self::$tmp['handles'][$component])) {
            self::$tmp['handles'][$component] = [];
        }

        foreach (self::$plugin['handles'][$component] as $key => $val) {
            $key = floatval($key);

            if ($weight > $key) {
                break;
            } elseif ($weight == $key) {
                $weight += 0.001;
            }
        }

        self::$plugin['handles'][$component][strval($weight)] = $value;
        self::$tmp['handles'][$component][] = $value;

        ksort(self::$plugin['handles'][$component], SORT_NUMERIC);
        self::registerLegacyHandle($component, $value, self::legacyPriority($weight));
    }

    /**
     * 回调处理函数
     *
     * @param string $component 当前组件
     * @param array $args 参数
     * @return mixed
     */
    public function call(string $component, ...$args)
    {
        $componentKey = $this->handle . ':' . $component;

        if (!self::events()->hasListeners($componentKey)) {
            return null;
        }

        $this->signal = true;
        $event = self::events()->dispatch($componentKey, [
            'handle'    => $this->handle,
            'component' => $component,
            'args'      => $args,
            'plugin'    => $this
        ]);

        return $event->getResult();
    }

    /**
     * 过滤处理函数
     *
     * @param string $component 当前组件
     * @param mixed $value 值
     * @param array $args 参数
     * @return mixed
     */
    public function filter(string $component, $value, ...$args)
    {
        $componentKey = $this->handle . ':' . $component;

        if (!self::events()->hasListeners($componentKey)) {
            return $value;
        }

        $this->signal = true;
        return self::events()->filter($componentKey, $value, [
            'handle'    => $this->handle,
            'component' => $component,
            'args'      => $args,
            'original'  => $value,
            'plugin'    => $this
        ]);
    }

    /**
     * @deprecated ^1.3.0
     * @param string $component
     * @param array $args
     * @return false|mixed|null
     */
    public function __call(string $component, array $args)
    {
        return $this->call($component, ... $args);
    }
}
