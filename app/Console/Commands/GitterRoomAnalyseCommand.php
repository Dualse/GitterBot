<?php
namespace App\Console\Commands;

use App\Ai\Analyser;
use App\User;
use App\Message;
use App\Gitter\Client;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;

/**
 * Class GitterRoomAnalyseCommand
 * @package App\Console\Commands
 *
 */
class GitterRoomAnalyseCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'gitter:analyse {room}';

    /**
     * @var string
     */
    protected $description = 'Analyse messages for bot ai';

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

        $analyser = new Analyser($room);
        $analyser->run();

        $client->run();
    }
}
