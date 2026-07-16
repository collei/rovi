<?php
namespace Rovi\Connections;

use Rovi\Query\Grammars\SqliteGrammar;
use Rovi\Query\Grammars\Sqlite335Grammar;

/**
 * Sqlite connection.
 */
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

	/**
	 * Custom initialization.
	 * 
	 * @return void
	 */
    protected function initialize()
    {
        $compare = $this->compareDbVersion('3.35.0');

        if (! is_null($compare)) {
            $this->grammar = ($compare >= 0) ? new Sqlite335Grammar() : new SqliteGrammar();
        }

		$this->grammar = new SqliteGrammar();

		$this->open();

		$this->getHandle()->exec('PRAGMA synchronous=NORMAL');
		$this->getHandle()->exec('PRAGMA cache_size=-8000');   // Cache de 8MB
		$this->getHandle()->exec('PRAGMA temp_store=MEMORY');
		$this->getHandle()->exec('PRAGMA mmap_size=268435456'); // 256MB mapeamento de memória		
    }
}