<?php
namespace App\Console\Commands;

use App\Bot\Middleware\PingPongEngMiddleware;
use App\Bot\Middleware\PingPongMiddleware;
use App\User;
use App\Message;
use App\Gitter\Client;
use Illuminate\Console\Command;
use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository;
use App\Gitter\Models\Message as GitterMessage;
use App\Console\Commands\Support\CommandValidatorTrait;
use App\Gitter\Extensions\Middleware\Repository as MiddlewareExtension;

/**
 * Class GitterRoomListenCommand
 * @package App\Console\Commands
 */
class GitterRoomListenCommand extends Command
{
    use CommandValidatorTrait;

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
        // Create Gitter client
        $token  = $this->getApiToken($config);
        $client = new Client($token);

        // Get room information
        $room   = $client->room($this->getRoomId($config));


        // Create middleware extension
        /** @var MiddlewareExtension $middleware */
        $middleware = $app->make(MiddlewareExtension::class, [ 'room' => $room ]);
        $middleware->register(PingPongMiddleware::class);


        // Start message event listener
        $client->stream($room, function(GitterMessage $message) use ($middleware) {
            User::createFromGitter($message->fromUser);
            $instance = Message::createFromGitter($message);

            $middleware->fire($instance);

        }, function(\Exception $e) {
            var_dump(get_class($e), $e->getMessage(), $e->getTraceAsString());
        });

        // Start event loop
        $client->run();
    }
}
