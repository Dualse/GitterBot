<?php
namespace Gitter\Http;

/**
 * Class Route
 * @package Gitter\Http
 */
class Route
{
    /**
     * @var string
     */
    protected $url;
    /**
     * @var array
     */
    protected $arguments = [];

    /**
     * Route constructor.
     * @param string $url
     * @param array $args
     */
    public function __construct(string $url, array $args = [])
    {
        $this->url = $url;
        $this->arguments = $args;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * @param array $arguments
     */
    public function setArguments($arguments)
    {
        $this->arguments = $arguments;
    }

    /**
     * @param string $key
     * @param string $value
     * @return Route
     */
    public function where(string $key, string $value) : Route
    {
        $result = clone $this;
        $result->setUrl(str_replace('{' . $key . '}', $value, $this->url));
        return $result;
    }

    /**
     * @param $key
     * @param $value
     * @return Route
     */
    public function with($key, $value)
    {
        $args = $this->arguments;
        $args[$key] = $value;
        $result = clone $this;
        $result->setArguments($args);
        return $result;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->get();
    }

    /**
     * @return string
     */
    public function get(): string
    {
        return $this->url . '?' . http_build_query($this->arguments);
    }
}
