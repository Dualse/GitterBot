<?php
namespace App\Console\Commands;

use App\User;
use App\Client;
use App\Message;
use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository;
use App\Console\Commands\Support\AbstractGitterCommand;

/**
 * Class GitterRoomFillCommand
 * @package App\Console\Commands
 *
 * Полностью синхронизирует чат с базой данных,
 * включая изменённые данные пользователей и сообщений
 */
class GitterRoomFillCommand extends AbstractGitterCommand
{
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

        // Create a new API Gitter client
        $client = $this->login($app, $config);

        // Resolve room from input argument
        $room   = $client->getRoom($this->getRoomId($config));

        // For option `--users` (or no options)
        if ($this->option('users') || !$this->option('messages')) {
            $this->info('Loading users:');

            foreach ($room->syncUsers() as $id => $user) {
                $this->output->write("\r" . $user->login . ' ' . $id);
            };

            $this->output->newLine();
        }


        // For option `--messages` (or no options)
        if ($this->option('messages') || !$this->option('users')) {
            $this->info('Loading messages:');
            foreach ($room->syncMessages() as $id => $message) {
                $info = str_replace(["\r", "\n"], '', mb_substr($message->text, 0, 100)) . '... ';
                $this->output->write("\r" . $info . $id);
            }
        }

        $client->run();
    }
}
