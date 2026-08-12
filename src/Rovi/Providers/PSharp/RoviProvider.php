<?php
namespace Rovi\Providers\PSharp;

use Rovi\RoviManager;
use PSharp\Core\Application;
use PSharp\Core\Config;
use PSharp\Core\Providers\ServiceProvider;

/**
 * Encapsulates a service provider.
 */
class RoviProvider extends ServiceProvider
{
    /**
     * @var Rovi\RoviManager
     */
    protected $manager;

    /**
     * Registers the services with the container.
     * 
     * @return void
     */
    public function register()
    {
        $this->container->configureBuilder(RoviManager::class, function(Application $app, Config $config){
            return new RoviManager($config->get('rovi'));
        });
    }

    /**
     * Boots the services within the container.
     * 
     * @return void
     */
    public function boot()
    {
        $this->manager = $this->container->make(RoviManager::class);
    }
}