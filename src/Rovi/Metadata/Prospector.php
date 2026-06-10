<?php
namespace Rovi\Metadata;

use Rovi\Metadata\Attributes\Schema\{Column, Computed, ForeignKey, Identity, Key, NotMapped, Table};
use Rovi\Metadata\Attributes\{MaxLength, Required, StringLength, DecimalSize};
use Rovi\Query\Grammars\{Grammar, SqliteGrammar};
use ReflectionClass;
use ReflectionProperty;
use ReflectionNamedType;
use ReflectionUnionType;

class Prospector
{
    protected const SCALAR = [
        'bool' => 'bool',
        'int' => 'int',
        'float' => 'float',
        'string' => 'string'
    ];

    protected const REGEX_PARSE_TYPE = '/(?<type>\w+( \w+)*)\s*(?>\(\s*(?<size>\d+)(?>\s*,\s*(?<precision>\d+))?\s*\))?/m';

    protected Grammar $grammar;
    protected string $class;
    protected string $table;
    protected ?string $schema = null;

    protected array $fields = [];

    protected array $statements = [];

    public function __construct(string|object $class)
    {
        $this->grammar = new SqliteGrammar();

        $this->class = is_object($class) ? get_class($class) : $class;

        $this->prospect();
    }

    protected function prospect()
    {
        $reflection = new ReflectionClass($this->class);

        foreach ($reflection->getAttributes(Table::class) as $attribute) {
            $table = $attribute->newInstance();

            $this->table = $table->name();
            $this->schema = $table->schema();

            break;
        }

        $this->fields = [];

        $sqlFields = [];

        foreach ($reflection->getProperties() as $property) {
            // ignore properties marked to not be mapped
            if ($array = $property->getAttributes(NotMapped::class)) {
                continue;
            }

            $name = $property->getName();
        
            $this->fields[$name] = $this->fetchPropertyConstraints($property);

            $sqlFields[] = $this->fieldAsSqlCreateTable($this->fields[$name]);
        }

        $this->statements['create table'] = PHP_EOL . $this->grammar->compileCreateTable(
            $this->grammar->compileTableName($this->table, $this->schema),
            $sqlFields
        );
    }

    protected function fetchPropertyConstraints(ReflectionProperty $property)
    {
        $name = $property->getName();
        $type = $this->fetchPropertyType($property);
        $nullable = true;
        $identity = $computed = false;
        $order = $key = $field = $references = $dbtype = $size = $precision = $default = null;

        $attributes = $property->getAttributes();

        if ($valueDefault = $property->getDefaultValue()) {
            $default = $valueDefault;
        }

        if (empty($attributes)) {
            $field = $name;                
        } else foreach ($attributes as $n => $attribute) {
            $attr = $attribute->newInstance();

            if ($attr instanceof ForeignKey) {
                $key = 'foreign';
                $references = $attr->references();
                $field = $attr->name() ?? $name; 
                $order = $attr->order() ?? $order;
                $dbtype = $attr->type() ?? $dbtype;
                $default = $attr->default() ?? $default;
            } elseif ($attr instanceof Key) {
                $key = 'primary';
                $nullable = false;
                $field = $attr->name() ?? $name; 
                $order = $attr->order() ?? $order;
                $dbtype = $attr->type() ?? $dbtype;
                $default = $attr->default() ?? $default;
            } elseif ($attr instanceof Column) {
                $field = $attr->name() ?? $name;
                $order = $attr->order() ?? $order;
                $dbtype = $attr->type() ?? $dbtype;
                $default = $attr->default() ?? $default;
            } elseif ($attr instanceof Required) {
                $nullable = false;
            } elseif ($attr instanceof Identity) {
                $identity = true;
            } elseif ($attr instanceof Computed) {
                $computed = true;
            } elseif ($attr instanceof MaxLength || $attr instanceof StringLength) {
                $size = $attr->size();
            } elseif ($attr instanceof DecimalSize) {
                $size = $attr->size();
                $precision = $attr->precision();
            }
        }

        if ($dbtype) if (1 === preg_match(self::REGEX_PARSE_TYPE, $dbtype, $res)) {
            $type = $res['type'];

            if (isset($res['size'])) {
                $size = (int) $res['size'];
            }

            if (isset($res['precision'])) {
                $precision = (int) $res['precision'];
            }
        }

        return compact(
            'name','type','size','precision',
            'default','field','dbtype','nullable','order',
            'identity','computed','key','references'
        );
    }

    protected function fetchPropertyType(ReflectionProperty $property)
    {
        if (! $property->hasType()) {
            return null;
        }

        $refType = $property->getType();
        
        if ($refType instanceof ReflectionNamedType) {
            return $refType->getName();
        }
        
        if ($refType instanceof ReflectionUnionType) {
            $types = $refType->getTypes();

            foreach ($types as $type) {
                if (array_key_exists($type, self::SCALAR)) {
                    return $type;
                }
            }
        }

        return null;
    }

    public function getClass()
    {
        return $this->class;
    }

    public function getTable()
    {
        return $this->table;
    }

    public function getSchema()
    {
        return $this->schema;
    }

    public function getFields()
    {
        return $this->fields;
    }

    protected function fieldAsSqlCreateTable(array $field)
    {
        $name = $field['field'] ?? $field['name'];
        $type = $field['type'] ?? $field['dbtype'];
        $size = $field['size'] ?? null;
        $precision = $field['precision'] ?? null;

        $sqlType = $this->grammar->compileType($type, $size, $precision);

        $isPrimary = ('primary' == $field['key']);
        $isIdentity = $field['identity'];
        $isNullable = $field['nullable'] && ($isPrimary === false);

        $default = (! empty($field['default'])) ? $field['default'] : null;

        if ($isPrimary) {
            return $this->grammar->compileColumnPrimaryKey($name, $sqlType, $isIdentity);
        }

        return $this->grammar->compileColumn($name, $sqlType, $isNullable, $default);
    }
}