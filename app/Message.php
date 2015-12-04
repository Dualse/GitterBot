<?php
namespace App;

use Carbon\Carbon;
use Gitter\Models\Message as GitterMessage;
use Illuminate\Database\Eloquent\ { Builder, Collection };

/**
 * Class Message
 * @package App
 *
 * @property int $id
 * @property string $gitter_id
 * @property string $user_id
 * @property string $room_id
 * @property Text $text
 * @property array $sentences
 * @property string $html
 * @property array $urls
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read Mention[]|Collection $mentions
 * @property-read User $user
 *
 * @method Message forRoom(Room $room)
 */
class Message extends \Eloquent
{
    /**
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * @var string
     */
    protected $table = 'messages';

    /**
     * @var array
     */
    protected $fillable = ['gitter_id', 'user_id', 'room_id', 'text', 'html', 'urls', 'created_at', 'updated_at'];

    /**
     * @param GitterMessage $gitter
     * @return Message
     */
    public static function createFromGitterMessage(GitterMessage $gitter)
    {
        // Mentions
        foreach ($gitter->mentions as $mention) {
            Mention::firstOrCreate([
                'user_id'    => $mention,
                'message_id' => $gitter->id,
            ]);
        }

        /** @var Message $message */
        $message = Message::where(['gitter_id' => $gitter->id])->first();
        if (!$message) {
            $message = Message::create([
                'gitter_id' => $gitter->id,
                'user_id'   => $gitter->fromUser->id,
                'room_id'   => $gitter->room->id,
                'text'      => $gitter->text,
                'html'      => $gitter->html,
                'urls'      => json_encode($gitter->urls),

                'created_at' => (new Carbon($gitter->sent ?? date('Y-m-d H:i:s', 0)))
                    ->setTimezone('Europe/Moscow')
                    ->timestamp,

                'updated_at' => (new Carbon($gitter->editedAt ?? date('Y-m-d H:i:s', 0)))
                    ->setTimezone('Europe/Moscow')
                    ->timestamp,
            ]);
        }

        return $message;
    }

    /**
     * @param Builder $builder
     * @return $this
     */
    public static function scopeOwn(Builder $builder)
    {
        /** @var User $user */
        $user = app(Client::class)->getUser();

        return $builder->where('user_id', $user->gitter_id);
    }

    /**
     * @param Builder $builder
     * @param Room|string $room
     * @return $this
     */
    public static function scopeForRoom(Builder $builder, $room)
    {
        $roomId = $room;
        if ($room instanceof Room) {
            $roomId = $room->gitter_id;
        }

        return $builder->where('room_id', $roomId);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function mentions()
    {
        return $this->hasMany(Mention::class, 'message_id', 'gitter_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'gitter_id');
    }

    /**
     * @outputs
     * @param $message
     * @return mixed
     */
    public function answer($message)
    {
        // Break while output breaks
        if (!app('config')->get('gitter.output')) {
            return null;
        }

        /** @var Client $client */
        $client = app(Client::class);

        return await($client
            ->request('message.send')
            ->where('roomId', $this->room_id)
            ->fetch([
                'method' => 'POST',
                'body'   => ['text' => $message],
            ]));
    }

    /**
     * @param $content
     * @return Text
     */
    public function getTextAttribute($content)
    {
        return new Text($content);
    }

    /**
     * @param array $urls
     */
    public function setUrlsAttribute($urls = [])
    {
        $this->attributes['urls'] = json_encode($urls);
    }

    /**
     * @param $urls
     * @return array|mixed
     */
    public function getUrlsAttribute($urls)
    {
        $result = json_decode($urls);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }

        return $result;
    }
}
