<?php
namespace App\Console\Commands;

use App\Message;
use App\Room;
use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository;
use App\Console\Commands\Support\AbstractGitterCommand;
use App\Gitter\Extensions\Middleware\Repository as Middlewares;


/**
 * Class GitterRoomListenCommand
 * @package App\Console\Commands
 *
 * Прослушивание сообщений требуемой комнаты
 */
class GitterRoomListenCommand extends AbstractGitterCommand
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
     * @param Container $app
     * @param Repository $config
     * @throws \Exception
     */
    public function handle(Container $app, Repository $config)
    {
        // Client
        $client      = $this->login($app, $config);

        $room        = $client->getRoom($this->getRoomId($config));
        $middlewares = $this->getMiddlewares($app, $room);

        // Get room information
        $room->listen(function(Room $room, Message $message) use ($middlewares) {
            $middlewares->fire($message);

        }, function(\Throwable $e) use ($client) {
            $this->output->error(get_class($e) . ': ' . $e->getMessage());
            $this->output->warning($e->getTraceAsString());
        });

        // Start event loop
        $client->run();
    }


}
