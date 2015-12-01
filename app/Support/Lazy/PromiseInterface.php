<?php
namespace App\Support\Lazy;

/**
 * Interface PromiseInterface
 * @package App\Support\Lazy
 */
interface PromiseInterface
{
    /**
     * @param callable $callback
     * @return mixed
     */
    public function then(callable $callback);

    /**
     * @param array ...$args
     * @return mixed
     */
    public function wait(...$args);
}
