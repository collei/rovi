<?php
namespace Rovi\Query\Grammars;

use DateTime;
use PDO;

/**
 * SQLite Grammar
 */
class SqliteGrammar extends Grammar
{
    /**
     * @var array
     */
    protected const TYPES = [
        'int' => ['integer','int','tinyint','smallint','mediumint','bigint'],
        'float' => ['float','double','decimal','numeric','real'],
        'string' => ['varchar','char','tinytext','text','tinyblob','blob','mediumblob','longblob'],
        'bool' => ['integer'],
        DateTime::class => ['date','datetime','timestamp','year'],
    ];

    /**
     * @var array
     */
    protected const DB_TYPES = [
        'int' => 'integer',
        'integer' => 'integer',
        'tinyint' => 'integer',
        'smallint' => 'integer',
        'mediumint' => 'integer',
        'bigint' => 'integer',
        'decimal' => 'numeric',
        'float' => 'real',
        'double' => 'real',
        'varchar' => 'text',
        'char' => 'text',
        'tinytext' => 'text',
        'text' => 'text',
        'tinyblob' => 'text',
        'blob' => 'blob',
        'mediumblob' => 'blob',
        'longblob' => 'blob',
        'date' => 'text',
        'datetime' => 'text',
        'timestamp' => 'text',
        'guid' => 'text',
        'year' => 'integer',
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
     * Compiles auto increment.
     * 
     * @param int $seed = 1
     * @param int $increment = 1
     * @return string
     */
    protected function compileAutoIncrement(int $seed = 1, int $increment = 1)
    {
        return 'AUTO_INCREMENT';
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
     * Compiles insert output clause.
     * 
     * @param array $output
     * @return string
     */
    protected function compileInsertOutputClause($output)
    {
        return '';
    }
}