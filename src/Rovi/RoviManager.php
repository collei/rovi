<?php
namespace Rovi;

use RuntimeException;
use Rovi\Connections\Connector;
use Rovi\Connections\ConnectionBuilder;

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
     * @var array
     */
    private $config = [];

    /**
     * @var string|null
     */
    private $configFile = null;

    /**
     * Constructor
     */
    public function __construct(string $file)
    {
        $this->loadConfig($file);
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
    protected function loadConfig(string $fileName)
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

        foreach ($this->config->db->connections as $connInfo) {
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