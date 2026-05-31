<?php

namespace Typecho\Event;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class Dispatcher
{
    /**
     * @var array<string, array<string, array<int, callable>>>
     */
    private array $listeners = [];

    /**
     * @param string $event
     * @param callable $listener
     * @param int $priority
     */
    public function listen(string $event, callable $listener, int $priority = 0)
    {
        $priority = (string) $priority;
        $this->listeners[$event][$priority][] = $listener;
        krsort($this->listeners[$event], SORT_NUMERIC);
    }

    /**
     * @param string $event
     * @return bool
     */
    public function hasListeners(string $event): bool
    {
        return !empty($this->listeners[$event]);
    }

    /**
     * @param string|Event $event
     * @param array $payload
     * @return Event
     */
    public function dispatch(string|Event $event, array $payload = []): Event
    {
        $event = is_string($event) ? new Event($event, $payload) : $event;

        foreach ($this->listeners[$event->getName()] ?? [] as $listeners) {
            foreach ($listeners as $listener) {
                $result = $listener($event);

                if ($result !== null) {
                    $event->setResult($result);
                }

                if ($event->isPropagationStopped()) {
                    break 2;
                }
            }
        }

        return $event;
    }

    /**
     * @param string $event
     * @param mixed $value
     * @param array $payload
     * @return mixed
     */
    public function filter(string $event, mixed $value, array $payload = []): mixed
    {
        $event = new Event($event, array_merge($payload, ['value' => $value]), true);

        foreach ($this->listeners[$event->getName()] ?? [] as $listeners) {
            foreach ($listeners as $listener) {
                $result = $listener($event);

                if ($result !== null) {
                    $event->setValue($result);
                }

                if ($event->isPropagationStopped()) {
                    break 2;
                }
            }
        }

        return $event->getValue();
    }

    /**
     * 清理所有监听器
     */
    public function reset()
    {
        $this->listeners = [];
    }
}
