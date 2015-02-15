<?php namespace Bogardo\Multiauth\Entity;

use Illuminate\Support\Collection;

/**
 * Class EntityCollection
 *
 * @package Bogardo\Multiauth\Entity
 */
class EntityCollection extends Collection
{

    /**
     * Convert configuration array to multiauth entities
     *
     * @param array $items
     */
    public function setConfig(array $items = [])
    {
        foreach ($items as $item) {
            $this->push(new Entity($item));
        }
    }

}
