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
abstract class Relation
{
    /**
     * @var string
     */
    protected $leftTable;

    /**
     * @var string
     */
    protected $rightTable;

    /**
     * @var string
     */
    protected $leftKey;

    /**
     * @var string
     */
    protected $rightKey;

    /**
     * @var Rovi\Connections\Connection
     */
    protected $connection;

    /**
     * Instantiator.
     * 
     * @param string $left
     * @param string $right
     * @param string|null $leftKey
     * @param string|null $rightKey
     * @throws InvalidArgumentException when the first or second arguments aren't classnames extending Model.
     * @throws LogicException when both models do not use the same database connection.
     */
    public function __construct(string $left, string $right, ?string $leftKey = null, ?string $rightKey = null)
    {
        if (! is_subclass_of($left, Model::class, true) || ! is_subclass_of($right, Model::class, true)) {
            throw new InvalidArgumentException('Both first and second arguments must be subclasses of Model');
        }

        list($left, $right) = array((new $left), (new $right));

        if ($left->getConnectionName() !== $right->getConnectionName()) {
            throw new LogicException('Both Model subclasses must use the same database connection');
        }

        $this->connection = $left->connection();

        list($this->leftTable, $this->rightTable) = array($left->getTable(), $right->getTable());

        $this->leftKey = $leftKey ?: $left->getKeyName();
        $this->rightKey = $rightKey ?: $right->getKeyName();
    }

    /**
     * Retrieves the relation connection.
     * 
     * @return Rovi\Connections\Connection
     */
    public final function connection()
    {
        return $this->connection;
    }

    /**
     * Retrieves the left table key.
     * 
     * @param bool $qualified = false
     * @return string
     */
    public final function getLeftKey(bool $qualified = false)
    {
        if ($qualified) {
            return $this->leftTable . '.' . $this->getLeftKey;
        }

        return $this->getLeftKey;
    }

    /**
     * Retrieves the right table key.
     * 
     * @param bool $qualified = false
     * @return string
     */
    public final function getRightKey(bool $qualified = false)
    {
        if ($qualified) {
            return $this->rightTable . '.' . $this->getRightKey;
        }

        return $this->getRightKey;
    }

    /**
     * Retrieves the relation results.
     * 
     * @return Rovi\Repository\Model|Collei\Collections\Collection|null
     */
    public abstract function get();
}