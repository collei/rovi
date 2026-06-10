<?php
namespace Rovi\Connections;

use Rovi\Query\Grammars\SqliteGrammar;

class SqliteConnection extends Connection
{
    public function __construct()
    {
        $this->grammar = new SqliteGrammar();
    }
}