<?php
namespace Rovi\Metadata\Attributes\Schema;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Identity implements DatabaseGenerated
{
    //
}