<?php
namespace App\Console\Commands;

use App\Gitter\Client;
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
        // 52f9b90e5e986b0712ef6b9d

        $client = new Client($config->get('gitter.token'));
        $room   = $client->room('52f9b90e5e986b0712ef6b9d');
        foreach ($room->messages as $messages) {
            echo $messages->text . "\n";
        }

        $client->run();
    }
}
