<?php
namespace Rovi\Query;

use Closure;
use InvalidArgumentException;
use Rovi\Connections\Connection;
use Rovi\Connections\Result;
use Rovi\Query\Keepers\BindingKeeper;
use Rovi\Query\Expressions\Expression;
use Rovi\Query\Expressions\Joiner;
use Collei\Collections\Collection;

/**
 * Query builder.
 */
class Builder
{
    /**
     * @var string
     */
    protected $builderID = null;

    /**
     * @var Rovi\Connections\Connection
     */
    protected $connection;

    /**
     * @var Rovi\Query\Keepers\BindingKeeper
     */
    protected $bindingKeeper;

    /**
     * @var array
     */
    protected $select = [];

    /**
     * @var array
     */
    protected $from = null;

    /**
     * @var array
     */
    protected $joins = [];

    /**
     * @var array
     */
    protected $where = [];

    /**
     * @var array
     */
    protected $groups = [];

    /**
     * @var array
     */
    protected $having = [];

    /**
     * @var array
     */
    protected $orders = [];

    /**
     * @var int
     */
    protected $offset = null;

    /**
     * @var int
     */
    protected $limit = null;

    /**
     * @var mixed
     */
    protected $lastError = null;

    /**
     * Instantiate.
     * 
     * @param Rovi\Connections\Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->builderID = bin2hex(random_bytes(4));
        $this->connection = $connection;
        $this->bindingKeeper = BindingKeeper::instance($connection);
    }

    /**
     * For internal use of PHP.
     * 
     * @return array
     */
    public function __debugInfo()
    {
        return [
            'builderID' => $this->builderID,
            'connection' => get_class($this->connection) . '@' . spl_object_id($this->connection),
            'bindingKeeper' => $this->bindingKeeper,
            'select' => empty($this->select) ? '*' : ($this->select ?: 'Array()'),
            'from' => empty($this->from) ? 'NULL' : $this->from,
            'joins' => $this->joins ?: 'Array()',
            'where' => $this->where ?: 'Array()',
            'groups' => $this->groups ?: 'Array()',
            'having' => $this->having ?: 'Array()',
            'orders' => $this->orders ?: 'Array()',
            'offset' => $this->offset ?? 'NULL',
            'limit' => $this->limit ?? 'NULL',
            'lastError' => $this->lastError ?? 'NULL',
        ];
    }

    /**
     * Shorthand of raw sql code.
     * 
     * @static
     * @param string $expression
     * @return Rovi\Query\Expressions\Expression
     */
    public static final function raw(string $expression)
    {
        return Expression::raw($expression);
    }

    /**
     * Create subquery builder.
     * 
     * @return self
     */
    protected final function createSub()
    {
        return new self($this->connection);
    }

    /**
     * Create join builder.
     * 
     * @return Rovi\Query\Expressions\Joiner
     */
    protected final function createJoiner()
    {
        return new Joiner($this->connection, $this);
    }

    /**
     * Return clause conditions. $clause may be either 'where' or 'having'.
     * 
     * @param string $clause
     * @return array
     */
    protected final function conditions(string $clause)
    {
        if (in_array($clause, ['where','having'])) {
            return $this->$clause;    
        }

        return [];
    }

    /**
     * Processes values.
     * 
     * @param mixed $value
     * @return array
     */
    protected final function processValue($value)
    {
        if ($value instanceof Closure) {
            $builder = $this->createSub();

            $value($builder);

            return $builder;
        }

        return $value;
    }

    /**
     * Return the connection.
     * 
     * @return Rovi\Connections\Connection
     */
    public final function getConnection()
    {
        return $this->connection;
    }

    /**
     * Return the bindings for the builder.
     * 
     * @return array
     */
    public final function getBindings()
    {
        return $this->bindingKeeper->getBindings($this->getBuilderID());
    }

    /**
     * Return the builder ID.
     * 
     * @return string
     */
    public final function getBuilderID()
    {
        return $this->builderID;
    }

    /**
     * Tells if any error occurred.
     * 
     * @return bool
     */
    public final function hasError()
    {
        return ! is_null($this->lastError);
    }

    /**
     * Return the Builder last error.
     * 
     * @return mixed
     */
    public final function lastError()
    {
        return $this->lastError;
    }

    /**
     * Define the fields to be selected.
     * 
     * @param string|array|Closure|self
     * @return $this
     */
    public function select(...$fields)
    {
        $argument = [];

        foreach ($fields as $field) {
            if (is_array($field)) {
                $argument = array_merge($argument, $field);
            } else {
                $argument[] = $field;
            }
        }

        $fields = [];
        
        foreach ($argument as $key => $item) {
            if ($item instanceof Closure) {
                $item($sub = $this->createSub());

                $item = $sub;
            }

            if ($item instanceof self) {
                $item = '(' . $item->asSql() . ')';
            }

            is_string($key)
                ? $fields[$key] = $item
                : $fields[] = $item;
        }

        $this->select = $fields;

        return $this;
    }

    /**
     * Define the table to be searched.
     * 
     * @param string $table
     * @param string $as = null
     * @return $this
     */
    public function table(string $table, ?string $as = null)
    {
        $this->from = empty($as) ? [$table] : [$as => $table];

        return $this;
    }

    /**
     * Alias of table().
     * 
     * @param string $table
     * @param string $as = null
     * @return $this
     */
    public function from(string $table, ?string $as = null)
    {
        return $this->table(...func_get_args());
    }

    /**
     * Define a subquery as the table to be searched.
     * 
     * @param Closure|self $table
     * @param string $as
     * @return $this
     */
    public function fromSub($table, string $as)
    {
        if (preg_match('/^[A-Za-z_]+/', $as) !== 1) {
            throw new InvalidArgumentException('Table alias is invalid');
        }

        if ($table instanceof Closure) {
            $table($sub = $this->createSub());

            $table = $sub;
        }

        if (! $table instanceof self) {
            throw new InvalidArgumentException('First parameter must be a Builder or a Closure');
        }

        $this->from = [$as => $table];

        return $this;
    }

    /**
     * Define a join to another table.
     * 
     * @param string $table
     * @param mixed $field
     * @param mixed $operator = null
     * @param mixed $value = null
     * @param string $type = 'inner'
     * @return $this
     */
    public function join(string $table, $field, $operator = null, $value = null, $type = 'inner')
    {
        if ($field instanceof Closure) {
            return $this->addJoinConditions($table, $field, $type);
        }

        if (is_string($field)) {
            list($operator, $value) = $this->normalizeOperation($operator, $value);

            return $this->addJoinCondition($table, $field, $operator, $value, $type);
        }

        throw new InvalidArgumentException(sprintf('Invalid argument type for join field: \'%s\'', gettype($field)));
    }

    /**
     * Define a join to another table.
     * 
     * @param Closure|self $table
     * @param string $as
     * @param Closure $field
     * @param mixed $operator = null
     * @param mixed $value = null
     * @param string $type = 'inner'
     * @return $this
     */
    public function joinSub($table, string $as, $field, $operator = null, $value = null, $type = 'inner')
    {
        if (preg_match('/^[A-Za-z_]+/', $as) !== 1) {
            throw new InvalidArgumentException('Table alias is invalid');
        }

        if ($table instanceof Closure) {
            $table($sub = $this->createSub());

            $table = $sub;
        }

        if (! $table instanceof self) {
            throw new InvalidArgumentException('First parameter must be a Builder or a Closure');
        }

        if ($field instanceof Closure) {
            return $this->addJoinConditions([$as => $table], $field, $type);
        }

        if (is_string($field)) {
            list($operator, $value) = $this->normalizeOperation($operator, $value);

            return $this->addJoinCondition([$as => $table], $field, $operator, $value, $type);
        }

        throw new InvalidArgumentException(sprintf('Invalid argument type for join field: \'%s\'', gettype($field)));
    }

    /**
     * Define an inner join to another table.
     * 
     * @param string $table
     * @param mixed $field
     * @param mixed $operator = null
     * @param mixed $value = null
     * @return $this
     */
    public function innerJoin($table, $field, $operator = null, $value = null)
    {
        return $this->join($table, $field, $operator, $value, 'inner');
    }

    /**
     * Define an outer join to another table.
     * 
     * @param string $table
     * @param mixed $field
     * @param mixed $operator = null
     * @param mixed $value = null
     * @return $this
     */
    public function outerJoin($table, $field, $operator = null, $value = null)
    {
        return $this->join($table, $field, $operator, $value, 'outer');
    }

    /**
     * Define a left join to another table.
     * 
     * @param string $table
     * @param mixed $field
     * @param mixed $operator = null
     * @param mixed $value = null
     * @return $this
     */
    public function leftJoin($table, $field, $operator = null, $value = null)
    {
        return $this->join($table, $field, $operator, $value, 'left');
    }

    /**
     * Define a right join to another table.
     * 
     * @param string $table
     * @param mixed $field
     * @param mixed $operator = null
     * @param mixed $value = null
     * @return $this
     */
    public function rightJoin($table, $field, $operator = null, $value = null)
    {
        return $this->join($table, $field, $operator, $value, 'right');
    }

    /**
     * Add a join condition.
     * 
     * @param array|string $table
     * @param mixed $field
     * @param mixed $operator
     * @param mixed $value
     * @param string $type = 'inner'
     * @param string $and = 'and' 
     * @return $this
     */
    protected function addJoinCondition($table, $field, $operator, $value, string $type = 'inner', string $and = 'and')
    {
        if (! is_array($table) && ! is_string($table)) {
            throw new InvalidArgumentException('First argument must be either an array or string');
        }

        list($alias, $source) = is_array($table)
            ? array(key($table), current($table))
            : array($table, $table);

        if (! array_key_exists($alias, $this->joins)) {
            $this->joins[$alias] = [];
        }

        $value = $this->processValue($value);

        if ($value instanceof Builder) {
            $value = Expression::raw($value->asSql());
        } else {
            $value = $this->bindingKeeper->addBindings($value, 'join', $this->builderID);
        }

        if (empty($this->joins[$alias])) {
            $this->joins[$alias]['source'] = $source;
            $this->joins[$alias]['type'] = $type;
            $this->joins[$alias]['conditons'] = [];
        }

        if (! empty($this->joins[$alias]['conditons'])) {
            $this->joins[$alias]['conditons'][] = $and;
        }

        $this->joins[$alias]['conditions'][] = compact('field','operator','value');

        return $this;
    }

    /**
     * Add several join conditions.
     * 
     * @param array|string $table
     * @param Closure $joinOns
     * @param string $type = 'inner'
     * @return $this
     */
    protected function addJoinConditions($table, Closure $joinOns, string $type = 'inner')
    {
        if (! is_array($table) && ! is_string($table)) {
            throw new InvalidArgumentException('First argument must be either an array or string');
        }

        list($alias, $source) = is_array($table)
            ? array(key($table), current($table))
            : array($table, $table);

        $type = strtolower($type);

        $type = ($type === 'left') ? 'left' : (($type === 'right') ? 'right' : 'inner');

        $joinOns($ons = $this->createJoiner());

        $conditions = $ons->conditions();

        $this->joins[$alias] = compact('source','type','conditions');

        return $this;
    }

    /**
     * Add a where clause.
     * 
     * @param string|Closure|Expression $field
     * @param mixed $operator = null
     * @param mixed $value = null
     * @param string $and = 'and'
     * @return $this
     */
    public function where($field, $operator = null, $value = null, string $and = 'and')
    {
        $and = strtolower($and) === 'or' ? 'or' : 'and';

        if ($field instanceof Closure) {
            return $this->addNestedCondition('where', $field, $and);
        }

        if (is_string($field) || $field instanceof Expression) {
            list($operator, $value) = $this->normalizeOperation($operator, $value);

            return $this->addCondition('where', $field, $operator, $value, $and);
        }

        throw new InvalidArgumentException(sprintf('Invalid argument type for where field: \'%s\'', gettype($field)));
    }

    /**
     * Add a and-where clause.
     * 
     * @param string|Closure|Expression $field
     * @param mixed $operator = null
     * @param mixed $value = null
     * @return $this
     */
    public function andWhere($field, $operator = null, $value = null)
    {
        return $this->where($field, $operator, $value, 'and');
    }

    /**
     * Add a or-where clause.
     * 
     * @param string|Closure|Expression $field
     * @param mixed $operator = null
     * @param mixed $value = null
     * @return $this
     */
    public function orWhere($field, $operator = null, $value = null)
    {
        return $this->where($field, $operator, $value, 'or');
    }

    /**
     * Add a where between clause.
     * 
     * @param string|Closure|Expression $field
     * @param array $range = null
     * @param string $and = 'and'
     * @return $this
     */
    public function whereBetween($field, array $range, string $and = 'and')
    {
        if (count($array) !== 2) {
            throw new InvalidArgumentException('second argument must be an array with exactly 2 elements');
        }

        return $this->where($field, 'between', $range, $and);
    }

    /**
     * Add an and-where between clause.
     * 
     * @param string|Closure|Expression $field
     * @param array $range = null
     * @return $this
     */
    public function andWhereBetween($field, array $range)
    {
        return $this->whereBetween($field, $range, 'and');
    }

    /**
     * Add an or-where between clause.
     * 
     * @param string|Closure|Expression $field
     * @param array $range = null
     * @return $this
     */
    public function orWhereBetween($field, array $range)
    {
        return $this->whereBetween($field, $range, 'or');
    }

    /**
     * Add a where not between clause.
     * 
     * @param string|Closure|Expression $field
     * @param array $range = null
     * @param string $and = 'and'
     * @return $this
     */
    public function whereNotBetween($field, array $range, string $and = 'and')
    {
        if (count($array) !== 2) {
            throw new InvalidArgumentException('second argument must be an array with exactly 2 elements');
        }

        return $this->where($field, 'not between', $range, $and);
    }

    /**
     * Add an and-where not between clause.
     * 
     * @param string|Closure|Expression $field
     * @param array $range = null
     * @return $this
     */
    public function andWhereNotBetween($field, array $range)
    {
        return $this->whereNotBetween($field, $range, 'and');
    }

    /**
     * Add an or-where not between clause.
     * 
     * @param string|Closure|Expression $field
     * @param array $range = null
     * @return $this
     */
    public function orWhereNotBetween($field, array $range)
    {
        return $this->whereNotBetween($field, $range, 'or');
    }

    /**
     * Add a where in clause.
     * 
     * @param string|Closure|Expression $field
     * @param array|Closure|Builder $list
     * @param string $and = 'and'
     * @return $this
     */
    public function whereIn($field, $list, string $and = 'and')
    {
        if ((! $list instanceof Closure) && (! $list instanceof self) && (! is_array($list))) {
            throw new InvalidArgumentException('argument must be either an array, a Closure or a Builder');
        }

        return $this->where($field, 'in', $list, $and);
    }

    /**
     * Add an and-where in clause.
     * 
     * @param string|Closure|Expression $field
     * @param array|Closure|Builder $list
     * @return $this
     */
    public function andWhereIn($field, $list)
    {
        return $this->whereIn($field, $list, 'and');
    }

    /**
     * Add an or-where in clause.
     * 
     * @param string|Closure|Expression $field
     * @param array|Closure|Builder $list
     * @return $this
     */
    public function orWhereIn($field, $list)
    {
        return $this->whereIn($field, $list, 'or');
    }

    /**
     * Add a where not in clause.
     * 
     * @param string|Closure|Expression $field
     * @param array|Closure|Builder $list
     * @param string $and = 'and'
     * @return $this
     */
    public function whereNotIn($field, $list, string $and = 'and')
    {
        if ((! $list instanceof Closure) && (! $list instanceof self) && (! is_array($list))) {
            throw new InvalidArgumentException('argument must be either an array, a Closure or a Builder');
        }

        return $this->where($field, 'not in', $list, $and);
    }

    /**
     * Add an and-where not in clause.
     * 
     * @param string|Closure|Expression $field
     * @param array|Closure|Builder $list
     * @return $this
     */
    public function andWhereNotIn($field, $list)
    {
        return $this->whereNotIn($field, $list, 'and');
    }

    /**
     * Add an or-where not in clause.
     * 
     * @param string|Closure|Expression $field
     * @param array|Closure|Builder $list
     * @return $this
     */
    public function orWhereNotIn($field, $list)
    {
        return $this->whereNotIn($field, $list, 'or');
    }

    /**
     * Add a where exists clause.
     * 
     * @param Closure|self $callback
     * @param string $and = 'and'
     * @return $this
     */
    public function whereExists($subquery, string $and = 'and')
    {
        if ((! $subquery instanceof Closure) && (! $subquery instanceof self)) {
            throw new InvalidArgumentException('argument must be either a Closure or a Builder');
        }

        if ($subquery instanceof Closure) {
            $subquery($sub = $this->createSub());
            
            $subquery = $sub;
        }

        return $this->where(self::raw(''), 'exists', $subquery, $and);
    }

    /**
     * Add an and-where exists clause.
     * 
     * @param Closure|self $callback
     * @return $this
     */
    public function andWhereExists($subquery)
    {
        return $this->whereExists($subquery, 'and');
    }

    /**
     * Add an or-where exists clause.
     * 
     * @param Closure|self $callback
     * @return $this
     */
    public function orWhereExists($subquery)
    {
        return $this->whereExists($subquery, 'or');
    }

    /**
     * Add a where not exists clause.
     * 
     * @param Closure|self $callback
     * @param string $and = 'and'
     * @return $this
     */
    public function whereNotExists($subquery, string $and = 'and')
    {
        if ((! $subquery instanceof Closure) && (! $subquery instanceof self)) {
            throw new InvalidArgumentException('argument must be either a Closure or a Builder');
        }

        if ($subquery instanceof Closure) {
            $subquery($sub = $this->createSub());
            
            $subquery = $sub;
        }

        return $this->where(self::raw(''), 'not exists', $subquery, $and);
    }

    /**
     * Add an and-where not exists clause.
     * 
     * @param Closure|self $callback
     * @return $this
     */
    public function andWhereNotExists($subquery)
    {
        return $this->whereNotExists($subquery, 'and');
    }

    /**
     * Add an or-where not exists clause.
     * 
     * @param Closure|self $callback
     * @return $this
     */
    public function orWhereNotExists($subquery)
    {
        return $this->whereNotExists($subquery, 'or');
    }

    /**
     * Helper to flexibilize operator omission.
     * 
     * @param mixed $operator = null
     * @param mixed $value = null
     * @return array 
     */
    protected function normalizeOperation($operator = null, $value = null)
    {
        if (is_null($value)) {
            if (is_null($operator)) {
                return array('=', Expression::from('NULL'));
            }

            return array('=', $operator);
        }

        if ($this->getConnection()->getGrammar()->isValidOperator($operator)) {
            return array($operator, $value);
        }

        throw new InvalidArgumentException(sprintf('Invalid operator: \'%s\'', $operator));
    }

    /**
     * Add a condition into a given clause.
     * 
     * @param string $clause
     * @param string $field
     * @param mixed $operator
     * @param mixed $value
     * @param string $and
     * @return $this
     */
    protected function addCondition(string $clause, string $field, $operator, $value, string $and)
    {
        list($type, $and) = array('plain', strtoupper($and));

        $value = $this->processValue($value);

        $value = $this->bindingKeeper->addBindings($value, $clause, $this->builderID);

        $arguments = compact('field','operator','value');

        if (! empty($this->{$clause})) {
            $this->{$clause}[] = $and;
        }

        $this->{$clause}[] = compact('field','operator','value');

        return $this;
    }

    /**
     * Add a nesting condition into a given clause.
     * 
     * @param string $clause
     * @param Closure $subquery
     * @param string $and
     * @return $this
     */
    protected function addNestedCondition(string $clause, Closure $subquery, string $and)
    {
        list($type, $and) = array('nested', strtoupper($and));

        $subquery($sub = $this->createSub());

        $nest = $sub->conditions($clause);

        if (! empty($this->{$clause})) {
            $this->{$clause}[] = $and;
        }

        $this->{$clause}[] = compact('nest');

        return $this;
    }

    /**
     * Adds a group by instruction.
     * 
     * @param mixed ...$groups
     * @return $this
     */
    public function groupBy(...$groups)
    {
        foreach ($groups as $group) {
            if (is_string($group) || $group instanceof Expression) {
                $this->groups[] = $group;

                continue;
            }

            if (is_array($groups)) foreach ($groups as $group) {
                $this->groupBy($group);
            }
        }

        return $this;
    }

    /**
     * Add a having condition.
     * 
     * @param string|Closure|Expression $field
     * @param mixed $operator = null
     * @param mixed $value = null
     * @param string $and = 'and'
     * @return $this
     */
    public function having($field, $operator = null, $value = null, string $and = 'and')
    {
        $and = strtolower($and) === 'or' ? 'or' : 'and';

        if ($field instanceof Closure) {
            return $this->addNestedCondition('having', $field, $and);
        }

        if (is_string($field) || $field instanceof Expression) {
            list($operator, $value) = $this->normalizeOperation($operator, $value);

            return $this->addCondition('having', $field, $operator, $value, $and);
        }

        throw new InvalidArgumentException(sprintf('Invalid argument type for where field: \'%s\'', gettype($field)));
    }

    /**
     * Add a and-having condition.
     * 
     * @param string|Closure|Expression $field
     * @param mixed $operator = null
     * @param mixed $value = null
     * @return $this
     */
    public function andHaving($field, $operator = null, $value = null)
    {
        return $this->having($field, $operator, $value, 'and');
    }

    /**
     * Add a or-having condition.
     * 
     * @param string|Closure|Expression $field
     * @param mixed $operator = null
     * @param mixed $value = null
     * @return $this
     */
    public function orHaving($field, $operator = null, $value = null)
    {
        return $this->having($field, $operator, $value, 'or');
    }

    /**
     * Orders a query by a given field.
     * 
     * @param string|array|Expression $order
     * @param bool $asc = true
     * @return $this
     */
    public function orderBy($order, bool $asc = true)
    {
        if (is_string($order) || $order instanceof Expression) {
            list($field, $asc) = array($order, ($asc ? 'ASC' : 'DESC')); 

            $this->orders[] = [$asc => $field];
        }

        if (is_array($order)) {
            foreach ($order as $field => $type) {
                if (is_string($field)) {
                    $type = is_bool($type) ? $type : (is_string($type) ? strtolower($type) === 'asc' : true);
                } else {
                    list($field, $type) = array($type, true);
                }

                $this->orderBy($field, $type);
            }
        }

        return $this;
    }

    /**
     * Removes the order by clause from the current query.
     * 
     * @return $this
     */
    public function reorder()
    {
        $this->orders = [];

        return $this;
    }

    /**
     * Adds a offset instruction.
     * 
     * @param int $offset
     * @return $this
     */
    public function offset(int $offset)
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * Removes the offset instruction from the query.
     * 
     * @return $this
     */
    public function removeOffset()
    {
        $this->offset = null;

        return $this;
    }

    /**
     * Adds a limit instruction.
     * 
     * @param int $limit
     * @return $this
     */
    public function limit(int $limit)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Removes the limit instruction from the query.
     * 
     * @return $this
     */
    public function removeLimit()
    {
        $this->limit = null;

        return $this;
    }

    /**
     * Returns the query as select SQL string.
     * 
     * @return string
     */
    public function asSql()
    {
        return $this->compileSelectSql();
    }

    /**
     * Performs the query and retrieve results.
     * 
     * @param string ...$fields
     * @return Collei\Collections\Collection|array|false
     */
    public function get(string ...$fields)
    {
        list($sql, $bindings) = array('', []);

        if ($fields) {
            $this->select(...$fields);
        }

        if ($this->makeSelectSql($sql, $bindings)) {
            if (false !== ($result = $this->connection->select($sql, $bindings, $errors))) {
                if (class_exists(Collection::class, true)) {
                    return new Collection(json_decode(json_encode($result)));
                }

                return json_decode(json_encode($result));
            }

            return $this->setLastCustomError($errors);
        }

        return $this->setLastError('malformed SQL', $sql);
    }

    /**
     * Performs the query and retrieve results.
     * 
     * @param string ...$fields
     * @return array
     */
    public function getAsArray(string ...$fields)
    {
        list($sql, $bindings) = array('', []);

        if ($fields) {
            $this->select(...$fields);
        }

        if ($this->makeSelectSql($sql, $bindings)) {
            if (false !== ($result = $this->connection->select($sql, $bindings, $errors))) {
                return json_decode(json_encode($result), true, 512, JSON_OBJECT_AS_ARRAY);
            }
        }

        return [];
    }

    /**
     * Performs a insert values query.
     * 
     * @param array $values
     * @param ?array $output = null
     * @return Rovi\Connections\Result|false
     */
    public function insert(array $values, ?array $output = null)
    {
        list($sql, $bindings) = array('', []);

        if ($this->makeInsertSql($values, $output, $sql, $bindings)) {
            if (false !== ($result = $this->connection->insert($sql, $bindings, $errors))) {
                return $result;
            }

            return $this->setLastCustomError($errors);
        }

        return $this->setLastError('malformed SQL', $sql);
    }

    /**
     * Performs a insert select query.
     * 
     * @param array $fields
     * @param Rovi\Query\Builder $values
     * @return Rovi\Connections\Result|false
     */
    public function insertSelect(array $fields, Builder $values)
    {
        list($sql, $bindings) = array('', []);

        if ($this->makeInsertSelectSql($fields, $sql, $values)) {
            $bindings = $this->bindingKeeper->getBindingsFor($sql);

            if (false !== ($result = $this->connection->insert($sql, $bindings, $errors))) {
                return $result;
            }

            return $this->setLastCustomError($errors);
        }

        return $this->setLastError('malformed SQL', $sql);
    }

    /**
     * Performs a update query.
     * 
     * @param array $values
     * @return Rovi\Connections\Result|false
     */
    public function update(array $values)
    {
        list($sql, $bindings) = array('', []);

        if ($this->makeUpdateSql($values, $bindings, $sql)) {
            $sqlBindings = $this->bindingKeeper->getBindingsFor($sql);

            $bindings += $sqlBindings;

            if (false !== ($result = $this->connection->update($sql, $bindings, $errors))) {
                return $result;
            }

            return $this->setLastCustomError($errors);
        }

        return $this->setLastError('malformed SQL', $sql);
    }

    /**
     * Performs a delete query.
     * 
     * @return Rovi\Connections\Result|false
     */
    public function delete()
    {
        list($sql, $bindings) = array('', []);
        
        if ($this->makeDeleteSql($sql)) {
            $bindings = $this->bindingKeeper->getBindingsFor($sql);

            if (false !== ($result = $this->connection->delete($sql, $bindings, $errors))) {
                return $result;
            }

            $this->lastError = $errors;

            return false;
        }

        return $this->setLastError('malformed SQL', $sql);
    }

    /**
     * Set lastError properties and returns false. For internal use.
     * 
     * @param string $error
     * @param string $sql
     * @param mixed ...$context
     * @return false
     */
    protected function setLastError(string $error, string $sql, ...$context)
    {
        $this->lastError = (object) compact('error','sql','context');

        return false;
    }

    /**
     * Set a custom lastError and returns false. For internal use.
     * 
     * @param mixed $error
     * @return false
     */
    protected function setLastCustomError($error)
    {
        $this->lastError = $error;

        return false;
    }

    /**
     * Returns the select sql code and bindings through parameters.
     * 
     * @param ?string &$sqlCode = null
     * @param ?array $bindings = []
     * @return bool
     */
    protected function makeSelectSql(?string &$sqlCode = null, ?array &$bindings = [])
    {
        try {
            $sqlCode = $this->compileSelectSql();
            
            $bindings = $this->bindingKeeper->getBindingsFor($sqlCode);
        } catch (Throwable $e) {
            return false;
        }

        return true;
    }

    /**
     * Returns the insert values sql code and bindings through parameters.
     * 
     * @param array $values
     * @param ?array $output = null
     * @param ?string &$sql = null
     * @param ?array $bindings = []
     * @return bool
     */
    protected function makeInsertSql(array $values, ?array $output = null, ?string &$sql = null, ?array &$bindings = [])
    {
        if (false === ($sqlCode = $this->compileInsertSql($values, [], $output, $bindings, $error))) {
            $sql = '--'.$error;

            return false;
        }

        $sql = $sqlCode;

        return true;
    }

    /**
     * Returns the insert-select sql code and bindings through parameters.
     * 
     * @param array $fields
     * @param ?string &$sql = null
     * @param Closure|Builder $values
     * @return bool
     */
    protected function makeInsertSelectSql(array $fields, ?string &$sql = null, $values)
    {
        if (! $values instanceof Closure && ! $values instanceof self) {
            $sql = '--The asInsertSelectSql() method supports only Closure and Builder instances.';

            return false;
        }

        if (false === ($sqlCode = $this->compileInsertSql($values, $fields, null, $bindings, $error))) {
            $sql = '--'.$error;

            return false;
        }

        $sql = $sqlCode;

        return true;
    }

    /**
     * Returns the update sql code and bindings through parameters.
     * 
     * @param array $values
     * @param ?array $bindings = []
     * @param ?string &$sql = null
     * @return bool
     */
    protected function makeUpdateSql(array $values, ?array &$bindings = [], ?string &$sql = null)
    {
        if (false === ($sqlCode = $this->compileUpdateSql($values, $bindings, $error))) {
            $sql = '--'.$error;

            return false;
        }

        $sql = $sqlCode;

        return true;
    }

    /**
     * Returns the delete sql code through parameters.
     * 
     * @param ?string &$sql = null
     * @return bool
     */
    protected function makeDeleteSql(?string &$sql = null)
    {
        if (false === ($sqlCode = $this->compileDeleteSql($error))) {
            $sql = '--'.$error;

            return false;
        }

        $sql = $sqlCode;

        return true;
    }

    /**
     * Compiles the query into a select sql.
     * 
     * @return string
     */
    protected function compileSelectSql()
    {
        $compiler = $this->getConnection()->getGrammar();

        $select = $compiler->compileSelectClause($this->select);

        $from = 'NOTHING';

        if (! empty($this->from)) {
            list($fromTable, $fromAs) = array(current($this->from), key($this->from));

            $nesting = ($fromTable instanceof self);
            $fromTable = $nesting ? $fromTable->asSql() : $fromTable;
            $fromAs = is_string($fromAs) ? $fromAs : null;

            $from = $compiler->compileFromClause($fromTable, $fromAs, $nesting);
        }

        $joins = [];

        foreach ($this->joins as $alias => $join) {
            $table = $join['source'];

            if ($nesting = ($table instanceof self)) {
                $table = $table->asSql();
            } else {
                $alias = null;
            }
                
            $type = $join['type'];
           
            $joins[] = $compiler->compileJoin($type, $table, $alias, $join['conditions'], $nesting);
        }

        $sql = $compiler->compileStatementSelect(
            $select, $from, $joins,
            $this->where, $this->groups, $this->having, $this->orders,
            $this->offset, $this->limit 
        );

        return $sql;
    }

    /**
     * Compiles the query into a insert sql.
     * 
     * @param array|Closure|Builder $values
     * @param array $fields = []
     * @param ?array $output = null
     * @param ?array &$bindings = []
     * @param ?string &$error = ''
     * @return string|null
     */
    protected function compileInsertSql($values, array $fields = [], ?array $output = null, ?array &$bindings = [], ?string &$error = '')
    {
        list($error, $bindingCount) = array('', 1);

        $compiler = $this->getConnection()->getGrammar();

        $from = 'NOTHING';

        if (empty($this->from)) {
            $error = 'INSERT INTO requires a table to operate upon - use from() method.';

            return false;
        } else {
            list($from, $as) = array(current($this->from), key($this->from));

            if (! is_string($from)) {
                $error = 'INSERT INTO does not support CTE (subquery) as target, only actual tables.';

                return false;
            }
        }

        if (is_array($values)) {
            if (! is_array(current($values))) {
                $values = array($values);
            }

            $fields = null;

            foreach ($values as $k => $row) {
                ksort($values[$k]);

                if (is_null($fields)) {
                    $fields = array_keys($values[$k]);
                }

                foreach ($row as $m => $cell) {
                    $binder = ':i' . ($bindingCount++);

                    $bindings[$binder] = $cell;

                    $values[$k][$m] = $binder;
                }               
            }

            return $compiler->compileStatementInsertValues($from, $fields, $values, $output);
        }

        if ($values instanceof Closure) {
            $values($sub = $this->createSub());

            $values = $sub;
        }

        if ($values instanceof self) {
            return $compiler->compileStatementInsertSelect($from, $fields, $values->asSql(), $output);
        }

        return null;
    }

    /**
     * Compiles the query into a update sql.
     * 
     * @param array $values
     * @param ?array &$bindings = []
     * @param ?string &$error = ''
     * @return string
     */
    protected function compileUpdateSql(array $values, ?array &$bindings = [], ?string &$error = '')
    {
        list($error, $bindingCount) = array('', 1);

        $compiler = $this->getConnection()->getGrammar();

        $from = 'NOTHING';

        if (empty($this->from)) {
            $error = 'Update requires a table to operate upon - use from() method.';

            return false;
        } else {
            list($from, $as) = array(current($this->from), key($this->from));

            if (! is_string($from)) {
                $error = 'Update does not support CTE (subquery), only actual tables.';

                return false;
            }
        }

        $setList = [];

        foreach ($values as $field => $value) {
            if ($value instanceof Closure) {
                $value($sub = $this->createSub());

                $value = $sub;
            }

            if ($nesting = $value instanceof self) {
                $value = $value->asSql();
            } elseif ($value instanceof Expression) {
                $value = $value->toString();
            } else {
                $binder = ':u' . ($bindingCount++);

                $bindings[$binder] = $value;

                $value = $binder;
            }

            $setList[] = $compiler->compileSet($field, $value, $nesting);
        }

        $sql = $compiler->compileStatementUpdate($from, $setList, $this->where);

        return $sql;
    }

    /**
     * Compiles the query into a delete sql.
     * 
     * @param ?string &$error = ''
     * @return string
     */
    protected function compileDeleteSql(?string &$error = '')
    {
        $error = '';

        $compiler = $this->getConnection()->getGrammar();

        $from = 'NOTHING';

        if (empty($this->from)) {
            $error = 'DELETE requires a table to operate upon - use from() method.';

            return false;
        } else {
            list($from, $as) = array(current($this->from), key($this->from));

            if (! is_string($from)) {
                $error = 'DELETE does not support CTE (subquery), only actual tables.';

                return false;
            }
        }

        $sql = $compiler->compileStatementDelete($from, $this->where);

        return $sql;
    }
}