<?php
namespace App;

use Carbon\Carbon;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use App\Gitter\Models\User as GitterUser;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

/**
 * Class User
 * @package App
 *
 * @property int $id
 * @property string $gitter_id
 * @property string $name
 * @property string $avatar
 * @property string $url
 * @property string $login
 * @property string $email
 * @property string $password
 * @property string $remember_token
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 */
class User extends Model implements
    AuthenticatableContract,
    AuthorizableContract,
    CanResetPasswordContract
{
    use Authenticatable,
        Authorizable,
        CanResetPassword;

    /**
     * @var string
     */
    protected $primaryKey = 'gitter_id';

    /**
     * The database table used by the model.
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = ['gitter_id', 'url', 'login', 'name', 'avatar', 'email', 'password'];

    /**
     * The attributes excluded from the model's JSON form.
     * @var array
     */
    protected $hidden = ['password', 'remember_token'];

    /**
     * @param GitterUser $gitter
     * @return static
     */
    public static function createFromGitter(GitterUser $gitter)
    {
        $user = static::firstOrNew(['gitter_id' => $gitter->id]);

        $user->name      = $gitter->displayName;
        $user->avatar    = $gitter->avatarUrlMedium;
        $user->url       = $gitter->url;
        $user->login     = $gitter->username;
        $user->email     = null;
        $user->password  = null;
        $user->save();

        return $user;
    }
}
