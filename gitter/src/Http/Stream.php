<?php
namespace Gitter\Http;

use Gitter\ { Client, Http\Stream\Buffer };
use React\Dns\Resolver\Factory as DnsResolver;
use React\EventLoop\LoopInterface;
use React\Socket\ConnectionException;
use React\HttpClient\{
    Factory as HttpClient, Response as ReactResponse
};

/**
 * Class Stream
 * @package Gitter\Http
 */
class Stream
{
    /**
     * @var \React\HttpClient\Request
     */
    protected $request;

    /**
     * Stream constructor.
     * @param Client $client
     * @param LoopInterface $loop
     * @param Route $route
     */
    public function __construct(Client $client, LoopInterface $loop, Route $route)
    {
        $dns = (new DnsResolver())->createCached('8.8.8.8', $loop);
        $client = (new HttpClient())->create($loop, $dns);

        $this->request = $client->request('GET', $route->get(), ['Connection' => 'Keep-Alive']);
    }

    /**
     * @param callable|null $fulfilled
     * @param callable|null $rejected
     * @return $this
     */
    public function fetch(callable $fulfilled = null, callable $rejected = null)
    {
        $fulfilled = $fulfilled ?: function ($data) {
        };
        $rejected = $rejected ?: function ($error) {
        };

        try {
            $buffer = new Buffer();

            $this->request->on('response', function (ReactResponse $response) use ($buffer) {
                $response->on('data', function ($data, ReactResponse $response) use ($buffer) {
                    $buffer->add((string)$data);
                });
            });

            $this->request->on('end', function () use ($rejected) {
                $rejected(new ConnectionException('Connection was aborted by server.'));
            });

            $this->request->on('error', function ($exception) use ($rejected) {
                $rejected($exception);
            });

            $buffer->subscribe(function ($data) use ($fulfilled) {
                if (!trim($data)) {
                    return;
                }

                $fulfilled($data);
            });

            $this->request->end();

        } catch (\Exception $e) {
            $rejected(new \RuntimeException($e->getMessage(), $e->getCode(), $e));

        } catch (\Throwable $e) {
            $rejected(new \RuntimeException($e->getMessage(), $e->getCode()));
        }

        return $this;
    }
}
