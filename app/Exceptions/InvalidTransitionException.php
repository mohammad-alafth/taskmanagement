<?php

namespace App\Exceptions;

use Exception;

class InvalidTransitionException extends Exception
{
    public function __construct(
        public string $currentStatus,
        public string $requestedStatus
    ) {
        parent::__construct("Invalid status transition: {$currentStatus} → {$requestedStatus}");
    }
}
