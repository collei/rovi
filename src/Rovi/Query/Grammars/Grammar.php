<?php
namespace Rovi\Query\Grammars;

use InvalidArgumentException;
use Rovi\Query\Expressions\Expression;

abstract class Grammar
{
    protected const MAX_INT_32 = 2147483647;

    protected const MAX_INT_64 = 9223372036854775807;

    protected const REGEX_SQL_IDENTIFIER = '/^\s*(?<identifier>(?>[A-Za-z_]\w*|´[^´]+´|\[[^\]]+\])(?>\s*\.\s*(?>[A-Za-z_]\w*|´[^´]+´|\[[^\]]+\])){0,2})(?>\s+as\s+(?<alias>[A-Za-z_]\w*|´[^´]+´|\[[^\]]+\]))?\s*$/i';

    protected const REGEX_JOIN_TYPE = '/\s*(full|left|right|)\s*(inner|outer|)?\s*(join)\s*/i';

    protected const REGEX_SQL_BINDING_VARIABLE = '/\s*:[A-Za-z]\w*\s*/i';

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

    protected const SQL_OPERATORS = [
        '=' => '=',
        '>' => '>',
        '<' => '<',
        '>=' => '>=',
        '<=' => '<=',
        '<>' => '<>',
        'is' => 'is',
        'in' => 'in',
        'not in' => 'not in',
        'like' => 'like',
        'not like' => 'not like',
        'between' => 'between',
        'not between' => 'not between',
    ];

    protected $defaultStringSize = 50;
    protected $defaultDecimalSize = 18;
    protected $defaultDecimalPrecision = 2;

    public function __debugInfo()
    {
        return [
            'defaultStringSize' => $this->defaultStringSize,
            'defaultDecimalSize' => $this->defaultDecimalSize,
            'defaultDecimalPrecision' => $this->defaultDecimalPrecision,
        ];
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

    public function setDefaultStringSize(int $size)
    {
        $this->defaultStringSize = $size;
    }

    public function setDefaultDecimalSizeAndPrecision(int $size, int $precision)
    {
        $this->defaultDecimalSize = $size;
        $this->defaultDecimalPrecision = $precision;
    }

    public function validateSqlIdentifier(string $sqlIdentifier)
    {
        return 1 === preg_match(self::REGEX_SQL_IDENTIFIER, $sqlIdentifier);
    }

    public function parseAliasedSqlIdentifier(string $sqlIdentifier)
    {
        if (1 === preg_match(self::REGEX_SQL_IDENTIFIER, $sqlIdentifier, $results)) {
            return array($results['identifier'], $results['alias'] ?? null);
        }

        return null;
    }

    public function compileCreateTable(string $name, array $compiledFields = [])
    {
        return sprintf(
            'CREATE TABLE %s (%s);',
            $name,
            PHP_EOL . implode(','.PHP_EOL, array_map('trim', $compiledFields)) . PHP_EOL
        );
    }

    public function compileTableName(string $table, ?string $schema = null, ?string $database = null)
    {
        if (! empty($schema)) {
            return (! empty($database))
                ? sprintf('[%s].[%s].[%s]', $database, $schema, $table)
                : sprintf('[%s].[%s]', $schema, $table);
        }

        return (! empty($database))
            ? sprintf('[%s].[%s]', $database, $table)
            : sprintf('[%s]', $table);
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

    public function compileType(string $type, ?int $size = null, ?int $precision = null)
    {
        if (array_key_exists($type, static::TYPES)) {
            $dbtype = static::TYPES[$type][0];

            if (array_key_exists($dbtype, static::DB_DECIMAL_TYPES)) {
                return sprintf(
                    static::DB_DECIMAL_TYPES[$dbtype],
                    $size ?? $this->defaultDecimalSize,
                    $precision ?? $this->defaultDecimalPrecision
                );
            }

            if (array_key_exists($dbtype, static::DB_STRING_TYPES)) {
                return sprintf(
                    static::DB_STRING_TYPES[$dbtype],
                    $size ?? $this->defaultStringSize
                );
            }
        }

        foreach (static::TYPES as $natural => $types) {
            if ($key = array_search($type, $types, true)) {
                $dbtype = $types[$key];

                if (array_key_exists($dbtype, static::DB_DECIMAL_TYPES)) {
                    return sprintf(
                        static::DB_DECIMAL_TYPES[$dbtype],
                        $size ?? $this->defaultDecimalSize,
                        $precision ?? $this->defaultDecimalPrecision
                    );
                }

                if (array_key_exists($dbtype, static::DB_STRING_TYPES)) {
                    return sprintf(
                        static::DB_STRING_TYPES[$dbtype],
                        $size ?? $this->defaultStringSize
                    );
                }

                if (array_key_exists($dbtype, static::DB_TYPES)) {
                    return static::DB_TYPES[$dbtype];
                }
            }
        }

        if (array_key_exists($type, static::DB_TYPES) && is_null($size) && is_null($precision)) {
            return static::DB_TYPES[$type];
        }

        throw new InvalidArgumentException("Invalid database type: {$type}");
    }

    public function compileAlias(string $target, ?string $alias = null, bool $nesting = false)
    {
        return empty($alias)
            ? sprintf('%s', trim($target))
            : ($nesting
                ? sprintf('(%s) AS %s', trim($target), trim($alias))
                : sprintf('%s AS %s', trim($target), trim($alias)));
    }

    public function compileJoin(string $type, string $joinTable, ?string $alias = null, array $conditions = [], bool $nesting = false)
    {
        if (empty($conditions)) {
            return sprintf('CROSS JOIN %s', $this->compileAlias($joinTable, $alias, $nesting));
        }

        return sprintf(
            '%s %s ON %s',
            $this->conformJoinType($type),
            $this->compileAlias($joinTable, $alias, $nesting),
            $this->compileConditions($conditions)
        );
    }

    public function compileSet(string $field, string $value, bool $nesting = false)
    {
        if ($nesting) {
            return sprintf('%s = (%s)', $field, $value);
        }

        return sprintf('%s = %s', $field, $value);
    }

    /**
     * clauses
     */

    public function compileSelectClause(?array $fields = null)
    {
        if (empty($fields)) {
            return 'SELECT *';
        }

        $callback = function($field, $as) {
            return is_string($as)
                ? $this->compileAlias($field, $as)
                : $this->compileAlias($field);
        };

        $select = array_map($callback, array_values($fields), array_keys($fields));

        return sprintf('SELECT %s', implode(', ', $select));
    }

    public function compileFromClause(string $from, ?string $alias = null, bool $nesting = false)
    {
        return sprintf('FROM %s', $this->compileAlias($from, $alias, $nesting));
    }

    public function compileWhereClause(array $conditions)
    {
        return sprintf('WHERE %s', $this->compileConditions($conditions));
    }

    public function compileGroupByClause(array $groups)
    {
        return sprintf('GROUP BY %s', implode(', ', $groups));        
    }

    public function compileHavingClause(array $conditions)
    {
        return sprintf('HAVING %s', $this->compileConditions($conditions));
    }

    public function compileOrderByClause(array $orders)
    {
        $items = [];

        foreach ($orders as $item) {
            list($order, $field) = array(key($item), current($item));

            if ($field instanceof Expression) {
                $field = "({$field})";
            }

            $order = is_int($order) ? (1 ? 'DESC' : 'ASC') : strtoupper($order);
                
            $order = ($order === 'DESC') ? 'DESC' : 'ASC';

            $items[] = $order . ' ' . $field;
        }

        return sprintf('ORDER BY %s', implode(', ', $items));        
    }

    public function compileInsertTable(string $table, array $fields)
    {
        return sprintf('INSERT INTO %s (%s)', $table, implode(', ', $fields));
    }

    public function compileInsertValuesClause(array $values)
    {
        if (is_array(current($values))) {
            $rows = [];

            foreach ($values as $row) if (is_array($row)) {
                $row = array_map([$this, 'quoteIfString'], $row);

                $rows[] = sprintf('(%s)', implode(', ', $row));
            }

            return sprintf('VALUES %s', implode(",\n", $rows));
        }

        return sprintf('VALUES (%s)', implode(', ', $values));
    }

    protected function quoteIfString($value)
    {
        if (is_string($value) && (1 !== preg_match(self::REGEX_SQL_BINDING_VARIABLE, $value))) {
            return "'{$value}'";
        }

        if (is_object($value) && method_exists($value, '__toString()')) {
            return "'{$value}'";
        }

        if ($value instanceof \DateTimeInterface) {
            return "'" . $value->format('Y-m-d H:i:s') . "'";
        }

        if (is_float($value)) {
            return number_format($value, 8, '.', '');
        }

        if (is_int($value)) {
            return ($value > self::MAX_INT_32) ? "'{$value}'" : number_format($value, 0, '', '');
        }

        return $value;
    }

    public function compileUpdateTable(string $table)
    {
        return sprintf('UPDATE %s', $table);
    }

    public function compileUpdateSetClause(array $items)
    {
        if (empty($items)) {
            return null;
        }

        return sprintf('SET %s', implode(', ', $items));
    }

    public function compileDeleteTable(string $table)
    {
        return sprintf('DELETE FROM %s', $table);
    }

    protected function conformJoinType(string $joinType)
    {
        $joinType .= ' join';

        if (1 === preg_match(self::REGEX_JOIN_TYPE, $joinType, $matches)) {
            unset($matches[0]);

            return strtoupper(preg_replace('/\s+/',' ',trim(implode(' ', $matches))));
        }

        return $joinType;
    }

    protected function compileConditions(array $conditions)
    {
        $compiled = [];

        foreach ($conditions as $condition) {
            if (is_string($condition)) {
                $compiled[] = $condition;

                continue;
            }

            if (isset($condition['nest'])) {
                $compiled[] = '(' . $this->compileConditions($condition['nest']) . ')';

                continue;
            }

            if (isset($condition['field']) && isset($condition['operator']) && isset($condition['value'])) {
                $compiled[] = $this->compileCondition(
                    $condition['field'], $condition['operator'], $condition['value']
                );
            }
        }

        return implode(' ', $compiled);
    }

    protected function compileCondition($field, $operator, $value)
    {
        $operator = preg_replace('/\s+/', ' ', strtoupper(trim($operator)));

        if (in_array($operator, ['BETWEEN','NOT BETWEEN'])) {
            $value = is_array($value) ? array_values($value) : [$value, $value];

            return sprintf(
                '(%s %s %s AND %s)',
                $field,
                $operator,
                $value[0] ?? null,
                $value[1] ?? $value[0] ?? null
            );
        }

        if (in_array($operator, ['IN','NOT IN'])) {
            $value = is_array($value) ? implode(',', $value) : $value;

            return sprintf('(%s %s (%s))', $field, $operator, $value);
        }

        if ($value instanceof Expression) {
            return sprintf('(%s %s (%s))', $field, $operator, $value);
        }

        return sprintf('(%s %s %s)', $field, $operator, $value);
    }

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

        if (! empty($offset)) {
            $sql[] = sprintf('OFFSET %s', $offset);
        }

        if (! empty($limit)) {
            $sql[] = sprintf('LIMIT %s', $limit);
        }

        return implode(' ', $sql);
    }

    public function compileStatementInsertValues(
        string $table,
        array $fields,
        array $values
    ) {
        $sql = [];

        $sql[] = $this->compileInsertTable($table, $fields);

        $sql[] = $this->compileInsertValuesClause($values);

        return implode(' ', $sql);
    }

    public function compileStatementInsertSelect(
        string $table,
        array $fields,
        string $selectSql
    ) {
        $sql = [];

        $sql[] = $this->compileInsertTable($table, $fields);

        $sql[] = $selectSql;

        return implode(' ', $sql);
    }

    public function compileStatementUpdate(
        string $table,
        array $setItems,
        ?array $wheres = null
    ) {
        $sql = [];

        $sql[] = $this->compileUpdateTable($table);

        if (! empty($setItems)) {
            $sql[] = $this->compileUpdateSetClause($setItems);
        }

        if (! empty($wheres)) {
            $sql[] = $this->compileWhereClause($wheres);
        }

        return implode(' ', $sql);
    }

    public function compileStatementDelete(
        string $table,
        ?array $wheres = null
    ) {
        $sql = [];

        $sql[] = $this->compileDeleteTable($table);

        if (! empty($wheres)) {
            $sql[] = $this->compileWhereClause($wheres);
        }

        return implode(' ', $sql);
    }

    protected abstract function compileAutoIncrement(int $seed = 1, int $increment = 1);

    protected abstract function compilePrimaryKey();

    protected abstract function compileConstraintPrimaryKey();

    protected function compileDefaultValue($value)
    {
        return sprintf('DEFAULT %s', $value);
    }
}
