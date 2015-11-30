<?php
namespace App;

/**
 * Class Mention
 * @package App
 *
 * @property string $user_id
 * @property string $message_id
 *
 * @property-read User $user
 * @property-read Message $message
 */
class Mention extends \Eloquent
{
    /**
     * @var string
     */
    protected $table = 'mentions';

    /**
     * @var array
     */
    protected $fillable = ['user_id', 'message_id'];

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'gitter_id', 'user_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function message()
    {
        return $this->belongsTo(Message::class, 'gitter_id', 'message_id');
    }
}