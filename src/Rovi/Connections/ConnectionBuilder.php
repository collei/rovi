<?php
namespace Rovi\Connections;

use InvalidArgumentException;

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
     * Create a new ConnectionBuilder instance.
     */
    public function __construct()
    {
        //
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
        return (new self)->name($name);
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
     * Performs the connection.
     * 
     * @return Rovi\Connections\Connection
     */
    public function connect()
    {
        if (empty($this->type)) {
            throw new InvalidArgumentException(
                'Database type cannot be null -- did you forgot calling the type() method when using the connection builder?'
            );
        }

        if (empty($this->database)) {
            throw new InvalidArgumentException(
                'Database cannot be null -- did you forgot calling the database() method when using the connection builder?'
            );
        }

        return Connector::build(
            $this->type,
            $this->server,
            $this->database,
            $this->user,
            $this->password,
            $this->name
        );
    }
}