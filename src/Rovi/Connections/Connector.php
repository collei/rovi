<?php
namespace Rovi\Connections;

use DB;
use PDO;
use PDOException;
use DateTime;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Rovi\RoviManager;

/**
 * Connection factory
 */
final class Connector
{
    /**
     * @var array
     */
    protected const DB_VENDORS = [
        'mysql' => \Rovi\Connections\MySqlConnection::class,
        'pgsql' => \Rovi\Connections\PostgresConnection::class,
        'oci' => \Rovi\Connections\OracleConnection::class,
        'sqlsrv' => \Rovi\Connections\SqlServerConnection::class,
        'sqlite' => \Rovi\Connections\SqliteConnection::class,
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
     * @var Rovi\RoviManager
     */
    protected $manager = null;

    /**
     * Constructor.
     * 
     * @param Rovi\Manager $manager
     */
    public function __construct(RoviManager $manager)
    {
        $this->manager = $manager;
    }

	/**
	 * Initializes a new instance
	 *
	 * @param string|null &$name
	 * @param string $type
	 * @param string|null $server
	 * @param string|null $database
	 * @param string|null $username
	 * @param string|null $password
	 * @param int|null $port
     * @return Rovi\Connections\Connection 
	 */
	public function build(
        ?string &$name = null,
        string $type,
        ?string $server = null,
        ?string $database = null,
        ?string $username = null,
        ?string $password = null,
        ?int $port = null
    ) {
		$vendor = self::getSupportedType($type);

        if (empty($vendor)) {
            throw new InvalidArgumentException(sprintf('Unsupported vendor: \'%s\'', $type));
        }

        $dsn = $this->buildDsn($vendor, $server, $database, $username, $password, $port);

        $class = self::DB_VENDORS[$vendor];

        $connection = new $class($dsn, $database, $username, $password);

        if (empty($name)) {
            $name = 'DBC' . (new DateTime())->format('YmdHisu');
        }

        $connection->name($name);

        return $connection;
	}

    /**
     * Builds a DSN string from parameters ,according to the supported vendors.
     * Returns empty string if vendor is not supported.
     * 
     * @param string $vendor
     * @param string|null $server = null
     * @param string|null $database = null
     * @param string|null $username = null
     * @param string|null $password = null
     * @param int|null $port = null
     * @param string|null $charset = null
     * @return string
     */
    public function buildDsn(
        string $vendor,
        ?string $server = null,
        ?string $database = null,
        ?string $username = null,
        ?string $password = null,
        ?int $port = null,
        ?string $charset = null
    ) {
        if ($type = $this->getSupportedType($vendor)) {
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