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
class OneToOne extends Relation
{
    /**
     * Retrieves the relation result.
     * 
     * @return Rovi\Repository\Model|null
     */
    public function get()
    {
        $result = $this->connection()->getBuilder()
                    ->table($this->rightTable())
                    ->where($this->leftKey(true), '=', $this->left()->getKey())
                    ->get()->first();

        if (empty($result)) {
            return null;
        }

        $class = $this->leftClass();

        $mapper = $class::getInstanceMapper();

        return $mapper($result);
    }
}