<?php
namespace Rovi\Metadata\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class MaxLength
{
    public function __construct(protected int $size = 50) {}

    public function size()
    {
        return $this->size;
    }
}