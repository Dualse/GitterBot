<?php
namespace App\Observers;

use App\Gitter\Client;
use App\Message;

/**
 * Class MessageObserver
 * @package App\Observers
 */
class MessageObserver
{
    /**
     * @param Message $message
     */
    public function saving(Message $message)
    {
        $before = (string)$message->getOriginal('text');
        $after  = (string)$message->getAttribute('text');

        if ($before && $before !== $after) {
            $message->text = app(Client::class)
                ->request('message.update')
                ->where('roomId', $message->room_id)
                ->where('messageId', $message->gitter_id)
                ->fetch([
                    'method' => 'PUT',
                    'body'   => ['text' => (string)$message->text],
                ])
                ->then(function ($d) {
                    return $d->text;
                })
                ->wait();
        }
    }
}
