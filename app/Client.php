<?php
namespace App;

use Gitter\Client as Gitter;
use Illuminate\Container\Container;

/**
 * Class Client
 * @package App
 */
class Client extends Gitter
{
    /**
     * Client constructor.
     * @param string $token
     * @param bool $debug
     */
    public function __construct(string $token, bool $debug = false)
    {
        parent::__construct($token, $debug);

        // Auth
        $this->user = User::where('gitter_id', $this->user->id)->first();
        \Auth::loginUsingId($this->user->id);
    }

    /**
     * @param Container $container
     * @return $this
     */
    public function register(Container $container)
    {
        $container->singleton(static::class, function() { return $this; });
        return $this;
    }

    /**
     * @param $roomId
     * @return Room
     */
    public function getRoom($roomId)
    {
        $room = Room::where('gitter_id', $roomId)->first();

        if (!$room) {
            /** @var \Gitter\Models\Room $data */
            $data = parent::room($roomId);
            $room = Room::unguarded(function() use ($data) {
                return Room::create([
                    'gitter_id' => $data->id,
                    'title'     => $data->name,
                    'url'       => $data->url,
                    'type'      => $data->oneToOne ? 'personal' : 'group',
                ]);
            });
        }

        return $room;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }
}
