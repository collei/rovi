<?php
namespace Rovi\Metadata\Attributes\Schema;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Column
{
    protected $name;
    protected $type;
    protected $default;
    protected $order;

    public function __construct(string $name = null, string $type = null, $default = null, int $order = null)
    {
        $this->name = $name;
        $this->type = $type;
        $this->default = $default;
        $this->order = $order;
    }

    public function name()
    {
        return $this->name;
    }

    public function type()
    {
        return $this->type;
    }

    public function default()
    {
        return $this->default;
    }

    public function order()
    {
        return $this->order;
    }
}