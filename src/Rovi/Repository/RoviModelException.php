<?php
namespace Rovi\Repository;

use Rovi\DatabaseException;

/**
 * Exception thrown by the Asta Database ORM.
 *
 */
class RoviModelException extends DatabaseException
{
    /**
     * @var Rovi\Reposityory\Model
     */
    protected $model;

    /**
     * Instantiate.
     * 
     * @param \Rovi\Reposityory\Model $model
     * @param ?string $message = null
     * @param \Throwable $previous = null
     */
	public function __construct(Model $model, ?string $message = null, ?Throwable $previous = null)
    {
        parent::__construct($message ?? 'Error related to the model system', 0, $previous);

        $this->model = $model;
    }

    /**
     * Retrieves the related model.
     * 
     * @return \Rovi\Reposityory\Model
     */
    public function model()
    {
        return $this->model;
    }
}