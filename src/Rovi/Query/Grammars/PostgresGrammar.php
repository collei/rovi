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
     * @var array
     */
    protected const TYPES = [
        'int' => ['integer','int','tinyint','smallint','mediumint','bigint'],
        'float' => ['float','double','decimal','numeric'],
        'string' => ['varchar','char','text','tinyblob','blob','mediumblob','longblob'],
        'bool' => ['boolean'],
        DateTime::class => ['date','datetime','timestamp','year'],
    ];

    /**
     * @var array
     */
    protected const DB_TYPES = [
        'int' => 'integer',
        'integer' => 'integer',
        'tinyint' => 'tinyint',
        'smallint' => 'smallint',
        'mediumint' => 'mediumint',
        'bigint' => 'bigint',
        'decimal' => 'numeric(%s,%s)',
        'float' => 'double precision',
        'double' => 'double precision',
        'boolean' => 'boolean',
        'varchar' => 'varchar(%s)',
        'char' => 'char(%s)',
        'longtext' => 'text',
        'text' => 'text',
        'tinyblob' => 'tinyblob',
        'blob' => 'bytea',
        'mediumblob' => 'mediumblob',
        'longblob' => 'longblob',
        'date' => 'date',
        'datetime' => 'timestamp',
        'timestamp' => 'timestamp',
        'guid' => 'varchar(38)',
        'year' => 'year',
    ];
    
    /**
     * @var array
     */
    protected const DB_TYPES_DEFAULTS = [
        'integer' => 0,
        'tinyint' => 0,
        'smallint' => 0,
        'mediumint' => 0,
        'bigint' => 0,
        'boolean' => false,
        'decimal' => 0,
        'double' => 0,
        'double precision' => 0,
        'varchar' => '',
        'char' => '',
        'text' => '',
        'tinyblob' => '',
        'blob' => '',
        'bytea' => '',
        'mediumblob' => '',
        'longblob' => '',
        'date' => 'CURRENT_TIMESTAMP',
        'datetime' => 'CURRENT_TIMESTAMP',
        'timestamp' => 'CURRENT_TIMESTAMP',
        'year' => 'YEAR(CURDATE())',
    ];

    /**
     * Custom grammar initialization.
     * 
     * @return void
     */
    protected function init()
    {
        //
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