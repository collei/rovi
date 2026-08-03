<?php
namespace Rovi\Query\Grammars;

use DateTime;
use PDO;
use PDOStatement;
use PDOException;
use Rovi\Connections\Connector;

class SqlServerGrammar extends SqlServer12Grammar
{
    /**
     * Compiles auto increment.
     * 
     * @param int $seed = 1
     * @param int $increment = 1
     * @return string
     */
    protected function compileAutoIncrement(int $seed = 1, int $increment = 1)
    {
        return sprintf('IDENTITY(%s,%s)', $seed, $increment);
    }

    /**
     * Compiles table primary key column.
     * 
     * @return string
     */
    protected function compilePrimaryKey()
    {
        return 'PRIMARY KEY';
    }

    /**
     * Compiles table primary key column constraint.
     * 
     * @return string
     */
    protected function compileConstraintPrimaryKey()
    {
        return 'CONSTRAINT PRIMARY KEY (%s)';
    }

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

        $outer_sql_where = null;

        if (! is_null($offset)) {
            $row_id = 'rowid_'.bin2hex(random_bytes(4));

            $select = rtrim($select) . ', ROW_NUMBER() OVER (ORDER BY (SELECT 0)) AS ' . $row_id;

            if (! is_null($limit)) {
                list($first, $last) = array($offset + 1, $offset + $limit);

                $outer_sql_where = $this->compileCondition($row_id, 'between', [$first, $last]);
            } else {
                $outer_sql_where = $this->compileCondition($row_id, '>', $offset);
            }
        }

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

        if (! is_null($outer_sql_where)) {
            return sprintf(
                'WITH NumberedResults AS (%s) SELECT * FROM NumberedResults WHERE (%s)',
                implode(' ', $sql),
                $outer_sql_where
            );
        }

        return implode(' ', $sql);
    }
}