<?php
namespace Rovi\Query\Keepers;

use Closure;
use InvalidArgumentException;
use Rovi\Connections\Connection;
use Rovi\Query\Builder;
use Rovi\Query\Expressions\Expression;
use Rovi\Query\Expressions\Joiner;

/**
 * Keeps tracking of SQL values binding.
 */
final class BindingKeeper
{
    /**
     * @var array
     */
    private const BINDING_CLAUSES = [
        's' => 'select',
        'j' => 'join',
        'w' => 'where',
        'h' => 'having'
    ];

    /**
     * @var string
     */
    private const REGEX_BINDING_ITEM = '/(?>:[sjwh]\d+)/';

    /**
     * @var self
     */
    private static $instance = null;

    /**
     * @var \Rovi\Connections\Connection
     */
    private $connection;

    /**
     * @var array
     */
    private $bindings = [
        'select' => [],
        'join' => [],
        'where' => [],
        'having' => [],
    ];

    /**
     * @var array
     */
    private $bindingCounters = [
        'select' => 0,
        'join' => 0,
        'where' => 0,
        'having' => 0,
    ];

    /**
     * Private Initializer.
     * 
     * @param \Rovi\Connections\Connection $connection
     */
    private function __construct(Connection $connection)
    {
        $this->connection = $connection;

        static::$instance = $this;
    }

    /**
     * For use of PHP funcions.
     * 
     * @return array
     */
    public function __debugInfo()
    {
        return [
            'select' => $this->bindings['select'] ?: 'Array()',
            'join' => $this->bindings['join'] ?: 'Array()',
            'where' => $this->bindings['where'] ?: 'Array()',
            'having' => $this->bindings['having'] ?: 'Array()',
        ];
    }

    /**
     * Retrieves the unique instance.
     * 
     * @param \Rovi\Connections\Connection $connection
     * @return self
     */
    public static function instance(Connection $connection)
    {
        if (is_null(static::$instance)) {
            return static::$instance = new static($connection);
        }

        return static::$instance;
    }

    /**
     * Retrieves the bindings.
     * 
     * @param ?string $owner = null
     * @return array
     */
    public function getBindings(?string $owner = null)
    {
        $everything = [];

        if (! empty($owner)) {
            foreach (self::BINDING_CLAUSES as $clause) {
                $everything = array_merge($everything, $this->bindings[$clause][$owner]);
            }
        } else {
            foreach (self::BINDING_CLAUSES as $clause) {
                foreach ($this->bindings[$clause] as $key => $clauseBindings) {
                    $everything = array_merge($everything, $clauseBindings);
                }
            }
        }

        return $everything;
    }

    /**
     * Retrieves the bindings for the clause.
     * 
     * @param string $clause
     * @param ?string $owner = null
     * @return array
     */
    public function getClauseBindings(string $clause, ?string $owner = null)
    {
        if (! empty($owner)) {
            if (array_key_exists($owner, $this->bindings[$clause])) {
                return $this->bindings[$clause][$owner];
            }
        } 

        $bindings = [];

        foreach ($this->bindings[$clause] as $key => $clauseBindings) {
            $bindings = array_merge($bindings, $clauseBindings);
        }

        return $bindings;
    }

    /**
     * Retrieves the bindings for the given $sql, if needed.
     * 
     * @param string $sql
     * @return array
     */
    public function getBindingsFor(string $sql)
    {
        if (preg_match_all(self::REGEX_BINDING_ITEM, $sql, $results) > 0) {
            $gathered = [];

            foreach ($results[0] as $binder) {
                $clause = self::BINDING_CLAUSES[substr($binder,1,1)];

                foreach ($this->bindings[$clause] as $owner => $ownerBindings) {
                    if (array_key_exists($binder, $ownerBindings)) {
                        $gathered[$binder] = $ownerBindings[$binder];

                        break;
                    }
                }
            }

            return $gathered;
        }

        return [];
    }

    /**
     * Adds bindings for $value by $owner in the given $clause.
     * 
     * @param mixed $value
     * @param string $clause
     * @param string $owner
     * @return mixed
     */
    public function addBindings($value, string $clause, string $owner)
    {
        if ($value instanceof Builder) {
            return $value->asSql();
        }

        if (is_array($value)) {
            $binders = [];

            foreach ($value as $item) {
                $binders[] = $this->addBindings($item, $clause, $owner);    
            }

            return $binders;
        }

        if (is_string($value) && ('join' == $clause) && $this->connection->getGrammar()->validateSqlIdentifier($value)) {
            return $value;
        }

        $binder = $this->getNextBindingVariable($clause);

        if (! isset($this->bindings[$clause][$owner])) {
            $this->bindings[$clause][$owner] = [];
        }

        $this->bindings[$clause][$owner][$binder] = $value;

        return $binder;
    }

    /**
     * Retrieves the next binding variable for the given $clause.
     * 
     * @param string $clause
     * @return string
     */
    protected function getNextBindingVariable(string $clause)
    {
        if (! array_key_exists($clause, $this->bindingCounters)) {
            return null;
        }

        return ':' . strtolower(substr($clause, 0, 1)) . (++$this->bindingCounters[$clause]);
    }
}