<?php
namespace Rovi\Repository\Relations;

use InvalidArgumentException;
use LogicException;
use Rovi\Repository\Model;
use Rovi\Connections\Connection;
use Collei\Collections\Collection;

/**
 * Loader of model relationships.
 */
class OneToMany extends Relation
{
    /**
     * Retrieves the relation result.
     * 
     * @return Collei\Collections\Collection|null
     */
    public function get()
    {
        $result = $this->connection()->getBuilder()
                    ->table($this->rightTable())
                    ->where($this->rightKey(true), '=', $this->left()->getKey())
                    ->get();

        if (empty($result) || $result->count() == 0) {
            return null;
        }

        $class = $this->leftClass();

        $mapper = $class::getInstanceMapper();

        return $reuslt->map($mapper);
    }
}