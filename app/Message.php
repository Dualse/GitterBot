<?php
namespace App;

use App\Support\EloquentFactoryTrait;
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
    use EloquentFactoryTrait;

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
