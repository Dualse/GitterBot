<?php
namespace App\Console\Commands;

use App\User;
use App\Message;
use App\Gitter\Client;
use App\Gitter\Http\Route;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use App\Gitter\Models\Message as GitterMessage;
use App\Console\Commands\Support\CommandValidatorTrait;

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
     * @param Container $app
     * @param Repository $config
     * @throws \Exception
     */
    public function handle(Container $app, Repository $config)
    {
        $token = $this->getApiToken($config);

        // Creating a new Gitter API Client
        $client = (new Client($token, $config->get('app.debug')))->register($app);

        // Creating room from request argument
        $room   = $client->room($this->getRoomId($config));

        // Get message chunk for most recent message
        $chunk = $room->messages(false, function(Route $route) use ($room) {
            return $route->with('afterId',
                Message::query()
                    ->forRoom($room)
                    ->latest()
                    ->first()
                    ->gitter_id
            );
        });


        $this->info('Loading messages:');
        while ($chunk->count()) {
            $i = 0;
            /** @var GitterMessage $message */
            foreach ($chunk as $message) {
                $info = str_replace(["\r", "\n"], '', mb_substr($message->text, 0, 100)) . '... ';
                $this->output->write("\r" . $info . ($i++));

                // Save new message
                Message::factoryResolve($message)->save();

                // Save message user
                User::factoryResolve($message->fromUser)->save();
            }

            $chunk = $chunk->next();
        }

    }
}
