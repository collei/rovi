<?php
namespace Rovi\Connections;

use InvalidArgumentException;
use Rovi\RoviManager;

/**
 * Connection factory
 */
final class ConnectionBuilder
{
    /**
     * @var ?string
     */
    private $name = null;

    /**
     * @var ?string
     */
    private $type = null;

    /**
     * @var ?string
     */
    private $server = null;

    /**
     * @var ?int
     */
    private $port = null;

    /**
     * @var ?string
     */
    private $database = null;

    /**
     * @var ?string
     */
    private $user = null;

    /**
     * @var ?string
     */
    private $password = null;

    /**
     * @var Rovi\RoviManager
     */
    private $manager = null;

    /**
     * Create a new ConnectionBuilder instance.
     */
    public function __construct(RoviManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Instantiates a new ConnectionBuilder for the given connection name.
     * 
     * @static
     * @param string $name
     * @return self
     */
    public static function named(string $name)
    {
        $manager = RoviManager::instance();

        return (new self($manager))->name($name);
    }

    /**
     * Defines the connection name.
     * 
     * @param string $name
     * @return $this
     */
    public function name(string $name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Defines the connection type (vendor).
     * 
     * @param string $type
     * @return $this
     */
    public function type(string $type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Defines the server.
     * 
     * @param string $server
     * @return $this
     */
    public function server(string $server)
    {
        $this->server = $server;
        return $this;
    }

    /**
     * Defines the port.
     * 
     * @param int $port
     * @return $this
     */
    public function port(int $port)
    {
        $this->port = $port;
        return $this;
    }

    /**
     * Defines the connection database.
     * 
     * @param string $database
     * @return $this
     */
    public function database(string $database)
    {
        $this->database = $database;
        return $this;
    }

    /**
     * Defines the connection db user.
     * 
     * @param string $user
     * @return $this
     */
    public function user(string $user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Defines the connection db password.
     * 
     * @param string $password
     * @return $this
     */
    public function password(string $password)
    {
        $this->password = $password;
        return $this;
    }

    /**
     * Performs the connection building.
     * 
     * @return Rovi\Connections\Connection
     */
    public function build()
    {
        $exception = false;

        if (empty($this->type)) {
            $exception = new InvalidArgumentException(
                'Database type cannot be null -- did you forgot calling the type() method when using the connection builder?'
            );
        }

        if (empty($this->database)) {
            $exception = new InvalidArgumentException(
                'Database cannot be null -- did you forgot calling the database() method when using the connection builder?'
            );
        }

        if (false !== $exception) {
            $this->manager->getLogger()->log('ERROR', $exception->getMessage(), compact('exception'));

            throw $exception;
        }

        return (new Connector($this->manager))->build(
            $this->name,
            $this->type,
            $this->server,
            $this->database,
            $this->user,
            $this->password,
            $this->port
        );
    }
}