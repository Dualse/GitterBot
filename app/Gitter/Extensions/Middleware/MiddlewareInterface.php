<?php
namespace App\Gitter\Extensions\Middleware;

use App\Message;

/**
 * Interface MiddlewareInterface
 * @package App\Gitter\Middleware
 */
interface MiddlewareInterface
{
    /**
     * @param Message $message
     * @param \Closure $next
     * @return mixed
     */
    public function handle(Message $message, \Closure $next);
}
