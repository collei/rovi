<?php
namespace Rovi\Connections;

use Rovi\Query\Grammars\SqliteGrammar;

class SqliteConnection extends Connection
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

    protected function initialize()
    {
        $this->grammar = new SqliteGrammar();
    }
}