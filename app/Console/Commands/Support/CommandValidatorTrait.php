<?php
namespace App\Console\Commands\Support;

use Illuminate\Contracts\Config\Repository;

/**
 * Class CommandValidatorTrait
 * @package App\Console\Commands\Support
 */
trait CommandValidatorTrait
{
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
     * @return null
     * @throws \Exception
     */
    protected function getRoomId(Repository $config)
    {
        if (!($roomId = $config->get('gitter.rooms')[$this->argument('room')] ?? null)) {
            throw new \Exception('Broken gitter room name');
        }

        return $roomId;
    }
}
