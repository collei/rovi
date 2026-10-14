<?php

/**
 * Database facade.
 */
class Rovi extends PSharp\Support\Facade
{
    /**
     * The available methods.
     * 
     * @var array
     */
    protected static $AVAILABLE = [
        'builder' => 'getBuilder',
        'query' => 'getBuilder',
        'raw' => 'raw',
        'rawSql' => 'raw',
    ];

    /**
     * Retrieves the facade acessor.
     * 
     * @return string
     */
    protected static function getAcessor()
    {
        return Rovi\RoviManager::class;
    }
}