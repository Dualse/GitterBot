<?php
namespace App\Bot\Middleware;

use App\Gitter\Extensions\Middleware\MiddlewareInterface;
use App\Message;

/**
 * Class PingPongMiddleware
 * @package App\Bot\Middleware
 */
class PingPongMiddleware implements MiddlewareInterface
{
    /**
     * @param Message $message
     * @param \Closure $next
     * @return string|null
     */
    public function handle(Message $message, \Closure $next)
    {
        if ($message->text === 'пинг') {
            return 'понг';
        }

        return $next($message);
    }
}

