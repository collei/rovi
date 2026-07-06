<?php
namespace Rovi\Repository;

use Rovi\Connections\Connector;
use Rovi\Repository\Relations\Relation;
use Rovi\Repository\Relations\BelongsTo;
use Rovi\Repository\Relations\HasMany;
use Rovi\Repository\Relations\BelongsToMany;

/**
 * Model base class. 
 */
abstract class Model
{
    /**
     * @var string
     */
    protected const TABLE = null;

    /**
     * @var string
     */
    protected const SCHEMA = null;

    /**
     * @var string
     */
    protected const KEY = 'id';

    /**
     * @var array
     */
    protected const DEFAULTS = [];

    /**
     * @var array
     */
    protected const REQUIRED = [];

    /**
     * @var array
     */
    protected const PROTECTED = ['*'];

    /**
     * @var string
     */
    protected const CONNECTION = 'db';

    /**
     * @var array
     */
    private const INNER_METHODS = [
        'getTable',
        'getKeyName',
        'getConnectionName',
        'getKey',
        'new',
        'fromResult',
        'getQuery',
        'connection',
        'toJson',
        'transformFieldNamesFrom',
        'transformFieldNamesTo',
        'hydrate',
        'toArray',
        'find',
        'all',
        'where',
        'save',
        'performUpdate',
        'performInsert',
        'delete',
        'getInstanceMapper',
        'BelongsTo',
        'HasMany',
        'BelongsToMany',
    ];

    /**
     * @var \Rovi\Connections\Connection
     */
    private $connection = null;

    /**
     * @var bool
     */
    private $hydrated = false; 

    /**
     * @var array
     */
    private $retrieved = [];

    /**
     * @var array
     */
    private $modified = [];

    /**
     * @var array
     */
    protected $relationships = [];

    /**
     * Builds a new instance.
     * 
     * @param array $fields = []
     */
    public function __construct(array $fields = [])
    {
        foreach ($fields as $name => $value) {
            if (! array_key_exists($name, $this->retrieved)) {
                $this->retrieved[$name] = static::DEFAULTS[$name] ?? null;
            }

            $this->modified[$name] = $value;
        }
    }

    /**
     * For use of PHP debugging functions.
     * 
     * @param array
     */
    public function __debugInfo()
    {
        return [
            'table' => static::TABLE,
            'key' => static::KEY,
            'hydrated' => $this->hydrated ? 'TRUE' : 'FALSE',
            'connection' => static::CONNECTION,
            'retrieved' => $this->retrieved,
            'modified' => $this->modified,
        ];
    }

    /**
     * Retrieves a value from table or relationship.
     * 
     * @param string $name
     * @return mixed
     */
    public final function __get(string $name)
    {
        if (method_exists($this, $name) && (! in_array($name, self::INNER_METHODS, true))) {
            $result = $this->{$name}();

            return ($result instanceof Relation) ? @$result->get() : $result;
        }
    
        $name = $this->transformFieldNamesFrom($name);

        if (array_key_exists($name, $this->modified)) {
            return $this->modified[$name];
        }

        if (array_key_exists($name, $this->retrieved)) {
            return $this->retrieved[$name];
        }

        return null;
    }

    /**
     * Defines new value for the field.
     * 
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public final function __set(string $name, $value)
    {
        $name = $this->transformFieldNamesFrom($name);
        
        if ($this->hydrated && (! array_key_exists($name, $this->retrieved))) {
            throw new RoviModelException(sprintf(
                'Not found property \'%s\' on table \'%s\'', $name, static::TABLE
            ));
        }

        if (static::KEY === $name) {
            throw new RoviModelException(sprintf(
                'Illegal assignment to primary key \'%s\' on table \'%s\'', $name, static::TABLE
            ));
        }

        $this->modified[$name] = $value;
    }

    /**
     * Tells if field was set.
     * 
     * @param string $name
     * @return bool
     */
    public final function __isset(string $name)
    {
        if (method_exists($this, $name) && ! in_array($name, self::INNER_METHODS, true)) {
            return true;
        }
        
        $name = $this->transformFieldNamesFrom($name);

        return (! $this->hydrated) && array_key_exists($name, $this->retrieved);
    }

    /**
     * Removes value from field by setting it null.
     * 
     * @param string $name
     * @return void
     */
    public final function __unset(string $name)
    {
        $name = $this->transformFieldNamesFrom($original = $name);
        
        if (in_array($name, static::REQUIRED, true)) {
            throw new RoviModelException(sprintf(
                'Non-nullable property \'%s\' on table \'%s\' cannot be nullified', $name, static::TABLE
            ));
        }

        $this->modified[$name] = null;
    }

    /**
     * Allows chaining property set one by one.
     * 
     * @param string $name
     * @param array $arguments
     * @return $this 
     */
    public final function __call(string $name, array $arguments)
    {
        $this->$name = current($arguments);

        return $this;
    }

    /**
     * Retrieves table name.
     * 
     * @return string
     */
    public final function getTable()
    {
        return static::TABLE ?? substr(static::class, strrpos(static::class, '\\') + 1);
    }

    /**
     * Retrieves primary key name.
     * 
     * @return string
     */
    public final function getKeyName()
    {
        return static::KEY ?? 'id';
    }

    /**
     * Retrieves db connection name.
     * 
     * @return string
     */
    public final function getConnectionName()
    {
        return static::CONNECTION;
    }

    /**
     * Retrieves primary key value.
     * 
     * @return mixed
     */
    public final function getKey()
    {
        return $this->retrieved[$this->getKeyName()];
    }

    /**
     * Creates a new instance, optionally with fields.
     * 
     * @param array $fields = []
     * @return static
     */
    public static final function new(array $fields = [])
    {
        return new static($fields);
    }

    /**
     * Creates new instance, optionally with fields or results.
     * 
     * @param mixed $fields = []
     * @return static
     */
    public static final function fromResult($fields = [])
    {
        return new static(
            is_array($fields) ? $fields : (array) $fields
        );
    }

    /**
     * Returns a Builder for this model.
     * 
     * @return Rovi\Repository\Builder
     */
    public static function getQuery()
    {
        return (new Builder(static::class, (new static)->connection()))->table(static::TABLE);
    }

    /**
     * Retrieves the model's connection.
     * 
     * @return Rovi\Connections\Connection
     */
    public final function connection()
    {
        if ($this->connection) {
            return $this->connection;
        }

        if (Connector::hasConnection(static::CONNECTION)) {
            return $this->connection = Connector::getConnection(static::CONNECTION);
        }

        throw new RoviModelException($this, sprintf('Connection not found: \'%s\'', static::CONNECTION));
    }

    /**
     * Retrieves instance as JSON string.
     * 
     * @return string
     */
    public final function toJson()
    {
        return json_encode($this->toArray());
    }

    /**
     * Transforms field names from if enabled to do so.
     * 
     * @param string $from
     * @return string
     */
    protected function transformFieldNamesFrom($from)
    {
        if (method_exists($this, 'fromCamelCaseField')) {
            $from = $this->fromCamelCaseField($from);
        }

        return $from;
    }

    /**
     * Transforms field names to if enabled to do so.
     * 
     * @param string $to
     * @return string
     */
    protected function transformFieldNamesTo($to)
    {
        if (method_exists($this, 'toCamelCaseField')) {
            $to = $this->toCamelCaseField($to);
        }

        return $to;
    }

    /**
     * Hydrates the model instance with data from database.
     * 
     * @param array $fields
     * @return $this
     */
    protected final function hydrate(array $fields)
    {
        foreach ($fields as $name => $value) {
            $this->retrieved[$name] = $value ?? null;
        }

        $this->modified = [];

        $this->hydrated = true;

        return $this;
    }

    /**
     * Retrieves instance data as array.
     * 
     * @return array
     */
    public function toArray()
    {
        return array_merge($this->retrieved, $this->modified);
    }

    /**
     * Retrieves the given record by primary key, if any.
     * 
     * @return static
     */
    public static final function find($id)
    {
        $result = (new static)->connection()->getBuilder()
                    ->table(static::TABLE)
                    ->where(static::KEY, '=', $id)
                    ->get()->first();

        if (is_object($result)) {
            $result = (array) $result;
        }

        if (is_array($result)) {
            return static::new()->hydrate($result);
        }

        return null;
    }

    /**
     * Return all rows of the underlying table as Model instances.
     * 
     * @param string ...$fields
     * @return Collei\Collections\Collection|array
     */
    public static final function all(string ...$fields)
    {
        if (count($fields) > 0) {
            return $this->connection()
                        ->table(static::TABLE)
                        ->getAsArray(...$fields);
        }

        return static::getQuery()->get();
    }

    /**
     * Initiates a query on the model.
     * 
     * @param string|Closure|Expression $field
     * @param mixed $operator = null
     * @param mixed $value = null
     * @return Rovi\Repository\Builder
     */
    public static final function where($field, $operator = null, $value = null)
    {
        return static::getQuery()->where($field, $operator, $value);
    }

    /**
     * Performs data save in the database.
     * 
     * @return bool
     */
    public final function save()
    {
        return $this->hydrated
                    ? $this->performUpdate()
                    : $this->performInsert();
    }

    /**
     * Performs data update in the database.
     * 
     * @return bool
     */
    private function performUpdate()
    {
        if (! $this->hydrated) {
            return false;
        }

        $query = static::getQuery()->getBuilder()
                    ->where(static::KEY, $this->retrieved[static::KEY])
                    ->update($this->modified);

        return $query !== false;
    } 

    /**
     * Performs data insert in the database.
     * 
     * @return bool
     */
    private function performInsert()
    {
        list($fields, $key) = array($this->modified, array(static::KEY));

        $query = static::getQuery()->getBuilder()->insert($fields, $key);

        if (false !== $query) {
            $id = $query->list[0][static::KEY] ?? $query->list[static::KEY] ?? $query->list[0];

            $this->hydrated = true;

            $this->modified[static::KEY] = $id;

            $this->retrieved = $this->modified;
        }
    }

    /**
     * Performs record removal from the database.
     * 
     * @return bool
     */
    public final function delete()
    {
        if (! $this->hydrated) {
            return false;
        }

        $query = static::getQuery()->getBuilder()
                    ->where(static::KEY, $this->retrieved[static::KEY])
                    ->delete();

        return $query !== false;
    }

    /**
     * For use of Rovi\Repository\Builder.
     * 
     * @return Closure
     */
    public static final function getInstanceMapper()
    {
        return function($item, $key = null) {
            return (new static)->hydrate((array) $item);
        };
    }

    /**
     * Builds a belongs-to relationship.
     * 
     * @param string $other
     * @param string $foreignKey = null
     * @param string $localKey = null
     * @return Rovi\Repository\Relations\BelongsTo
     */
    protected final function belongsTo(string $other, ?string $foreignKey = null, ?string $localKey = null)
    {
        $relation = $this->guessCallerMethodName();

        if (! empty($this->relationships[$relation])) {
            return $this->relationships[$relation];
        }

        return $this->relationships[$relation] = new BelongsTo($this, $other, $foreignKey, $localKey);
    }

    /**
     * Builds a has-many relationship.
     * 
     * @param string $other
     * @param string $foreignKey = null
     * @param string $localKey = null
     * @return Rovi\Repository\Relations\HasMany
     */
    protected final function hasMany(string $other, ?string $foreignKey = null, ?string $localKey = null)
    {
        $relation = $this->guessCallerMethodName();

        if (! empty($this->relationships[$relation])) {
            return $this->relationships[$relation];
        }

        return $this->relationships[$relation] = new HasMany($this, $other, $foreignKey, $localKey);
    }

    /**
     * Builds a belongs-to-many relationship.
     * 
     * @param string $other
     * @param string $intermediate = null
     * @param string $leftKey = null
     * @param string $rightKey = null
     * @return Rovi\Repository\Relations\BelongsToMany
     */
    protected final function belongsToMany(string $other, ?string $intermediate = null, ?string $leftKey = null, ?string $rightKey = null)
    {
        $relation = $this->guessCallerMethodName();

        if (! empty($this->relationships[$relation])) {
            return $this->relationships[$relation];
        }

        return $this->relationships[$relation] = new BelongsToMany($this, $other, $intermediate, $leftKey, $rightKey);
    }

    /**
     * Returns the caller method name.
     * 
     * @return string
     */
    private function guessCallerMethodName()
    {
        list($a, $b, $caller) = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);

        return $caller['function'];
    }
}