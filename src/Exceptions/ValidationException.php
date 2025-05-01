<?php
namespace Nava\Pandago\Exceptions;

class ValidationException extends PandagoException
{
    /**
     * @var array
     */
    protected $errors;

    /**
     * ValidationException constructor.
     *
     * @param string $message
     * @param array $errors
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(string $message = "", array $errors = [], int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->errors = $errors;
    }

    /**
     * Get the validation errors.
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
