<?php
namespace App;

/**
 * Class Text
 * @package App
 */
class Text implements \JsonSerializable
{
    /**
     * @var string
     */
    protected $content;

    /**
     * Text constructor.
     * @param string $content
     */
    public function __construct(string $content)
    {
        $this->content = $content;
    }

    /**
     * @return Text[]|array
     */
    public function sentences()
    {
        $result = [];
        foreach (preg_split('/(\n|:[a-z_]+:|\.|\){2,})/isu', $this->content) as $text) {
            $text = trim($text);
            if ($text) { $result[] = new Text($text); }
        }
        return $result;
    }

    /**
     * @return Text
     */
    public function escape()
    {
        $content = $this->content;

        $content = preg_replace('/(?P<char>(?:_|\*))(.+?)(?P=char)/isu', '\\\$1$2\\\$1', $content); // Bug
        $content = preg_replace('/\*\*(.+?)\*\*/isu', '*\\*$1*\\*', $content);
        $content = preg_replace('/\-\-(\-)+/isu', '\-\-\-', $content);
        $content = preg_replace('/\n*^(?!\w\s+)(#)/isu', '\\#', $content);
        $content = preg_replace('/\[(.*?)\]\((.*?)\)/isu', '\\[$1\\]\\($2\\)', $content);

        return new Text($content);
    }

    /**
     * @param $type
     * @return mixed
     */
    public function typeof($type)
    {
        $function = 'is_' . strtolower($type);

        return $function($this->content);
    }

    /**
     * @param $content
     * @param bool $ignoreCase
     * @return bool
     */
    public function is($content, $ignoreCase = false)
    {
        if ($ignoreCase) {
            return mb_strtolower($this->content) === mb_strtolower($content);
        }

        return $this->content === $content;
    }

    /**
     * @param $pattern
     * @return int
     */
    public function match($pattern)
    {
        return preg_match('/^' . $pattern . '$/isu', $this->content);
    }

    /**
     * @param $pattern
     * @return mixed
     */
    public function matches($pattern)
    {
        preg_match('/^' . $pattern . '$/isu', $this->content, $matches);

        return $matches;
    }

    /**
     * @param $content
     * @param $ignoreCase
     * @return string
     */
    public function contains($content, $ignoreCase = false)
    {
        if ($ignoreCase) {
            return mb_strstr(mb_strtolower($this->content), mb_strtolower($content));
        }

        return mb_strstr($this->content, $content);
    }

    /**
     * @return string
     */
    public function onlyWords()
    {
        $content = $this->content;

        $content = preg_replace('/@[a-z0-9_\-]+/iu', '', $content);
        $content = preg_replace('/[^\s\w]/iu', '', $content);
        $content = str_replace(["\n", "\r"], '', $content);

        return new Text(trim($content));
    }

    /**
     * @return string
     */
    public function toLowerCase()
    {
        return new Text(mb_strtolower($this->content));
    }

    /**
     * @return string
     */
    public function toUpperCase()
    {
        return new Text(mb_strtoupper($this->content));
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->content;
    }

    /**
     * @return string
     */
    public function jsonSerialize()
    {
        return (string)$this;
    }
}
