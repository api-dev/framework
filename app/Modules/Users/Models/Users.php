<?php
/**
 * Users - A Users Model.
 *
 * @author Virgil-Adrian Teaca - virgil@giulianaeassociati.com
 * @version 3.0
 */

namespace App\Modules\Users\Models;

use Auth\Model as BaseModel;

use \stdClass;


class Users extends BaseModel
{
    protected $table = 'users';

    protected $primaryKey = 'id';


    public function __construct()
    {
        parent::__construct();
    }

    public function updateUser($user, array $data)
    {
        $keyName = $this->getKeyName();

        if($user instanceof stdClass) {
            // We have a stdClass instance; extract the userId from it.
            $userId = $user->{$keyName};
        } else {
            // We have an ID; just use it for userId.
            $userId = intval($user);
        }

        $this->where($keyName, $userId)->update($data);
    }
}
