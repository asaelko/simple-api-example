<?php

namespace App\Domain\Core\Client\Exception;

use CarlBundle\Exception\RestException;

/**
 * Ошибка частоты запроса кода; код можно запрашивать не чаще, чем раз в 30 секунд
 */
class InvalidVerificationCodeApiException extends RestException
{
    public const HTTP_CODE = 470;

    protected $message = 'error.phone_verification.incorrect_code';
}
