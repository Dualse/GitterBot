<?php
namespace App\Console\Commands;

use App\Gitter\Client;
use App\Message;
use App\User;
use Illuminate\Console\Command;
use React\EventLoop\Factory as EventLoop;
use Illuminate\Contracts\Config\Repository;
use App\Gitter\Models\Message as GitterMessage;

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

        $client->stream($roomId, function(GitterMessage $message) {
            User::createFromGitter($message->fromUser);
            Message::createFromGitter($message);
        });

        $client->run();
    }
}
