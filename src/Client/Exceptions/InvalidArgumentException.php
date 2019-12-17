<?php


namespace SMSPLANET\PHP\Client\Exceptions;

use Exception;
use Throwable;

class InvalidArgumentException extends Exception
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct(sprintf('Nieprawidłowa wartość parametru: %s', $message), $code, $previous);
    }
}