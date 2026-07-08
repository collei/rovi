<?php
namespace Rovi\Repository\Relations;

use InvalidArgumentException;
use LogicException;
use Rovi\Repository\Model;
use Rovi\Repository\Traits\BuilderTrait;
use Rovi\Query\Builder;
use Rovi\Connections\Connection;
use Collei\Collections\Collection;

/**
 * Loader of model relationships.
 */
abstract class Relation
{
    use BuilderTrait;

    /**
     * @var Rovi\Connections\Connection
     */
    private $connection;

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
    protected $foreignKey;

    /**
     * @var string
     */
    protected $localKey;

    /**
     * Instantiator.
     * 
     * @param Rovi\Repository\Model $left
     * @param string $right
     * @throws InvalidArgumentException when the first or second arguments aren't classnames extending Model.
     * @throws LogicException when both models do not use the same database connection.
     */
    public function __construct(Model $left, string $rightClass)
    {
        if (! is_subclass_of($rightClass, Model::class, true)) {
            throw new InvalidArgumentException('Both first and second arguments must be subclasses of Model');
        }

        $right = (new $rightClass);

        if ($left->getConnectionName() !== $right->getConnectionName()) {
            throw new LogicException('Both Model subclasses must use the same database connection');
        }

        $this->connection = $left->connection();

        $this->builder = new Builder($this->connection);

        list($this->left, $this->rightClass) = array($left, $this->modelClass = $rightClass);

        list($this->foreignKey, $this->localKey) = array($right->getKeyName(), $left->getKeyName());
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
     * @param bool $shortName = false
     * @param bool $lowercased = false
     * @return string
     */
    protected final function leftClass(bool $shortName = false, bool $lowercased = false)
    {
        if ($shortName) {
            $class = get_class($this->left);

            $shortClass = substr($class, strrpos($class, '\\'));

            return $lowercased ? strtolower($shortClass) : $shortClass;
        }

        return get_class($this->left);
    }

    /**
     * Retrieves the left table name.
     * 
     * @return string
     */
    protected final function leftTable()
    {
        return $this->left->getTable();
    }

    /**
     * Retrieves the right table name.
     * 
     * @param bool $shortName = false
     * @param bool $lowercased = false
     * @return string
     */
    protected final function rightClass(bool $shortName = false, bool $lowercased = false)
    {
        if ($shortName) {
            $shortClass = substr($this->rightClass, strrpos($this->rightClass, '\\'));

            return $lowercased ? strtolower($shortClass) : $shortClass;
        }

        return $this->rightClass;
    }

    /**
     * Retrieves the right table name.
     * 
     * @return string
     */
    protected final function rightTable()
    {
        $class = $this->rightClass;

        return (new $class)->getTable();
    }

    /**
     * Retrieves the left table key.
     * 
     * @param bool $qualified = false
     * @return string
     */
    protected function foreignKey(bool $qualified = false)
    {
        if ($qualified) {
            return $this->rightTable() . '.' . $this->foreignKey;
        }

        return $this->foreignKey;
    }

    /**
     * Retrieves the right table key.
     * 
     * @param bool $qualified = false
     * @return string
     */
    protected function localKey(bool $qualified = false)
    {
        if ($qualified) {
            return $this->leftTable() . '.' . $this->localKey;
        }

        return $this->localKey;
    }

    /**
     * Retrieves the relation query.
     * 
     * @return Rovi\Query\Builder
     */
    public abstract function query();

    /**
     * Retrieves the relation results.
     * 
     * @return Rovi\Repository\Model|Collei\Collections\Collection|null
     */
    public function get()
    {
        $model = $this->modelClass;
        
        $mapper = $model::getInstanceMapper();

        $result = $this->query()->getBuilder()->get();

        return ($this instanceof BelongsTo)
            ? $mapper($result->first()) 
            : ($result ? $result->map($mapper) : new Collection());
    }
}