<?php

namespace IbraheemGhazi\OmniTenancy\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class TenantCreationFailedException extends HttpException
{

    public function __construct($message = null, \Throwable $previous = null, array $headers = [], $code = 0)
    {
        parent::__construct(422, $message, $previous, $headers, $code);
    }

}
