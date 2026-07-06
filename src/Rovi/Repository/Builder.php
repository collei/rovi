<?php
namespace Rovi\Repository;

use Closure;
use LogicException;
use InvalidArgumentException;
use Rovi\Query\Builder as QueryBuilder;
use Rovi\Repository\Traits\BuilderTrait;
use Rovi\Connections\Connection;

/**
 * Model query builder.
 */
class Builder
{
    use BuilderTrait;

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
}