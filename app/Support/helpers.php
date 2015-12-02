<?php
use App\Support\Lazy\AwaitProxy;

/**
 * @param object $object
 * @param callable|null|string $callback
 * @return $this|Closure
 */
function await($object, $callback = null)
{
    if ($object instanceof Generator) {
        return iterator_to_array($object);
    }

    if ($callback === null) {
        return $object->wait();
    }

    if (is_string($callback)) {
        $callback = function(...$args) use ($object, $callback) {
            return $object->$callback(...$args);
        };
    }

    return (new AwaitProxy($object))->proxy($callback);
}
