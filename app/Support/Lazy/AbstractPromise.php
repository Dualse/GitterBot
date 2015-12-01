<?php
namespace App\Support\Lazy;

abstract class AbstractPromise implements PromiseInterface
{
    /**
     * @var callable
     */
    protected $awaitChain = [];

    /**
     * @param callable $callback
     * @return $this
     */
    public function then(callable $callback)
    {
        $this->awaitChain[] = $callback;
        return $this;
    }

    /**
     * @param array ...$args
     * @return array
     */
    public function wait(...$args)
    {
        if (count($args) === 0 && $this instanceof \Traversable) {
            $args = [iterator_to_array($this)];
        }

        $result = $args;
        foreach ($this->awaitChain as $await) {
            $data = $await(...$result);
            if ($data) { $result = $data; }
        }
        return count($result) === 1 ? $result[0] : $result;
    }
}
