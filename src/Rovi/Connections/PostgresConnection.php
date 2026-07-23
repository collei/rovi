<?php
namespace Rovi\Connections;

use Rovi\Query\Grammars\PostgresGrammar;

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
        $compare = $this->compareDbVersion('0.0.0.0');

        if (! is_null($compare)) {
            $this->grammar = ($compare >= 0) ? new PostgresGrammar() : new PostgresGrammar();
        }

        $this->grammar = new PostgresGrammar();
    }

    /**
     * Extract DB version.
     * 
     * @return string|null;
     */
    protected function extractDbVersion()
    {
        // default version 
        $version = '0.0.0.0';

        $stmt = $this->getHandle()->query('SELECT version() ');

        $this->dbVersionString = $info = $stmt->fetch(PDO::FETCH_COLUMN) ?? '';

        if (preg_match('/(?<build>[0-9]+\.[0-9\.]+)/', $info, $extract) === 1) {
            $version = $extract['build'];
        }

        return $this->parseDbVersionNumber($version);
    }
}