<?php
namespace Gitter\Models;

use Gitter\ {
    Client, Http\Route
};
use Illuminate\Support\Collection;

/**
 * Class MessagesChunk
 * @package Gitter\Models
 */
class MessagesChunk extends Collection
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var Room
     */
    protected $room;

    /**
     * MessagesChunk constructor.
     * @param Client $client
     * @param Room $room
     * @param array $items
     */
    public function __construct(Client $client, Room $room, $items = [])
    {
        $this->client = $client;
        $this->room = $room;
        parent::__construct($items);
    }

    /**
     * @return MessagesChunk|Message[]
     */
    public function next()
    {
        return $this->query(function (Route $route) {
            return $route->with('afterId', $this->last()->id);
        });
    }

    /**
     * @param callable $forRoute
     * @param bool $async
     * @return MessagesChunk|Message[]
     */
    public function query(callable $forRoute = null, bool $async = false)
    {
        if ($forRoute === null) {
            $forRoute = function (Route $route) {
                return $route;
            };
        }

        $result = $forRoute(
            $this->client
                ->request('room.messages')
                ->where('roomId', $this->room->id)
                ->with('limit', 100)
        )
            ->then(function ($messages) {
                $result = [];
                foreach ($messages as $message) {
                    $result[] = new Message($this->client, $this->room, $message);
                }

                return $result;
            });

        return new static($this->client, $this->room, $result->wait());
    }

    /**
     * @return MessagesChunk|Message[]
     */
    public function prev()
    {
        return $this->query(function (Route $route) {
            return $route->with('beforeId', $this->first()->id);
        });
    }
}
