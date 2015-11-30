<?php
namespace App\Console\Commands;

use App\Gitter\Client;
use App\Gitter\Models\Message;
use Illuminate\Console\Command;
use React\EventLoop\Factory as EventLoop;
use Illuminate\Contracts\Config\Repository;

/**
 * Class GitterRoomListenCommand
 * @package App\Console\Commands
 */
class GitterRoomListenCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'gitter:listen {room}';

    /**
     * @var string
     */
    protected $description = 'Listen gitter room';

    /**
     * @param Repository $config
     */
    public function handle(Repository $config)
    {
        $client = new Client($config->get('gitter.token'), EventLoop::create());

        $client->stream('5602e05e0fc9f982beb19cdc', function(Message $message) {
            dd($message->toArray());
        });

        //dd(iterator_to_array($client->rooms));

        $client->run();
    }
}
