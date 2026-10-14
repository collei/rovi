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