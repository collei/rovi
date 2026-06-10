<?php
namespace Rovi\Metadata\Attributes\Schema;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Table
{
    protected $schema;

    public function __construct(protected string $table, string $schema = null)
    {
        $this->schema = $schema;
    }

    public function table()
    {
        return $this->schema ? ($this->schema.'.'.$this->table) : $this->table;
    }

    public function name()
    {
        return $this->table;
    }

    public function schema()
    {
        return $this->schema;
    }
}