<?php
namespace App\Gitter\Http;

use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;
use GuzzleHttp\Psr7\Response as Psr7Response;

/**
 * Class Request
 * @package App\Gitter\Http
 */
class Request
{
    /**
     * @var string
     */
    protected $url;

    /**
     * @var string
     */
    protected $method = 'GET';

    /**
     * @var mixed
     */
    protected $body = null;

    /**
     * @var array
     */
    protected $headers = [];

    /**
     * @param $url
     * @param array $args
     * @return PromiseInterface
     */
    public static function fetch($url, array $args = [])
    {
        $request = new static($url);
        foreach ($args as $arg => $value) {
            try {
                $method = 'set' . ucfirst($arg);
                $request->$method($value);
            } catch (\Exception $e) {
                throw new \InvalidArgumentException('Invalid argument ' . $arg);
            }
        }

        return $request->send();
    }

    /**
     * Request constructor.
     * @param string $url
     */
    public function __construct(string $url)
    {
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     * @return $this
     */
    public function setUrl(string $url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @param string $method
     * @return $this
     */
    public function setMethod(string $method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param mixed $body
     * @return $this
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @param array $headers
     * @return $this
     */
    public function setHeaders(array $headers = [])
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * @param $name
     * @param $value
     * @return $this
     */
    public function addHeader($name, $value)
    {
        $this->headers[$name] = $value;

        return $this;
    }

    /**
     * @param $name
     * @return $this
     */
    public function removeHeader($name)
    {
        if (array_key_exists($name, $this->headers)) {
            unset($this->headers[$name]);
        }

        return $this;
    }

    /**
     * @return PromiseInterface
     */
    public function send()
    {
        $arguments = [
            'verify'  => false,
            'headers' => $this->headers,
        ];

        $setJsonBody = function($body) use (&$arguments) {
            $arguments['headers']['Content-Type'] = 'application/json';
            $arguments['headers']['Accept']       = 'application/json';

            $arguments['body'] = $body;
        };

        if ($this->body !== null) {
            switch(true) {
                case is_array($this->body):
                case is_object($this->body) && $this->body instanceof \JsonSerializable:
                    $setJsonBody(json_encode($this->body));
                    break;

                case is_object($this->body) && $this->body instanceof Arrayable:
                    $setJsonBody(json_encode($this->body->toArray()));
                    break;

                case is_object($this->body) && $this->body instanceof Jsonable:
                    $setJsonBody($this->body->toJson());
                    break;

                default:
                    $arguments['body'] = (string)$this->body;
            }
        }

        return (new Guzzle)
            ->requestAsync($this->method, $this->url, $arguments)
            ->then(function(Psr7Response $response) {
                return new Response($this->url, $response);
            });
    }
}
