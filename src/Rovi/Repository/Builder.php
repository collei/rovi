<?php
namespace Rovi\Repository;

use Closure;
use LogicException;
use InvalidArgumentException;
use Rovi\Query\Builder as QueryBuilder;
use Rovi\Connections\Connection;

class Builder
{
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

    protected const VALUED_PASSTHROUGH = [
        'asSql',
        'update',
        'delete',
    ];

    protected $builder;
    protected $modelClass;

    public function __construct(string $modelClass, Connection $connection)
    {
        $this->builder = new QueryBuilder($connection);
        $this->modelClass = $modelClass;
    }

    public function __call(string $method, array $arguments)
    {
        if (in_array($method, self::CHAINING_PASSTHROUGH)) {
            $that = $this->builder->{$method}(...$arguments);

            return $this;
        }

        if (in_array($method, self::VALUED_PASSTHROUGH)) {
            $that = $this->builder->{$method}(...$arguments);

            return $this;
        }

        throw new LogicException(sprintf('Method not implemented: \'%s\'', $method));
    }

    public function table(string $table)
    {
        $this->builder->table($table);

        return $this;
    }

    public function get()
    {
        return $this->builder->get()->map(function($item) {
            return call_user_func_array([$this->modelClass, 'mapIntoInstance'], [$item]);
        });
    }
}