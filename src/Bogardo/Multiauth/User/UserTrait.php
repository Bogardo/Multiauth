<?php namespace Bogardo\Multiauth\User;

/**
 * Class UserTrait
 *
 * @package Bogardo\Multiauth\User
 */
trait UserTrait
{

    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     * @throws \Exception
     */
    public function getAuthIdentifier()
    {
        if (!$this->authtype) {
            throw new \Exception("Class " . get_class($this) . " must specify 'authtype' property");
        }

        return $this->authtype . "." . $this->getKey();
    }

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->password;
    }

    /**
     * Get the token value for the "remember me" session.
     *
     * @return string
     */
    public function getRememberToken()
    {
        return $this->{$this->getRememberTokenName()};
    }

    /**
     * Set the token value for the "remember me" session.
     *
     * @param  string  $value
     * @return void
     */
    public function setRememberToken($value)
    {
        $this->{$this->getRememberTokenName()} = $value;
    }

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function getRememberTokenName()
    {
        return 'remember_token';
    }

}
