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
class BelongsToMany extends Relation
{
    /**
     * @var bool
     */
    private $queryCalled = false;

    /**
     * @var string
     */
    private $joiner;

    /**
     * @var string
     */
    private $joinerLeft;

    /**
     * @var string
     */
    private $joinerRight;

    /**
     * Instantiator.
     * 
     * @param Rovi\Repository\Model $left
     * @param string $rightClass
     * @param string|null $joinerTable
     * @param string|null $leftKey
     * @param string|null $rightKey
     * @throws InvalidArgumentException when the first or second arguments aren't classnames extending Model.
     * @throws LogicException when both models do not use the same database connection.
     */
    public function __construct(Model $left, string $rightClass, ?string $joinerTable = null, ?string $leftKey = null, ?string $rightKey = null)
    {
        parent::__construct($left, $rightClass);

        list($tableLeft, $tableRight) = array_map('strtolower', [$this->leftClass(), $this->rightClass()]);

        $this->joiner = $joiner = (
            $joinerTable ?: (($tableLeft > $tableRight) ? "{$tableLeft}_{$tableRight}" : "{$tableRight}_{$tableLeft}")
        );

        $this->joinerLeft = $leftKey ?: "{$joiner}.{$tableLeft}_id";
        $this->joinerRight = $rightKey ?: "{$joiner}.{$tableRight}_id";
    }

    /**
     * Retrive the name of the internediate table.
     * 
     * @return string
     */
    protected function joiner()
    {
        return $this->joiner;
    }

    /**
     * Retrive the name of the left foreign key in the internediate table.
     * 
     * @return string
     */
    protected function joinerLeft()
    {
        return $this->joinerLeft;
    }

    /**
     * Retrive the name of the right foreign key in the internediate table.
     * 
     * @return string
     */
    protected function joinerRight()
    {
        return $this->joinerRight;
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

        return $this->table($this->rightTable())
                    ->join($this->joiner(), $this->foreignKey(true), '=', $this->joinerRight())
                    ->join($this->leftTable(), $this->localKey(true), '=', $this->joinerLeft())
                    ->select(Builder::raw($this->rightTable().'.*'))
                    ->where($this->joinerLeft(), '=', $this->left()->getKey());
    }
}