<?php
namespace App;

use App\Gitter\Models\Room as GitterRoom;
use App\Support\Lazy\EloquentReader;
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
     * @return Gitter\Client
     */
    public function getClient()
    {
        return $this->room->getClient();
    }

    /**
     * @return \Generator|Message[]
     */
    public function messages()
    {
        return new EloquentReader(Message::room($this->room)->latest());
    }

    /**
     * @return Message[]|\Generator
     */
    public function getIterator()
    {
        return $this->messages();
    }
}
