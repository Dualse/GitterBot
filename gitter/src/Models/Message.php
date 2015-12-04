<?php
namespace Gitter\Models;

use Gitter\Client;

/**
 * Class Message
 * @package Gitter\Models
 *
 * === Attributes ===
 *
 * @property-read string $id
 * @property-read string $text
 * @property-read string $html
 * @property-read string $sent
 * @property-read string $editedAt
 * @property-read User $fromUser
 * @property-read bool $unread
 * @property-read int $readBy
 * @property-read array $urls
 * @property-read array $mentions
 * @property-read array $issues
 * @property-read array $meta
 * @property-read int $v
 * @property-read Room $room
 *
 */
class Message extends AbstractModel
{
    /**
     * Message constructor.
     * @param Client $client
     * @param Room $room
     * @param \stdClass $data
     */
    public function __construct(Client $client, Room $room, \stdClass $data)
    {
        $data->fromUser = new User($client, $data->fromUser);
        $data->room     = $room;


        $mentions = $data->mentions;
        $data->mentions = [];
        foreach ($mentions as $mention) {
            if (!property_exists($mention, 'userId') || in_array($mention->userId, $data->mentions)) {
                continue;
            }
            $data->mentions[] = $mention->userId;
        }

        parent::__construct($client, $data);
    }
}

