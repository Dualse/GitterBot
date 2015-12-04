<?php
namespace Gitter\Models;

use Gitter\Client;
use Illuminate\Contracts\Support\ {
    Jsonable, Arrayable
};

/**
 * Class AbstractModel
 * @package Gitter\Models
 */
abstract class AbstractModel implements Arrayable, Jsonable
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * AbstractModel constructor.
     * @param Client $client
     * @param \stdClass $data
     */
    public function __construct(Client $client, \stdClass $data)
    {
        $this->client = $client;
        $this->attributes = (array)$data;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param $field
     * @return mixed|null
     */
    public function __get($field)
    {
        if (array_key_exists($field, $this->attributes)) {
            return $this->attributes[$field];
        }

        if (method_exists($this, $field)) {
            return $this->$field(false);
        }

        return null;
    }

    /**
     * @param int $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray());
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $result = [];
        foreach ($this->attributes as $field => $value) {
            $result[$field] = (is_object($value) && $value instanceof Arrayable)
                ? $value->toArray()
                : $value;
        }

        return $result;
    }
}
