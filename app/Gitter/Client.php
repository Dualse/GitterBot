<?php
namespace App\Gitter;


use App\Gitter\Http\Route;
use App\Gitter\Http\Stream;
use App\Gitter\Models\Room;
use App\Gitter\Models\User;
use App\Gitter\Http\Request;
use App\Gitter\Http\Response;
use App\Gitter\Models\Message;
use App\Gitter\Http\RouteStorage;
use React\EventLoop\LoopInterface;
use GuzzleHttp\Promise\PromiseInterface;
use React\EventLoop\Factory as EventLoop;


/**
 * Class Client
 * @package App\Gitter
 *
 * === Relations ===
 *
 * @property-read \Generator|Room[] $rooms
 * @property-read \Generator|User[] $users
 */
class Client
{
    /**
     * @var RouteStorage
     */
    protected $routes;

    /**
     * @var string
     */
    protected $token;

    /**
     * @var LoopInterface
     */
    protected $loop;

    /**
     * Client constructor.
     * @param string $token
     */
    public function __construct(string $token)
    {
        $this->token = $token;
        $this->loop  = EventLoop::create();

        $this->routes = (new RouteStorage)
            ->resolver(function (Route $route) {
                return $route->with('access_token', $this->token);
            });
    }

    /**
     * @param Room $room
     * @param callable|null $fulfilled
     * @param callable|null $rejected
     * @return Stream
     */
    public function stream(Room $room, callable $fulfilled = null, callable $rejected = null)
    {
        if ($fulfilled === null) { $fulfilled = function() {}; }
        if ($rejected === null)  { $rejected  = function() {}; }

        $room  = $this->room($room->id);

        $route = $this->routes
            ->get('stream.messages')
            ->where('roomId', $room->id);

        return (new Stream($this, $this->loop, $route))
            ->fetch(function($data) use ($fulfilled, $rejected, $room) {
                // Parse JSON response
                $result = json_decode($data);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return $rejected(new \LogicException((string)json_last_error_msg(), (int)json_last_error()));
                }

                // StdClass to Message
                try {
                    $message = new Message($this, $room, $result);
                    $fulfilled($message);
                } catch (\Exception $e) {
                    $rejected($e);
                }

                return null;
            }, $rejected);
    }

    /**
     * @param bool $async
     * @return PromiseInterface|Room[]
     * @throws \RuntimeException
     */
    public function rooms($async = false)
    {
        $result = $this->request('room.all')
            ->then(function ($rooms) {
                foreach ($rooms as $roomData) {
                    yield new Room($this, $roomData);
                }
            });

        return $async ? $result : $result->wait();
    }

    /**
     * @param string $routeName
     * @return Route|mixed
     * @throws Http\RouteNotFoundException
     * @throws \RuntimeException
     */
    public function request(string $routeName)
    {
        $route = $this->routes->get($routeName);

        return new class($route, $this->token) extends Route
        {
            /**
             * @var string
             */
            protected $token;

            /**
             * class@anonymous constructor.
             * @param Route $route
             * @param string $accessToken
             */
            final public function __construct(Route $route, string $accessToken)
            {
                parent::__construct($route->getUrl(), $route->getArguments());
                $this->token = $accessToken;
            }

            /**
             * @param array $arguments
             * @return PromiseInterface
             * @throws \RuntimeException
             */
            public function fetch(array $arguments = [])
            {
                $arguments = array_merge([
                    'method'  => 'GET',
                    'headers' => [],
                    'body'    => null
                ], $arguments);

                if (strtoupper($arguments['method']) !== 'GET') {
                    $arguments['headers']['Authorization'] = 'Bearer ' . $this->token;
                }

                return Request::fetch($this->get(), $arguments)
                    ->then(function (Response $response) {
                        return $response->json();
                    });
            }

            /**
             * @param callable $callback
             * @return PromiseInterface
             * @throws \RuntimeException
             */
            public function then(callable $callback)
            {
                return $this->fetch()->then($callback);
            }

            /**
             * @param bool $unwrap
             * @return mixed
             * @throws \RuntimeException
             */
            public function wait($unwrap = true)
            {
                return $this->fetch()->wait($unwrap);
            }
        };
    }

    /**
     * @param $roomId
     * @param bool $async
     * @return PromiseInterface|Room
     */
    public function room($roomId, $async = false)
    {
        $result = $this->request('room')
            ->where('roomId', $roomId)
            ->then(function ($room) {
                return new Room($this, $room);
            });

        return $async ? $result : $result->wait();
    }

    /**
     * @param bool $async
     * @return PromiseInterface|User
     * @throws \RuntimeException
     */
    public function user($async = false)
    {
        $result = $this
            ->request('user.current')
            ->then(function ($users) {
                return new User($this, $users[0]);
            });

        return $async ? $result : $result->wait();
    }

    /**
     * @return void
     */
    public function run()
    {
        return $this->loop->run();
    }

    /**
     * @param $field
     * @return mixed|null
     */
    public function __get($field)
    {
        if (method_exists($this, $field)) {
            return $this->$field(false);
        }

        return null;
    }
}
