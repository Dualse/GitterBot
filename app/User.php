<?php
namespace App;

use Carbon\Carbon;
use Gitter\Models\User as GitterUser;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\{
    Authenticatable as AuthenticatableContract,
    Access\Authorizable as AuthorizableContract,
    CanResetPassword as CanResetPasswordContract
};

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
     * @return User
     */
    public static function createFromGitterUser(GitterUser $gitter)
    {
        /** @var User $user */
        $user = User::firstOrNew(['gitter_id' => $gitter->id]);

        $user->name = $gitter->displayName;
        $user->avatar = $gitter->avatarUrlMedium;
        $user->url = $gitter->url;
        $user->login = $gitter->username;
        $user->email = null;
        $user->password = null;
        $user->save();

        return $user;
    }
}
