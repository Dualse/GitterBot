<?php
namespace App\Bot\Middleware;

use App\Gitter\Client;
use App\Gitter\Extensions\Middleware\MiddlewareInterface;
use App\Gitter\Models\Room;
use App\Message;
use App\Text;
use React\EventLoop\Timer\Timer;

/**
 * Class PingPongMiddleware
 * @package App\Bot\Middleware
 */
class PingPongMiddleware implements MiddlewareInterface
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
        if ($message->text->match('timer')) {
            return 1;
        }

        if ($message->text->match('start')) {
            /** @var Message $message */
            $answer = Message::own()
                ->forRoom($this->room->id)
                ->orderBy('created_at', 'desc')->first();
            $number   = (int)(string)$answer->text;

            /** @var Client $client */
            $client = app(Client::class);
            $client->setInterval(function(Timer $timer) use (&$number, $answer, $client) {
                $t = 'я печатаю сообщеньку =)';
                if ($number <= mb_strlen($t)) {
                    $answer->text = mb_substr($t, 0, ++$number);
                    $answer->save();
                } else {
                    $timer->cancel();
                }
            }, 0.1);
        }

        return $next($message);
    }
}

