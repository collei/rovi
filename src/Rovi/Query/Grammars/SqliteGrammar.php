<?php
namespace Rovi\Query\Grammars;

use DateTime;
use PDO;

class SqliteGrammar extends Grammar
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

    protected const DB_DECIMAL_TYPES = [
        'decimal' => 'decimal(%s,%s)',
    ];

    protected const DB_STRING_TYPES = [
        'varchar' => 'varchar(%s)',
        'char' => 'char(%s)',
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

    protected function init()
    {
        $dbh = new PDO('sqlite::memory:');
        $sqliteVersion = trim($dbh->query('select sqlite_version()')->fetch()[0]);
        $dbh = null;
        
        $this->dbEngineVersion = $sqliteVersion;
    }

    public function isValidOperator($operator)
    {
        if (in_array($operator, self::SQL_OPERATORS, true)) {
            return true;
        }

        if (! is_string($operator)) {
            return false;
        }

        return array_key_exists(strtolower($operator), self::SQL_OPERATORS);
    }

    public function compileCreateTable(string $name, array $compiledFields = [])
    {
        return sprintf(
            'CREATE TABLE %s (%s);',
            $name,
            PHP_EOL . implode(','.PHP_EOL, $compiledFields) . PHP_EOL
        );
    }

    public function compileColumnPrimaryKey(string $name, string $type, bool $isIdentity = true)
    {
        return sprintf(
            '%s %s NOT NULL %s %s',
            $name,
            $type,
            ($isIdentity ? $this->compileAutoIncrement() : null),
            $this->compilePrimaryKey()
        );
    }

    public function compileColumn(string $name, string $type, bool $isNullable = true, $default = null)
    {
        return sprintf(
            '%s %s %s %s',
            $name,
            $type,
            ($isNullable ? 'NULL' : 'NOT NULL'),
            (is_null($default) ? null : $default)
        );
    }

    protected function compileAutoIncrement(int $seed = 1, int $increment = 1)
    {
        return 'AUTO_INCREMENT';
    }

    protected function compilePrimaryKey()
    {
        return 'PRIMARY KEY';
    }

    protected function compileConstraintPrimaryKey()
    {
        return 'CONSTRAINT PRIMARY KEY (%s)';
    }

    protected function compileInsertOutputClause($output)
    {
        if ($this->engineVersionCompare('3.35.0') >= 0) {
            return sprintf('RETURNING %s', implode(', ', $output));
        }

        return '';
    }
}