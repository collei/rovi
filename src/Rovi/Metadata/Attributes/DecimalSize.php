<?php
namespace Rovi\Metadata\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class DecimalSize
{
    public function __construct(protected int $size = 12, protected int $precision = 2) {}

    public function size()
    {
        return $this->size;
    }

    public function precision()
    {
        return $this->precision;
    }
}