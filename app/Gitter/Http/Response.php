<?php
namespace App\Gitter\Http;

use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Psr7\Response as GuzzleResponse;

/**
 * Class Response
 * @package App\Gitter\Http
 */
class Response
{
    /**
     * @var int
     */
    public $status;

    /**
     * @var null|string
     */
    public $statusText;

    /**
     * @var string|string
     */
    public $url;

    /**
     * @var array
     */
    public $headers = [];

    /**
     * @var GuzzleResponse
     */
    protected $response;

    /**
     * Response constructor.
     * @param string $url
     * @param GuzzleResponse $response
     */
    public function __construct(string $url, GuzzleResponse $response)
    {
        $this->response     = $response;
        $this->status       = $response->getStatusCode();
        $this->headers      = $response->getHeaders();
        $this->statusText   = $response->getReasonPhrase();
        $this->url          = $url;
    }

    /**
     * @return mixed
     * @throws \RuntimeException
     */
    public function json()
    {
        return json_decode($this->body());
    }

    /**
     * @return \Psr\Http\Message\StreamInterface
     * @throws \RuntimeException
     */
    public function body()
    {
        $stream = $this->response->getBody();
        $stream->seek(0);
        $result = $stream->getContents();
        $stream->close();

        return $result;
    }
}
