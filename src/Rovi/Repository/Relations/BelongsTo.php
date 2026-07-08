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
     * @var bool
     */
    private $queryCalled = false;

    /**
     * Instantiator.
     * 
     * @param Rovi\Repository\Model $left
     * @param string $right
     * @param string|null $foreignKey
     * @param string|null $localKey
     * @throws InvalidArgumentException when the first or second arguments aren't classnames extending Model.
     * @throws LogicException when both models do not use the same database connection.
     */
    public function __construct(Model $left, string $rightClass, ?string $foreignKey = null, ?string $localKey = null)
    {
        parent::__construct($left, $rightClass);

        $this->localKey = $localKey ?: $this->rightClass(true, true).'_id';
    }

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

        $foreignName = $this->localKey();

        return $this->table($this->rightTable())
                    ->where($this->foreignKey(true), '=', $this->left()->$foreignName);
    }
}