<?php

namespace Typecho\Event;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class Event
{
    private string $name;

    private array $payload;

    private bool $filter;

    private bool $propagationStopped = false;

    private mixed $value = null;

    private mixed $result = null;

    /**
     * @param string $name
     * @param array $payload
     * @param bool $filter
     */
    public function __construct(string $name, array $payload = [], bool $filter = false)
    {
        $this->name = $name;
        $this->payload = $payload;
        $this->filter = $filter;

        if (array_key_exists('value', $payload)) {
            $this->value = $payload['value'];
        }
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function isFilter(): bool
    {
        return $this->filter;
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->payload[$key] ?? $default;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function set(string $key, mixed $value): self
    {
        $this->payload[$key] = $value;
        return $this;
    }

    /**
     * @return array
     */
    public function all(): array
    {
        return $this->payload;
    }

    /**
     * @return mixed
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function setValue(mixed $value): self
    {
        $this->value = $value;
        $this->payload['value'] = $value;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getResult(): mixed
    {
        return $this->result;
    }

    /**
     * @param mixed $result
     * @return $this
     */
    public function setResult(mixed $result): self
    {
        $this->result = $result;
        return $this;
    }

    /**
     * @return $this
     */
    public function stopPropagation(): self
    {
        $this->propagationStopped = true;
        return $this;
    }

    /**
     * @return bool
     */
    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }
}
