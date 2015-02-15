<?php namespace Bogardo\Multiauth\Entity;

/**
 * Class Entity
 *
 * @package Bogardo\Multiauth\Entity
 */
class Entity
{

    /**
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    public $table;

    /**
     * @var string
     */
    public $model;

    /**
     * @var string
     */
    public $identifier;

    /**
     * @param array $item
     *
     * @throws \Exception
     */
    public function __construct(array $item)
    {
        $this->type = $this->get('type', $item);

        $this->table = $this->get('table', $item);

        $this->model = $this->get('model', $item);

        $this->identifier = $this->get('identifier', $item);
    }

    /**
     * @param string $key
     * @param array  $array
     *
     * @return mixed
     * @throws \Exception
     */
    private function get($key, array $array)
    {
        if ( ! isset($array[ $key ])) {
            throw new \Exception("Missing config key '{$key}' in multiauth entity configuration");
        }

        return $array[ $key ];
    }
}
