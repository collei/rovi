<?php
namespace Rovi\Connections;

/**
 * Readonly class for INSERT, UPDATE, DELETE results.
 * 
 * @property-read string|null $type
 * @property-read int|null $count
 * @property-read array $list
 */
final class Result
{
    /**
     * @var string|null
     */
    private $type = null;

    /**
     * @var int|null
     */
    private $count = null;

    /**
     * @var array
     */
    private $list = [];

    /**
     * Private constructor
     */
    private function __construct()
    {
        //
    }

    /**
     * Private instantiator@var string|null
     */
    private static function new()
    {
        return new self;
    }

    /**
     * Private setter for internal use.
     * 
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    private function set(string $name, $value)
    {
        if (property_exists($this, $name)) {
            $this->$name = $value;
        }

        return $this;
    }

    /**
     * Public getter.
     * 
     * @param string $name
     * @return mixed
     */
    public function __get(string $name)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        }

        return null;
    }

    /**
     * For use of PHP only, for debug purporses.
     * 
     * @return array
     */
    public function __debugInfo()
    {
        return [
            'type' => $this->type,
            'count' => $this->count,
            'list' => $this->list,
        ];
    }

    /**
     * For use of Builder only, for returning INSERT statement.
     * 
     * @param int $count
     * @param array $list - list of inserted ID
     * @return static
     */
    public static function fromInsert(int $count, array $list)
    {
        return self::new()->set('type','INSERT')
                ->set('count', $count)
                ->set('list', $list);         
    }

    /**
     * For use of Builder only, for returning UPDATE statement.
     * 
     * @param int $count
     * @return static
     */
    public static function fromUpdate(int $count)
    {
        return self::new()->set('type','UPDATE')
                ->set('count', $count);         
    }

    /**
     * For use of Builder only, for returning DELETE statement.
     * 
     * @param int $count
     * @return static
     */
    public static function fromDelete(int $count)
    {
        return self::new()->set('type','DELETE')
                ->set('count', $count);         
    }
}