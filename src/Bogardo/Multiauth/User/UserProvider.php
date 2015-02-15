<?php namespace Bogardo\Multiauth\User;

use Bogardo\Multiauth\Service;
use Illuminate\Auth\UserInterface;
use Illuminate\Auth\UserProviderInterface;
use Illuminate\Hashing\HasherInterface;

/**
 * Class UserProvider
 *
 * @package Bogardo\Multiauth\User
 */
class UserProvider implements UserProviderInterface
{

    /**
     * The hasher implementation.
     *
     * @var \Illuminate\Hashing\HasherInterface
     */
    protected $hasher;

    /**
     * The Eloquent user model.
     *
     * @var string
     */
    protected $model;

    /**
     * @var Service
     */
    protected $service;

    public function __construct(HasherInterface $hasher, Service $service)
    {
        $this->hasher = $hasher;

        $this->service = $service;
    }

    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed $identifier
     *
     * @return \Illuminate\Auth\UserInterface|null
     */
    public function retrieveById($identifier)
    {
        list($type, $id) = explode('.', $identifier, 2);

        $entity = $this->service->getEntityByType($type);

        $model = new $entity->model;

        return $model->find($id);
    }

    /**
     * Retrieve a user by by their unique identifier and "remember me" token.
     *
     * @param  mixed  $identifier
     * @param  string $token
     *
     * @return \Illuminate\Auth\UserInterface|null
     */
    public function retrieveByToken($identifier, $token)
    {
        list($type, $id) = explode('.', $identifier, 2);

        $entity = $this->service->getEntityByType($type);

        $model = new $entity->model;

        return $model->newQuery()
                     ->where($model->getKeyName(), $id)
                     ->where($model->getRememberTokenName(), $token)
                     ->first();
    }

    /**
     * Update the "remember me" token for the given user in storage.
     *
     * @param  \Illuminate\Auth\UserInterface $user
     * @param  string                         $token
     *
     * @return void
     */
    public function updateRememberToken(UserInterface $user, $token)
    {
        $user->setRememberToken($token);

        $user->save();
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array $credentials
     *
     * @return \Illuminate\Auth\UserInterface|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        $user = $this->service->getUserByCredentials($credentials);

        if ($user) {
            return $user;
        }

        return null;

    }

    /**
     * Validate a user against the given credentials.
     *
     * @param  \Illuminate\Auth\UserInterface $user
     * @param  array                          $credentials
     *
     * @return bool
     */
    public function validateCredentials(UserInterface $user, array $credentials)
    {
        $plain = $credentials['password'];

        return $this->hasher->check($plain, $user->getAuthPassword());
    }
}
