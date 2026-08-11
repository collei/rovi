<?php
namespace Rovi;

use RuntimeException;
use InvalidArgumentException;
use Rovi\Connections\Connector;
use Rovi\Connections\ConnectionBuilder;
use Rovi\Logging\RoviLogger;
use Psr\Log\NullLogger;

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
    private $connections = [];

    /**
     * @var array
     */
    private $loggers = [];

    /**
     * @var static
     */
    private static $instance = null;

    /**
     * Constructor
     * 
     * @param string|array|object|null $configInfo 
     */
    public function __construct($configInfo = null)
    {
        $started = false;

        if (empty($configInfo) || 0 == $configInfo || false == $configInfo) {
            $this->setDefaultConfig();
            $started = true;
        }

        if (is_string($configInfo)) {
            $this->loadConfig($configInfo);
            $started = true;
        }
        
        if (is_array($configInfo)) {
            $this->config = json_decode(json_encode($configInfo), false, 512, JSON_OBJECT_AS_ARRAY);
            $started = true;
        }
        
        if (is_object($configInfo)) {
            $this->config = $configInfo;
            $started = true;
        }

        if ($started) {
            self::$instance = $this;
            return;
        }

        throw new InvalidArgumentException(
            'The parameter should be either a string (a path to the config file),'
            . ' an array, an object, or an empty value.'
        );
    }

    /**
     * Retrieves the current instance, if any.
     * 
     * @return static
     */
    public static function instance()
    {
        return self::$instance;
    }

    /**
     * Retrieves a logger.
     * 
     * @param string|null $connectionName
     * @return Psr\Log\LoggerInterface
     */
    public function getLogger(?string $connectionName = null)
    {
        $logger = empty($connectionName)
                ? $this->provideLogger()
                : $this->provideLoggerFor($connectionName);

        return $logger ? $logger : new NullLogger();
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

        if (0 === $json_flag && JSON_ERROR_NONE !== json_last_error()) {
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
        return array_key_exists($name, $this->connections);
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
            $name = $this->config->db->default ?? 'default';
        }

        if ($this->hasConnection($name)) {
            return $this->connections[$name];
        }

        if (empty($name)) {
            return null;
        }

        if (empty($this->config->db->connections)) {
            return null;
        }

        foreach ($this->config->db->connections as $connInfo) {
            if ($name != $connInfo->name) {
                continue;
            }

            $builder = ConnectionBuilder::named($conn_name = $name);

            foreach (self::CONN_PARAMETERS as $param) {
                $value = $connInfo->$param;
                
                if (! empty($value)) {
                    $builder->{$param}($value);
                }
            }

            return $this->connections[$name] = ($logger = $this->provideLoggerFor($name))
                ? $builder->build()->withLogger($logger)->open()
                : $builder->build()->open();
        }
    }

    /**
     * Retrieves the first connection it finds, if any.
     * 
     * @param string $name = null
     * @param string $type = null
     * @return Rovi\Connections\Connection|null
     */
    public function getAnyConnection(?string $name = null, ?string $type = null)
    {
        if ($connection = $this->getConnection($name ?? '')) {
            if (empty($type)) {
                return $connection;
            }

            if ($connection->isType($type)) {
                return $connection;
            }
        }

        foreach ($this->connections as $connection) {
            if (empty($type)) {
                return $connection;
            }

            if ($connection->isType($type)) {
                return $connection;
            }
        }

        return null;
    }

    /**
     * Catter a Logger as configured if logging is enabled.
     * 
     * @return Psr\Log\LoggerInterface|null
     */
    protected function provideLogger()
    {
        if (true != ($this->config->logs->enabled ?? false)) {
            return null;
        }

        return (new RoviLogger())
            ->withPath($this->config->logs->path)
            ->withDateFormat($this->config->logs->dateFormat)
            ->withDateEntryFormat($this->config->logs->dateEntryFormat);
    }

    /**
     * Catter a Logger as configured if logging is enabled.
     * 
     * @param string $connectionName
     * @return Psr\Log\LoggerInterface|null
     */
    protected function provideLoggerFor(string $connectionName)
    {
        if (! empty($this->loggers[$connectionName])) {
            return $this->loggers[$connectionName];
        }

        if ($logger = $this->provideLogger()) {
            return $this->loggers[$connectionName] = $logger->withConnectionName($connectionName);
        }

        return null;
    }
}