<?php

namespace App\Domain\Core\Client\Exception;

use CarlBundle\Exception\RestException;

/**
 * Время действия кода подтверждения истекло
 */
class ExpiredVerificationRequestApiException extends RestException
{
    public const HTTP_CODE = 410;

    protected $message = 'error.phone_verification.request_expired';
}
