<?php
namespace App\Support;
use Illuminate\Database\Eloquent\Model;

/**
 * Class EloquentFactoryTrait
 * @package App\Support
 */
trait EloquentFactoryTrait
{
    /**
     * @var array
     */
    protected static $factoryResolvers = [];

    /**
     * @param $class
     * @return \Illuminate\Foundation\Application|mixed
     */
    public static function factoryRegister($class)
    {
        return app($class);
    }

    /**
     * @param $class
     * @param callable $callback
     */
    public static function factory(callable $callback)
    {
        $function = new \ReflectionFunction($callback);
        if (!count($function->getParameters())) {
            throw new \LogicException('Closure must be define 1 parameter.');
        }

        /** @var \ReflectionParameter $parameter */
        $parameter = $function->getParameters()[0];

        try {
            $class = $parameter->getClass()->getName();
        } catch (\Exception $e) {
            throw new \LogicException('Unavailable factory closure type hinting.');
        }

        /** @var \Eloquent $ctx */
        static::$factoryResolvers[$class] = $callback;
        return static::class;
    }

    /**
     * @param $instance
     * @return \Eloquent
     */
    public static function factoryResolve($instance)
    {
        $class = get_class($instance);
        if (array_key_exists($class, static::$factoryResolvers)) {
            /** @var \Eloquent $ctx */
            $ctx = static::class;

            return $ctx::unguarded(function() use ($class, $instance) {
                $result = call_user_func(static::$factoryResolvers[$class], $instance);
                if ($result instanceof Model) {
                    return $result;
                }
                return new static($result);
            });
        }


        throw new \RuntimeException(sprintf(
            'Can not found available resolver for %s class in %s',
            $class,
            static::class . '::factoryResolve(' . $class . ' $instance)'
        ));
    }
}
