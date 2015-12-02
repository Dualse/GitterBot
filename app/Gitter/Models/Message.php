<?php
namespace App\Gitter\Models;

use App\Gitter\Client;
use Carbon\Carbon;

/**
 * Class Message
 * @package App\Gitter\Models
 *
 * === Attributes ===
 *
 * @property-read string $id
 * @property-read string $text
 * @property-read string $html
 * @property-read Carbon $sent
 * @property-read Carbon $editedAt
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
        $data->sent     = (new Carbon($data->sent))->setTimezone('Europe/Moscow');
        $data->editedAt = (new Carbon($data->editedAt ?? date('Y-m-d H:i:s', 0)))->setTimezone('Europe/Moscow');

        $data->fromUser = new User($client, $data->fromUser);
        $data->room     = $room;

        parent::__construct($client, $data);
    }
}

