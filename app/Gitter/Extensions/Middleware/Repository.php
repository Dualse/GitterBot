<?php
namespace App\Gitter\Extensions\Middleware;


use App\Message;
use App\Gitter\Models\Room;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Support\Arrayable;

/**
 * Class Repository
 * @package App\Gitter\Middleware
 */
class Repository
{
    /**
     * @var array
     */
    protected $items = [];

    /**
     * @var Container
     */
    protected $container;

    /**
     * @var Room
     */
    protected $room;

    /**
     * Repository constructor.
     * @param Room $room
     * @param Container $container
     */
    public function __construct(Room $room, Container $container)
    {
        $this->room = $room;
        $this->container = $container;
    }

    /**
     * @param $middleware
     * @return $this
     */
    public function register($middleware)
    {
        $this->items[] = $this->container->make($middleware, [
            'room' => $this->room,
        ]);
        return $this;
    }

    /**
     * @param Message $message
     */
    public function fire(Message $message)
    {
        if (count($this->items)) {
            $response = new Response($this->fireMiddleware(0, $message));

            if (!$response->isEmpty()) {
                $this->room->say($response->getContent());
            }
        }
    }

    /**
     * @param int $number
     * @param Message $message
     * @return mixed
     */
    protected function fireMiddleware(int $number, Message $message)
    {
        if (!array_key_exists($number, $this->items)) {
            return new Response;
        }

        $middleware = $this->items[$number];

        return $this->container->call([$middleware, 'handle'], [
            'message' => $message,
            'next'    => function(Message $message) use ($number) {
                $result = $this->fireMiddleware(++$number, $message);

                if (!($result instanceof Response)) {
                    return new Response($result);
                }

                return $result;
            }
        ]);
    }
}
