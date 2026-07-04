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
     * @var Rovi\Repository\Model
     */
    private $left;

    /**
     * @var string
     */
    private $rightClass;

    /**
     * @var string
     */
    private $foreignKey;

    /**
     * @var string
     */
    private $localKey;

    /**
     * @var Rovi\Connections\Connection
     */
    private $connection;

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
        if (! is_subclass_of($rightClass, Model::class, true)) {
            throw new InvalidArgumentException('Both first and second arguments must be subclasses of Model');
        }

        $right = (new $rightClass);

        if ($left->getConnectionName() !== $right->getConnectionName()) {
            throw new LogicException('Both Model subclasses must use the same database connection');
        }

        $this->connection = $left->connection();

        $this->left = $left;
        
        $this->rightClass = $rightClass;

        $this->foreignKey = $foreignKey ?: $right->getKeyName();
        $this->localKey = $localKey ?: $left->getKeyName();
    }

    /**
     * Retrieves the relation connection.
     * 
     * @return Rovi\Connections\Connection
     */
    protected final function connection()
    {
        return $this->connection;
    }

    /**
     * Retrieves the left model instance.
     * 
     * @return Rovi\Repository\Model
     */
    protected final function left()
    {
        return $this->left;
    }

    /**
     * Retrieves the left model class name.
     * 
     * @return string
     */
    public final function leftClass()
    {
        return get_class($this->left);
    }

    /**
     * Retrieves the left table name.
     * 
     * @return string
     */
    public final function leftTable()
    {
        return $this->left->getTable();
    }

    /**
     * Retrieves the right table name.
     * 
     * @return string
     */
    public final function rightTable()
    {
        return $this->rightTable;
    }

    /**
     * Retrieves the left table key.
     * 
     * @param bool $qualified = false
     * @return string
     */
    public final function leftKey(bool $qualified = false)
    {
        if ($qualified) {
            return $this->leftTable() . '.' . $this->leftKey;
        }

        return $this->leftKey;
    }

    /**
     * Retrieves the right table key.
     * 
     * @param bool $qualified = false
     * @return string
     */
    public final function rightKey(bool $qualified = false)
    {
        if ($qualified) {
            return $this->rightTable() . '.' . $this->rightKey;
        }

        return $this->rightKey;
    }

    /**
     * Retrieves the relation results.
     * 
     * @return Rovi\Repository\Model|Collei\Collections\Collection|null
     */
    public abstract function get();
}