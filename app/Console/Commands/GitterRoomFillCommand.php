<?php
namespace App\Console\Commands;

use App\User;
use App\Message;
use App\Gitter\Client;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use App\Gitter\Models\Message as GitterMessage;
use App\Console\Commands\Support\CommandValidatorTrait;

/**
 * Class GitterRoomFillCommand
 * @package App\Console\Commands
 *
 * Полностью синхронизирует чат с базой данных,
 * включая изменённые данные пользователей и сообщений
 */
class GitterRoomFillCommand extends Command
{
    use CommandValidatorTrait;

    /**
     * @var string
     */
    protected $signature = 'gitter:fill {room} {--users} {--messages}';

    /**
     * @var string
     */
    protected $description = 'Parse all room data. Load users and messages';

    /**
     * @param Container $app
     * @param Repository $config
     * @throws \Exception
     */
    public function handle(Container $app, Repository $config)
    {
        // Take API Token
        $token = $this->getApiToken($config);

        // Create a new API Gitter client
        $client = (new Client($token, $config->get('app.debug')))->register($app);

        // Resolve room from input argument
        $room   = $client->room($this->getRoomId($config));

        // For option `--users` (or no options)
        if ($this->option('users') || !$this->option('messages')) {

            $this->info('Loading users:');
            foreach ($room->users as $i => $gitter) {
                $this->output->write("\r" . $gitter->username . ' ' . ($i++));

                // Create a new user
                User::factoryResolve($gitter)->save();
            }

            $this->output->newLine();
        }


        // For option `--messages` (or no options)
        if ($this->option('messages') || !$this->option('users')) {
            $this->info('Loading messages:');

            $chunk = $room->messages;
            while ($chunk->count()) {
                $i = 0;

                /** @var \App\Gitter\Models\Message $message */
                foreach ($chunk as $message) {
                    $info = str_replace(["\r", "\n"], '', mb_substr($message->text, 0, 100)) . '... ';
                    $this->output->write("\r" . $info . ($i++));

                    // Create a new message
                    Message::factoryResolve($message)->save();
                }

                $chunk = $chunk->prev();
            }
        }

        $client->run();
    }
}
