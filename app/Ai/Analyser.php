<?php
namespace App\Ai;

use App\Room;
use App\Message;
use App\Support\Lazy\FileReader;
use Illuminate\Support\Collection;
use App\Support\Lazy\EloquentReader;
use App\Gitter\Models\Room as GitterRoom;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class Analyser
 * @package App\Ai
 */
class Analyser
{
    /**
     * @var Room
     */
    protected $room;

    /**
     * Analyser constructor.
     * @param GitterRoom $room
     */
    public function __construct(GitterRoom $room)
    {
        $this->room = new Room($room);
    }

    /**
     *
     */
    public function run()
    {
        // @TODO Run analyser
    }
}
