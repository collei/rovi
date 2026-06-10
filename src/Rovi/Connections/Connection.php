<?php
namespace Rovi\Connections;

use Rovi\Query\Builder;

abstract class Connection
{
    protected $grammar;

    public function __construct()
    {
        //
    }

    public function __debugInfo()
    {
        return [
            'grammar' => get_class($this->grammar) . '@' . spl_object_id($this->grammar),
        ];
    }

    public final function getGrammar()
    {
        return $this->grammar;
    }

    public final function getBuilder()
    {
        return new Builder($this);
    }
}