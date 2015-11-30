<?php
namespace App\Console\Commands;

use App\User;
use App\Message;
use App\Gitter\Client;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use App\Gitter\Models\Message as GitterMessage;

/**
 * Class GitterRoomSyncCommand
 * @package App\Console\Commands
 */
class GitterRoomSyncCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'gitter:sync {room}';

    /**
     * @var string
     */
    protected $description = 'Sync room messages';

    /**
     * @param Repository $config
     * @throws \Exception
     */
    public function handle(Repository $config)
    {
        if (!($roomId = $config->get('gitter.rooms')[$this->argument('room')] ?? null)) {
            throw new \Exception('Broken gitter room name');
        }

        if (!($token = $config->get('gitter.token'))) {
            throw new \Exception('Gitter token not defined');
        }


        $client = new Client($token);
        $room   = $client->room($roomId);

        $lastMessage = Message::last($roomId);
        $messages = $client->request('room.messages')
            ->where('roomId', $roomId)
            ->with('limit', 100)
            ->with('afterId', $lastMessage->gitter_id)
            ->then(function($messages) use ($client, $room) {
                foreach ($messages as $message) {
                    yield new GitterMessage($client, $room, $message);
                }
            })
            ->wait();

        /** @var GitterMessage $message */
        foreach ($messages as $i => $message) {
            echo "\r" . 'Loading messages ' . ($i++);
            User::createFromGitter($message->fromUser);
            Message::createFromGitter($message);
        }
    }
}
