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
     * @var bool
     */
    private $queryCalled = false;

    /**
     * Retrieves the relation query.
     * 
     * @return Rovi\Query\Builder
     */
    public function query()
    {
        if ($this->queryCalled) {
            return $this;
        }

        $this->queryCalled = true;

        return $this->table($this->rightTable())
                    ->where($this->foreignKey(true), '=', $this->left()->getKey());
    }
}