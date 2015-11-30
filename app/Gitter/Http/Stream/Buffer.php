<?php
namespace App\Gitter\Http\Stream;

/**
 * Class Buffer
 * @package App\Gitter\Http\Stream
 */
class Buffer
{
    /**
     * @var string
     */
    protected $data = '';

    /**
     * @var array
     */
    protected $callbacks = [];

    /**
     * @param $chunk
     * @return Buffer
     */
    public function add($chunk): Buffer
    {
        $chunks = explode("\n", $chunk);
        $count = count($chunks);

        if ($count === 1) {
            $this->data .= $chunk;

        } else {
            for ($i = 0; $i < $count; $i++) {
                $this->data .= $chunks[$i];
                if ($i !== $count - 1) {
                    $this->flush();
                }
            }
        }

        return $this;
    }

    /**
     * @param callable|array $callback
     * @return Buffer
     */
    public function subscribe($callback): Buffer
    {
        $this->callbacks[] = $callback;

        return $this;
    }

    /**
     * @return $this
     */
    public function clear()
    {
        $this->data = '';

        return $this;
    }

    /**
     * @return mixed|string
     */
    public function flush(): string
    {
        $result = $this->data;
        $this->data = '';

        foreach ($this->callbacks as $callback) {
            $callback($result);
        }

        return $result;
    }

    /**
     * @return mixed|integer
     */
    public function size(): integer
    {
        return strlen($this->data);
    }
}
