<?php
namespace App\Gitter\Middleware;

use App\Room;
use App\Client;
use App\Message;
use App\Gitter\Extensions\Middleware\MiddlewareInterface;

/**
 * Class GoogleSearchMiddleware
 * @package App\Bot\Middleware
 */
class GoogleSearchMiddleware implements MiddlewareInterface
{
    /**
     * @var Room
     */
    protected $room;

    /**
     * PingPongMiddleware constructor.
     * @param Room $room
     */
    public function __construct(Room $room)
    {
        $this->room = $room;
    }

    /**
     * @param Message $message
     * @param \Closure $next
     * @return string|null
     */
    public function handle(Message $message, \Closure $next)
    {
        $matches = $message->text->escape()->matches('(@.*?\s)?(?:погугли|гугли)\s(.*?)');

        if ($matches) {
            if (!trim($matches[2])) {
                return $next($message);
            }

            $hasMentions = $message->mentions->count();
            $mention = null;

            if ($hasMentions) {
                $first = $message->mentions[0]->user;
                $mention = $first->login === app(Client::class)->user->login
                    ? $message->user
                    : $first;
            }

            return trim($matches[1]) && $mention
                ? trans('google.personal', [
                    'user'  => $mention->login,
                    'query' => urlencode($matches[2]),
                ])
                : trans('google.common', [
                    'query' => urlencode($matches[2]),
                ]);
        }

        return $next($message);
    }
}

