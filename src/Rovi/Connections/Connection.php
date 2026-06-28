<?php
namespace Rovi\Connections;

use Rovi\DatabaseException;
use Rovi\Query\Builder;
use Rovi\Query\Grammars\Grammar;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;
use PDO;
use PDOStatement;
use PDOException;
use DateTime;

/**
 * Base connection.
 */
abstract class Connection
{
    /**
     * @var string
     */
    private $type = null;

    /**
     * @var resource
     */
    private $handle = null;

    /**
     * @var bool
     */
    private $isOpen = false;

    /**
     * @var array
     */
    private $errors = [];

    /**
     * @var Psr\Log\LoggerInterface
     */
    private $logger = null;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $dsn;

    /**
     * @var string
     */
    protected $database;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var array
     */
    protected $prepared = [];

    /**
	 * @var array
	 */
	protected $options = [
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_EMULATE_PREPARES => false,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		PDO::ATTR_PERSISTENT => false, // evitar conexões que remanesçam após o término do ciclo
	];

	/**
	 * Initializes a new instance
	 *
	 * @param string $type
	 * @param mixed $dsn
	 * @param string $database
	 * @param string $username
	 * @param string $password
	 */
	public function __construct($type, $dsn = '', string $database = '', ?string $username = '', ?string $password = '')
	{
		$this->type = Connector::getSupportedType($type);

		$this->dsn = $dsn;
		$this->database = $database;
		$this->username = $username ?? '';
		$this->password = $password ?? '';

        $this->initialize();
	}

    /**
     * Retrieves the grammar;
     * 
     * @return string|null
     */
    public final function getGrammar()
    {
        return $this->grammar;
    }

    /**
     * Retrieves the builder for this connection;
     * 
     * @return string|null
     */
    public final function getBuilder()
    {
        return new Builder($this);
    }

    /**
     * Retrieves the database type;
     * 
     * @return string|null
     */
    public function type()
    {
        return $this->type;
    }

    /**
     * Tells if the connection is of a given type/vendor.
     * 
     * @param string|null $type
     * @return bool
     */
    public function isType(string $type = null)
    {
        if (empty($type)) {
            return false;
        }

        if ($type == $this->type) {
            return true;
        }

        return Connector::getSupportedType($type) == $this->type;
    }

    /**
     * Provides a PSR-3 logger for logging erros to.
     * 
     * @param Psr\Log\LoggerInterface $logger
     */
    public function withLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Returns the logger associated to the connection.
     * 
     * @return Psr\Log\LoggerInterface
     */
    public function logger()
    {
        if ($this->logger) {
            return $this->logger;
        }

        return $this->logger = new NullLogger();
    }

    /**
     * Performs custom initialization.
     * 
     * @return void
     */
    abstract protected function initialize();

	/**
	 * Opens the connection if not yet open.
	 *
	 * @param mixed $dsn
	 * @param string $user
	 * @param string $pass
	 * @param array $options
	 * @return $this
	 */
	protected function openHandle($dsn, string $user = '', string $pass = '', array $options = [])
	{
        if ($this->isOpen && ! is_null($this->handle)) {
            return $this;
        }

		try {
            if ($this->type() == 'sqlsrv') {
                unset($options[PDO::ATTR_PERSISTENT]);
            }

			$this->handle = new PDO($dsn, $user, $pass, $options);

            if (! is_null($this->handle)) {
                $this->isOpen = true;
                $this->handle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } else {
                $this->isOpen = false;
            }
			//
		} catch (PDOException $ex) {
			$reason = sprintf('Error while trying open connection at file \'%s\', line %s', __FILE__, __LINE__);

            throw new DatabaseException($reason, 0, $ex);
		}

        return $this;
	}

	/**
	 * Closes the connection
	 *
	 * @return $this
	 */
	protected function closeHandle()
	{
		$this->handle = null;
        $this->isOpen = false;

        return $this;
	}

    /**
     * Tells if connection is open.
     * 
     * @return bool
     */
    public function isOpen()
    {
        return $this->isOpen;
    }

    /**
     * Opens the connection.
     * 
     * @return $this
     */
    public function open()
    {
        return $this->openHandle($this->dsn, $this->username, $this->password, $this->options);
    }

    /**
     * Closes the connection.
     * 
     * @return $this
     */
    public function close()
    {
        return $this->closeHandle();
    }

	/**
	 * returns the underlying PDO connection
	 *
	 * @return void
	 */
	public function getHandle()
	{
		return $this->handle;
	}

	/**
	 * Executes the raw sql statement. Returns true on success, false on fail.
	 * On fail, the second argument holds an object describing the error ocurred.
	 *
	 * @param string $sql
     * @param mixed &$errors
	 * @return bool
	 */
	public function run(string $sql, &$errors = null)
	{
		try {
            if ($this->isOpen()) {
    			$this->getHandle()->exec($sql);
            } else {
                throw new DatabaseException($sql, sprintf('Connection %s not open!', $this->name));
            }
			//
		} catch (PDOException $exception) {
            $errors = $this->processException($exception, $sql);

            return false;
			//
		} catch (Throwable $exception) {
            $errors = $this->processException($exception, $sql);

            return false;
		}

        $errors = null;

        return true;
	}

	/**
	 * Executes the select statement. Returns results as array (it may be empty).
	 * On fail, the third argument holds an object describing the error ocurred.
	 *
	 * @param string $sql
     * @param array|null $data
     * @param mixed &$errors
	 * @return array|false
	 */
	public function select(string $sql, array $data = null, &$errors = null)
	{
		try {
            if ($this->isOpen()) {
                $stmt = $this->getPrepared($sql);
                $stmt->execute($data);
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $stmt->closeCursor();
                return $result;
            }

            throw new DatabaseException($sql, sprintf('Connection %s not open!', $this->name));
			//
		} catch (PDOException $exception) {
            $errors = $this->processException($exception, $sql);
			//
		} catch (Throwable $exception) {
            $errors = $this->processException($exception, $sql);
		}

        return false;
	}

	/**
	 * Executes the select statement. Returns results as a generator.
	 * On fail, the third argument holds an object describing the error ocurred.
	 *
	 * @param string $sql
     * @param array|null $data
     * @param mixed &$errors
	 * @return Generator|null
	 */
	public function lazySelect(string $sql, array $data = null, &$errors = null)
	{
        if (! $this->isOpen()) {
            return null;
        }

        $connection = $this;

        return function() use ($connection, $sql, $data, &$errors) {
    		$lazy = $connection->performLazySelect($sql, $data, $errors);

            yield from $lazy();
        };
    }

	/**
	 * Executes the select statement. Returns results as a generator.
	 * On fail, the third argument holds an object describing the error ocurred.
	 *
	 * @param string $sql
     * @param array|null $data
     * @param mixed &$errors
	 * @return Generator|null
	 */
	protected function performLazySelect(string $sql, array $data = null, &$errors = null)
	{
		try {
            if ($this->isOpen()) {
                $stmt = $this->getPrepared($sql);
                $stmt->execute($data);

                return function() use ($stmt) {
                    while ($row = $stmt->fetch()) {
                        yield $row; 
                    }

                    $stmt->closeCursor();
                };
            }

            throw new DatabaseException($sql, sprintf('Connection %s not open!', $this->name));
			//
		} catch (PDOException $exception) {
            $errors = $this->processException($exception, $sql);
			//
		} catch (Throwable $exception) {
            $errors = $this->processException($exception, $sql);
		}

        return function() { yield null; };
	}

	/**
	 * Executes a insert statement.
     * On success, returns a Result instance with property 'list' (list of inserted IDs)
     * and property 'count' (number of inserted rows).
	 * On fail, returns false and the third argument holds an object
     * describing the error ocurred.
	 *
	 * @param string $sql
     * @param array|null $data
     * @param mixed &$errors
	 * @return Rovi\Connections\Result|false
	 */
	public function insert(string $sql, array $data = null, &$errors = null)
	{
		try {
            if ($this->isOpen()) {
                $stmt = $this->getPrepared($sql);
                $stmt->execute($data);

                $count = $stmt->rowCount();
                $lastID = $this->getHandle()->lastInsertId();

                $list = null;

                /**
                 * Tries to fetch from OUTPUT clause (SqlServer) or
                 * RETURNING clause (Sqlite), if any. It suppress exceptions.
                 * In case of exception, just leaves the list empty.
                 */
                try {
                    $list = $stmt->fetchAll();
                } catch (PDOException $e2) {
                    //
                }

                /**
                 * If list cames empty, fills it accordingly.
                 */
                if (empty($list)) {
                    if (preg_match('/\s*\d+(\.\d+)?\s*/', $lastID) !== false) {
                        $lastID = (int) (float) trim($lastID);
                    }

                    $list = is_int($lastID) ? range($lastID - $count + 1, $lastID) : array($lastID);
                }

                return Result::fromInsert($count, $list);
            }

            throw new DatabaseException($sql, sprintf('Connection %s not open!', $this->name));
			//
		} catch (PDOException $exception) {
            $errors = $this->processException($exception, $sql);
			//
		} catch (Throwable $exception) {
            $errors = $this->processException($exception, $sql);
		}

        return false;
	}

	/**
	 * Executes an update statement.
     * On success, returns a Result instance with a 'count' property.
     * On fail, returns false and the third argument holds an object
     * describing the error ocurred.
	 *
	 * @param string $sql
     * @param array|null $data
     * @param mixed &$errors
	 * @return Rovi\Connections\Result|false
	 */
	public function update(string $sql, array $data = null, &$errors = null)
    {
        $count = $this->execute($sql, $data, $errors);

        return ($count >= 0) ? Result::fromUpdate($count) : false;
    }

	/**
	 * Executes a delete statement.
     * On success, returns a Result instance with a 'count' property.
     * On fail, returns false and the third argument holds an object
     * describing the error ocurred.
	 *
	 * @param string $sql
     * @param array|null $data
     * @param mixed &$errors
	 * @return Rovi\Connections\Result|false
	 */
	public function delete(string $sql, array $data = null, &$errors = null)
    {
        $count = $this->execute($sql, $data, $errors);

        return ($count >= 0) ? Result::fromDelete($count) : false;
    }

	/**
	 * Executes a sql statement without returning results.
     * On success, returns how many rows affected (0 if none).
	 * On fail, returns -1 and the third argument holds an object
     * describing the error ocurred.
	 *
	 * @param string $sql
     * @param array|null $data
     * @param mixed &$errors
	 * @return int
	 */
	protected function execute(string $sql, array $data = null, &$errors = null)
	{
		try {
            if ($this->isOpen()) {
                $stmt = $this->getPrepared($sql);
                $stmt->execute($data);
                return $stmt->rowCount();
            }

            throw new DatabaseException($sql, sprintf('Connection %s not open!', $this->name));
			//
		} catch (PDOException $exception) {
            $errors = $this->processException($exception, $sql);
			//
		} catch (Throwable $exception) {
            $errors = $this->processException($exception, $sql);
		}

        return -1;
	}

    /**
     * Returns prepared statement for the given $sql code.
     * 
     * @return PDOStatement
     * @throws PDOException
     */
    protected function getPrepared(string $sql)
    {
        $hash = md5($sql);

        if (array_key_exists($hash, $this->prepared)) {
            if ($this->prepared[$hash]) {
                return $this->prepared[$hash];
            }
        }

        return $this->prepared[$hash] = $this->open()->getHandle()->prepare($sql);
    }

    /**
     * Processes and returns the exception data as data object.
     * 
     * @param Throwable $exception
     * @param string|null $sql
     * @param string|null $reason
     * @return object
     */
    protected function processException(Throwable $exception, string $sql = null, string $reason = null)
    {
        $reason = $reason ?? $exception->getMessage();

        $context = compact('exception','reason','sql');

        $this->logger()->error($reason, $context);

        $error = (object) $context;

        $this->addError($error);

        return $error;
    }

    /**
     * Adds an error descriptor to the error list.
     * 
     * @param object $error
     * @return void
     */
    protected function addError(object $error)
    {
        $this->errors[] = $error;
    }

    /**
     * Tells if any error has occurred.
     * 
     * @return bool
     */
    public function hasErrors()
    {
        return ! empty($this->errors);
    }

    /**
     * Retrieves all errors occurred, if any.
     * 
     * @return array
     */
    public function getErrors()
    {
        return $this->errors ?? [];
    }

    /**
     * Retrieves the last error occurred, if any.
     * 
     * @return object|null
     */
    public function lastError()
    {
        if ($this->hasErrors()) {
            return $this->errors[array_key_last($this->errors)];
        }

        return null;
    }

    /**
     * Crafts instance display for debugging internals.
     * 
     * @return array
     */
    public function __debugInfo()
    {
        return [
            'isOpen' => $this->isOpen,
            'dsn' => $this->dsn,
            'type' => $this->type,
            'name' => $this->name,
            'database' => $this->database,
            'username' => $this->username,
            'grammar' => $this->grammar ? (get_class($this->grammar) . '@' . spl_object_id($this->grammar)) : 'NULL',
            'handle' => $this->handle ? (get_class($this->handle) . '@' . spl_object_id($this->handle)) : 'NULL',
            'logger' => $this->logger ? (get_class($this->logger) . '@' . spl_object_id($this->logger)) : 'NULL',
            'options' => $this->options,
        ];
    }    
}