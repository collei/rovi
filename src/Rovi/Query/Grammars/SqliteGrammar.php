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
        'float' => ['float','double','decimal','numeric'],
        'string' => ['varchar','char','tinytext','text','tinyblob','blob','mediumblob','longblob'],
        'bool' => ['bit'],
        DateTime::class => ['date','datetime','timestamp','year'],
    ];

    /**
     * @var array
     */
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

    /**
     * Custom grammar initialization.
     * 
     * @return void
     */
    protected function init()
    {
        $dbh = new PDO('sqlite::memory:');
        $sqliteVersion = trim($dbh->query('select sqlite_version()')->fetch()[0]);
        $dbh = null;
        
        $this->dbEngineVersion = $sqliteVersion;
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
        if ($this->engineVersionCompare('3.35.0') >= 0) {
            return sprintf('RETURNING %s', implode(', ', $output));
        }

        return '';
    }
}