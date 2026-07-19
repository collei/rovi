<?php
namespace Rovi\Connections;

use Rovi\Query\Grammars\SqlServerGrammar;
use Rovi\Query\Grammars\SqlServer12Grammar;

/**
 * SqlServer connection.
 */
class SqlServerConnection extends Connection
{
	/**
	 * Initializes a new instance
	 *
	 * @param mixed $dsn
	 * @param string $database
	 * @param string $username
	 * @param string $password
	 */
	public function __construct($dsn = '', string $database = '', ?string $username = null, ?string $password = null)
    {
        parent::__construct('sqlite', $dsn, $database, $username, $password);
    }

	/**
	 * Custom initialization.
	 * 
	 * @return void
	 */
    protected function initialize()
    {
        $compare = $this->compareDbVersion('11.0.2100.60');

        if (! is_null($compare)) {
            $this->grammar = ($compare >= 0) ? new SqlServer12Grammar() : new SqlServerGrammar();
        }

        $this->grammar = new SqlServer12Grammar();
    }

    /**
     * Extract DB version.
     * 
     * @return string|null;
     */
    protected function extractDbVersion()
    {
        // default version (based upon SQL Server 2012 - 11.0.2100.60)
        // source: https://sqlserverbuilds.blogspot.com/ viewed 2026-07-11 10:54 GMT-3
        $version = '11.0.2100.60';

        $stmt = $this->getHandle()->query('SELECT @@version');

        $this->dbVersionString = $info = $stmt->fetch(PDO::FETCH_COLUMN) ?? '';

        if (preg_match('/(?<build>[0-9]+\.[0-9]+\.[0-9\.]+)/', $info, $extract) === 1) {
            $version = $extract['build'];
        }

        return $this->parseDbVersionNumber($version);
    }
}