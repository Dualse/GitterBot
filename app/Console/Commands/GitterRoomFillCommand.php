<?php
namespace App\Console\Commands;

use App\User;
use App\Message;
use App\Gitter\Client;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;

/**
 * Class GitterRoomFillCommand
 * @package App\Console\Commands
 */
class GitterRoomFillCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'gitter:fill {room}';

    /**
     * @var string
     */
    protected $description = 'Parse all room data. Load users and messages';

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

        echo 'Loading users ' . "\n";
        foreach ($room->users as $i => $gitter) {
            echo ($i + 1) . ': ' . $gitter->username . "\n";
            User::createFromGitter($gitter);
        }

        echo "\n";

        $i = 0;
        foreach ($room->messages as $message) {
            echo "\r" . 'Loading messages ' . ($i++);
            Message::createFromGitter($message);
        }

        $client->run();
    }
}
