<?php
namespace App\Console\Commands;

use App\Console\Commands\Support\CommandValidatorTrait;
use App\User;
use App\Message;
use App\Gitter\Client;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;

/**
 * Class GitterRoomFillCommand
 * @package App\Console\Commands
 *
 * Полностью синхронизирует чат с базой данных,
 * включая изменённые данные пользователей и сообщений
 *
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
        $token = $this->getApiToken($config);

        $client = (new Client($token))->register($app);
        $room   = $client->room($this->getRoomId($config));



        if ($this->option('users') || !$this->option('messages')) {
            echo 'Loading users:' . "\n";
            foreach ($room->users as $i => $gitter) {
                echo "\r" . $gitter->username . ' ' . ($i++);
                User::createFromGitter($gitter);
            }
            echo "\n";
        }



        if ($this->option('messages') || !$this->option('users')) {
            $chunk = $room->messages;
            echo 'Loading messages:' . "\n";
            while ($chunk->count()) {
                $i = 0;
                foreach ($chunk as $message) {
                    echo "\r" . str_replace(["\r", "\n"], '', mb_substr($message->text, 0, 100)) . '... ' . ($i++);
                    Message::createFromGitter($message);
                }
                $chunk = $chunk->prev();
            }
        }


        $client->run();
    }
}
