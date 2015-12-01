<?php
namespace App;

use App\Gitter\Models\Room as GitterRoom;

/**
 * Class Room
 * @package App
 */
class Room implements \IteratorAggregate
{
    /**
     * @var GitterRoom
     */
    protected $room;

    /**
     * Room constructor.
     * @param GitterRoom $room
     */
    public function __construct(GitterRoom $room)
    {
        $this->room = $room;
    }

    /**
     * @param $page
     * @return Message[]|array|\Illuminate\Database\Eloquent\Collection|static[]
     */
    public function getMessagesChunk($page)
    {
        return Message::room($this->room)
            ->latest()
            ->take(100)
            ->skip($page * 100)
            ->get();
    }

    /**
     * @return \Generator|Message[]
     */
    public function messages()
    {
        $page = 0;
        $chunk = $this->getMessagesChunk($page);
        while ($chunk->count()) {
            foreach ($chunk as $message) {
                yield $message;
            }
            $chunk = $this->getMessagesChunk($page++);
        }
    }

    /**
     * @return Message[]|\Generator
     */
    public function getIterator()
    {
        return $this->messages();
    }
}
