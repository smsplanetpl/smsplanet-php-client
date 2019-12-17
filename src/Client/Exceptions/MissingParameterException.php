<?php


namespace SMSPLANET\PHP\Client\Exceptions;

use Exception;
use Throwable;

class MissingParameterException extends Exception
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct(sprintf('Brak wymaganego parametru: %s', $message), $code, $previous);
    }
}