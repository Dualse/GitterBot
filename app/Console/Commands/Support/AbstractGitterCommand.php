<?php
namespace App\Console\Commands\Support;

use App\Client;
use App\Room;
use Illuminate\Console\Command;
use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository;
use App\Gitter\Extensions\Middleware\Repository as Middlewares;

/**
 * Class AbstractGitterCommand
 * @package App\Console\Commands\Support
 */
abstract class AbstractGitterCommand extends Command
{
    /**
     * @param Container $app
     * @param Repository $config
     * @return Client
     * @throws \Exception
     */
    protected function login(Container $app, Repository $config)
    {
        // Resolve token
        $token  = $this->getApiToken($config);

        // Create a Gitter API client
        $client = (new Client($token, $config->get('app.debug')))->register($app);

        return $client;
    }

    /**
     * @param Repository $config
     * @return string
     * @throws \Exception
     */
    protected function getApiToken(Repository $config)
    {
        if (!($token = $config->get('gitter.token'))) {
            throw new \Exception('Gitter token not defined');
        }

        return $token;
    }

    /**
     * @param Repository $config
     * @return string
     * @throws \Exception
     */
    protected function getRoomId(Repository $config)
    {
        // Input room alias
        $roomAlias = trim($this->argument('room'));

        // Check alias
        $rooms = $config->get('gitter.rooms');
        if (!array_key_exists($roomAlias, $rooms)) {
            return $roomAlias;
        }

        // Check room id
        if (array_key_exists('id', $rooms[$roomAlias])) {
            return $rooms[$roomAlias]['id'];
        }

        throw new \Exception('Broken gitter room configuration. Room id not exists.');
    }

    /**
     * @param Container $app
     * @param Room $room
     * @return Middlewares
     */
    protected function getMiddlewares(Container $app, Room $room)
    {
        $classes   = $this->getMiddlewareClasses($app->make('config'), $room->gitter_id);

        $container = new Middlewares($room, $app);

        /** @var string $class */
        foreach ($classes as $class) {
            $container->register($class);
        }

        return $container;
    }

    /**
     * @param Repository $config
     * @param string $roomId
     * @return array
     */
    private function getMiddlewareClasses(Repository $config, string $roomId)
    {
        $rooms = $config->get('gitter.rooms');
        $room  = null;
        foreach ($rooms as $roomConfigs) {
            if ($roomConfigs['id'] === $roomId) {
                $room = $roomConfigs;
                break;
            }
        }

        if ($room !== null && array_key_exists('middlewares', $room)) {
            $result = [];
            $keys   = $room['middlewares'];
            foreach ($keys as $key) {
                $result = array_merge(
                    $result,
                    $config->get('gitter.middlewares.' . $key, [])
                );
            }

            return $result;
        }

        return [];
    }
}
