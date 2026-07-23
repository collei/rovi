<?php
namespace Rovi\Query\Grammars;

use InvalidArgumentException;
use Rovi\Query\Expressions\Expression;

/**
 * MySQL Grammar 
 */
class PostgresGrammar extends Grammar
{
    /**
     * Compiles select statement.
     * 
     * @param string $select
     * @param string $from
     * @param array $joins = null
     * @param array $wheres = null
     * @param array $groups = null
     * @param array $havings = null
     * @param array $orders = null
     * @param int $offset = null
     * @param int $limit = null
     * @return string
     */
    public function compileStatementSelect(
        $select,
        $from,
        ?array $joins = null,
        ?array $wheres = null,
        ?array $groups = null,
        ?array $havings = null,
        ?array $orders = null,
        ?int $offset = null,
        ?int $limit = null
    ) {
        $sql = [];

        $sql[] = $select . ' ' . $from;

        if (! empty($joins)) {
            $sql = array_merge($sql, $joins);
        }

        if (! empty($wheres)) {
            $sql[] = $this->compileWhereClause($wheres);
        }

        if (! empty($groups)) {
            $sql[] = $this->compileGroupByClause($groups);
        }

        if (! empty($havings)) {
            $sql[] = $this->compileHavingClause($havings);
        }

        if (! empty($orders)) {
            $sql[] = $this->compileOrderByClause($orders);
        }

        if (! empty($limit)) {
            $sql[] = sprintf('LIMIT %s', $limit);
        }

        if (! empty($offset)) {
            $sql[] = sprintf('OFFSET %s', $offset);
        }

        return implode(' ', $sql);
    }
}