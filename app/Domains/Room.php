<?php
/**
 * This file is part of GitterBot package.
 *
 * @author Serafim <nesk@xakep.ru>
 * @date 09.10.2015 17:08
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Domains;

use Interfaces\Gitter\Client;
use Interfaces\Gitter\Http\Stream;
use InvalidArgumentException;
use Interfaces\Gitter\Middleware\Storage as Middlewares;
use Interfaces\Gitter\Subscriber\Storage as Subscribers;

/**
 * Class Room
 * @deprecated
 *
 * @property int $id
 */
class Room
{
    /**
     * @param $roomId
     * @return mixed
     */
    public static function getId($roomId)
    {
        $rooms = \Config::get('gitter.rooms');
        $alias = mb_strtolower(trim($roomId));

        if (array_key_exists($alias, $rooms)) {
            return $rooms[$alias];
        }

        return $roomId;
    }

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var string
     */
    public $id;

    /**
     * @var Middlewares
     */
    protected $middlewares;

    /**
     * Room constructor.
     * @param string $roomId
     */
    public function __construct($roomId)
    {
        $this->client = \App::make(Client::class);
        $this->id = static::getId($roomId);
        $this->middlewares = $this->createMiddlewaresStorage();

        $this->createSubscribersStorage();
    }

    /**
     * Create subscribers storage
     * @return Subscribers
     */
    protected function createSubscribersStorage()
    {
        $container = \App::make('app');
        $subscribers = \Config::get('gitter.subscribers');


        $storage = new Subscribers($container);
        foreach ($subscribers as $subscriber) {
            $storage->add($subscriber);
        }

        return $storage;
    }

    /**
     * Create middlewares storage
     * @return Middlewares
     */
    protected function createMiddlewaresStorage()
    {
        $container = \App::make('app');
        $middlewares = \Config::get('gitter.middlewares');


        $storage = new Middlewares($container);
        foreach ($middlewares as $middleware => $priority) {
            $storage->add($middleware, $priority);
        }

        return $storage;
    }

    /**
     * @throws InvalidArgumentException
     * @return Client
     */
    public function listen()
    {
        $client = $this->client
            ->stream('messages', ['roomId' => $this->id])
            ->on(Stream::EVENT_MESSAGE, function ($stream, $data) {
                $this->onMessage(Message::fromGitterObject($data));
            })
            ->on(Stream::EVENT_END, [$this, 'onClose'])
            ->on(Stream::EVENT_ERROR, [$this, 'onError']);

        return $client;
    }

    /**
     * @param \Exception $e
     */
    protected function logException(\Exception $e)
    {
        \Log::error(
            $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . "\n" .
            $e->getTraceAsString() . "\n" .
            str_repeat('=', 80) . "\n"
        );
    }

    /**
     * @param Message $message
     */
    public function onMessage(Message $message)
    {
        try {
            $this->middlewares->handle($message);
        } catch (\Exception $e) {
            $this->logException($e);
        }
    }

    /**
     * @TODO I do not know if it works
     * @param Stream $stream
     */
    public function onClose(Stream $stream)
    {
        $stream->reconnect();
    }

    /**
     * @param Stream $stream
     * @param \Exception $e
     */
    public function onError(Stream $stream, \Exception $e)
    {
        $this->logException($e);
    }

    /**
     * @param $text
     * @return $this
     */
    public function write($text)
    {
        if (\Config::get('gitter.output')) {
            $client = \App::make(Client::class);

            $client->request('message.send', ['roomId' => $this->id], [
                'text' => (string)$text,
            ], 'POST');
        }

        return $this;
    }
}
