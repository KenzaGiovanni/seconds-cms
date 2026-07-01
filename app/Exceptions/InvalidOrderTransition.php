<?php

namespace App\Exceptions;

use App\Enums\OrderStatus;
use RuntimeException;

class InvalidOrderTransition extends RuntimeException
{
    public static function between(OrderStatus $from, OrderStatus $to): self
    {
        return new self("Cannot transition an order from {$from->value} to {$to->value}.");
    }
}
