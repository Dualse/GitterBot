<?php
namespace Gitter;


use Gitter\Http\ {
    Request, Response, Route, RouteStorage, Stream
};
use Gitter\Models\ {
    Message, Room, User
};
use GuzzleHttp\Promise\PromiseInterface;
use React\EventLoop\{
    Factory as EventLoop, LoopInterface, Timer\Timer
};


/**
 * Class Client
 * @package Gitter
 *
 * === Relations ===
 *
 * @property-read \Generator|Room[] $rooms
 * @property-read \Generator|User[] $users
 */
class Client
{
    /**
     * Cached rooms instances
     *
     * @var array
     */
    protected $storageRooms = [];

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
     * @var User|PromiseInterface
     */
    protected $user;

    /**
     * @var bool
     */
    protected $debug = false;

    /**
     * Client constructor.
     * @param string $token
     * @param bool $debug
     */
    public function __construct(string $token, bool $debug = false)
    {
        $this->debug = $debug;
        $this->token = $token;
        $this->loop = EventLoop::create();

        $this->routes = (new RouteStorage)
            ->resolver(function (Route $route) {
                return $route->with('access_token', $this->token);
            });

        $this->user = $this->user();
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
     * @param string $routeName
     * @return Route|mixed
     * @throws Http\RouteNotFoundException
     * @throws \RuntimeException
     */
    public function request(string $routeName)
    {

        return new class($this, $this->routes->get($routeName), $this->token) extends Route
        {
            /**
             * @var string
             */
            protected $token;

            /**
             * @var Client
             */
            protected $client;

            /**
             * @constructor
             * @param Client $client
             * @param Route $route
             * @param string $accessToken
             */
            final public function __construct(Client $client, Route $route, string $accessToken)
            {
                parent::__construct($route->getUrl(), $route->getArguments());
                $this->client = $client;
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
                    'body'    => null,
                ], $arguments);

                if (strtoupper($arguments['method']) !== 'GET') {
                    $arguments['headers']['Authorization'] = 'Bearer ' . $this->token;
                }

                $this->client->logRoute($this, $arguments);

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
     * @param Route $route
     * @param null $args
     * @return $this
     */
    public function logRoute(Route $route, $args = null)
    {
        if ($this->debug) {
            echo (is_string($args) ? ($args . ' ') : '') . $route->get() . "\n";
            if ($args && !is_string($args)) {
                $output = preg_replace('/(\s*\(|\s*\)|\n*array\n*)/isu', '', print_r($args, 1));
                echo preg_replace('/(\n\n)/isu', "\n", $output) . "\n";
            }
        }

        return $this;
    }

    /**
     * @param $timer
     */
    public function cancelTimer(Timer $timer)
    {
        $this->loop->cancelTimer($timer);
    }

    /**
     * @param callable $callback
     * @param int $timer
     * @return \React\EventLoop\Timer\Timer|\React\EventLoop\Timer\TimerInterface
     */
    public function setInterval(callable $callback, int $timer = 1)
    {
        return $this->loop->addPeriodicTimer($timer, $callback);
    }

    /**
     * @param callable $callback
     * @param int $timer
     * @return \React\EventLoop\Timer\Timer|\React\EventLoop\Timer\TimerInterface
     */
    public function setTimeout(callable $callback, int $timer = 1)
    {
        return $this->loop->addTimer($timer, $callback);
    }

    /**
     * @return User|PromiseInterface
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param string $roomId
     * @param callable|null $fulfilled
     * @param callable|null $rejected
     * @return Stream
     */
    public function stream(string $roomId, callable $fulfilled = null, callable $rejected = null)
    {
        if ($fulfilled === null) {
            $fulfilled = function () {
            };
        }
        if ($rejected === null) {
            $rejected = function () {
            };
        }

        $room = $this->room($roomId);

        $route = $this->routes
            ->get('stream.messages')
            ->where('roomId', $room->id);

        $this->logRoute($route, 'GET');

        return (new Stream($this, $this->loop, $route))
            ->fetch(function ($data) use ($fulfilled, $rejected, $room) {
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
     * @param $roomId
     * @return PromiseInterface|Room
     */
    public function room($roomId)
    {
        if (!array_key_exists($roomId, $this->storageRooms)) {
            $this->storageRooms[$roomId] = $this->request('room')
                ->where('roomId', $roomId)
                ->then(function ($room) {
                    return new Room($this, $room);
                })
                ->wait();
        }

        return $this->storageRooms[$roomId];
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
