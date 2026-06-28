<?php
namespace Rovi\Query\Expressions;

use Rovi\Connections\Connection;
use Rovi\Query\Grammars\Grammar;

/**
 * A literal expression to be added directly to SQL code.
 */
class Expression
{
    /**
     * @var string
     */
    protected $expression;

    /**
     * Crafts a new Expression.
     * 
     * @param string $expression
     */
    public function __construct(string $expression)
    {
        $this->expression = $expression;
    }

    /**
     * For use of PHP funcions.
     * 
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * For use of PHP funcions.
     * 
     * @return array
     */
    public function __debugInfo()
    {
        return [
            'expression' => $this->expression,
        ];
    }

    /**
     * Crafts a new Expression.
     * 
     * @param string $expression
     * @return static
     */
    public static function raw(string $expression)
    {
        return new self($expression);
    }

    /**
     * Returns the expression.
     * 
     * @return string
     */
    public function toSql()
    {
        return $this->expression;
    }

    /**
     * Returns the expression.
     * 
     * @return string
     */
    public function toString()
    {
        return $this->expression;
    }
}