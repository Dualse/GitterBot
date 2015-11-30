<?php
namespace App\Gitter\Models;

use GuzzleHttp\Promise\PromiseInterface;

/**
 * Class User
 * @package App\Gitter\Models
 *
 * === Attributes ===
 *
 * @property-read string $id
 * @property-read string $username
 * @property-read string $displayName
 * @property-read string $url
 * @property-read string $avatarUrlSmall
 * @property-read string$avatarUrlMedium
 * @property-read string $gv
 *
 * === Relations ===
 *
 * @property-read \Generator|Room[] $rooms
 *
 */
class User extends AbstractModel
{
    /**
     * @param bool $async
     * @return PromiseInterface
     * @throws \RuntimeException
     */
    public function rooms(bool $async = false)
    {
        $result = $this->client
            ->request('user.rooms')
            ->where('userId', $this->id)
            ->then(function($rooms) {
                foreach ($rooms as $roomData) {
                    yield new Room($this->client, $roomData);
                }
            });

        return $async ? $result : $result->wait();
    }


}
