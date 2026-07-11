<?php
namespace Rovi\Connections;

use DB;
use PDO;
use PDOException;
use DateTime;
use InvalidArgumentException;
use Rovi\Query\Grammars\SqliteGrammar;
use Rovi\Query\Grammars\SqlServerGrammar;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Connection factory
 */
final class Connector
{
    /**
     * @var array
     */
    protected const DB_VENDORS = [
        'mssql' => \Rovi\Connections\SqlServerConnection::class,
        'pgsql' => \Rovi\Connections\PostgresSqlConnection::class,
        'mysql' => \Rovi\Connections\MySqlConnection::class,
        'sqlite' => \Rovi\Connections\SqliteConnection::class,
        'oci' => \Rovi\Connections\OracleConnection::class,
    ];

    /**
     * @var array
     */
    protected const DB_TYPES = [
        'mysql' => ['mysql','mariadb'],
        'pgsql' => ['pgsql','postgres','postgresql'],
        'oci' => ['oci','oracle'],
        'sqlsrv' => ['sqlsrv','mssql'],
        'sqlite' => ['sqlite','sqlite3'],
    ];

    /**
     * @var array
     */
    protected const DB_STANDARD_PORTS = [
        'mysql' => 3306,
        'pgsql' => 5432,
        'oci' => 1521,
        'sqlsrv' => 1433,
        'sqlite' => null,
    ];

    /**
     * @var array
     */
    protected const DB_DSNS = [
        'mysql' => 'mysql:host={server};dbname={database};port={port};charset={charset}',
        'pgsql' => 'pgsql:host={server};port={port};dbname={database};user={username};password={password}',
        'oci' => 'oci:dbname={server}',
        'sqlsrv' => 'sqlsrv:server={server};database={database}',
        'sqlite' => 'sqlite:{database}',
    ];

    /**
     * @var array
     */
    protected static $connectionPool = [];

    /**
     * Retrieves the connection, if any.
     * 
     * @param string $name
     * @return bool
     */
    public static function hasConnection(string $name)
    {
        return array_key_exists($name, self::$connectionPool);
    }

    /**
     * Retrieves the connection, if any.
     * 
     * @param string $name
     * @return Rovi\Connections\Connection|null
     */
    public static function getConnection(string $name)
    {
        return self::$connectionPool[$name] ?? null;
    }

    /**
     * Retrieves the first connection it finds, if any.
     * 
     * @param string $name = null
     * @param string $type = null
     * @return Rovi\Connections\Connection|null
     */
    public static function getAnyConnection(?string $name = null, ?string $type = null)
    {
        if ($conn = static::getConnection($name ?? '')) {
            if (empty($type)) {
                return $conn;
            }

            if ($conn->isType($type)) {
                return $conn;
            }
        }

        foreach (self::$connectionPool as $conn) {
            if (empty($type)) {
                return $conn;
            }

            if ($conn->isType($type)) {
                return $conn;
            }
        }

        return null;
    }

	/**
	 * Initializes a new instance
	 *
	 * @param string $type
	 * @param mixed $dsn
	 * @param string $database
	 * @param string $username
	 * @param string $password
     * @return Rovi\Connections\Connection 
	 */
	public static function build(
        string $type,
        ?string $server = null,
        ?string $database = null,
        ?string $username = null,
        ?string $password = null,
        ?string &$name = null
    ) {
		$vendor = self::getSupportedType($type);

        if (empty($vendor)) {
            throw new InvalidArgumentException(sprintf('Unsupported vendor: \'%s\'', $type));
        }

        $dsn = self::buildDsn($vendor, $server, $database, $username, $password);

        $class = self::DB_VENDORS[$vendor];

        $connection = new $class($dsn, $database, $username, $password);

        if (empty($name)) {
            $name = 'DBC' . (new DateTime())->format('YmdHisu');
        }

        $connection->name($name);

        return self::$connectionPool[$name] = $connection;
	}

    /**
     * Builds a DSN string from parameters ,according to the supported vendors.
     * Returns empty string if vendor is not supported.
     * 
     * @static
     * @param string $vendor
     * @param string|null $server = null
     * @param string|null $database = null
     * @param string|null $username = null
     * @param string|null $password = null
     * @param int|null $port = null
     * @param string|null $charset = null
     * @return string
     */
    public static function buildDsn(
        string $vendor,
        ?string $server = null,
        ?string $database = null,
        ?string $username = null,
        ?string $password = null,
        ?int $port = null,
        ?string $charset = null
    ) {
        if ($type = self::getSupportedType($vendor)) {
            $port = ($port > 0) ? $port : self::DB_STANDARD_PORTS[$type];
            $charset = is_null($charset) ? 'utf8' : $charset;

            $parameters = compact('server','database','port','charset','username','password');

            $dsn = self::DB_DSNS[$type];

            foreach ($parameters as $name => $value) {
                $dsn = str_replace('{'.$name.'}', $value, $dsn);
            }

            return $dsn;
        }

        return '';
    }

    /**
     * Returns the standardized type for the given DB type/vendor, if supported,
     * and according to the class definition.
     * 
     * @static
     * @param string $type
     * @return string|null
     */
    public static function getSupportedType(string $type)
    {
        foreach (self::DB_TYPES as $key => $possible) if (in_array($type, $possible, true)) {
            return $key;
        }

        return null;
    }

    /**
     * Tells if the given type/vendor is internally supported.
     * 
     * @static
     * @param string $type
     * @return bool
     */
    public static function isSupportedType(string $type)
    {
        $resultType = self::getSupportedType($type);

        return ! empty($resultType);
    }
}