<?php
namespace App;

use Carbon\Carbon;
use App\Gitter\Client;
use App\Gitter\Models\User as GitterUser;
use App\Gitter\Models\Room as GitterRoom;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use App\Gitter\Models\Message as GitterMessage;

/**
 * Class Message
 * @package App
 *
 * @property int $id
 * @property string $gitter_id
 * @property string $user_id
 * @property string $room_id
 * @property Text $text
 * @property string $html
 * @property array $urls
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read User[]|Collection $mentions
 * @property-read User $user
 *
 * @method Message forRoom(GitterRoom $room)
 */
class Message extends \Eloquent
{
    /**
     * @var string
     */
    protected $primaryKey = 'gitter_id';

    /**
     * @var string
     */
    protected $table = 'messages';

    /**
     * @var array
     */
    protected $fillable = ['gitter_id', 'user_id', 'room_id', 'text', 'html', 'urls', 'created_at', 'updated_at'];

    public static function boot()
    {
        parent::boot();

        static::saving(function(Message $message) {
            $before = $message->getOriginal('text');
            $after  = $message->getAttribute('text');

            if ($before && $before !== $after) {
                $message->text = app(Client::class)
                    ->request('message.update')
                    ->where('roomId', $message->room_id)
                    ->where('messageId', $message->gitter_id)
                    ->fetch([
                        'method' => 'PUT',
                        'body'   => ['text' => (string)$message->text]
                    ])
                    ->then(function ($d) {
                        return $d->text;
                    })
                    ->wait();
            }
        });
    }


    /**
     * @param GitterMessage $gitter
     * @return static
     */
    public static function createFromGitter(GitterMessage $gitter)
    {
        /** @var Message $message */
        $message = static::where(['gitter_id' => $gitter->id])->first();
        if (!$message) {
            $message = static::create([
                'gitter_id'     => $gitter->id,
                'user_id'       => $gitter->fromUser->id,
                'room_id'       => $gitter->room->id,
                'text'          => $gitter->text,
                'html'          => $gitter->html,
                'urls'          => json_encode($gitter->urls),
                'created_at'    => $gitter->sent->timestamp,
                'updated_at'    => $gitter->editedAt->timestamp
            ]);
        };


        /** @var {$userId}[] */
        if ($gitter->mentions !== []) {
            foreach ($gitter->mentions as $mention) {
                if (!property_exists($mention, 'userId')) {
                    continue;
                }

                Mention::firstOrCreate([
                    'user_id'    => $mention->userId,
                    'message_id' => $gitter->id
                ]);
            }
        }

        return $message;
    }

    /**
     * @param Builder $builder
     * @return $this
     */
    public static function scopeOwn(Builder $builder)
    {
        /** @var GitterUser $user */
        $user = app(Client::class)->getUser();
        return $builder->where('user_id', $user->id);
    }

    /**
     * @param Builder $builder
     * @param GitterRoom $room
     * @return $this
     */
    public static function scopeForRoom(Builder $builder, GitterRoom $room)
    {
        return $builder->where('room_id', $room->id);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function mentions()
    {
        return $this->belongsToMany(User::class, 'mentions', 'message_id', 'user_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'gitter_id');
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
