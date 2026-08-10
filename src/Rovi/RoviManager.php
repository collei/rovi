<?php
namespace Rovi;

use RuntimeException;
use InvalidArgumentException;
use Rovi\Connections\Connector;
use Rovi\Connections\ConnectionBuilder;
use Rovi\Logging\RoviLogger;

/**
 * Manages databse connection.
 * 
 */
class RoviManager
{
    /**
     * @var array
     */
    private const CONN_PARAMETERS = ['name','type','server','database','user','password'];

    /**
     * @var object
     */
    private $config = null;

    /**
     * @var string|null
     */
    private $configFile = null;

    /**
     * @var array
     */
    private $loggers = [];

    /**
     * Constructor
     * 
     * @param string|array|object|null $configInfo 
     */
    public function __construct($configInfo = null)
    {
        if (empty($configInfo) || 0 == $configInfo || false == $configInfo) {
            $this->setDefaultConfig();
            return;
        }

        if (is_string($configInfo)) {
            $this->loadConfig($configInfo);
            return;
        }
        
        if (is_array($configInfo)) {
            $this->config = json_decode(json_encode($configInfo), false, 512, JSON_OBJECT_AS_ARRAY);
            return;
        }
        
        if (is_object($configInfo)) {
            $this->config = $configInfo;
            return;
        }

        throw new InvalidArgumentException(
            'The parameter should be either a string (a path to the config file),'
            . ' an array, an object, or an empty value.'
        );
    }

    /**
     * Provides some sort of provisional config to work with.
     * 
     * @return void
     */
    protected function setDefaultConfig()
    {
        $this->config = (object) [
            'version' => '1.0.1',
            'logs' => (object) [
                'enabled' => false,
                'path' => 'logs/{conn_name}.{date}.{level}.log',
                'dateFormat' => 'Y-m-d',
                'dateEntryFormat' => 'Y-m-d H:i:s',
                'level' => 'ERROR'
            ],
            'db' => (object) [
                'default' => null,
                'connections' => []
            ]
        ];
    }

    /**
     * Loads the config file.
     * 
     * @param string $fileName
     * @return true
     * @throws JsonException when json_decode() fails
     * @throws RuntimeException when file does not exist or it is not readable
     * @throws RuntimeException when json_decode() gets failed (PHP < 7.3)
     */
    public function loadConfig(string $fileName)
    {
        $file = $this->configFile = $fileName;

        if (! is_file($file)) {
            throw new RuntimeException(sprintf('File \'%s\' does not exist or it is not readable', $file));
        }

        $content = file_get_contents($file);

        $json_flag = class_exists('JsonException') ? JSON_THROW_ON_ERROR : 0;
        
        $json = json_decode($content, false, 512, $json_flag);

        if (0 === $json_flag && 0 !== json_last_error()) {
            throw new RuntimeException(json_last_error_msg());
        }

        $this->config = $json;

        return true;
    }

    /**
     * Tells if a given connection $name does exist.
     * 
     * @param string $name
     * @return bool
     */
    public function hasConnection(string $name)
    {
        return Connector::hasConnection($name);
    }

    /**
     * Retrieves the connection $name if it does exist.
     * 
     * @param string|null $name
     * @return Rovi\Connections\Connection|null
     */
    public function getConnection(?string $name = null)
    {
        if (empty($name) && ! empty($this->config->db->default)) {
            $name = $this->config->db->default;
        }

        if ($connection = Connector::getAnyConnection($name)) {
            return $connection;
        }

        if (empty($name)) {
            return null;
        }

        if (! empty($this->config->db->connections)) foreach ($this->config->db->connections as $connInfo) {
            if ($name != $connInfo->name) {
                continue;
            }

            $conn = ConnectionBuilder::named($conn_name = $name);

            foreach (self::CONN_PARAMETERS as $param) {
                $value = $connInfo->$param;
                
                if (! empty($value)) {
                    $conn->{$param}($value);
                }
            }

            return $conn->connect()->open();
        }
    }
}