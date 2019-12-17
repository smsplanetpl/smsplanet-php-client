<?php


namespace SMSPLANET\PHP\Client\Exceptions;

use Exception;
use Throwable;

class InvalidParameterException extends Exception
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct(sprintf('Nieprawidłowy parametr: %s', $message), $code, $previous);
    }
}