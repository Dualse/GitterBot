<?php
namespace App\Factories;

use App\User;
use App\Mention;
use App\Gitter\Models\User as GitterUser;

/**
 * Class UserFactory
 * @package App\Factories
 */
class UserFactory
{
    /**
     * MessageFactory constructor.
     */
    public function __construct()
    {
        User::factory(function(GitterUser $user) {
            return $this->fromGitterUser($user);
        });
    }

    /**
     * @param GitterUser $gitter
     * @return array
     */
    protected function fromGitterUser(GitterUser $gitter)
    {
        /** @var User $user */
        $user = User::firstOrNew(['gitter_id' => $gitter->id]);

        $user->name      = $gitter->displayName;
        $user->avatar    = $gitter->avatarUrlMedium;
        $user->url       = $gitter->url;
        $user->login     = $gitter->username;
        $user->email     = null;
        $user->password  = null;

        return $user;
    }
}
