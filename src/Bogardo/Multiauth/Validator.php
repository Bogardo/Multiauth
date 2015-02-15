<?php namespace Bogardo\Multiauth;

use App;

class Validator
{

    public function validate($attribute, $value, $parameters)
    {
        $user = App::make('multiauth.service')->queryUsersByIdentifier($value)->first();

        if ($user === null) return true;

        if ( ! empty($parameters) && count($parameters) == 2) {
            list($type, $id) = $parameters;

            return ($user->type == $type && $user->id == $id);
        }

        return false;
    }

}
