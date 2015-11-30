<?php
namespace App;

use Carbon\Carbon;

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
 */
class Message extends \Eloquent
{
    /**
     * @var string
     */
    protected $table = 'messages';

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
