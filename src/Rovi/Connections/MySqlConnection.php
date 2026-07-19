<?php
namespace Rovi\Connections;

use Rovi\Query\Grammars\MySqlGrammar;
use Rovi\Query\Grammars\MariaDbGrammar;

/**
 * SqlServer connection.
 */
class MySqlConnection extends Connection
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
     * Tells whether the underlying MySql implementation is MariaDB.
     * 
     * @return bool
     */
    public function isMariaDb()
    {
        return stripos($this->dbVersionString ?? '', 'maria') !== false;
    }

	/**
	 * Custom initialization.
	 * 
	 * @return void
	 */
    protected function initialize()
    {
        $compare = $this->compareDbVersion('0.0.0.0');

        if ($this->isMariaDb()) {
            $this->grammar = new MariaDbGrammar();
        } elseif (! is_null($compare)) {
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
        // default version number if no version could be captured
        $version = '0.0.0.0';

        $stmt = $this->getHandle()->query('SELECT VERSION()');

        $this->dbVersionString = $info = $stmt->fetch(PDO::FETCH_COLUMN) ?? '';

        if (preg_match('/(?<build>[0-9]+\.[0-9]+\.[0-9\.]+)/', $info, $extract) === 1) {
            $version = $extract['build'];
        }

        return $this->parseDbVersionNumber($version);
    }
}