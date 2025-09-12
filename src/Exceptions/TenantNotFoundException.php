<?php

namespace IbraheemGhazi\OmniTenancy\Exceptions;

use Exception;
use Throwable;

class TenantNotFoundException extends Exception
{
    public function __construct(?string $tenantId = null, ?Throwable $previous = null)
    {
        $message = "The requested tenant was not found";
        if($tenantId){
            $message = "The requested tenant \"$tenantId\" was not found";
        }
        parent::__construct($message, 404, $previous);
    }

    public static function make(?string $tenantId = null, ?Throwable $previous = null): static
    {
        return new static($tenantId, $previous);
    }
}
