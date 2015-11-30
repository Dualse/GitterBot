<?php
namespace App;

use Carbon\Carbon;
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
 * @property string $text
 * @property string $html
 * @property array $urls
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read User[]|Collection $mentions
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
    protected $fillable = ['gitter_id', 'user_id', 'room_id', 'text', 'html', 'urls'];

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
                'gitter_id' => $gitter->id,
                'user_id'   => $gitter->fromUser->id,
                'room_id'   => $gitter->room->id,
                'text'      => $gitter->text,
                'html'      => $gitter->html,
                'urls'      => json_encode($gitter->urls)
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
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function mentions()
    {
        return $this->belongsToMany(User::class, 'mentions', 'message_id', 'user_id');
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
