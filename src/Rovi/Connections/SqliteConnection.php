<?php
namespace Rovi\Connections;

use Rovi\Query\Grammars\SqliteGrammar;
use Rovi\Query\Grammars\Sqlite335Grammar;
use PDO;

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

    /**
     * Extract DB version.
     * 
     * @return string|null;
     */
    protected function extractDbVersion()
    {
        // defaults to SQLite 3 version
        $version = '3.0.0.0';

		$conn = ($conn = $this->getHandle()) ? $conn : new PDO('sqlite::memory:');

		$stmt = $conn->query('SELECT sqlite_version()');

        $info = $stmt->fetch(PDO::FETCH_COLUMN) ?? '';

        if (preg_match('/(?<build>[0-9]+\.[0-9]+\.[0-9\.]+)/', $info, $extract) === 1) {
            $version = $extract['build'];
        }

        return $this->parseDbVersionNumber($version);
    }
}