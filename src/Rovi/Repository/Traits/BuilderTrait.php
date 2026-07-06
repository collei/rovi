<?php
namespace Rovi\Repository\Traits;

use Closure;
use LogicException;
use InvalidArgumentException;
use Rovi\Connections\Connection;
use Collei\Collections\Collection;

/**
 * Model query builder trait.
 */
trait BuilderTrait
{
    /**
     * @var Rovi\Query\Builder
     */
    protected $builder;

    /**
     * @var string
     */
    protected $modelClass;

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
        if ($this->isPassthru($method)) {
            $that = $this->builder->{$method}(...$arguments);

            return $this;
        }

        if ($this->isPassthru($method, true)) {
            return $this->builder->{$method}(...$arguments);
        }

        throw new LogicException(sprintf('Method not implemented: \'%s\'', $method));
    }

    /**
     * Tells if the given name is a passthru method name.
     * 
     * @param string $name
     * @param bool $valued = false
     * @return bool
     */
    protected function isPassthru(string $name, bool $valued = false)
    {
        if ($valued) {
            return in_array($name, ['asSql','update','delete'], true);
        }

        return in_array($name, [
            'select', 'join', 'joinSub',
            'innerJoin', 'outerJoin', 'leftJoin', 'rightJoin',
            'where', 'andWhere', 'orWhere',
            'whereBetween', 'andWhereBetween', 'orWhereBetween',
            'whereNotBetween', 'andWhereNotBetween', 'orWhereNotBetween',
            'whereIn', 'andWhereIn', 'orWhereIn',
            'whereNotIn', 'andWhereNotIn', 'orWhereNotIn',
            'whereExists', 'andWhereExists', 'orWhereExists',
            'whereNotExists', 'andWhereNotExists', 'orWhereNotExists',
            'orderBy', 'reorder',
            'offset', 'removeOffset',
            'limit', 'removeLimit',
        ], true);
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
     * Retrieves results, if any.
     * 
     * @return Collei\Collections\Collection
     */
    public function get()
    {
        $model = $this->modelClass;
        
        $modelMapper = $model::getInstanceMapper();

        return $this->builder->get()->map($modelMapper);
    }

    /**
     * Retrieves all filtered results, if any.
     * 
     * @return Collei\Collections\Collection
     */
    public function all()
    {
        return $this->get();
    }

    /**
     * Retrieves the first of the filtered results, if any.
     * 
     * @return Rovi\Repository\Model|null
     */
    public function first()
    {
        return $this->get()->first();
    }
}