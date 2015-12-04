<?php
namespace App\Gitter\Extensions\Middleware;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Class Response
 * @package App\Gitter\Extensions\Middleware
 */
class Response
{
    /**
     * @var string
     */
    protected $content = '';

    /**
     * @var bool
     */
    protected $empty = true;

    /**
     * Response constructor.
     * @param string $content
     */
    public function __construct($content = '')
    {
        $this->setContent($content);
    }

    /**
     * @param $result
     * @return null|string
     */
    protected function parse($result)
    {
        switch (true) {
            case is_string($result) || is_int($result) ||
                (is_object($result) && method_exists($result, '__toString')):
                return (string)$result;

            case is_array($result):
                return implode("\n", $result);

            case is_object($result) && method_exists($result, 'toString'):
                return $result->toString();

            case is_object($result) && $result instanceof Arrayable:
                return implode("\n", $result->toArray());

            case ($result instanceof static):
                return $result->getContent();
        }

        return null;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param $content
     * @return Response
     */
    public function setContent($content)
    {
        $content = $this->parse($content);

        $this->empty   = !$content;
        $this->content = $content;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isEmpty()
    {
        return $this->empty;
    }

    /**
     * @return Response
     */
    public function italic()
    {
        return $this->wrap('_');
    }

    /**
     * @return Response
     */
    public function bold()
    {
        return $this->wrap('**');
    }

    /**
     * @param string|null $lang
     * @return Response
     */
    public function code(string $lang = null)
    {
        if ($lang === null) {
            return $this->wrap('`');
        }
        return $this->wrap('```' . $lang . "\n", "\n```");
    }

    /**
     * @param $src
     * @param bool $after
     * @return Response
     */
    public function image($src, $after = true)
    {
        $code = '![' . $src . '](' . $src . ')';
        if ($after) {
            return $this->after($code);
        }
        return $this->before($code);
    }

    /**
     * @param bool $after
     * @return Response
     */
    public function hr($after = true)
    {
        if ($after) {
            return $this->after('---', true);
        }
        return $this->before('---', true);
    }

    /**
     * @return Response
     */
    public function quote()
    {
        return $this->before('> ');
    }

    /**
     * @param string $symbol
     * @param string|null $symbolAfter
     * @return Response
     */
    public function wrap(string $symbol, string $symbolAfter = null)
    {
        if ($symbolAfter !== null) {
            $symbolAfter = $symbol;
        }

        $this->setContent($symbol . $this->getContent() . $symbolAfter);
        return $this;
    }

    /**
     * @param $text
     * @param bool $newline
     * @return Response
     */
    public function before($text, $newline = false)
    {
        return $this->wrap($text . ($newline ? "\n" : ''), '');
    }

    /**
     * @param $text
     * @param bool $newline
     * @return Response
     */
    public function after($text, $newline = false)
    {
        return $this->wrap('', ($newline ? "\n" : '') . $text);
    }
}
