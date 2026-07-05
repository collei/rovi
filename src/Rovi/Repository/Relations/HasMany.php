<?php
namespace Rovi\Repository\Relations;

use InvalidArgumentException;
use LogicException;
use Rovi\Repository\Model;
use Rovi\Query\Builder;
use Rovi\Connections\Connection;
use Collei\Collections\Collection;

/**
 * One-to-many relationship.
 */
class HasMany extends Relation
{
    /**
     * Retrieves the relation query.
     * 
     * @return Rovi\Query\Builder
     */
    public function query()
    {
        return $this->getBuilder()
                    ->where($this->foreignKey(true), '=', $this->left()->getKey());
    }
}