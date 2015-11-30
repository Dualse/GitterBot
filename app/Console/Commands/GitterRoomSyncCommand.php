<?php
namespace App\Console\Commands;

use App\Gitter\Client;
use App\Message;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;

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
    protected $description = 'Synchronize gitter room. Load users and messages';

    /**
     * @param Repository $config
     */
    public function handle(Repository $config)
    {
        $client = new Client($config->get('gitter.token'));
        $room   = $client->room('52f9b90e5e986b0712ef6b9d');

        foreach ($room->users as $i => $gitter) {
            echo "\r" . 'Loading users ' . $i;
            User::createFromGitter($gitter);
        }

        echo "\n";

        foreach ($room->messages as $i => $message) {
            echo "\r" . 'Loading messages ' . $i;

            Message::createFromGitter($message);
        }

        $client->run();
    }
}
