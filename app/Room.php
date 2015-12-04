<?php
namespace App;

use App\Support\Lazy\EloquentReader;
use Carbon\Carbon;
use Gitter\Models\{
    User as GitterUser,
    Message as GitterMessage,
    Room as GitterRoom
};
use Gitter\Models\MessagesChunk;

/**
 * Class Room
 * @package App
 *
 * @property int $id
 * @property string $gitter_id
 * @property string $title
 * @property string $url
 * @property string $type `personal` or `group`
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read Message[] $messages
 *
 */
class Room extends \Eloquent
{
    /**
     * @var string
     */
    protected $table = 'rooms';

    /**
     * @param callable|\Closure $callback
     * @param callable|\Closure|null $rejected
     * @return \Gitter\Http\Stream
     */
    public function listen(callable $callback, callable $rejected = null)
    {
        /** @var Client $client */
        $client = app(Client::class);

        return $client->stream($this->gitter_id, function (GitterMessage $gitter) use ($callback) {
            $message = Message::createFromGitterMessage($gitter);
            $callback($this, $message);
        }, $rejected);
    }

    /**
     * @outputs
     * @param string $message
     * @return mixed
     */
    public function write(string $message)
    {
        // Break while output breaks
        if (!app('config')->get('gitter.output')) {
            return null;
        }

        /** @var Client $client */
        $client = app(Client::class);

        return await($client
            ->request('message.send')
            ->where('roomId', $this->gitter_id)
            ->fetch([
                'method' => 'POST',
                'body'   => ['text' => $message],
            ]));
    }

    /**
     * @DANGEROUS Do not fetch all messages
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function messages()
    {
        return $this->hasMany(Message::class, 'room_id', 'gitter_id');
    }

    /**
     * @return EloquentReader
     */
    public function messagesIterator()
    {
        return new EloquentReader(Message::query()->where('room_id', $this->gitter_id));
    }

    /**
     * @param callable|null $each
     * @return \Generator|int
     */
    public function syncMessages(callable $each = null)
    {
        if ($each === null) { $each = function() {}; }
        $count = 0;

        /** @var Client $client */
        $client = app(Client::class);

        /** @var GitterRoom $room */
        $room = $client->room($this->gitter_id);

        /** @var MessagesChunk $chunk */
        $chunk = $room->messages;
        while ($chunk->count()) {
            /** @var GitterMessage $message */
            foreach ($chunk as $message) {
                $instance = Message::createFromGitterMessage($message);
                $each($instance, ++$count);

                yield $count => $instance;
            }
            $chunk = $chunk->prev();
        }

        return $count;
    }

    /**
     * @param callable|null $each
     * @return \Generator|int
     */
    public function syncUsers(callable $each = null)
    {
        if ($each === null) { $each = function() {}; }
        $count = 0;

        /** @var Client $client */
        $client = app(Client::class);
        $room = $client->room($this->gitter_id);

        /** @var GitterUser $user */
        foreach ($room->users as $user) {
            $instance = User::createFromGitterUser($user);
            $each($instance, ++$count);

            yield $count => $instance;
        }

        return $count;
    }
}
