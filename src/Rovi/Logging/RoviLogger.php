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
     * @var array
     */
    protected const LEVEL_SCALE = ['DEBUG','INFO','NOTICE','WARNING','ERROR','CRITICAL','ALERT','EMERGENCY']; 

    /**
     * @var string
     */
    protected $path = '';

    /**
     * @var string
     */
    protected $minimalLevel = 'WARNING';

    /**
     * @var int
     */
    protected $minimalLevelCalculated = 3;

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
        // Ignores log levels lesser the mininal.
        if ($this->calcLevelOf($level) < $this->minimalLevelCalculated) {
            return;
        }

        list($date, $conn_name) = array(date($this->dateFormat), $this->connectionName);

        $filename = $this->catterFileName(compact('level','date','conn_name'));

        if (! file_exists($filename)) {
            $dir = dirname($filename);

            if (! is_dir($dir) && ! is_file($dir)) {
                @mkdir($dir, 0777, true);
            }
        }

        $logContent = sprintf("[%s] %s :: %s\n", date($this->dateEntryFormat), $message, json_encode($context));

        @file_put_contents($filename, $logContent, FILE_APPEND);
    }

    /**
     * Defines the minimal log level that will be logged.
     * 
     * @param string $level
     * @return $this
     * @throws \Psr\Log\InvalidArgumentException for non-existent level
     */
    public function withMinimalLevel(string $level)
    {
        $result = array_search($importance = strtoupper($level), self::LEVEL_SCALE, true);

        if (false !== $result) {
            $this->minimalLevel = $importance;
            $this->minimalLevelCalculated = $result;

            return $this;
        }

        throw new InvalidArgumentException(sprintf('Invalid log level: %s', $level));
    }

    /**
     * Calculates the importance of a log level. Case insensitive.
     * 
     * @param string $level
     * @return int
     * @throws \Psr\Log\InvalidArgumentException for non-existent level
     */
    protected function calcLevelOf(string $level)
    {
        $result = array_search(strtoupper($level), self::LEVEL_SCALE, true);

        if (false !== $result) {
            return $result;
        }

        throw new InvalidArgumentException(sprintf('Invalid log level: %s', $level));
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