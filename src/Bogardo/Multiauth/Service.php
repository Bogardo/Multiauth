<?php namespace Bogardo\Multiauth;

use Bogardo\Multiauth\Entity\Entity;
use Bogardo\Multiauth\Entity\EntityCollection;
use Illuminate\Auth\UserInterface;
use Illuminate\Database\DatabaseManager;

/**
 * Class Service
 *
 * @package Bogardo\Multiauth
 */
class Service
{

    /**
     * @var DatabaseManager
     */
    protected $database;

    /**
     * Collection of configured authentication models
     *
     * @var EntityCollection
     */
    protected $entities;

    /**
     * @var string
     */
    protected $identifierKey;


    /**
     * @param array           $config
     * @param DatabaseManager $database
     */
    public function __construct(array $config, DatabaseManager $database)
    {
        $this->database = $database;

        $this->entities = new EntityCollection();
        $this->entities->setConfig($config['entities']);

        $this->identifierKey = $config['identifier_key'];
    }

    /**
     * @param $type
     *
     * @return Entity|null
     */
    public function getEntityByType($type)
    {
        return $this->entities->filter(function ($item) use ($type) {
            return $item->type === $type;
        })->first();
    }

    /**
     * Get the entity collection
     *
     * @return EntityCollection
     */
    public function getEntities()
    {
        return $this->entities;
    }

    /**
     * @param array $credentials
     *
     * @return UserInterface|null
     * @throws \Exception
     */
    public function getUserByCredentials($credentials)
    {
        if (isset($credentials[ $this->identifierKey ])) {
            $identifier = $credentials[ $this->identifierKey ];
        } elseif (isset($credentials['email'])) {
            $identifier = $credentials['email'];
        } else {
            throw new \Exception("Invalid user identifier");
        }

        $user = $this->queryUsersByIdentifier($identifier)->first();

        if (!$user) {
            return null;
        }

        $entity = $this->getEntityByType($user->type);

        $model = new $entity->model;

        return $model->find($user->id);
    }

    /**
     * @param string $identifier
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function queryUsersByIdentifier($identifier)
    {
        $first = $this->getEntities()->first();

        $query = $this->database->table($first->table)
                                ->selectRaw($this->database->raw("'{$first->type}' AS `type`, `id`, `{$first->identifier}` AS `identifier`"))
                                ->where($first->identifier, '=', $identifier);

        foreach ($this->getEntities()->slice(1) as $entity) {
            $subQuery = $this->database->table($entity->table)
                                       ->selectRaw($this->database->raw("'{$entity->type}' AS `type`, `id`, `{$entity->identifier}` AS `identifier`"))
                                       ->where($entity->identifier, '=', $identifier);

            $query = $query->union($subQuery);
        }

        return $query;
    }

}
