<?php

namespace App\Exceptions;

use Exception;

/** Thrown when an order cannot be placed (empty cart, not enough stock...). */
class CheckoutException extends Exception
{
    /** @param  string[]  $messages */
    public function __construct(public readonly array $messages)
    {
        parent::__construct(implode(' ', $messages));
    }
}
