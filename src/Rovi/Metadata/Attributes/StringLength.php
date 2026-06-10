<?php
namespace Rovi\Metadata\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class StringLength extends MaxLength
{
    public function __construct(int $size = 50, protected int $minimum = 0)
    {
        parent::__construct($size);
    }

    public function minimum()
    {
        return $this->minimum;
    }
}