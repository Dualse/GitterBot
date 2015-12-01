<?php
namespace App\Support\Lazy;

/**
 * Class AwaitProxy
 * @package App\Support\Lazy
 */
class AwaitProxy
{
    /**
     * @var object
     */
    protected $target;

    /**
     * @var \Closure[]
     */
    protected $proxy = [];

    /**
     * AwaitProxy constructor.
     * @param object $object
     */
    public function __construct($object)
    {
        $this->target = $object;
    }

    /**
     * @param \Closure $callback
     * @return $this
     */
    public function proxy(\Closure $callback)
    {
        $this->proxy[] = $callback;
        return $this;
    }

    /**
     * @param array ...$args
     * @return object|object
     */
    protected function callProxy(...$args)
    {
        $result = $this->target;
        foreach ($this->proxy as $proxy) {
            $data = $proxy->call($result, ...$args);

            if ($data) { $result = $data; }
        }
        return $result;
    }

    /**
     * @param string $field
     * @return mixed
     */
    public function __get(string $field)
    {
        return $this->target->$field;
    }

    /**
     * @param string $field
     * @param $value
     * @return mixed
     */
    public function __set(string $field, $value)
    {
        $this->target->$field = $value;
    }

    /**
     * @param $method
     * @param array $arguments
     */
    public function __call($method, array $arguments)
    {
        $this->callProxy($method, $arguments);
        $this->target->$method(...$arguments);
    }
}
