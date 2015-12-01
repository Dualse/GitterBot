<?php
namespace App\Support\Lazy;

use Illuminate\Database\Eloquent\Builder;

/**
 * Class EloquentReader
 * @package App\Support
 */
class EloquentReader extends AbstractPromise implements \IteratorAggregate
{
    /**
     * @var Builder
     */
    protected $builder;

    /**
     * @var int
     */
    protected $chunkSize = 100;

    /**
     * EloquentLazyReader constructor.
     * @param Builder $builder
     */
    public function __construct(Builder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * @param $page
     * @return mixed
     */
    protected function chunk($page)
    {
        return $this->builder
            ->take($this->chunkSize)
            ->skip($page * $this->chunkSize)
            ->get();
    }

    /**
     * @return \Generator
     */
    public function getIterator()
    {
        $page  = 0;
        $chunk = $this->chunk($page);
        while ($chunk->count()) {
            foreach ($chunk as $message) {
                yield $message;
            }
            $chunk = $this->chunk(++$page);
        }
    }
}
