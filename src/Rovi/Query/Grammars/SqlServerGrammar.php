<?php
namespace Rovi\Query\Grammars;

use DateTime;
use PDO;
use PDOStatement;
use PDOException;
use Rovi\Connections\Connector;

class SqlServerGrammar extends Grammar
{
    protected const TYPES = [
        'int' => ['integer','int','tinyint','smallint','mediumint','bigint'],
        'float' => ['float','double','decimal','numeric'],
        'string' => ['varchar','char','tinytext','text','tinyblob','blob','mediumblob','longblob'],
        'bool' => ['bit'],
        DateTime::class => ['date','datetime','timestamp','year'],
    ];

    protected const DB_TYPES = [
        'int' => 'int',
        'integer' => 'integer',
        'tinyint' => 'tinyint',
        'smallint' => 'smallint',
        'mediumint' => 'mediumint',
        'bigint' => 'bigint',
        'decimal' => 'decimal(%s,%s)',
        'float' => 'float',
        'double' => 'double',
        'varchar' => 'varchar(%s)',
        'char' => 'char(%s)',
        'tinytext' => 'tinytext',
        'text' => 'text',
        'tinyblob' => 'tinyblob',
        'blob' => 'blob',
        'mediumblob' => 'mediumblob',
        'longblob' => 'longblob',
        'date' => 'date',
        'datetime' => 'datetime',
        'timestamp' => 'timestamp',
        'guid' => 'varchar(38)',
        'year' => 'year',
    ];
    
    protected const DB_TYPES_DEFAULTS = [
        'int' => 0,
        'integer' => 0,
        'tinyint' => 0,
        'smallint' => 0,
        'mediumint' => 0,
        'bigint' => 0,
        'decimal' => 0,
        'float' => 0,
        'double' => 0,
        'varchar' => '',
        'char' => '',
        'tinytext' => '',
        'text' => '',
        'tinyblob' => '',
        'blob' => '',
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
}