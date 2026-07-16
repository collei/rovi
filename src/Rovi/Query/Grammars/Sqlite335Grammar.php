<?php
namespace Rovi\Query\Grammars;

use DateTime;
use PDO;

/**
 * SQLite >=3.35.0 Grammar
 */
class Sqlite335Grammar extends SqliteGrammar
{
    /**
     * Compiles insert output clause.
     * 
     * @param array $output
     * @return string
     */
    protected function compileInsertOutputClause($output)
    {
        return sprintf('RETURNING %s', implode(', ', $output));
    }
}