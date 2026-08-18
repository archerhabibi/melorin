<?php

namespace App\Exceptions;

use Exception;

class InsufficientBalanceException extends Exception
{
    public function __construct(string $message = 'موجودی کیف پول کافی نیست.')
    {
        parent::__construct($message);
    }
}
