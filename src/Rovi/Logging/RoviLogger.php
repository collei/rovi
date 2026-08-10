<?php
namespace Rovi\Logging;

use Stringable;
use Psr\Log\LoggerInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\InvalidArgumentException;

/**
 * Simple file logger for Rovi.
 * 
 */
class RoviLogger extends AbstractLogger implements LoggerInterface
{
    /**
     * @var string
     */
    protected $path = '';

    /**
     * @var string
     */
    protected $dateFormat = 'Y-m-d';

    /**
     * @var string
     */
    protected $dateEntryFormat = 'Y-m-d H:i:s';

    /**
     * @var string
     */
    protected $connectionName = 'default';

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param mixed[] $context
     *
     * @throws \Psr\Log\InvalidArgumentException
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        list($date, $conn_name) = array(date($this->dateFormat), $this->connectionName);

        $filename = $this->catterFileName(compact('level','date','conn_name'));

        if (! file_exists($filename)) {
            $dir = dirname($filename);

            if (! is_dir($dir) && ! is_file($dir)) {
                mkdir($dir, 0777, true);
            }
        }

        $logContent = sprintf('[%s] %s :: %s', date($this->dateEntryFormat), $message, json_encode($context));

        file_put_contents($filename, $logContent, FILE_APPEND);
    }

    /**
     * Defines the date format for the file name.
     * 
     * @param array $varaibles
     * @return string
     */
    protected function catterFileName(array $variables = [])
    {
        $result = $this->path;

        foreach ($variables as $name => $value) {
            $label = '{'.$name.'}';

            $result = str_replace($label, $value, $result);
        }

        return $result;
    }

    /**
     * Defines the base file path.
     * 
     * If it ends with '/' or '\', 'rovi.log' will be supplied as file name
     * residing inside the folder.
     * 
     * @param string $path
     * @return $this
     */
    public function withPath(string $path)
    {
        $this->path = $path;
        return $this;
    }

    /**
     * Defines the date format for the file name.
     * 
     * @param string $dateFormat
     * @return $this
     */
    public function withDateFormat(string $dateFormat)
    {
        $this->dateFormat = $dateFormat;
        return $this;
    }

    /**
     * Defines the date format for every log entry.
     * 
     * @param string $dateEntryFormat
     * @return $this
     */
    public function withDateEntryFormat(string $dateEntryFormat)
    {
        $this->dateEntryFormat = $dateEntryFormat;
        return $this;
    }

    /**
     * Defines the connection name being logged.
     * 
     * @param string $connectionName
     * @return $this
     */
    public function withConnectionName(string $connectionName)
    {
        $this->connectionName = $connectionName;
        return $this;
    }
}