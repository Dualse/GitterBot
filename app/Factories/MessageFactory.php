<?php
namespace App\Factories;

use App\Mention;
use App\Message;
use App\Gitter\Models\Message as GitterMessage;
use Carbon\Carbon;

/**
 * Class MessageFactory
 * @package App\Factories
 */
class MessageFactory
{
    /**
     * MessageFactory constructor.
     */
    public function __construct()
    {
        Message::factory(function(GitterMessage $message) {
            return $this->fromGitterMessage($message);
        });
    }

    /**
     * @param GitterMessage $gitter
     * @return array
     */
    protected function fromGitterMessage(GitterMessage $gitter)
    {
        /** @var {$userId}[] */
        if ($gitter->mentions !== []) {
            foreach ($gitter->mentions as $mention) {
                if (!property_exists($mention, 'userId')) {
                    continue;
                }

                Mention::firstOrCreate([
                    'user_id'    => $mention->userId,
                    'message_id' => $gitter->id
                ]);
            }
        }


        /** @var Message $message */
        $message = Message::where(['gitter_id' => $gitter->id])->first();
        if ($message) {
            return $message;
        }

        $createdAt = (new Carbon($gitter->sent))
            ->setTimezone('Europe/Moscow')
            ->timestamp;

        $updatedAt = (new Carbon($gitter->editedAt ?? date('Y-m-d H:i:s', 0)))
            ->setTimezone('Europe/Moscow')
            ->timestamp;

        return [
            'gitter_id'  => $gitter->id,
            'user_id'    => $gitter->fromUser->id,
            'room_id'    => $gitter->room->id,
            'text'       => $gitter->text,
            'html'       => $gitter->html,
            'urls'       => json_encode($gitter->urls),
            'created_at' => $createdAt,
            'updated_at' => $updatedAt
        ];
    }
}
