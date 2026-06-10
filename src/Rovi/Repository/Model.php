<?php
namespace Rovi\Repository;

use Rovi\Metadata\Prospector;

abstract class Model
{
    protected const TABLE = null;
    protected const SCHEMA = null;
    protected const KEY = 'id';
    protected const DEFAULTS = [];

    protected const CONNECTION = 'db';

    private $connection;

    private $hydrated = false; 

    private $required = [];
    private $retrieved = [];
    private $modified = [];
    private $protected = ['*'];

    public function __construct(array $fields = [])
    {
        foreach ($fields as $name => $value) {
            if (! array_key_exists($name, $this->$retrieved)) {
                $this->retrieved[$name] = static::DEFAULTS[$name] ?? null;
            }

            $this->modified[$name] = $value;
        }
    }

    public final function __get(string $name)
    {
        if ($this->hydrated && (! array_key_exists($name, $this->$retrieved))) {
            throw new RoviModelException(sprintf(
                'Not found property \'%s\' on table \'%s\'', $name, static::TABLE
            ));
        }

        return $this->$modified[$name] ?? $this->$retrieved[$name] ?? null;
    }

    public final function __set(string $name, $value)
    {
        if ($this->hydrated && (! array_key_exists($name, $this->$retrieved))) {
            throw new RoviModelException(sprintf(
                'Not found property \'%s\' on table \'%s\'', $name, static::TABLE
            ));
        }

        $this->$modified[$name] = $value;
    }

    public final function __isset(string $name)
    {
        return (! $this->recent) && array_key_exists($name, $this->$retrieved);
    }

    public final function __unset(string $name)
    {
        if (in_array($name, $this->required, true)) {
            throw new RoviModelException(sprintf(
                'Non-nullable property \'%s\' on table \'%s\' cannot be nullified', $name, static::TABLE
            ));
        }

        $this->$modified[$name] = null;
    }

    public final function __toJson()
    {
        return json_encode($this->toArray());
    }

    public static function hydrated(array $fields)
    {
        return static::new()->hydrate($fields);
    }

    public static function new(array $fields = [])
    {
        return new static($fields);
    }

    protected final function hydrate(array $fields)
    {
        foreach ($fields as $name => $value) {
            $this->retrieved[$name] = static::DEFAULTS[$name] ?? null;

            if (! is_null($value)) {
                $this->required[] = $name;
            }
        }

        $this->modified = [];

        $this->hydrated = true;

        return $this;
    }

    public function toArray()
    {
        return array_merge($this->retrieved, $this->modified);
    }
}