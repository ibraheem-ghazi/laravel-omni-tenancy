<?php

namespace IbraheemGhazi\OmniTenancy\Exceptions;

use Exception;
use Throwable;

class TenantNotActiveException extends Exception
{
    public function __construct(?string $tenantId = null, ?Throwable $previous = null)
    {
        $message = "The requested tenant is not active";
        if($tenantId){
            $message = "The requested tenant \"$tenantId\" is not active";
        }
        parent::__construct($message, 401, $previous);
    }

    public static function make(?string $tenantId = null, ?Throwable $previous = null): static
    {
        return new static($tenantId, $previous);
    }
}
