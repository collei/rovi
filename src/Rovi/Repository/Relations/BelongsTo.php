<?php
namespace Rovi\Repository\Relations;

use InvalidArgumentException;
use LogicException;
use Rovi\Repository\Model;
use Rovi\Query\Builder;
use Rovi\Connections\Connection;
use Collei\Collections\Collection;

/**
 * Loader of model relationships.
 */
class BelongsTo extends Relation
{
    /**
     * Retrieves the relation query.
     * 
     * @return Rovi\Query\Builder
     */
    public function query()
    {
        return $this->table($this->rightTable())
                    ->where($this->foreignKey(true), '=', $this->left()->getKey());
    }
}