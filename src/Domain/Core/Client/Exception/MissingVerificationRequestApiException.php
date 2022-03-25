<?php

namespace App\Domain\Core\Client\Exception;

use CarlBundle\Exception\RestException;

/**
 * Не найден запрос на подтверждение номера
 */
class MissingVerificationRequestApiException extends RestException
{
    public const HTTP_CODE = 404;

    protected $message = 'error.phone_verification.missing_request';
}
