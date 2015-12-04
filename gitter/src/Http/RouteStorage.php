<?php
namespace Gitter\Http;

/**
 * Class RouteStorage
 * @package Gitter\Http
 */
class RouteStorage
{
    /**
     * @var array<string, Route>
     */
    protected $routes = [];

    /**
     * @var \Closure[]
     */
    protected $resolvers = [];

    /**
     * Routes constructor.
     */
    public function __construct()
    {
        $this->add('stream.events',     'https://stream.gitter.im/v1/rooms/{roomId}/events');
        $this->add('stream.messages',   'https://stream.gitter.im/v1/rooms/{roomId}/chatMessages');
        $this->add('message.send',      'https://api.gitter.im/v1/rooms/{roomId}/chatMessages');
        $this->add('message.update',    'https://api.gitter.im/v1/rooms/{roomId}/chatMessages/{messageId}');
        $this->add('message.unread',    'https://api.gitter.im/v1/user/{userId}/rooms/{roomId}/unreadItems');

        $this->add('room',              'https://api.gitter.im/v1/rooms/{roomId}');
        $this->add('room.all',          'https://api.gitter.im/v1/rooms');
        $this->add('room.join',         'https://api.gitter.im/v1/rooms/{roomId}');
        $this->add('room.users',        'https://api.gitter.im/v1/rooms/{roomId}/users');
        $this->add('room.channels',     'https://api.gitter.im/v1/rooms/{roomId}/channels');
        $this->add('room.messages',     'https://api.gitter.im/v1/rooms/{roomId}/chatMessages');

        $this->add('user',              'https://api.gitter.im/v1/user/{userId}');
        $this->add('user.current',      'https://api.gitter.im/v1/user');
        $this->add('user.rooms',        'https://api.gitter.im/v1/user/{userId}/rooms');
        $this->add('user.orgs',         'https://api.gitter.im/v1/user/{userId}/orgs');
        $this->add('user.repos',        'https://api.gitter.im/v1/user/{userId}/repos');
        $this->add('user.channels',     'https://api.gitter.im/v1/user/{userId}/channels');
    }

    /**
     * @param string $name
     * @param string $url
     * @return $this
     */
    public function add(string $name, string $url) : RouteStorage
    {
        $this->routes[$name] = new Route($url);
        return $this;
    }

    /**
     * @param callable $callback
     * @return $this
     */
    public function resolver(callable $callback)
    {
        $this->resolvers[] = $callback;
        return $this;
    }

    /**
     * @param $name
     * @return Route
     * @throws RouteNotFoundException
     */
    public function get(string $name): Route
    {
        if (array_key_exists($name, $this->routes)) {
            $route = $this->routes[$name];

            foreach ($this->resolvers as $resolver) {
                $route = call_user_func($resolver, $route);
            }

            return $route;
        }
        throw new RouteNotFoundException('Route ' . $name . ' not found');
    }
}
