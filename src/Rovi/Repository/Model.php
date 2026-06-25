<?php
namespace Rovi\Repository;

use Rovi\Connections\Connector;

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
     * @var \Rovi\Connections\Connection
     */
    private $connection;

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
     * Builds a new instance.
     * 
     * @param array $fields = []
     */
    public function __construct(array $fields = [])
    {
        foreach ($fields as $name => $value) {
            if (! array_key_exists($name, $this->$retrieved)) {
                $this->retrieved[$name] = static::DEFAULTS[$name] ?? null;
            }

            $this->modified[$name] = $value;
        }
    }

    /**
     * Retrieves a value from table or relationship.
     * 
     * @param string $name
     * @return mixed
     */
    public final function __get(string $name)
    {
        $name = $this->transformFieldNamesFrom($name);

        if (array_key_exists($name, $this->modified)) {
            return $this->modified[$name];
        }

        if ($this->hydrated && array_key_exists($name, $this->retrieved)) {
            return $this->retrieved[$name];
        }

        if (method_exists($this, $name)) {
            return $this->$name();
        }

        throw new RoviModelException(sprintf(
            'Not found property \'%s\' on table \'%s\'', $name, static::TABLE
        ));
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
        
        if ($this->hydrated && (! array_key_exists($name, $this->$retrieved))) {
            throw new RoviModelException(sprintf(
                'Not found property \'%s\' on table \'%s\'', $name, static::TABLE
            ));
        }

        $this->$modified[$name] = $value;
    }

    /**
     * Tells if field was set.
     * 
     * @param string $name
     * @return bool
     */
    public final function __isset(string $name)
    {
        $name = $this->transformFieldNamesFrom($name);
        
        return (! $this->recent) && array_key_exists($name, $this->$retrieved);
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

        $this->$modified[$name] = null;
    }

    /**
     * Retrieves instance as JSON string.
     * 
     * @return string
     */
    public final function __toJson()
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
     * @param string $from
     * @return string
     */
    protected function transformFieldNamesTo($from)
    {
        if (method_exists($this, 'toCamelCaseField')) {
            $from = $this->toCamelCaseField($from);
        }

        return $from;
    }

    /**
     * Creates a new instance, optionally with fields.
     * 
     * @param array $fields = []
     * @return static
     */
    public static function new(array $fields = [])
    {
        return new static($fields);
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
            $this->retrieved[$name] = static::DEFAULTS[$name] ?? null;
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
}