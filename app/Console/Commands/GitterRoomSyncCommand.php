<?php
namespace App\Console\Commands;

use App\Console\Commands\Support\CommandValidatorTrait;
use App\User;
use App\Message;
use App\Gitter\Client;
use App\Gitter\Http\Route;
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
    use CommandValidatorTrait;

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
        $token = $this->getApiToken($config);
        $client = new Client($token);
        $room   = $client->room($this->getRoomId($config));

        $chunk = $room->messages(false, function(Route $route) use ($room) {
            return $route->with('afterId', Message::room($room)->latest()->first()->gitter_id);
        });



        echo 'Loading messages:' . "\n";
        while ($chunk->count()) {
            $i = 0;
            /** @var GitterMessage $message */
            foreach ($chunk as $message) {
                echo "\r" . str_replace(["\r", "\n"], '', mb_substr($message->text, 0, 100)) . '... ' . ($i++);
                Message::createFromGitter($message);
                User::createFromGitter($message->fromUser);
            }
            $chunk = $chunk->next();
        }

    }
}
