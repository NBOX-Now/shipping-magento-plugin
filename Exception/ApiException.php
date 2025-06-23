<?php

namespace Nbox\Shipping\Exception;

/**
 * Custom Exception for API Errors
 */
class ApiException extends \Exception
{
    /**
     * ApiException constructor.
     *
     * @param string $message The exception message
     * @param int $code The exception code (default: 0)
     * @param \Throwable|null $previous The previous exception (default: null)
     */
    public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
