<?php
namespace Rovi\Metadata\Attributes\Schema;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class ForeignKey extends Key
{
    protected $references;

    public function __construct(string $references, string $name = null, string $type = null, int $order = null)
    {
        parent::__construct($name ?? $references, $type, $order);

        $this->references = $references;
    }

    public function references()
    {
        return $this->references;
    }
}