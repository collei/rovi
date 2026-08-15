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
            $manager = new RoviManager($config->get('rovi'));

            if ($manager->getConfig('db.connectionAutoImport', false) === false) {
                return $manager;
            }

            $connections = $config->get('db.connections');

            foreach ($connections as $name => $data) {
                if ('default' == $name) {
                    continue;
                }

                $manager->importDatabaseConnection(
                    $name, $data['type'], $data['server'], $data['database'], $data['username'], $data['password'], $data['port']
                );
            }

            return $manager;
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