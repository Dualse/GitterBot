<?php
namespace App\Support\Lazy;

/**
 * Class FileReader
 * @package App\Support\Lazy
 */
class FileReader extends AbstractPromise implements \IteratorAggregate
{
    /**
     * @var string
     */
    protected $file;

    /**
     * @var resource
     */
    protected $fpointer;

    /**
     * @var bool
     */
    protected $closed = true;

    /**
     * FileReader constructor.
     * @param string $file
     */
    public function __construct(string $file)
    {
        $this->file = $file;
        $this->open()->lock();
    }

    /**
     * @return $this
     */
    public function clear()
    {
        $position = ftell($this->fpointer);
        $this->close();
        fclose(fopen($this->file, 'w'));
        $this->open();
        $this->position($position);
        return $this;
    }

    /**
     * @return $this
     */
    public function open()
    {
        if ($this->fpointer) {
            $this->close();
        }

        $this->closed = false;
        $this->fpointer = fopen($this->file, 'a+');
        return $this;
    }

    /**
     * @param int $position
     * @return $this
     */
    public function position(int $position = 0)
    {
        fseek($this->fpointer, $position);
        return $this;
    }

    /**
     * @param $text
     * @return $this
     */
    public function write($text)
    {
        fwrite($this->fpointer, $text);
        return $this;
    }

    /**
     * @param null $size
     * @return \Generator
     */
    public function read(int $size = null)
    {
        $i = 0;
        while (!feof($this->fpointer) || ($size !== null && $i < $size)) {
            $i += 1;
            yield fread($this->fpointer, 1);
        }
    }

    /**
     * @return \Generator
     */
    public function stream()
    {
        while (!feof($this->fpointer)) {
            $data = yield fread($this->fpointer, 1);
            if ($data) {
                fwrite($this->fpointer, $data);
            }
        }
    }

    /**
     * @return \Generator
     */
    public function getIterator()
    {
        return $this->stream();
    }

    /**
     * @param array ...$args
     * @return array
     */
    final public function wait(...$args)
    {
        return parent::wait(implode('', iterator_to_array($this)));
    }

    /**
     * @return $this
     */
    public function lock()
    {
        if (!$this->closed) {
            flock($this->fpointer, LOCK_EX);
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function unlock()
    {
        if (!$this->closed) {
            flock($this->fpointer, LOCK_UN);
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function close()
    {
        if (!$this->closed && $this->fpointer) {
            $this->unlock();
            fclose($this->fpointer);
            $this->closed = true;
            $this->fpointer = null;
        }
        return $this;
    }

    /**
     * @return void
     */
    public function __destruct()
    {
        $this->close();
    }
}
