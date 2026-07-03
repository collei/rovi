<?php
namespace Rovi\Repository;

use Closure;
use LogicException;
use InvalidArgumentException;
use Rovi\Query\Builder as QueryBuilder;
use Rovi\Connections\Connection;

/**
 * Model query builder.
 */
class Builder
{
    /**
     * @var array<string>
     */
    protected const CHAINING_PASSTHROUGH = [
        'join',
        'joinSub',
        'innerJoin',
        'outerJoin',
        'leftJoin',
        'rightJoin',
        'where',
        'andWhere',
        'orWhere',
        'whereBetween',
        'andWhereBetween',
        'orWhereBetween',
        'whereNotBetween',
        'andWhereNotBetween',
        'orWhereNotBetween',
        'whereIn',
        'andWhereIn',
        'orWhereIn',
        'whereNotIn',
        'andWhereNotIn',
        'orWhereNotIn',
        'whereExists',
        'andWhereExists',
        'orWhereExists',
        'whereNotExists',
        'andWhereNotExists',
        'orWhereNotExists',
        'orderBy',
        'reorder',
        'offset',
        'removeOffset',
        'limit',
        'removeLimit',
    ];

    /**
     * @var array<string>
     */
    protected const VALUED_PASSTHROUGH = [
        'asSql',
        'update',
        'delete',
    ];

    /**
     * @var Rovi\Query\Builder
     */
    protected $builder;

    /**
     * @var string
     */
    protected $modelClass;

    /**
     * Instantiate it.
     * 
     * @param string $modelClass
     * @param Rovi\Connections\Connection
     */
    public function __construct(string $modelClass, Connection $connection)
    {
        $this->builder = new QueryBuilder($connection);
        $this->modelClass = $modelClass;
    }

    /**
     * Forwards calls to the query builder.
     * 
     * @param string $method
     * @param array $arguments
     * @return $this
     * @throws LogicException
     */
    public function __call(string $method, array $arguments)
    {
        if (in_array($method, self::CHAINING_PASSTHROUGH)) {
            $that = $this->builder->{$method}(...$arguments);

            return $this;
        }

        if (in_array($method, self::VALUED_PASSTHROUGH)) {
            return $this->builder->{$method}(...$arguments);
        }

        throw new LogicException(sprintf('Method not implemented: \'%s\'', $method));
    }

    /**
     * Retrieves the inner builder.
     * 
     * @return Rovi\Query\Builder
     */
    public function getBuilder()
    {
        return $this->builder;
    }

    /**
     * Define the table to be queried.
     * 
     * @param string $table
     * @return $this
     */
    public function table(string $table)
    {
        $this->builder->table($table);

        return $this;
    }

    /**
     * Retrieves results.
     * 
     * @return Collei\Collections\Collection
     */
    public function get()
    {
        $model = $this->modelClass;
        
        $modelMapper = $model::getInstanceMapper();

        return $this->builder->get()->map($modelMapper);
    }
}