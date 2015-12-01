<?php
namespace App\Console\Commands;

use App\Gitter\Http\Route;
use App\User;
use App\Message;
use App\Gitter\Client;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use App\Gitter\Models\Message as GitterMessage;

/**
 * Class GitterRoomSyncCommand
 * @package App\Console\Commands
 *
 * Синхронизирует недостающие данные чата
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

        $chunk = $room->messages(false, function(Route $route) use ($roomId) {
            return $route->with('afterId', Message::last($roomId)->gitter_id);
        });

        echo 'Loading messages:' . "\n";
        while ($chunk->count()) {
            $i = 0;
            foreach ($chunk as $message) {
                echo "\r" . str_replace(["\r", "\n"], '', mb_substr($message->text, 0, 100)) . '... ' . ($i++);
                Message::createFromGitter($message);
                User::createFromGitter($message->fromUser);
            }
            $chunk = $chunk->next();
        }

    }
}
