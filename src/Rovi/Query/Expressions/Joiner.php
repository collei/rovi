<?php
namespace Rovi\Query\Expressions;

use Closure;
use InvalidArgumentException;
use Rovi\Connections\Connection;
use Rovi\Query\Builder;
use Rovi\Query\Keepers\BindingKeeper;
use Rovi\Query\Grammars\Grammar;

/**
 * Partial Builder for joins.
 */
class Joiner
{
    /**
     * @var \Rovi\Connections\Connection
     */
    protected $connection;

    /**
     * @var \Rovi\Query\Builder
     */
    protected $builderOwner;

    /**
     * @var \Rovi\Query\Keepers\BindingKeeper
     */
    protected $bindingKeeper;

    /**
     * @var array
     */
    protected $conditions = [];

    /**
     * Initializer.
     * 
     * @param \Rovi\Connections\Connection $connection
     * @param \Rovi\Query\Builder $builder
     */
    public function __construct(Connection $connection, Builder $owner)
    {
        $this->connection = $connection;
        $this->builderOwner = $owner;
        $this->bindingKeeper = BindingKeeper::instance($connection);
    }

    /**
     * For use of PHP funcions.
     * 
     * @return array
     */
    public function __debugInfo()
    {
        $formatter = function($object) {
            return get_class($object) . '@' . spl_object_id($object);
        };

        return [
            'connection' => $formatter($this->connection),
            'conditions' => $this->conditions,
        ];
    }

    /**
     * Retrieves the connection.
     * 
     * @return \Rovi\Connections\Connection
     */
    public final function getConnection()
    {
        return $this->connection;
    }

    /**
     * Retrieves the connection.
     * 
     * @return array
     */
    public final function conditions()
    {
        return $this->conditions;
    }

    /**
     * Retrieves a new Joiner.
     * 
     * @return self
     */
    protected final function createSub()
    {
        return new self($this->connection, $this->builderOwner);
    }

    /**
     * Process values accordingly.
     * 
     * @param mixed $value
     * @return mixed
     */
    protected final function processValue($value)
    {
        if ($value instanceof Closure) {
            $builder = $this->getConnection()->getBuilder();

            $value($builder);

            return $builder;
        }

        return $value;
    }

    /**
     * Adds a join condition.
     * 
     * @param string|\Closure $field
     * @param mixed $operator = null
     * @param mixed $value = null
     * @param string $and = 'and'
     * @return $this
     * @throws \InvalidArgumentException for invalid operator.
     */
    public function on($field, $operator = null, $value = null, string $and = 'and')
    {
        if ($field instanceof Closure) {
            return $this->addNestedOn($field, $and);
        }

        if (is_string($field)) {
            list($operator, $value) = $this->normalizeOperation($operator, $value);

            return $this->addOn($field, $operator, $value, $and);
        }

        throw new InvalidArgumentException(sprintf('Invalid argument type for where field: \'%s\'', gettype($field)));
    }

    /**
     * Adds a AND join condition.
     * 
     * @param string|\Closure $field
     * @param mixed $operator = null
     * @param mixed $value = null
     * @return $this
     */
    public function andOn($field, $operator = null, $value = null)
    {
        return $this->on($field, $operator, $value, 'and');
    }

    /**
     * Adds a OR join condition.
     * 
     * @param string|\Closure $field
     * @param mixed $operator = null
     * @param mixed $value = null
     * @return $this
     */
    public function orOn($field, $operator = null, $value = null)
    {
        return $this->on($field, $operator, $value, 'or');
    }

    /**
     * Normalizes the operator and value pair.
     * 
     * @param mixed $operator = null
     * @param mixed $value = null
     * @return array
     * @throws \InvalidArgumentException for invalid operator.
     */
    protected function normalizeOperation($operator = null, $value = null)
    {
        if (is_null($value)) {
            if (is_null($operator)) {
                return array('=', Expression::raw('NULL'));
            }

            return array('=', $operator);
        }

        if ($this->getConnection()->getGrammar()->isValidOperator($operator)) {
            return array($operator, $value);
        }

        throw new InvalidArgumentException(sprintf('Invalid operator: \'%s\'', $operator));
    }

    /**
     * Adds a plain join condition.
     * 
     * @param string $field
     * @param mixed $operator = null
     * @param mixed $value = null
     * @param string $and
     * @return $this
     */
    protected function addOn(string $field, $operator, $value, string $and)
    {
        $and = strtoupper($and);

        $value = $this->processValue($value);

        if ($value instanceof Builder) {
            $value = Expression::raw($value->asSql());
        } else {
            $value = $this->bindingKeeper->addBindings($value, 'join', $this->builderOwner->getBuilderID());
        }

        if (! empty($this->conditions)) {
            $this->conditions[] = $and;
        }

        $this->conditions[] = compact('field','operator','value');

        return $this;
    }

    /**
     * Adds a nested join condition.
     * 
     * @param Closure $closure
     * @param string $and
     * @return $this
     */
    protected function addNestedOn(Closure $closure, string $and)
    {
        $and = strtoupper($and);

        $closure($sub = $this->createSub());

        $nest = $sub->conditions();

        if (! empty($this->conditions)) {
            $this->conditions[] = $and;
        }

        $this->conditions[] = compact('nest');

        return $this;
    }
}